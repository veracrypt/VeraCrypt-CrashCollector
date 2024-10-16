<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as Basefield;

class Hidden extends Basefield
{
    public function __construct(string $inputName, array $constraints = [], mixed $value = null)
    {
        parent::__construct('hidden', '', $inputName, $constraints, $value, false);
    }
}
