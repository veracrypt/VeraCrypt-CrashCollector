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

    /**
     * Disabled users are not allowed to log in
     */
    public function isActive(): bool;

    /**
     * Returns false for Anonymous users
     */
    public function isAuthenticated(): bool;
}
