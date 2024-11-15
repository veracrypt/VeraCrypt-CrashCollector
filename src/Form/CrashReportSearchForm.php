<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\CrashReport;

/**
 * @todo we could implement custom validation checks in handleRequest
 */
class CrashReportSearchForm extends CrashReportBaseForm
{
    protected string $submitLabel = 'Search';
    protected int $submitOn = self::ON_GET;

    protected function getFieldsDefinitions(string $actionUrl, ?CrashReport $report = null): array
    {
        return array_merge(
            parent::getFieldsDefinitions($actionUrl, $report),
            [
                'minDate' => new Field\DateTime('After', 'da'),
                'maxDate' => new Field\DateTime('Before', 'db'),
            ]
        );
    }
}
