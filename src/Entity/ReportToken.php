<?php

namespace Veracrypt\CrashCollector\Entity;

use DateTimeInterface;
use Veracrypt\CrashCollector\Repository\CrashReportRepository;

class ReportToken extends Token
{
    public function __construct(
        ?int $id,
        string $hash,
        public readonly int $reportId,
        int|DateTimeInterface $dateCreated,
        null|int|DateTimeInterface $expirationDate
    )
    {
        parent::__construct($id, $hash, $dateCreated, $expirationDate);
    }

    public function getReport(): null|CrashReport
    {
        $repo = new CrashReportRepository();
        return $repo->fetchReport($this->reportId);
    }
}
