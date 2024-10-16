<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Form\Form as BaseForm;
use Veracrypt\CrashCollector\Security\PasswordHasher;

class LoginForm extends BaseForm
{
    protected string $submitLabel = 'Login';

    public function __construct(string $actionUrl, string $redirect)
    {
        $this->fields = [
            /// @todo get the field length from the Repo fields
            'username' => new Field\Text('Username', 'un', [FC::Required => true, FC::MaxLength => 180]),
            'password' => new Field\Password('Password', 'pw', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
            'redirect' => new Field\Redirect('r', [FC::Required => true], $redirect)
        ];

        parent::__construct($actionUrl);
    }
}
