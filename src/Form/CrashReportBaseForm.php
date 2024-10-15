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
            'programVersion' => new Field('Program version', 'pv', 'text', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'osVersion' => new Field('OS version', 'ov', 'text', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'hwArchitecture' => new Field('Architecture', 'ha', 'text', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'executableChecksum' => new Field('Executable checksum', 'ck', 'text', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'errorCategory' => new Field('Error category', 'ec', 'text', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
            'errorAddress' => new Field('Error address', 'ea', 'text', [FC::Required => $this->requireAllFieldsByDefault, FC::MaxLength => 255]),
        ];

        parent::__construct($actionUrl);
    }
}
