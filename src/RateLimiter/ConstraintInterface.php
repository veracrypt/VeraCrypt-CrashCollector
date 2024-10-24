<?php

namespace Veracrypt\CrashCollector\RateLimiter;

interface ConstraintInterface
{
    /**
     * @throws \RuntimeException
     */
    public function validateRequest(?string $extraIdentifier = null): void;
}
