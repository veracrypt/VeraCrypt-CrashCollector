<?php

namespace Veracrypt\CrashCollector\Repository;

class Field
{
    public function __construct(
        public readonly ?string $entityField,
        public readonly string $type,
        public readonly array $constraints
    ) {
    }
}
