<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Entity\Token;
use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;
use Veracrypt\CrashCollector\Storage\Database\Index;

abstract class TokenRepository extends DatabaseRepository
{
    protected string $entityClass;

    /**
     * @param int $tokenLength keep below PasswordHasher::MAX_PASSWORD_LENGTH
     * @throws \DomainException in case of unsupported database type
     * @trows \PDOException
     */
    protected function __construct(public readonly int $tokenLength)
    {
        $this->fields = $this->getFieldsDefinitions();
        $this->foreignKeys = $this->getForeignKeyDefinitions();
        $this->indexes = $this->getIndexesDefinitions();

        parent::__construct();
    }

    protected function getFieldsDefinitions()
    {
        return [
            'id' => new Field('id', 'integer', [FC::NotNull => true, FC::PK => true, FC::Autoincrement => true]),
            'hash' => new Field('hash', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'date_created' => new Field('dateCreated', 'integer', [FC::NotNull => true]),
            'expiration_date' => new Field('expirationDate', 'integer', []),
            // could add cols: usage_count, last_usage_datetime
        ];
    }

    protected function getForeignKeyDefinitions(): array
    {
        return [];
    }

    protected function getIndexesDefinitions(): array
    {
        return [
            // used by the purge command
            'idx_' . $this->tableName . '_ed' => new Index(['expiration_date']),
        ];
    }

    abstract protected function newTokenExpirationDate(): null|int;

    public function fetch(int $id): null|Token
    {
        $query = $this->buildFetchEntityQuery() . ' where id = :id';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? new $this->entityClass(...$result) : null;
    }

    public function delete(int $id): bool
    {
        $query = 'delete from ' . $this->tableName . ' where id = :id';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }

    /**
     * Removes all expired tokens
     */
    public function prune(): bool
    {
        $query = 'delete from ' . $this->tableName . ' where expiration_date <= :now';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':now', time());
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }
}
