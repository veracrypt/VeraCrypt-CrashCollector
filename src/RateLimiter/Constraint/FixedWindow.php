<?php

namespace Veracrypt\CrashCollector\RateLimiter\Constraint;

use Veracrypt\CrashCollector\Exception\AuthorizationException;
use Veracrypt\CrashCollector\Exception\RateLimitExceedException;
use Veracrypt\CrashCollector\RateLimiter\RateLimiterInterface;
use Veracrypt\CrashCollector\Security\ClientIPAware;

class FixedWindow extends  RedisConstraint implements RateLimiterInterface
{
    use ClientIPAware;

    protected $postfix = 'FW';

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

        $key = $this->getKey($extraIdentifier);
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

    /**
     * @throws \RedisException
     */
    public function reset(?string $extraIdentifier = null): void
    {
        self::$rh->unlink($this->getKey());
    }

    /// @todo allow using other means than the client IP to group together requests, esp. when $extraIdentifier is not null
    protected function getKey(?string $extraIdentifier = null): string
    {
        // the order of fields is chosen to allow wildcard purging of all constraints matching  a given identifier,
        // regardless of constraint type / config
        return $this->prefix . '|' . str_replace('|', '||', $this->identifier) . '|' .
            ($extraIdentifier !== null ? md5($extraIdentifier) : '') . '|' .
            $this->getClientIP() . '|' .
            $this->intervalLength . '|' . $this->maxHitsPerInterval  . '|' . $this->postfix;
    }
}
