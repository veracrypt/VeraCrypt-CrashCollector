<?php

namespace Veracrypt\CrashCollector\Form\Field\Constraint;

use Veracrypt\CrashCollector\Exception\ConstraintUserInactiveException;
use Veracrypt\CrashCollector\Exception\ConstraintUserNotFoundException;
use Veracrypt\CrashCollector\Repository\UserRepository;

class ActiveUserEmailConstraint implements ConstraintInterface
{
    use UserConstraintTrait;

    public function validateRequest(?string $value = null): void
    {
        $ur = new UserRepository();
        $users = $ur->fetchUsersByEmail($value);
        if (!$users || count($users) !== 1) {
            throw new ConstraintUserNotFoundException('User matching email either not found or not unique');
        }
        $user = $users[0];
        if (!$user->isActive()) {
            throw new ConstraintUserInactiveException('User matching email is not active');
        }
        $this->user = $user;
    }
}
