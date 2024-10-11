<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;

class CrashReportSubmitForm extends CrashReportBaseForm
{
    protected bool $requireAllFieldsByDefault = true;

    public function __construct()
    {
        parent::__construct();
        $this->fields['callStack'] = new Field('Call stack', 'cs', 'textarea', [FC::Required => $this->requireAllFieldsByDefault]);
    }
}
