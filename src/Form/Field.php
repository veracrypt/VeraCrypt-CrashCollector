<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Exception\AntiCSRFException;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\Security\AntiCSRF;

/**
 * @property-read ?string $value
 * @property-read ?string $errorMessage
 */
class Field
{
    const SupportedFieldTypes = ['anticsrf', 'datetime', 'email', 'hidden', 'password', 'submit', 'text', 'textarea'];

    protected mixed $value = null;
    protected ?string $errorMessage = null;

    public function __construct(
        public readonly string $label,
        public readonly string $inputName,
        public readonly string $inputType,
        public readonly array $constraints = [],
        mixed $value = null
    )
    {
        // type validation
        if (!in_array($this->inputType, self::SupportedFieldTypes)) {
            throw new \DomainException("Unsupported field type: $inputType");
        }

        /// @todo for 'anticsrf' fields, check that there are no constraints defined, as it does not check those anyway.
        ///       Also, we should prevent those fields to be used on forms which submit via GET

        // constraint validation
        foreach ($this->constraints as $constraint => $targetValue) {
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
        $this->value = $value;
    }

    /**
     * Used to set (and validate) the submitted value.
     * NB: constraints are checked in the order they are defined
     * @return bool false when the value is not valid
     */
    public function setValue(mixed $value): bool
    {
        $isValid = true;

        if ($value !== null || $this->inputType === 'anticsrf') {
            $value = trim($value);

            switch($this->inputType) {
                case 'anticsrf':
                    // we always store null, so that the csrf token will be omitted from the form's getData
                    $this->value = null;
                    $antiCSRF = new AntiCSRF();
                    try {
                        /// @todo allow checking the token against the form it was generated on
                        $antiCSRF->validateToken($value);
                        return true;
                    } catch (AntiCSRFException $e) {
                        $this->errorMessage = 'The ANTI-CSRF token has been tampered or is missing';
                        $logger = Logger::getInstance('audit');
                        $logger->info('ANTI-CSRF token tampering attempt. ' . $e->getMessage());
                        return false;
                    }
                    break;
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
                    if ($targetValue > 0 && strlen($value) > $targetValue) {
                        $this->errorMessage = "Value should not be longer than {$targetValue} characters";
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

    public function isRequired(): bool
    {
        return array_key_exists(FC::Required, $this->constraints) && $this->constraints[FC::Required];
    }

    public function getMaxLength(): ?int
    {
        return array_key_exists(FC::MaxLength, $this->constraints) ? (int)$this->constraints[FC::MaxLength] : null;
    }

    public function isVisible(): bool
    {
        return !in_array($this->inputType, ['anticsrf', 'hidden']);
    }

    public function getAntiCSRFToken()
    {
        if ($this->inputType !== 'anticsrf') {
            throw new \DomainException("getAntiCSRFToken called on form field of type '{$this->inputType}'");
        }

        $antiCSRF = new AntiCSRF();
        return $antiCSRF->getToken();
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
