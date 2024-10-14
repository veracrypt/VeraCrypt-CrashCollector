<?php

namespace Veracrypt\CrashCollector\Storage;

use PDO;

trait Database
{
    protected static ?PDO $dbh = null;
    /** @var string[]|null */
    protected static ?array $tableDefs = null;

    /**
     * @throws \PDOException
     */
    protected function connect(): void
    {
        if (self::$dbh === null) {
            self::$dbh = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);

            self::$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
    }

    /**
     * Check if a table exists in the database
     *
     * @param string $tableName
     * @return bool
     * @throws \DomainException in case of unsupported database type
     * @throws \PDOException
     */
    protected function tableExists(string $tableName): bool
    {
        if (!is_array(self::$tableDefs)) {
            self::$tableDefs = $this->listTables();
        }
        return in_array($tableName, self::$tableDefs);
    }

    /**
     * List all db tables accessible to the current user.
     *
     * @return string[] value has to be the table name
     * @throws \DomainException in case of unsupported database type
     * @throws \PDOException
     */
    protected function listTables(): array
    {
        $dbType =  self::$dbh->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($dbType) {
            case 'sqlite':
                $query = "SELECT name FROM sqlite_schema WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%'";
                break;
            default:
                throw new \DomainException("Database type '$dbType' is not supported");
        }

        return self::$dbh->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}
