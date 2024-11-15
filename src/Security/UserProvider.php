<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Exception\UserNotFoundException;
use Veracrypt\CrashCollector\Repository\UserRepository;

class UserProvider implements UserProviderInterface
{
    /** @var UserRepository $userRepository */
    protected $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * @throws UserNotFoundException
     * @throws \PDOException
     */
    public function loadUserByIdentifier(string $identifier): User
    {
        $user = $this->userRepository->fetchUser($identifier);
        if ($user === null) {
            throw new UserNotFoundException("No such user: $identifier");
        }
        return $user;
    }
}
