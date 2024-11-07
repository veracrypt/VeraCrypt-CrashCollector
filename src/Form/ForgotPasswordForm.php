<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Form\Field\Constraint\ActiveUserEmailConstraint;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Form\Form as BaseForm;
use Veracrypt\CrashCollector\RateLimiter\Constraint\FixedWindow;
use Veracrypt\CrashCollector\RateLimiter\RateLimiter;

class ForgotPasswordForm extends BaseForm
{
    protected string $submitLabel = 'Submit';
    protected ActiveUserEmailConstraint $userConstraint;
    protected bool $pretendIsValid = false;

    public function __construct(string $actionUrl)
    {
        $this->userConstraint = new ActiveUserEmailConstraint();
        $this->fields = [
            /// @todo get the field length from the Repo field
            'email' => new Field\Email('Email', 'em', [
                FC::Required => true,
                FC::MaxLength => 254,
                FC::RateLimit => new RateLimiter([
                    new FixedWindow($actionUrl, 10, 300), // equivalent to once every 30 secs
                    new FixedWindow($actionUrl, 12, 3600), // equivalent to once every 5 minutes
                    new FixedWindow($actionUrl, 120, 86400), // equivalent to once every 12 minutes
                ]),
                FC::Custom => $this->userConstraint
            ])
        ];

        parent::__construct($actionUrl);
    }

    public function handleRequest(?array $request = null): void
    {
        parent::handleRequest($request);

        // Allow hiding all error messages from the end user, except empty and too-long email, to avoid the enumeration
        // of existing users email (but keep $this->isValid false)
        if (!$this->isValid && str_starts_with($this->fields['email']->errorMessage, 'User matching email ')) {
            $this->pretendIsValid = true;
        }
    }

    public function getUser(): null|User
    {
        return $this->userConstraint->getUser();
    }

    public function pretendIsValid(): bool
    {
        return $this->pretendIsValid;
    }
}
