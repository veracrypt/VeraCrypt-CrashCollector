<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as Basefield;

class SubmitButton extends Basefield
{
    /**
     * @todo does it make sense to support custom validation rules for submit buttons?
     */
    public function __construct(string $label, string $inputName, array $constraints = [], mixed $value = null)
    {
        parent::__construct('submit-button', $label, $inputName, $constraints, $value);
    }
}
