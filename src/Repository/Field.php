<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Storage\Database\Column;

class Field
{
    use Column;

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
