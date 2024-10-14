<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Exception\AccountExpiredException;
use Veracrypt\CrashCollector\Exception\AuthenticationException;
use Veracrypt\CrashCollector\Exception\BadCredentialsException;
use Veracrypt\CrashCollector\Exception\UserNotFoundException;
use Veracrypt\CrashCollector\Logger;

class UsernamePasswordAuthenticator
{
    /** @var UserProvider $userProvider */
    protected $userProvider;
    /** @var PasswordHasher $passwordHasher */
    protected $passwordHasher;
    /** @var Logger $logger */
    protected $logger;

    public function __construct()
    {
        $this->userProvider = new UserProvider();
        $this->passwordHasher = new PasswordHasher();
        $this->logger = new Logger($_ENV['AUDIT_LOG_FILE'], $_ENV['AUDIT_LOG_LEVEL']);
    }

    /**
     * @throws AuthenticationException
     */
    public function authenticate(string $username, #[\SensitiveParameter] string $password): UserInterface
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException $e) {
            $this->logger->info("User '$username' failed logging in: not found");
            throw $e;
        }
        if (!$this->passwordHasher->verify($user->passwordHash, $password)) {
            $this->logger->info("User '$username' failed logging in: bad password");
            throw new BadCredentialsException('Invalid username/password');
        }
        if (!$user->isActive()) {
            $this->logger->info("User '$username' failed logging in: it is not active");
            throw new AccountExpiredException('User account is not active');
        }

        Firewall::getInstance()->loginUser($user);

        $this->logger->debug("User '$username' logged in");

        return $user;
    }
}
