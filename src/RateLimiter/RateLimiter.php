<?php

namespace Veracrypt\CrashCollector\RateLimiter;

use Veracrypt\CrashCollector\Exception\AuthorizationException;
use Veracrypt\CrashCollector\Exception\RateLimitExceedException;

class RateLimiter
{
    /**
     * @param ConstraintInterface[] $constraints
     * @throws \DomainException
     */
    public function __construct(
        protected array $constraints = []
    )
    {
        if (!array_filter($constraints, function ($entry) { return $entry instanceof ConstraintInterface; })) {
            throw new \DomainException("Unsupported configuration for rate-limiter: not an array of constraints");
        }
    }

    /**
     * @throws RateLimitExceedException in case of rate limit exceeded
     * @throws AuthorizationException for any other auth-related issue
     * @throws \RuntimeException for anything else
     */
    public function validateRequest(?string $extraIdentifier = null): void
    {
        foreach ($this->constraints as $constraint) {
            $constraint->validateRequest($extraIdentifier);
        }
    }
}
