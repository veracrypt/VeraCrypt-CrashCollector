<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Entity\CrashReport;
use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Form\Form as BaseForm;

abstract class CrashReportBaseForm extends BaseForm
{
    protected bool $requireAllFieldsByDefault = false;
    protected bool $isReadOnly = false;

    public function __construct(string $actionUrl, ?CrashReport $report = null)
    {
        parent::__construct($actionUrl);
        $this->fields = $this->getFieldsDefinitions($actionUrl, $report);
    }

    protected function getFieldsDefinitions(string $actionUrl, ?CrashReport $report = null): array
    {
        return [
            /// @todo get the field lengths from the Repo fields
            'programVersion' => new Field\Text('Program version', 'pv', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255], $report?->programVersion , $this->isReadOnly),
            'osVersion' => new Field\Text('OS version', 'ov', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255], $report?->osVersion, $this->isReadOnly),
            'hwArchitecture' => new Field\Text('Architecture', 'ha', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255], $report?->hwArchitecture, $this->isReadOnly),
            'executableChecksum' => new Field\Text('Executable checksum', 'cksum', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255], $report?->executableChecksum, $this->isReadOnly),
            'errorCategory' => new Field\Text('Error category', 'err', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255], $report?->errorCategory, $this->isReadOnly),
            'errorAddress' => new Field\Text('Error address', 'addr', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255], $report?->errorAddress, $this->isReadOnly),
        ];
    }
}
