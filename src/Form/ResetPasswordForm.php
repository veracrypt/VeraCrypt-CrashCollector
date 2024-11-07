<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Exception\BadCredentialsException;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Security\PasswordHasher;
use Veracrypt\CrashCollector\Security\UserInterface;
use Veracrypt\CrashCollector\Security\UsernamePasswordAuthenticator;

class ResetPasswordForm extends PasswordUpdateBaseForm
{
    protected UserInterface $currentUser;

    public function __construct(string $actionUrl, UserInterface $currentUser)
    {
        $this->currentUser = $currentUser;
        parent::__construct($actionUrl);
    }

    protected function getFieldsDefinitions(string $actionUrl): array
    {
        return array_merge(
            ['oldPassword' => new Field\Password('Current Password', 'cp', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH])],
            parent::getFieldsDefinitions($actionUrl),
            ['antiCSRF' => new Field\AntiCSRF('ac', $actionUrl)]
        );
    }

    protected function validateSubmit(?array $request = null): void
    {
        parent::validateSubmit($request);
        if ($this->isValid) {
            $authenticator = new UsernamePasswordAuthenticator();
            try {
                $authenticator->authenticate($this->currentUser->getUserIdentifier(), $this->fields['oldPassword']->getData());
            } catch (BadCredentialsException) {
                $this->fields['oldPassword']->setError('The current password is wrong');
                $this->isValid = false;
            }
            /// @todo what to do in case we get an AccountExpiredException or UserNotFoundException?
            ///       This can happen, hopefully infrequently, when the form is displayed to a user still active, and
            ///       then submitted after the user got deactivated/deleted
        }
    }
}
