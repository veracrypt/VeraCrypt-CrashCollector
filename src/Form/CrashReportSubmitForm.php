<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\EnvVarProcessor;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\RateLimiter\Constraint\FixedWindow;

class CrashReportSubmitForm extends CrashReportBaseForm
{
    protected bool $requireAllFieldsByDefault = true;

    public function __construct(string $actionUrl, ?CrashReport $report = null)
    {
        parent::__construct($actionUrl, $report);

        if (EnvVarProcessor::bool($_ENV['ENABLE_BROWSER_UPLOAD'])) {
            $this->submitOn = self::ON_POST;
        } else {
            $this->submitOn = self::ON_GET;
        }
    }

    public function isSubmitted(?array $request = null): bool
    {
        if (EnvVarProcessor::bool($_ENV['ENABLE_BROWSER_UPLOAD'])) {
            return parent::isSubmitted($request);
        }

        // We consider the form always submitted
        /// @todo Should we consider it submitted only if all or some of the fields are present?
        return true;
    }

    protected function getFieldsDefinitions(string $actionUrl, ?CrashReport $report = null): array
    {
        return array_merge(
            parent::getFieldsDefinitions($actionUrl, $report),
            [
                'callStack' => new Field\TextArea('Call stack', 'st', [FC::Required => $this->requireAllFieldsByDefault], null, $this->isReadOnly),
                'rateLimit' => new Field\RateLimiter([
                    new FixedWindow($actionUrl, 1, 30), // equivalent to once every 30 secs
                    new FixedWindow($actionUrl, 12, 3600), // equivalent to once every 5 minutes
                    new FixedWindow($actionUrl, 24, 86400), // equivalent to once every 30 minutes
                ]),
            ]
        );
    }
}
