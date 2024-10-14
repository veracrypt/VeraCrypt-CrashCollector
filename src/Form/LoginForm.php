<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Form\Form as BaseForm;
use Veracrypt\CrashCollector\Security\PasswordHasher;

class LoginForm extends BaseForm
{
    protected string $submitLabel = 'Login';

    public function __construct(string $redirect)
    {
        $this->fields = [
            /// @todo get the field length from the Repo fields
            'username' => new Field('Username', 'un', 'text', [FC::Required => true, FC::MaxLength => 180]),
            'password' => new Field('Password', 'pw', 'password', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
            'redirect' => new Field('', 'r', 'hidden', [FC::Required => true], $redirect)
        ];
    }
}
