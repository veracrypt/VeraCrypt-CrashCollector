<?php

namespace Veracrypt\CrashCollector\Repository;

use DateTimeInterface;
use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;

class CrashReportRepository extends Repository
{
    protected $tableName = 'crash_report';

    public function __construct()
    {
        /// @todo add a 'hash' column as PK instead of the ID? If so, it could/should probably include the source IP too...
        $this->fields = [
            'id' => new Field(null, 'integer', [FC::NotNull => true, FC::PK => true, FC::Autoincrement => true]),
            'date_reported' => new Field('dateReported', 'integer', [FC::NotNull => true]),
            'program_version' => new Field('programVersion', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'os_version' => new Field('osVersion', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'hw_architecture' => new Field('hwArchitecture', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'executable_checksum' => new Field('executableChecksum', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'error_category' => new Field('errorCategory', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'error_address' => new Field('errorAddress', 'varchar', [FC::Length => 255, FC::NotNull => true]),
            'call_stack' => new Field('callStack', 'blob', [FC::NotNull => true]),
        ];

        parent::__construct();
    }

    /**
     * Note: this does not validate the length of the fields, nor truncate them. The length validation is left to the Form
     */
    public function createReport(string $programVersion, string $osVersion, string $hwArchitecture, string $executableChecksum,
        string $errorCategory, string $errorAddress, string $callStack): CrashReport
    {
        $dateReported = time();
        $cr = new CrashReport($dateReported, $programVersion, $osVersion, $hwArchitecture, $executableChecksum, $errorCategory,
            $errorAddress, $callStack);
        $this->storeEntity($cr);
        return $cr;
    }

    /**
     * @return CrashReport[]
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
