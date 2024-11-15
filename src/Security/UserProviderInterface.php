<?php

namespace Veracrypt\CrashCollector\Security;

interface UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface;
}
