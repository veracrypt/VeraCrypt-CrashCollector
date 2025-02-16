<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\Form\Field\Constraint\ReportTokenConstraint;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\RateLimiter\Constraint\FixedWindow;
use Veracrypt\CrashCollector\RateLimiter\RateLimiter;
use Veracrypt\CrashCollector\Repository\ManageReportTokenRepository;

class CrashReportConfirmForm extends CrashReportBaseForm
{
    protected int $submitOn = self::ON_GET;
    protected bool $isReadOnly = true;
    protected ReportTokenConstraint $reportConstraint;

    public function __construct(string $actionUrl, ?int $tokenId = null, #[\SensitiveParameter] ?string $secret = null, ?CrashReport $report = null)
    {
        parent::__construct($actionUrl, $report);
        // the parent class already has built the fields in fact, but we need to call getFieldsDefinitions with extra args
        $this->fields = $this->getFieldsDefinitions($actionUrl, $report, $tokenId, $secret);
    }

    protected function getFieldsDefinitions(string $actionUrl, ?CrashReport $report = null, ?int $tokenId = null, #[\SensitiveParameter] ?string $secret = null): array
    {
        $this->reportConstraint = new ReportTokenConstraint(ManageReportTokenRepository::class);
        return [
            'token' => new Field\Hidden('tkn', [
                /// @todo add an is-integer constraint?
                FC::Required => true,
                FC::RateLimit => new RateLimiter([
                    new FixedWindow($actionUrl, 10, 300), // equivalent to once every 30 secs
                    new FixedWindow($actionUrl, 12, 3600), // equivalent to once every 5 minutes
                    new FixedWindow($actionUrl, 120, 86400), // equivalent to once every 12 minutes
                ]),
                FC::Custom => $this->reportConstraint
            ], $tokenId),
            /// @todo get the field length from the TokenRepository
            'secret' => new Field\Hidden('sec', [
                FC::Required => true,
                FC::MinLength => 64,
                FC::MaxLength => 64,
            ], $secret),
        ];
    }

    protected function validateSubmit(?array $request = null): void
    {
        // use the same error message used for invalid token-ids
        if (!$this->reportConstraint->validateHash($this->getFieldData('secret'))) {
            $this->setError("Token not found");
        }
    }

    public function isSubmitted(?array $request = null): bool
    {
        if ($request === null) {
            $request = $this->getRequest();
        }
        return array_key_exists('tkn', $request);
    }

    public function getReport(): null|CrashReport
    {
        return $this->reportConstraint->getReport();
    }
}
