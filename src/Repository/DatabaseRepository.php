<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Storage\Database\Table;

/**
 * @property Field[] $fields
 */
abstract class DatabaseRepository
{
    use Table;

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
     * @return null|array when there are autoincrement cols, and no value is passed in for those, their value is returned
     * @throws \PDOException
     */
    protected function storeEntity($value): null|array
    {
        $query = 'insert into ' . $this->tableName . ' (';
        $vq = '';
        $autoIncrementCols = [];
        foreach($this->fields as $colName => $field) {
            foreach ($field->constraints as $constraintType => $constraintValue) {
                if ($constraintType === FieldConstraint::Autoincrement && $constraintValue) {
                    $entityField = $field->entityField;
                    if ($entityField == '' || $value->$entityField === null) {
                        $autoIncrementCols[] = $colName;
                    }
                }
            }
            if ($field->entityField == '') {
                continue;
            }
            $query .= $colName . ', ';
            $vq .= ":$colName" . ', ';
        }
        $query = substr($query, 0, -2) . ') values (' . substr($vq, 0, -2) . ')';
        if ($autoIncrementCols) {
            // 'returning' is supported by sqlite >= ..., mariadb >= 10.5, postgresql
            $query .= ' returning ' . implode(', ', $autoIncrementCols);
        }

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

        if ($autoIncrementCols) {
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        return null;
    }
}
