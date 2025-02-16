<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Entity\ManageReportToken;

/**
 * @method null|ManageReportToken fetch(string $hash)
 */
class ManageReportTokenRepository extends ReportTokenRepository
{
    protected string $tableName = 'token_managereport';
    protected string $entityClass = ManageReportToken::class;

    public function __construct()
    {
        parent::__construct(64);
    }

    /**
     * The tokens are valid for 1 hour by default
     */
    protected function newTokenExpirationDate(): null|int
    {
        return time() + 3600;
    }
}
