<?php

namespace Veracrypt\CrashCollector\Security;

enum UserRole: string
{
    case Anon = 'Anonymous';
    case User = 'User';
    case Admin = 'Admin';
}
