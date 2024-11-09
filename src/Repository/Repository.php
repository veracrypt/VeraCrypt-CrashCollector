<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Repository\Storage\DatabaseTable;
use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;

abstract class Repository
{
    use DatabaseTable;

    /** @var Field[] $fields NB: has to be set up before calling parent::__construct */
    protected array $fields = [];

    /**
     * @throws \DomainException, \PDOException
     */
    public function __construct()
    {
        $this->connect();
        $this->createTableIfNeeded();
    }

    /**
     * @throws \DomainException
     */
    public function getField(string $entityFieldName): Field
    {
        foreach($this->fields as $field) {
            if ($field->entityField === $entityFieldName) {
                return $field;
            }
        }
        throw new \DomainException("Repository has no field named '$entityFieldName'");
    }

    /**
     * A few notes on the SQLite type system (full docs at https://www.sqlite.org/datatype3.html), for the unwary:
     * - any column can hold any type!
     * - type juggling is in effect, but type-conversion rules are not the same as in php. Notably, expressions have no type!
     * - there is no 'bool' or 'date' column/data type. Columns defined as such get a 'numeric' preferential type (aka 'affinity')
     * - the length limit on varchar columns is ignored
     * @throws \DomainException, \PDOException
     */
    protected function createTable(): void
    {
        $query = 'CREATE TABLE ' . $this->tableName . ' (';
        foreach ($this->fields as $colName => $f) {
            $query .= $colName . ' ' . $f->type . ' ';
            $constraints = $f->constraints;
            if (isset($constraints[FC::Length])) {
                $query .= '(' . $f->constraints[FC::Length] . ') ';
                unset($constraints[FC::Length]);
            }
            foreach($constraints as $cn => $cv) {
                switch($cn) {
                    case FC::PK:
                        if ($cv) {
                            $query .= 'primary key ';
                        }
                        break;
                    case FC::Autoincrement:
                        if ($cv) {
                            $query .=  'autoincrement ';
                        }
                        break;
                    case FC::NotNull:
                        if ($cv) {
                            $query .=  'not null ';
                        }
                        break;
                    case FC::Unique:
                        if ($cv) {
                            $query .=  'unique ';
                        }
                        break;
                    case FC::Default:
                        if ($cv !== null) {
                            $query .=  'default ' . $cv . ' ';
                        }
                        break;
                    default:
                        throw new \DomainException("Unsupported Field constraint '$cn'");
                }
            }
            $query = substr($query, 0, -1) . ', ';
        }
        $query = substr($query, 0, -2) . ')';

        /// @todo convert PDO exceptions into repository exceptions?
        self::$dbh->exec($query);
    }

    protected function buildFetchEntityQuery(): string
    {
        $query = 'select ';
        foreach($this->fields as $col => $field) {
            if ($field->entityField == '') {
                continue;
            }
            $query .= $col;
            if ($field->entityField !== $col) {
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
        foreach($this->fields as $col => $field) {
            if ($field->entityField == '') {
                continue;
            }
            $query .= $col . ', ';
            $vq .= ":$col" . ', ';
        }
        $query = substr($query, 0, -2) . ') values (' . substr($vq, 0, -2) . ')';

        $stmt = self::$dbh->prepare($query);
        /// @todo test: can `bindvalue` or `execute` fail without throwing?
        foreach($this->fields as $col => $field) {
            if ($field->entityField == '') {
                continue;
            }
            $entityField = $field->entityField;
            $val = $value->$entityField;
            if ($field->type === 'bool') {
                // we cast to int as otherwise SQLite will store php false as ''...
                $val = (int)$val;
            }
            $stmt->bindValue(":$col", $val);
        }
        $stmt->execute();
    }
}
