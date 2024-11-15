<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as Basefield;

class TextArea extends Basefield
{
    public function __construct(string $label, string $inputName, array $constraints = [], ?string $value = null, bool $isReadOnly = false)
    {
        parent::__construct('textarea', $label, $inputName, $constraints, $value, true, $isReadOnly);
    }
}
