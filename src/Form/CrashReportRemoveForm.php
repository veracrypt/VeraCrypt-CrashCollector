<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Repository\ReportTokenRepository;

/**
 * Note: this form is used atm only for display and rate-limiting purposes
 */
class CrashReportRemoveForm extends CrashReportConfirmForm
{
    protected int $submitOn = self::ON_POST;
    protected string $submitLabel = 'Delete';

    protected function getFieldsDefinitions(string $actionUrl, ?CrashReport $report = null, ?int $tokenId = null, #[\SensitiveParameter] ?string $secret = null): array
    {
        return array_merge(
            [
                'id' => new Field\Text('Report Id', 'id', [FC::Required => $this->requireAllFieldsByDefault], $report?->id, $this->isReadOnly),
                'reported' => new Field\Text('Date', 'dt', [FC::Required => $this->requireAllFieldsByDefault], $report ? date('Y-m-d H:i:s', $report->dateReported) : null, $this->isReadOnly),
            ],
            CrashReportBaseForm::getFieldsDefinitions($actionUrl, $report),
            [
                'callStack' => new Field\TextArea('Call stack', 'cs', [FC::Required => $this->requireAllFieldsByDefault], $report?->callStack, $this->isReadOnly),
            ],
            parent::getFieldsDefinitions($actionUrl, $report, $tokenId, $secret),
        );
    }

    public function isSubmitted(?array $request = null): bool
    {
        return CrashReportBaseForm::isSubmitted($request);
    }

    public function getTokenRepository(): ReportTokenRepository
    {
        return $this->reportConstraint->getTokenRepository();
    }
}
