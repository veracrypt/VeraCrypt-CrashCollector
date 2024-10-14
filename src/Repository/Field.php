<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Storage\DatabaseColumn;

class Field
{
    use DatabaseColumn;

    /**
     * @param mixed $constraints keys must be FieldConstraint constants
     */
    public function __construct(
        public readonly ?string $entityField,
        string $type,
        array $constraints
    )
    {
        $this->type = $type;
        $this->constraints = $constraints;
    }
}
