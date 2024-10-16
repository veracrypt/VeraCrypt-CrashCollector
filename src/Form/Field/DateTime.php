<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as Basefield;

/**
 * @todo override validateConstraintsDefinitions to check that only supported constraints are accepted
 */
class DateTime extends Basefield
{
    public function __construct(string $label, string $inputName, array $constraints = [], mixed $value = null)
    {
        parent::__construct('datetime-local', $label, $inputName, $constraints, $value);
    }

    /**
     * We allow either valid datetime strings, or null. No empty strings
     */
    protected function validateValue(mixed $value): null|string
    {
        if (null === $value || '' === ($value = trim($value))) {
            return null;
        }
        if (strtotime($value) === false) {
            $this->errorMessage = 'Value is not a valid datetime';
        }
        return $value;
    }

    public function getData(): null|\DateTimeImmutable
    {
        return match($this->value) {
            null => null,
            default => new \DateTimeImmutable($this->value),
        };
    }
}
