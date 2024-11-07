<?php

namespace Veracrypt\CrashCollector\Storage\Database;

use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;
use Veracrypt\CrashCollector\Storage\Database;

trait Table
{
    use Database;

    /**
     * To be set in subclasses.
     * @var string
     */
    protected string $tableName;

    /** @var Column[] $fields Keys are table column names. NB: has to be set up before calling parent::__construct */
    protected array $fields = [];
    /** @var Index[] $indexes */
    protected array $indexes = [];
    /** @var ForeignKey[] $indexes */
    protected array $foreignKeys = [];

    /**
     * Flag to indicate that the underlying table has been created.
     * @var bool $tableExists
     */
    private bool $tableExists = false;

    /**
     * Check if the version db table exists and create it if not.
     *
     * @return bool true if table has been created, false if it was already there
     * @throws \DomainException in case of unsupported database type or unsupported field constraint
     * @throws \PDOException
     * @todo add a 'force' flag to force table drop + re-create
     * @todo manage changes to table definition
     */
    protected function createTableIfNeeded(): bool
    {
        if ($this->tableExists) {
            return false;
        }

        if ($this->tableExists($this->tableName)) {
            $this->tableExists = true;
            return false;
        }

        $this->createTable();

        $this->tableExists = true;
        return true;
    }

    /**
     * A few notes on the SQLite type system (full docs at https://www.sqlite.org/datatype3.html), for the unwary:
     * - any column can hold any type!
     * - type juggling is in effect, but type-conversion rules are not the same as in php. Notably, expressions have no type!
     * - there is no 'bool' or 'date' column/data type. Columns defined as such get a 'numeric' preferential type (aka 'affinity')
     * - the length limit on varchar columns is ignored
     * @throws \DomainException in case of unsupported field constraint
     * @throws \PDOException
     * @todo should we disallow '' as value for all non-null string fields (or via a custom constraint)? Sqlite f.e.
     *       supports `CHECK()`, or we could use the cross-database attribute PDO::NULL_EMPTY_STRING
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
                            $query .= 'PRIMARY KEY ';
                        }
                        break;
                    case FC::Autoincrement:
                        if ($cv) {
                            $query .=  'AUTOINCREMENT ';
                        }
                        break;
                    case FC::NotNull:
                        if ($cv) {
                            $query .=  'NOT NULL ';
                        }
                        break;
                    case FC::Unique:
                        if ($cv) {
                            $query .=  'UNIQUE ';
                        }
                        break;
                    case FC::Default:
                        if ($cv !== null) {
                            $query .=  'DEFAULT ' . $cv . ' ';
                        }
                        break;
                    default:
                        throw new \DomainException("Unsupported Field constraint '$cn'");
                }
            }
            $query = substr($query, 0, -1) . ', ';
        }

        /// @todo figure out how to enforce the fact that the referenced table has been already created
        foreach ($this->foreignKeys as $fk) {
            $query .= 'FOREIGN KEY (' . implode(', ', $fk->columns). ') REFERENCES ' . $fk->parentTable. '(' .
                implode(', ', $fk->parentColumns). ') ON DELETE ' . $fk->onDelete->value . ' ON UPDATE ' .
                $fk->onUpdate->value . ', ';
        }

        $query = substr($query, 0, -2) . ')';

        self::$dbh->exec($query);

        foreach ($this->indexes as $name => $idx) {
            $query = 'CREATE ' . ($idx->unique ? 'UNIQUE ' : '') . 'INDEX ' . $name . ' ON ' . $this->tableName . '(' .
                implode(', ', $idx->columns) . ')';
            self::$dbh->exec($query);
        }
    }
}
