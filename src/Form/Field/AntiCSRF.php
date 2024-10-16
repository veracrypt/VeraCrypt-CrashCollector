<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Exception\AntiCSRFException;
use Veracrypt\CrashCollector\Form\Field as Basefield;
use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\Security\AntiCSRF as AntiCSRFTokenManager;

class AntiCSRF extends Basefield
{
    /**
     * This field supports no specific validation rules nor a default value
     */
    public function __construct(string $inputName, protected ?string $formActionUrl = null)
    {
        parent::__construct('anticsrf', '', $inputName, [], null, false);
    }

    /**
     * NB: the value is always set to NULL so that it will be skipped by the form's `getData` method.
     * To retrieve the actual token, call `getAntiCSRFToken` and remember: that should only be done on form display.
     * @return null (php 8.1 does not allow to typehint return to null...)
     */
    protected function validateValue(mixed $value): null|string
    {
        $antiCSRF = new AntiCSRFTokenManager();
        try {
            $antiCSRF->validateToken((string)$value, $this->formActionUrl);
        } catch (AntiCSRFException $e) {
            $this->errorMessage = 'The ANTI-CSRF token has been tampered or is missing';
            $logger = Logger::getInstance('audit');
            $logger->info('ANTI-CSRF token tampering attempt. ' . $e->getMessage());
        }
        return null;
    }

    public function getAntiCSRFToken(): string
    {
        $antiCSRF = new AntiCSRFTokenManager();
        return $antiCSRF->getToken($this->formActionUrl);
    }
}
