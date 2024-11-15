<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Entity\ForgotPasswordToken;

/**
 * @method null|ForgotPasswordToken fetch(string $hash)
 */
class ForgotPasswordTokenRepository extends UserTokenRepository
{
    protected string $tableName = 'token_forgotpassword';
    protected string $entityClass = ForgotPasswordToken::class;

    public function __construct()
    {
        parent::__construct(128);
    }

    /**
     * The tokens are valid for 1 hour by default
     */
    protected function newTokenExpirationDate(): null|int
    {
        return time() + 3600;
    }
}
