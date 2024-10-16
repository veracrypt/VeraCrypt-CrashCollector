<?php

namespace Veracrypt\CrashCollector\Form;

/**
 * @todo we could implement custom validation checks in handleRequest
 */
class CrashReportSearchForm extends CrashReportBaseForm
{
    protected string $submitLabel = 'Search';
    protected int $submitOn = self::ON_GET;

    public function __construct(string $actionUrl)
    {
        parent::__construct($actionUrl);
        $this->fields['minDate'] = new Field\DateTime('After', 'da');
        $this->fields['maxDate'] = new Field\DateTime('Before', 'db');
    }
}
