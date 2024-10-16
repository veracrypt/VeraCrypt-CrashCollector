<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Form\Form as BaseForm;

abstract class CrashReportBaseForm extends BaseForm
{
    protected bool $requireAllFieldsByDefault = false;

    public function __construct(string $actionUrl)
    {
        $this->fields = [
            /// @todo get the field lengths from the Repo fields
            'programVersion' => new Field\Text('Program version', 'pv', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'osVersion' => new Field\Text('OS version', 'ov', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'hwArchitecture' => new Field\Text('Architecture', 'ha', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'executableChecksum' => new Field\Text('Executable checksum', 'ck', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'errorCategory' => new Field\Text('Error category', 'ec', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'errorAddress' => new Field\Text('Error address', 'ea', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
        ];

        parent::__construct($actionUrl);
    }
}
