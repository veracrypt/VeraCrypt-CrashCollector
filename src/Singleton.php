<?php

namespace Veracrypt\CrashCollector;

trait Singleton
{
    protected static $_instance;

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}
