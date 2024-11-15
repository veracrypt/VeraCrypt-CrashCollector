<?php

namespace Veracrypt\CrashCollector\Storage;

use Redis as RedisServer;

/**
 * Requires PHPRedis, but it could be expanded in the future to support PRedis too
 */
trait Redis
{
    protected static ?RedisServer $rh = null;

    /**
     * @return void
     * @throws \RedisException
     */
    protected function connect(): void
    {
        if (self::$rh === null) {
            self::$rh = new RedisServer();
            self::$rh->connect($_ENV['REDIS_HOST'], (int)$_ENV['REDIS_PORT']);
            if (@$_ENV['REDIS_PASSWORD'] != '') {
                self::$rh->auth($_ENV['REDIS_PASSWORD']);
            }
        }
    }
}
