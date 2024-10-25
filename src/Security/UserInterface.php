<?php

namespace Veracrypt\CrashCollector\Security;

interface UserInterface
{
    /**
     * @return UserRole[]
     */
    public function getRoles(): array;

    /**
     * Returns the identifier for this user (e.g. username or email address).
     */
    public function getUserIdentifier(): string;

    public function isActive(): bool;
}
