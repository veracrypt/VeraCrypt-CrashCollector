<?php

namespace Veracrypt\CrashCollector\Repository\Storage;

trait DatabaseTable
{
    use Database;

    /**
     * To be set in subclasses.
     * @var string
     */
    protected $tableName;

    /**
     * Flag to indicate that the underlying table has been created.
     * @var null|bool $tableExists
     */
    private $tableExists;

    /**
     * Check if the version db table exists and create it if not.
     *
     * @return bool true if table has been created, false if it was already there
     * @throws \DomainException
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
     * @return void
     * @throws \DomainException in case of bad config (unsupported database)
     * @throws \PDOException in case of failure creating the db table
     */
    abstract protected function createTable(): void;
}
