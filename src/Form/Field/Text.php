<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as Basefield;

class Text extends Basefield
{
    public function __construct(string $label, string $inputName, array $constraints = [], ?string $value = null)
    {
        parent::__construct('text', $label, $inputName, $constraints, $value);
    }
}
