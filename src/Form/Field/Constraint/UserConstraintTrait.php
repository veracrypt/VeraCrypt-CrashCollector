<?php

namespace Veracrypt\CrashCollector\Form\Field\Constraint;

use Veracrypt\CrashCollector\Entity\User;

trait UserConstraintTrait
{
    protected ?User $user = null;

    public function getUser(): null|User
    {
        return $this->user;
    }
}
