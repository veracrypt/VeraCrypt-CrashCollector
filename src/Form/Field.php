<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;

/**
 * @property-read ?string $value
 * @property-read ?string $errorMessage
 * @property-read bool isValid
 */
abstract class Field
{
    //public readonly string $label;
    //public readonly string $inputName;
    //public readonly string $inputType;
    //public readonly array $constraints;
    //protected mixed $value = null;
    protected ?string $errorMessage = null;

    protected function __construct(
        public readonly string $inputType,
        public readonly string $label,
        public readonly string $inputName,
        public readonly array $constraints = [],
        protected mixed $value = null,
        public readonly bool $isVisible = true
    )
    {
        $this->validateConstraintsDefinitions($constraints);
    }

    /**
     * @throws \DomainException
     */
    protected function validateConstraintsDefinitions(array $constraints): void
    {
        foreach ($constraints as $constraint => $targetValue) {
            switch ($constraint) {
                case FC::Required:
                    break;
                case FC::MaxLength:
                    if ($targetValue < 0) {
                        throw new \DomainException("Unsupported field maxlength: $targetValue");
                    }
                    break;
                default:
                    throw new \DomainException("Unsupported field constraint: '$constraint");
            }
        }
    }

    /**
     * Used to set (and validate) the value submitted.
     * NB: constraints are checked in the order they are defined.
     * @param mixed $value null is used when the field is not present in the request received
     * @return bool false when the value is not valid
     */
    public function setValue(mixed $value): bool
    {
        $this->value = $this->validateValue($value);

        if (null !== $this->errorMessage && '' !== $this->errorMessage) {
            return false;
        }

        return $this->validateConstraints($value);
    }

    /**
     * Used to validate and optionally convert to the desired representation the value submitted.
     * By default, it converts non-null values to strings and trims whitespace. Null is received when the field is not
     * present in the request received and it should generally be let through unchanged.
     * NB: should set $this->errorMessage if a non-constraint is violated.
     */
    protected function validateValue(mixed $value): null|string
    {
        return match ($value) {
            null => null,
            default => trim($value),
        };
    }

    /**
     * Used to validate the value submitted.
     * NB: sets $this->errorMessage if a constraint is violated.
     * @todo add support for more constraints: regex, ...
     */
    protected function validateConstraints(?string $value): bool
    {
        foreach ($this->constraints as $constraint => $targetValue) {
            switch ($constraint) {
                case FC::Required:
                    if ($targetValue && ($value === '' || $value === null)) {
                        $this->errorMessage = 'Value is required';
                        return false;
                    }
                    break;
                case FC::MaxLength:
                    if ($targetValue > 0 && strlen($value) > $targetValue) {
                        $this->errorMessage = "Value should not be longer than {$targetValue} characters";
                        return false;
                    }
                    break;
                case FC::MinLength:
                    if ($targetValue > 0 && strlen($value) < $targetValue) {
                        $this->errorMessage = "Value should not be shorter than {$targetValue} characters";
                        return false;
                    }
                    break;
                // this is checked at constructor time
                //default:
                //    throw new \DomainException("Unsupported field constraint: '$constraint");
            }
        }

        return true;
    }

    public function setError(string $erroMessage)
    {
        $this->errorMessage = $erroMessage;
    }

    /**
     * Returns the field value as usable by php code, which might differ from what is output
     */
    public function getData(): mixed
    {
        return $this->value;
    }

    public function isRequired(): bool
    {
        return array_key_exists(FC::Required, $this->constraints) && $this->constraints[FC::Required];
    }

    public function getMaxLength(): ?int
    {
        return array_key_exists(FC::MaxLength, $this->constraints) ? (int)$this->constraints[FC::MaxLength] : null;
    }

    public function getMinLength(): ?int
    {
        return array_key_exists(FC::MinLength, $this->constraints) ? (int)$this->constraints[FC::MinLength] : null;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'value':
            case 'errorMessage':
                return $this->$name;
            case 'isValid':
                return null !== $this->errorMessage && '' !== $this->errorMessage;
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
            'isValid' => true,
            default => false
        };
    }
}
