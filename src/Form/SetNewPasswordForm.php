<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Form\Field\Constraint\ActiveUserTokenConstraint;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Repository\ForgotPasswordTokenRepository;
use Veracrypt\CrashCollector\Repository\UserTokenRepository;

/**
 * Displayed to users who follow the link in the forgotpassword email
 */
class SetNewPasswordForm extends PasswordUpdateBaseForm
{
    protected ActiveUserTokenConstraint $userConstraint;
    private ?int $tokenId;
    private ?string $secret;

    public function __construct(string $actionUrl, ?int $tokenId, #[\SensitiveParameter] ?string $secret)
    {
        $this->tokenId = $tokenId;
        $this->secret = $secret;
        parent::__construct($actionUrl);
    }

    protected function getFieldsDefinitions(string $actionUrl, string $token = ''): array
    {
/// @todo add anti-csrf and/or rate-limiting
        $this->userConstraint = new ActiveUserTokenConstraint(ForgotPasswordTokenRepository::class);
        return array_merge(parent::getFieldsDefinitions($actionUrl), [
            'token' => new Field\Hidden('tkn', [
                FC::Required => true,
                FC::Custom => $this->userConstraint
            ], $this->tokenId),
            /// @todo get the field length from the TokenRepository
            'secret' => new Field\Hidden('sec', [
                FC::Required => true,
                FC::MinLength => 128,
                FC::MaxLength => 128,
            ], $this->secret)
        ]);
    }

    protected function validateSubmit(?array $request = null): void
    {
        parent::validateSubmit($request);
        if ($this->isValid) {
            if (!$this->userConstraint->validateHash($this->getFieldData('secret'))) {
                // use the same error message used for invalid token-ids
                $this->setError("Token not found");
            }
        }
    }


    public function getUser(): null|User
    {
        return $this->userConstraint->getUser();
    }

    public function getTokenRepository(): UserTokenRepository
    {
        return $this->userConstraint->getTokenRepository();
    }
}