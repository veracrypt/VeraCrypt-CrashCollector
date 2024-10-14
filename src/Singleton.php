<?php

namespace Veracrypt\CrashCollector;

trait Singleton
{
    protected static $_instance;

    public static function getInstance(...$args)
    {
        if (self::$_instance === null) {
            self::$_instance = new self(...$args);
        }

        return self::$_instance;
    }
}
