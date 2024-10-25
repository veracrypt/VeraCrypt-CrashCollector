<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Form\Form as BaseForm;
use Veracrypt\CrashCollector\RateLimiter\Constraint\FixedWindow;
use Veracrypt\CrashCollector\RateLimiter\RateLimiter;
use Veracrypt\CrashCollector\Security\PasswordHasher;

class LoginForm extends BaseForm
{
    protected string $submitLabel = 'Login';
    protected RateLimiter $rateLimiter;

    public function __construct(string $actionUrl, string $redirect)
    {
        $this->rateLimiter = new RateLimiter([
            new FixedWindow($actionUrl, 5, 10), // equivalent to once every 2 secs
            new FixedWindow($actionUrl, 80, 3600), // equivalent to once every 45 secs
            new FixedWindow($actionUrl, 288, 86400), // equivalent to once every 5 minutes
        ]);

        $this->fields = [
            /// @todo get the field length from the Repo fields
            'username' => new Field\Text('Username', 'un', [
                FC::Required => true,
                FC::MaxLength => 180,
                FC::RateLimit => $this->rateLimiter,
            ]),
            'password' => new Field\Password('Password', 'pw', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
            'redirect' => new Field\Redirect('r', [FC::Required => true], $redirect),
        ];

        parent::__construct($actionUrl);
    }

    public function onSuccessfulLogin()
    {
        $this->rateLimiter->reset($this->getField('username')->getData());
    }
}
