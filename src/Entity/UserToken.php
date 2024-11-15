<?php

namespace Veracrypt\CrashCollector\Entity;

use DateTimeInterface;
use Veracrypt\CrashCollector\Repository\UserRepository;

class UserToken extends Token
{
    public function __construct(
        ?int $id,
        string $hash,
        public readonly string $username,
        int|DateTimeInterface $dateCreated,
        null|int|DateTimeInterface $expirationDate
    )
    {
        parent::__construct($id, $hash, $dateCreated, $expirationDate);
    }

    public function getUser(): null|User
    {
        $repo = new UserRepository();
        return $repo->fetchUser($this->username);
    }
}
