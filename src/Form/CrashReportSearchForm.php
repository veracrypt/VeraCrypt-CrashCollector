<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;

class CrashReportSearchForm extends CrashReportBaseForm
{
    protected string $submitLabel = 'Search';
    protected int $submitOn = self::ON_GET;

    public function __construct(string $actionUrl)
    {
        parent::__construct($actionUrl);
        $this->fields['minDate'] = new Field('After', 'da', 'datetime');
        $this->fields['maxDate'] = new Field('Before', 'db', 'datetime');
    }
}
