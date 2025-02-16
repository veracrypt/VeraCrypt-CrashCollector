<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as Basefield;

class Email extends Basefield
{
    public function __construct(string $label, string $inputName, array $constraints = [], ?string $value = null, bool $isReadOnly = false)
    {
        parent::__construct('email', $label, $inputName, $constraints, $value, true, $isReadOnly);
    }

    protected function validateValue(mixed $value): null|string
    {
        if (null === $value) {
            return null;
        }
        $value = trim($value);
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errorMessage = 'Value is not a valid email address';
        }
        return $value;
    }
}
