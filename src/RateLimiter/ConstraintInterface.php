<?php

namespace Veracrypt\CrashCollector\RateLimiter;

use Veracrypt\CrashCollector\Exception\AuthorizationException;
use Veracrypt\CrashCollector\Exception\RateLimitExceedException;

interface ConstraintInterface
{
    /**
     * @throws RateLimitExceedException in case of rate limit exceeded
     * @throws AuthorizationException for any other auth-related issue
     * @throws \RuntimeException for anything else
     */
    public function validateRequest(?string $extraIdentifier = null): void;
}
