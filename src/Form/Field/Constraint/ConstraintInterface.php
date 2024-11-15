<?php

namespace Veracrypt\CrashCollector\Form\Field\Constraint;

use Veracrypt\CrashCollector\Exception\ConstraintException;

interface ConstraintInterface
{
    /**
     * @throws ConstraintException for violations of the constraint
     */
    public function validateRequest(?string $value = null): void;
}
