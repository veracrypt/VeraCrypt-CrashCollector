<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;

class CrashReportSubmitForm extends CrashReportBaseForm
{
    protected bool $requireAllFieldsByDefault = true;

    public function __construct(string $actionUrl)
    {
        parent::__construct($actionUrl);
        $this->fields['callStack'] = new Field\TextArea('Call stack', 'cs', [FC::Required => $this->requireAllFieldsByDefault]);
    }
}
