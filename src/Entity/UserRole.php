<?php

namespace Veracrypt\CrashCollector\Entity;

enum UserRole: string
{
    case Anon = 'Anonymous';
    case User = 'User';
    case Admin = 'Admin';
}
