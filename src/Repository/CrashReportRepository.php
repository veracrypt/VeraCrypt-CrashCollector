<?php

namespace Veracrypt\CrashCollector\Repository;

use DateTimeInterface;
use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;
use Veracrypt\CrashCollector\Storage\Database\Index;

class CrashReportRepository extends DatabaseRepository
{
    protected string $tableName = 'crash_report';

    /**
     * @throws \DomainException in case of unsupported database type
     * @trows \PDOException
     */
    public function __construct()
    {
        /// @todo add a 'hash' column as PK instead of the ID? If so, it could/should probably include the source IP too...
        $this->fields = [
            'id' => new Field('id', 'integer', [FC::NotNull => true, FC::PK => true, FC::Autoincrement => true]),
            'date_reported' => new Field('dateReported', 'integer', [FC::NotNull => true]),
            'program_version' => new Field('programVersion', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'os_version' => new Field('osVersion', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'hw_architecture' => new Field('hwArchitecture', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'executable_checksum' => new Field('executableChecksum', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'error_category' => new Field('errorCategory', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'error_address' => new Field('errorAddress', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'call_stack' => new Field('callStack', 'blob', [FC::NotNull => true]),
        ];
        /// @todo should we just add a covering index which uses all columns? If so, figure out first the cardinality of each
        $this->indexes = [
            'idx_' . $this->tableName . '_dr' => new Index(['date_reported']),
            'idx_' . $this->tableName . '_pm' => new Index(['program_version']),
            'idx_' . $this->tableName . '_ov' => new Index(['os_version']),
            'idx_' . $this->tableName . '_ha' => new Index(['hw_architecture']),
            'idx_' . $this->tableName . '_es' => new Index(['executable_checksum']),
            'idx_' . $this->tableName . '_ec' => new Index(['error_category']),
            'idx_' . $this->tableName . '_ea' => new Index(['error_address']),
            'idx_' . $this->tableName . '_cs' => new Index(['call_stack']),
        ];
        parent::__construct();
    }

    /**
     * Note: this does not validate the length of the fields, nor truncate them. The length validation is left to the Form
     * @throws \PDOException
     */
    public function createReport(string $programVersion, string $osVersion, string $hwArchitecture, string $executableChecksum,
        string $errorCategory, string $errorAddress, string $callStack): CrashReport
    {
        $dateReported = time();
        $cr = new CrashReport(null, $dateReported, $programVersion, $osVersion, $hwArchitecture, $executableChecksum, $errorCategory,
            $errorAddress, $callStack);
        $autoincrements = $this->storeEntity($cr);
        // we have to create a new entity object in order to inject the id into it
        return new CrashReport($autoincrements['id'], $dateReported, $programVersion, $osVersion, $hwArchitecture,
            $executableChecksum, $errorCategory, $errorAddress, $callStack);
    }

    /**
     * @throws \PDOException
     */
    public function fetchReport(int $id): CrashReport|null
    {
        $query = $this->buildFetchEntityQuery() . ' where id = :id';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? new CrashReport(...$result) : null;
    }

    /**
     * @throws \PDOException
     */
    public function deleteReport(int $id): bool
    {
        $query = 'delete from ' . $this->tableName . ' where id = :id';
        $stmt = self::$dbh->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $deleted = (bool)$stmt->rowCount();
        //if ($deleted) {
        //    $this->logger->debug("Report '$id' was deleted");
        //}
        return $deleted;
    }

    /**
     * @return CrashReport[]
     * @throws \PDOException
     */
    public function searchReports(int $limit, int $offset = 0, ?string $programVersion = null, ?string $osVersion = null,
        ?string $hwArchitecture = null, ?string $executableChecksum = null, ?string $errorCategory = null, ?string $errorAddress = null,
        null|int|DateTimeInterface $minDate = null, null|int|DateTimeInterface $maxDate = null): array
    {
        $query = $this->buildFetchEntityQuery();
        $whereConditions = $this->addConditionsToSearchQuery($query, $programVersion, $osVersion, $hwArchitecture, $executableChecksum,
            $errorCategory, $errorAddress, $minDate, $maxDate);
        $query .= ' order by date_reported desc limit :limit offset :offset';
        $stmt = self::$dbh->prepare($query);
        if ($whereConditions) {
            foreach ($whereConditions as $i => $def) {
                $stmt->bindValue(":$i", $def[2]);
            }
        }
        $stmt->bindValue(':limit', $limit);
        $stmt->bindValue(':offset', $offset);

        $stmt->execute();
        // sadly there seems to be no better solution that this, at least in php8.1, to create objects out of a
        // result-set, as long as we want the constructor to be meaningfully enforcing...
        // see https://github.com/php/php-src/issues/13174
        $results = $stmt->fetchAll(\PDO::FETCH_NUM);
        return array_map(static fn($result) => new CrashReport(...$result), $results);
    }

    /**
     * @throws \PDOException
     */
    public function countReports(?string $programVersion = null, ?string $osVersion = null, ?string $hwArchitecture = null,
        ?string $executableChecksum = null, ?string $errorCategory = null, ?string $errorAddress = null, null|int|DateTimeInterface $minDate = null,
        null|int|DateTimeInterface $maxDate = null): int
    {
        $query = 'select count(*) from ' . $this->tableName;

        $whereConditions = $this->addConditionsToSearchQuery($query, $programVersion, $osVersion, $hwArchitecture,
            $executableChecksum, $errorCategory, $errorAddress, $minDate, $maxDate);

        $stmt = self::$dbh->prepare($query);
        if ($whereConditions) {
            foreach ($whereConditions as $i => $def) {
                $stmt->bindValue(":$i", $def[2]);
            }
        }

        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row[0];
    }

    protected function addConditionsToSearchQuery(string &$query, ?string $programVersion = null, ?string $osVersion = null,
        ?string $hwArchitecture = null, ?string $executableChecksum = null, ?string $errorCategory = null, ?string $errorAddress = null,
        null|int|DateTimeInterface $minDate = null, null|int|DateTimeInterface $maxDate = null): array
    {
        $whereConditions = [];
        if (trim($programVersion) !== '') {
            $whereConditions[] = ['program_version', 'like', trim($programVersion)];
        }
        if (trim($osVersion) !== '') {
            $whereConditions[] = ['os_version', 'like', trim($osVersion)];
        }
        if (trim($hwArchitecture) !== '') {
            $whereConditions[] = ['hw_architecture', 'like', trim($hwArchitecture)];
        }
        if (trim($executableChecksum) !== '') {
            $whereConditions[] = ['executable_checksum', 'like', trim($executableChecksum)];
        }
        if (trim($errorCategory) !== '') {
            $whereConditions[] = ['error_category', 'like', trim($errorCategory)];
        }
        if (trim($errorAddress) !== '') {
            $whereConditions[] = ['error_address', 'like', trim($errorAddress)];
        }
        if ($minDate !== null) {
            $whereConditions[] = ['date_reported', '>=', $minDate instanceof DateTimeInterface ? $minDate->getTimestamp() : $minDate];
        }
        if ($maxDate !== null) {
            $whereConditions[] = ['date_reported', '<=', $maxDate instanceof DateTimeInterface ? $maxDate->getTimestamp() : $maxDate];
        }
        if ($whereConditions) {
            $sqlConditions = [];
            foreach ($whereConditions as $i => $def) {
                $sqlConditions[] = "{$def[0]} {$def[1]} :$i";
            }
            $query .= ' where ' . implode(' and ', $sqlConditions);
        }

        return $whereConditions;
    }
}
