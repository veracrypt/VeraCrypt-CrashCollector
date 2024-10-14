<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Entity\UserRole;

class AnonymousUser implements UserInterface
{
    public function getRoles(): array
    {
        return [UserRole::Anon];
    }

    public function getUserIdentifier(): string
    {
        return 'anonymous';
    }

    public function isActive(): bool
    {
        return true;
    }
}
