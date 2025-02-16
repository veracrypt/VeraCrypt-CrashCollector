<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Form\Field\Constraint\ActiveUserTokenConstraint;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Form\Form as BaseForm;
use Veracrypt\CrashCollector\RateLimiter\Constraint\FixedWindow;
use Veracrypt\CrashCollector\RateLimiter\RateLimiter;
use Veracrypt\CrashCollector\Repository\ForgotPasswordTokenRepository;

class ForgotPasswordEmailForm extends BaseForm
{
    protected int $submitOn = self::ON_GET;
    protected ActiveUserTokenConstraint $userConstraint;

    public function __construct(string $actionUrl, ?int $tokenId = null, #[\SensitiveParameter] ?string $secret = null)
    {
        $this->userConstraint = new ActiveUserTokenConstraint(ForgotPasswordTokenRepository::class);
        $this->fields = [
            /// @todo add an is-integer constraint?
            'token' => new Field\Hidden('tkn', [
                FC::Required => true,
                FC::RateLimit => new RateLimiter([
                    new FixedWindow($actionUrl, 10, 300), // equivalent to once every 30 secs
                    new FixedWindow($actionUrl, 12, 3600), // equivalent to once every 5 minutes
                    new FixedWindow($actionUrl, 120, 86400), // equivalent to once every 12 minutes
                ]),
                FC::Custom => $this->userConstraint
            ], $tokenId),
            /// @todo get the field length from the TokenRepository
            'secret' => new Field\Hidden('sec', [
                FC::Required => true,
                FC::MinLength => 128,
                FC::MaxLength => 128,
            ], $secret)
        ];

        parent::__construct($actionUrl);
    }

    protected function validateSubmit(?array $request = null): void
    {
        // use the same error message used for invalid token-ids
        if (!$this->userConstraint->validateHash($this->getFieldData('secret'))) {
            $this->setError("Token not found");
        }
    }

    public function isSubmitted(?array $request = null): bool
    {
        if ($request === null) {
            $request = $this->getRequest();
        }
        return array_key_exists('tkn', $request);
    }

    public function getUser(): null|User
    {
        return $this->userConstraint->getUser();
    }
}
