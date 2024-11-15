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
            // this is necessary for ex. for queries using bound params for offset, limit on mariadb...
            self::$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


            $dbType = self::$dbh->getAttribute(PDO::ATTR_DRIVER_NAME);
            switch ($dbType) {
                case 'sqlite':
                    $query = "PRAGMA foreign_keys = ON";
                    self::$dbh->query($query);
                    break;
            }
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
     * @todo decide if we want to include or exclude views and be consistent about it
     */
    protected function listTables(): array
    {
        $dbType =  self::$dbh->getAttribute(PDO::ATTR_DRIVER_NAME);
        // Queries taken from Doctrine DBAL
        $query = match ($dbType) {
            'mysql' => "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'",
            'pgsql' => "SELECT quote_ident(table_name) AS table_name
                    FROM information_schema.tables
                    WHERE table_schema NOT LIKE 'pg\_%'
                    AND table_schema != 'information_schema'
                    AND table_name != 'geometry_columns'
                    AND table_name != 'spatial_ref_sys'
                    AND table_type != 'VIEW'",
            'sqlite' => "SELECT name FROM sqlite_master
                    WHERE type = 'table'
                    AND name != 'sqlite_sequence'
                    AND name != 'geometry_columns'
                    AND name != 'spatial_ref_sys'
                    UNION ALL
                    SELECT name
                    FROM sqlite_temp_master
                    WHERE type = 'table'",
            default => throw new \DomainException("Database type '$dbType' is not supported"),
        };

        return self::$dbh->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}
