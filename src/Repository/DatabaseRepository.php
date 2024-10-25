<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Storage\DatabaseTable;

abstract class DatabaseRepository
{
    use DatabaseTable;

    /**
     * @throws \DomainException
     * @trows \PDOException
     */
    public function __construct()
    {
        $this->connect();
        $this->createTableIfNeeded();
    }

    /**
     * @throws \DomainException
     */
    /*public function getField(string $entityFieldName): Field
    {
        foreach($this->fields as $field) {
            if ($field->entityField === $entityFieldName) {
                return $field;
            }
        }
        throw new \DomainException("Repository has no field named '$entityFieldName'");
    }*/

    protected function buildFetchEntityQuery(): string
    {
        $query = 'select ';
        foreach($this->fields as $colName => $field) {
            if ($field->entityField == '') {
                continue;
            }
            $query .= $colName;
            if ($field->entityField !== $colName) {
                $query .= ' as ' . $field->entityField;
            }
            $query .= ', ';
        }
        return substr($query, 0, -2) . ' from ' . $this->tableName;
    }

    /**
     * @throws \PDOException
     */
    protected function storeEntity($value): void
    {
        $query = 'insert into ' . $this->tableName . ' (';
        $vq = '';
        foreach($this->fields as $colName => $field) {
            if ($field->entityField == '') {
                continue;
            }
            $query .= $colName . ', ';
            $vq .= ":$colName" . ', ';
        }
        $query = substr($query, 0, -2) . ') values (' . substr($vq, 0, -2) . ')';

        $stmt = self::$dbh->prepare($query);
        /// @todo test: can `bindvalue` or `execute` fail without throwing?
        foreach($this->fields as $colName => $field) {
            if ($field->entityField == '') {
                continue;
            }
            $entityField = $field->entityField;
            $val = $value->$entityField;
            if ($field->type === 'bool') {
                // we cast to int as otherwise SQLite will store php false as ''...
                $val = (int)$val;
            }
            $stmt->bindValue(":$colName", $val);
        }
        $stmt->execute();
    }
}
