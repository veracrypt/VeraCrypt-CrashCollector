<?php

namespace Veracrypt\CrashCollector\Repository;

use Veracrypt\CrashCollector\Entity\ReportToken;
use Veracrypt\CrashCollector\Repository\FieldConstraint as FC;
use Veracrypt\CrashCollector\Storage\Database\ForeignKey;
use Veracrypt\CrashCollector\Storage\Database\ForeignKeyAction;

/**
 * @method null|ReportToken fetch(int $id)
 */
abstract class ReportTokenRepository extends TokenRepository
{
    protected function getFieldsDefinitions(): array
    {
        return array_merge(parent::getFieldsDefinitions(), [
            'report_id' => new Field('reportId', 'integer', [FC::NotNull => true]),
        ]);
    }

    protected function getForeignKeyDefinitions(): array
    {
        return [
            /// @todo make 'crash_report' a static var or class const of CrashReportRepository, so that we can grab it from there
            new ForeignKey(['report_id'], 'crash_report', ['id'], ForeignKeyAction::Cascade, ForeignKeyAction::Cascade),
        ];
    }

    public function createToken(string $reportId, string $hash): ReportToken
    {
        $args['id'] = null;
        $args['hash'] = $hash;
        $args['reportId'] = $reportId;
        $args['dateCreated'] = time();
        $args['expirationDate'] = $this->newTokenExpirationDate();
        $token = new $this->entityClass(...$args);
        $autoincrements = $this->storeEntity($token);
        // we have to create a new entity object in order to inject the id into it
        $args['id'] = $autoincrements['id'];
        return new $this->entityClass(...$args);
    }
}
