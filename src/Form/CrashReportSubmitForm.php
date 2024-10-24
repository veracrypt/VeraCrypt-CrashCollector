<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\RateLimiter\Constraint\FixedWindow;

class CrashReportSubmitForm extends CrashReportBaseForm
{
    protected bool $requireAllFieldsByDefault = true;

    public function __construct(string $actionUrl)
    {
        parent::__construct($actionUrl);
        $this->fields['callStack'] = new Field\TextArea('Call stack', 'cs', [FC::Required => $this->requireAllFieldsByDefault]);
        $this->fields['rateLimit']  = new Field\RateLimiter([
            new FixedWindow($actionUrl, 1, 30), // equivalent to once every 30 secs
            new FixedWindow($actionUrl, 12, 3600), // equivalent to once every 5 minutes
            new FixedWindow($actionUrl, 24, 86400), // equivalent to once every 30 minutes
        ]);
    }
}
