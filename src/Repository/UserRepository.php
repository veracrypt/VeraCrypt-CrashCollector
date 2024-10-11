<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;

class UserRepository extends Repository
{
    protected $tableName = 'auth_user';

    public function __construct()
    {
        // Col names, type and size are inspired from Django + Symfony.
        $this->fields = [
            /// @todo should we disallow '' as value for all non-null string fields? Sqlite f.e. supports `CHECK()`
            /// @todo make the SQL more portable - `autoincrement` does not exist in either MySQL or Postgresql - drop the id col altogether?
            'id' => new Field(null, 'integer', [FC::NotNull => true, FC::PK => true, FC::Autoincrement => true]),
            'username' => new Field('username', 'varchar', [FC::Length => 180, FC::NotNull => true, FC::Unique => true]),
            'password' => new Field('passwordHash', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'email' => new Field('email', 'varchar', [FC::Length => 254, FC::NotNull => true]),
            'first_name' => new Field('firstName', 'varchar', [FC::Length => 150, FC::NotNull => true]),
            'last_name' => new Field('lastName', 'varchar', [FC::Length => 150, FC::NotNull => true]),
            'date_joined' => new Field('dateJoined', 'integer', [FC::NotNull => true]),
            'last_login' => new Field('lastLogin', 'integer', []),
            'is_active' => new Field('isActive', 'bool', [FC::NotNull => true, FC::Default => 'true']),
            //'is_staff' => new Field('isStaff', 'bool', [FC::NotNull => true, FC::Default => 'false']),
            'is_superuser' => new Field('isSuperuser', 'bool', [FC::NotNull => true, FC::Default => 'false']),
        ];

        parent::__construct();
    }

    /**
     * Note: this does not validate the length of the fields, nor truncate or validate them
     */
    public function createUser(string $username, string $passwordHash, string $email, string $firstName,
        string $lastName, bool $isSuperUser = false, bool $isActive = true): User
    {
        $dateJoined = time();
        $user = new User($username, $passwordHash, $email, $firstName, $lastName, $dateJoined, null, $isActive, $isSuperUser);
        $this->storeEntity($user);
        return $user;
    }

    /*
    public function fetchUser(string $username): User|null
    {
        $query = $this->buildFetchEntityQuery() . ' where username = :username';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $result = $stmt->fetchObject(User::class);
        return $result ? $result : null;
    }
    */

    /**
     * NB: passing in an empty string for any value will trigger the data to be updated in the DB, unlike passing in a NULL.
     * This might be unexpected...
     * @todo we could allow the username to be changed too, by adding a $newUsername argument
     * @throws \BadMethodCallException
     */
    public function updateUser(string $username, ?string $passwordHash = null, ?string $email = null, ?string $firstName = null,
        ?string $lastName = null, ?bool $isSuperUser = null, ?bool $isActive = null): bool
    {
        $setClauses = [];
        if ($passwordHash !== null) {
            $setClauses[] = ['password', $passwordHash];
        }
        if ($email !== null) {
            $setClauses[] = ['email', $email];
        }
        if ($firstName !== null) {
            $setClauses[] = ['first_name', $firstName];
        }
        if ($lastName !== null) {
            $setClauses[] = ['last_name', $lastName];
        }
        if ($isSuperUser !== null) {
            $setClauses[] = ['is_superuser', (int)$isSuperUser];  // we cast to int as otherwise SQLite will store php false as ''...
        }
        if ($isActive !== null) {
            $setClauses[] = ['is_active', (int)$isActive];  // we cast to int as otherwise SQLite will store php false as ''...
        }
        if (!$setClauses) {
            throw new \BadMethodCallException("At least one argument to updateUser must be not null");
        }

        $sqlParts = [];
        foreach ($setClauses as $i => $def) {
            $sqlParts[] = "{$def[0]} = :$i";
        }
        $query = 'update ' . $this->tableName . ' set ' . implode(', ', $sqlParts) . ' where username = :username';
        $stmt = self::$dbh->prepare($query);
        foreach ($setClauses as $i => $def) {
            $stmt->bindValue(":$i", $def[1]);
        }
        $stmt->bindValue(":username", $username);
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }

    public function deleteUser(string $username): bool
    {
        $query = 'delete from ' . $this->tableName . ' where username = :username';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }

    public function activateUser(string $username): bool
    {
        $query = 'update ' . $this->tableName . ' set is_active = true where username = :username';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }

    public function deactivateUser(string $username): bool
    {
        $query = 'update ' . $this->tableName . ' set is_active = false where username = :username';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }

    public function userLoggedIn(string $username): bool
    {
        /// @todo add a condition on existing last_login not being later than the new one?
        $query = 'update ' . $this->tableName . ' set last_login = :last_login where username = :username';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':last_login', time());
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }

    /**
     * @return mixed[][] we return arrays instead of value-objects, to make it easy for the console table helper.
     *                   This is also why the password hash is omitted.
     */
    public function listUsers(): Array
    {
        $query = 'select username, email, first_name, last_name, date_joined, last_login, is_active, is_superuser from ' .
            $this->tableName;
        $stmt = self::$dbh->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
