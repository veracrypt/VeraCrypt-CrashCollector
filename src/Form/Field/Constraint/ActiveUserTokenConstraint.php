<?php

namespace Veracrypt\CrashCollector\Form\Field\Constraint;

use Veracrypt\CrashCollector\Entity\UserToken;
use Veracrypt\CrashCollector\Exception\TokenNotFoundException;
use Veracrypt\CrashCollector\Exception\ConstraintUserInactiveException;
use Veracrypt\CrashCollector\Exception\ConstraintUserNotFoundException;
use Veracrypt\CrashCollector\Repository\UserTokenRepository;
use Veracrypt\CrashCollector\Security\PasswordHasher;

class ActiveUserTokenConstraint implements ConstraintInterface
{
    protected UserToken $token;
    use UserConstraintTrait;

    public function __construct(
        protected readonly string $repositoryClass
    )
    {
        /// @todo make UserTokenRepository implement an interface instead of checking for a subclass
        if (!is_a($repositoryClass, UserTokenRepository::class, true)) {
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
        /** @var UserTokenRepository $repo */
        $repo = new $this->repositoryClass();

        $tokenId = (int)$value;
        if ($tokenId <= 0) {
            throw new TokenNotFoundException('Token not found');
        }
        $this->token = $repo->fetch($tokenId);
        if ($this->token === null) {
            throw new TokenNotFoundException('Token not found');
        }
        $user = $this->token->getUser();
        if ($user === null) {
            throw new ConstraintUserNotFoundException('User matching token not found');
        }
        if (!$user->isActive()) {
            throw new ConstraintUserInactiveException('User matching token is not active');
        }
        $this->user = $user;
    }

    public function getTokenRepository(): UserTokenRepository
    {
        return new $this->repositoryClass();
    }
}
