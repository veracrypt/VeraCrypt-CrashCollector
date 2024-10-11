<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;

/**
 * @property-read ?string $value
 * @property-read ?string $errorMessage
 */
class Field
{
    protected $value;
    protected $errorMessage;

    /**
     * NB: constraints are checked in the order they are defined
     */
    public function __construct(
        public readonly string $label,
        public readonly string $inputName,
        public readonly string $inputType,
        public readonly array $constraints = []
    )
    {
    }

    /**
     * @param mixed $value
     * @return bool false when the value is not valid
     * @throws \DomainException
     */
    public function setValue(mixed $value): bool
    {
        $isValid = true;

        if ($value !== null) {
            $value = trim($value);

            switch($this->inputType) {
                case 'datetime':
                    if ($value !== '') {
                        if (strtotime($value) === false) {
                            $this->errorMessage = 'Value is not a valid datetime';
                            $isValid = false;
                        }
                    } else {
                        // we allow either valid datetime strings, or null. No empty strings
                        $value = null;
                    }
                    break;
            }
        }

        $this->value = $value;

        if (!$isValid) {
            return false;
        }

        foreach ($this->constraints as $constraint => $targetValue) {
            /// @todo add validation for minLength, regex, integer fields, datetimes, etc...
            switch ($constraint) {
                case FC::Required:
                    if ($targetValue && ($value === '' || $value === null)) {
                        $this->errorMessage = 'Value is required';
                        return false;
                    }
                    break;
                case FC::MaxLength:
                    /// @todo throw if $targetValue < 0
                    if ($targetValue > 0 && strlen($value) > $targetValue) {
                        $this->errorMessage = "Value should not be longer than {$targetValue} characters";
                        return false;
                    }
                    break;
                default:
                    throw new \DomainException("Unsupported field constraint: '$constraint");
            }
        }

        return true;
    }

    public function getData(): null|string|\DateTimeImmutable
    {
        if ($this->value === null) {
            return $this->value;
        }
        return match($this->inputType) {
            'datetime' => new \DateTimeImmutable($this->value),
            default => $this->value,
        };
    }

    public function __get($name)
    {
        switch ($name) {
            case 'value':
            case 'errorMessage':
                return $this->$name;
            default:
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                trigger_error('Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' .
                    $trace[0]['line'], E_USER_ERROR);
        }
    }

    public function __isset($name)
    {
        return match ($name) {
            'value', 'errorMessage' => isset($this->$name),
            default => false
        };
    }
}
