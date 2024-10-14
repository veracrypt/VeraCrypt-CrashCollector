<?php

namespace Veracrypt\CrashCollector\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Veracrypt\CrashCollector\Security\UserInterface;

/**
 * @property-read int $dateJoined
 * @property-read DateTimeImmutable $dateJoinedDT
 * @property-read int|null $lastLogin
 * @property-read DateTimeImmutable|null $lastLoginDT
 */
class User implements UserInterface
{
    // we store timestamps as properties instead of datetimes in order to be able to use PDO automatic hydration to object
    private int $dateJoined;
    private ?int $lastLogin;

    public function __construct(
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly ?string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        // NB: we use integers (unix timestamps) for dates for maximum portability between databases
        int|DateTimeInterface $dateJoined,
        null|int|DateTimeInterface $lastLogin,
        public readonly bool $isActive,
        public readonly bool $isSuperuser,
    ) {
        if (is_int($dateJoined)) {
            $this->dateJoined = $dateJoined;
        } else {
            $this->dateJoined = $dateJoined->getTimestamp();
        }
        if ($lastLogin === null || is_int($lastLogin)) {
            $this->lastLogin = $lastLogin;
        } else {
            $this->lastLogin = $lastLogin->getTimestamp();
        }
    }

    public function getRoles(): array
    {
        $roles = [UserRole::User];
        if ($this->isSuperuser) {
            $roles[] = UserRole::Admin;
        }
        return $roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'dateJoined':
                return $this->dateJoined;
            case 'dateJoinedDT':
                return new DateTimeImmutable("@{$this->dateJoined}");
            case 'lastLogin':
                return $this->lastLogin;
            case 'lastLoginDT':
                return $this->lastLogin === null ? null : new DateTimeImmutable("@{$this->lastLogin}");
            default:
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                trigger_error('Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' .
                    $trace[0]['line'], E_USER_ERROR);
        }
    }

    public function __isset($name)
    {
        switch ($name) {
            case 'dateJoined':
            case 'dateJoinedDT':
                return true;
            case 'lastLogin':
            case 'lastLoginDT':
                return isset($this->lastLogin);
            default:
                return false;
        }
    }
}
