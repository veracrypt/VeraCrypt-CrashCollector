<?php

namespace Veracrypt\CrashCollector\Form\Field\Constraint;

use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\Entity\ReportToken;
use Veracrypt\CrashCollector\Exception\ConstraintReportNotFoundException;
use Veracrypt\CrashCollector\Exception\TokenNotFoundException;
use Veracrypt\CrashCollector\Repository\ReportTokenRepository;
use Veracrypt\CrashCollector\Security\PasswordHasher;

class ReportTokenConstraint implements ConstraintInterface
{
    protected ReportToken $token;
    protected ?CrashReport $report = null;

    public function __construct(
        protected readonly string $repositoryClass
    )
    {
        /// @todo make UserTokenRepository implement an interface instead of checking for a subclass
        if (!is_a($repositoryClass, ReportTokenRepository::class, true)) {
            throw new \DomainException("Class '$repositoryClass' should extend UserTokenRepository");
        }
    }

    /**
     * To be called after validateRequest
     */
    public function validateHash(#[\SensitiveParameter] string $secret): bool
    {
        $ph = new PasswordHasher();
        //$repo = new $this->repositoryClass();
        return $ph->verify($this->token->hash, $secret);
    }

    /**
     * @throws \RuntimeException or subclasses thereof
     */
    public function validateRequest(?string $value = null): void
    {
        /** @var ReportTokenRepository $repo */
        $repo = new $this->repositoryClass();

        $tokenId = (int)$value;
        if ($tokenId <= 0) {
            throw new TokenNotFoundException('Token not found');
        }
        $token = $repo->fetch($tokenId);
        if ($token === null) {
            throw new TokenNotFoundException('Token not found');
        }
        $this->token = $token;
        $report = $this->token->getReport();
        if ($report === null) {
            throw new ConstraintReportNotFoundException('Report matching token not found');
        }

        $this->report = $report;
    }

    public function getTokenRepository(): ReportTokenRepository
    {
        return new $this->repositoryClass();
    }

    public function getReport(): null|CrashReport
    {
        return $this->report;
    }
}
