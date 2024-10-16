<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\Field\SubmitButton;

/**
 * @property-read ?string $errorMessage
 * @property-read string $actionUrl
 */
abstract class Form
{
    const ON_GET = 1;
    const ON_POST = 2;
    const ON_BOTH = 3;

    /** @var Field[] $fields */
    protected array $fields = [];
    protected string $submitLabel = 'Submit';
    protected int $submitOn = self::ON_POST;
    protected bool $isValid = false;
    protected ?string $errorMessage = null;
    protected string $actionUrl;

    public function __construct(string $actionUrl)
    {
        $this->actionUrl = $actionUrl;
    }

    /**
     * @throws \DomainException
     */
    public function getField($fieldName): Field
    {
        if (array_key_exists($fieldName, $this->fields)) {
            return $this->fields[$fieldName];
        }
        throw new \DomainException("Form has no field named '$fieldName'");
    }

    public function getMethod(): string
    {
        if ($this->submitOn == self::ON_GET) {
            return 'get';
        }
        return 'post';
    }

    public function getSubmit(): Field
    {
        return new SubmitButton($this->submitLabel, 's', [], 1);
    }

    public function isSubmitted(?array $request = null): bool
    {
        $submit = $this->getSubmit();
        if ($request === null) {
            $request = $this->getRequest();
        }
        return isset($request[$submit->inputName]) && $request[$submit->inputName] == $submit->value;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function handleRequest(?array $request = null): void
    {
        $this->isValid = true;
        if ($request === null) {
            $request = $this->getRequest();
        }
        foreach($this->fields as &$field) {
            if (!$field->setValue(array_key_exists($field->inputName, $request) ? $request[$field->inputName] : null)) {
                // in case the field is not shown to the end user, we show its error message as the form's error message
                if (!$field->isVisible) {
                    $this->setError($field->errorMessage);
                }
                $this->isValid = false;
            }
        }

        if ($this->isValid()) {
            $this->validateSubmit($request);
        }
    }

    /**
     * To be overridden in forms which have custom validation rules besides single field validation.
     * Called after field validation, only if all the fields did validate.
     * Should set $this->isValid and $this->errorMessage if there's anything wrong.
     * Should work preferably with values from $this->fields rather than $request, which is passed in as a commodity
     */
    protected function validateSubmit(?array $request = null): void
    {
    }

    public function getData(): array
    {
        $data = [];
        foreach($this->fields as $name => $field) {
            if ($field->value !== null) {
                $data[$name] = $field->getData();
            }
        }
        return $data;
    }

    public function getQueryStringParts(bool $includeSubmit = false)
    {
        if ($this->submitOn == self::ON_POST) {
            return [];
        }
        $qs = [];
        foreach($this->fields as $field) {
            if ($field->value !== null) {
                $qs[$field->inputName] = $field->value;
            }
        }
        if ($includeSubmit) {
            $field = $this->getSubmit();
            $qs[$field->inputName] = $field->value;
        }
        return $qs;
    }

    protected function getRequest()
    {
        // exclude values from $_COOKIE
        switch ($this->submitOn) {
            case self::ON_GET:
                return $_GET;
            case self::ON_POST:
                return $_POST;
            case self::ON_BOTH:
                return array_merge($_GET, $_POST);
        }
    }

    public function setError(?string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
        $this->isValid = ($errorMessage !== null && $errorMessage !== '');
    }

    public function __get($name)
    {
        switch ($name) {
            case 'actionUrl':
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
            'actionUrl' => true,
            'errorMessage' => isset($this->$name),
            default => false
        };
    }
}
