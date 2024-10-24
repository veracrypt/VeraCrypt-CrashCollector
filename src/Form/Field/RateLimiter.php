<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as BaseField;
use Veracrypt\CrashCollector\RateLimiter\ConstraintInterface;
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
     * @var ConstraintInterface[] $constraints
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
        } catch (\RuntimeException $e) {
            /// @todo should we tell apart rate limit hit vs. bad config or connection issues?
            $this->errorMessage = $e->getMessage();
        }
        return null;
    }
}
