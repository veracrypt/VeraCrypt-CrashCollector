<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\RateLimiter\Constraint\FixedWindow;

class CrashReportSubmitForm extends CrashReportBaseForm
{
    protected bool $requireAllFieldsByDefault = true;

    protected function getFieldsDefinitions(string $actionUrl, ?CrashReport $report = null): array
    {
        return array_merge(
            parent::getFieldsDefinitions($actionUrl, $report),
            [
                'callStack' => new Field\TextArea('Call stack', 'cs', [FC::Required => $this->requireAllFieldsByDefault], null, $this->isReadOnly),
                'rateLimit' => new Field\RateLimiter([
                    new FixedWindow($actionUrl, 1, 30), // equivalent to once every 30 secs
                    new FixedWindow($actionUrl, 12, 3600), // equivalent to once every 5 minutes
                    new FixedWindow($actionUrl, 24, 86400), // equivalent to once every 30 minutes
                ]),
            ]
        );
    }
}
