<?php

namespace Veracrypt\CrashCollector\Storage\Database;

class Index
{
    /**
     * @param string[] $columns
     */
    public function __construct(
        public readonly array $columns,
        public readonly bool $unique = false,
    )
    {}
}
