<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Exception\BadCredentialsException;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Security\PasswordHasher;
use Veracrypt\CrashCollector\Security\UserInterface;
use Veracrypt\CrashCollector\Security\UsernamePasswordAuthenticator;

class ResetPasswordForm extends Form
{
    protected UserInterface $currentUser;

    public function __construct(UserInterface $currentUser)
    {
        $this->fields = [
            'oldPassword' => new Field('Current Password', 'cp', 'password', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
            'newPassword' => new Field('New Password', 'np', 'password', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
            'newPasswordConfirm' => new Field('Confirm new Password', 'npc', 'password', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
        ];
        $this->currentUser = $currentUser;
    }

    public function handleRequest(?array $request = null): void
    {
        parent::handleRequest($request);

        if ($this->isValid) {
            if ($this->fields['newPassword']->getData() !== $this->fields['newPasswordConfirm']->getData()) {
                $this->fields['newPasswordConfirm']->setError('The password does not match');
                $this->isValid = false;
            } else {
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
}
