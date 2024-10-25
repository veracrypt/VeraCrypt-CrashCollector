<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Exception\RateLimitExceedException;
use Veracrypt\CrashCollector\Form\Field as BaseField;
use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\RateLimiter\RateLimiterInterface;
use Veracrypt\CrashCollector\RateLimiter\RateLimiter as Limiter;

/**
 * The difference between the RateLimiter Field and the RateLimiter Constraint is that the latter does take into
 * account the currently submitted field value as part of the identifier key used to count the number of requests.
 * That allows f.e. to have a form with an email field where each unique email can be submitted N times per minute,
 * while there is no limit on submittal of that form in general.
 */
class RateLimiter extends BaseField
{
    protected Limiter $limiter;

    /**
     * @var RateLimiterInterface[] $constraints
     */
    public function __construct(
        array $constraints
    )
    {
        parent::__construct('ratelimiter', '', '', [], null, false);

        $this->limiter = new Limiter($constraints);
    }

    protected function validateValue(mixed $value): null|string
    {
        try {
            $this->limiter->validateRequest();
        } catch (RateLimitExceedException $e) {
            $this->errorMessage = "You have submitted the form too many times. Please wait for a while before re-submitting";

            /// @todo improve this - add some info on the specific form (and the client IP?)
            $logger = Logger::getInstance('audit');
            $logger->info("Form was denied submission - rate limit achieved for field: " . $e->getMessage());
        }
        return null;
    }
}
