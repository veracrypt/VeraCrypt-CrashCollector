<?php

namespace Veracrypt\CrashCollector;

class EnvVarProcessor
{
    /**
     * True values are 'true', 'on', 'yes', all numbers except 0 and 0.0 and all numeric strings except '0' and '0.0'; everything else is false
     */
    public static function bool($value): bool
    {
        return (bool) (filter_var($value, \FILTER_VALIDATE_BOOL) ?: filter_var($value, \FILTER_VALIDATE_INT) ?: filter_var($value, \FILTER_VALIDATE_FLOAT));
    }
}
