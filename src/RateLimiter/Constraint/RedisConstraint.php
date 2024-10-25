<?php

namespace Veracrypt\CrashCollector\RateLimiter\Constraint;

use Veracrypt\CrashCollector\Storage\Redis;

class RedisConstraint
{
    use Redis;

    protected string $prefix = 'RL';
}
