<?php

namespace Veracrypt\CrashCollector\Storage\Database;

trait Column
{
    public readonly string $type;
    public readonly array $constraints;
}
