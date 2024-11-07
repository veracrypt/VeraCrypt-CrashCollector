<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Entity\UserToken;
use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;

/**
 * @method null|UserToken fetch(int $id)
 */
abstract class UserTokenRepository extends TokenRepository
{
    protected function getFieldsDefinitions(): array
    {
        return array_merge(parent::getFieldsDefinitions(), [
            'username' => new Field('username', 'varchar', [FC::Length => 180, FC::NotNull => true]),
        ]);
    }

    public function createToken(string $userName, string $hash): UserToken
    {
        $args['id'] = null;
        $args['hash'] = $hash;
        $args['username'] = $userName;
        $args['dateCreated'] = time();
        $args['expirationDate'] = $this->newTokenExpirationDate();
        $token = new $this->entityClass(...$args);
        $autoincrements = $this->storeEntity($token);
        // we have to create a new entity object in order to inject the id into it
        $args['id'] = $autoincrements['id'];
        return new $this->entityClass(...$args);
    }
}
