<?php

namespace Veracrypt\CrashCollector\RateLimiter;

class RateLimiter
{
    /**
     * @param ConstraintInterface[] $constraints
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
     * @throws \RuntimeException
     */
    public function validateRequest(?string $extraIdentifier = null): void
    {
        foreach ($this->constraints as $constraint) {
            $constraint->validateRequest($extraIdentifier);
        }
    }
}
