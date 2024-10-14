<?php

namespace Veracrypt\CrashCollector\Storage;

trait DatabaseColumn
{
    public readonly string $type;
    public readonly array $constraints;
}
