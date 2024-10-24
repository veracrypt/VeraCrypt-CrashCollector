<?php

namespace Veracrypt\CrashCollector\RateLimiter\Constraint;

use Veracrypt\CrashCollector\Exception\AuthorizationException;
use Veracrypt\CrashCollector\Exception\RateLimitExceedException;
use Veracrypt\CrashCollector\RateLimiter\ConstraintInterface;
use Veracrypt\CrashCollector\Security\ClientIPAware;
use Veracrypt\CrashCollector\Storage\Redis;

class FixedWindow implements ConstraintInterface
{
    use Redis;
    use ClientIPAware;

    protected string $prefix = 'RL_FW_';

    /**
     * @param int $intervalLength in seconds
     */
    public function __construct(
        public readonly string $identifier,
        public readonly int $maxHitsPerInterval,
        public readonly int $intervalLength,
    )
    {
    }

    /**
     * NB: this implements a straightforward fixed-window algorithm. For best results, you can use multiple rate limiters
     * on the same resource, eg. 1 per minute, plus 10 per hour, plus 50 per day.
     * @throws RateLimitExceedException
     * @throws AuthorizationException in case client IP can not be reliably determined
     * @throws \DomainException in case of bad config
     * @throws \RuntimeException|\RedisException in case Redis is KO
     */
    public function validateRequest(?string $extraIdentifier = null): void
    {
        $this->connect();

        $key = $this->getTokenName($extraIdentifier);
        if (!self::$rh->exists($key)) {
            if (!self::$rh->set($key, 1) || !self::$rh->expire($key, $this->intervalLength)) {
                throw new \RuntimeException('Error saving RateLimiter data in Redis');
            }
        } else {
            $totalCalls = self::$rh->incr($key);
            if ($totalCalls === false) {
                throw new \RuntimeException('Error getting RateLimiter data from Redis');
            }
            if ($totalCalls > $this->maxHitsPerInterval) {
                throw new RateLimitExceedException("More than {$this->maxHitsPerInterval} requests were made in {$this->intervalLength} seconds");
            }
        }
    }

    /// @todo allow using other means than the client IP to group together requests, esp. when $extraIdentifier is not null
    protected function getTokenName(?string $extraIdentifier = null): string
    {
        $clientIP = $this->getClientIP();
        $token = $this->prefix . '|' . str_replace('|', '||', $this->identifier) . '|' . $this->intervalLength . '|' . $this->maxHitsPerInterval . '|' . $clientIP;
        if ($extraIdentifier !== null) {
            // Avoid extra-long strings - the worst case scenario for hash key collisions in this case is preventing
            // someone else from submitting a form with same value, iff they share the same IP...
            $token .= '|' . md5($extraIdentifier);
        }
        return $token;
    }
}
