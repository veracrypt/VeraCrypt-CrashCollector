<?php

namespace Veracrypt\CrashCollector\Form;

use Veracrypt\CrashCollector\Form\FieldConstraint as FC;
use Veracrypt\CrashCollector\Security\PasswordHasher;

abstract class PasswordUpdateBaseForm extends Form
{
    public function __construct(string $actionUrl)
    {
        $this->fields = $this->getFieldsDefinitions($actionUrl);
        parent::__construct($actionUrl);
    }

    protected function getFieldsDefinitions(string $actionUrl): array
    {
        return [
            'newPassword' => new Field\Password('New Password', 'np', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
            'newPasswordConfirm' => new Field\Password('Confirm new Password', 'npc', [FC::Required => true, FC::MaxLength => PasswordHasher::MAX_PASSWORD_LENGTH]),
        ];
    }

    protected function validateSubmit(?array $request = null): void
    {
/// @check: do we need the ref assignment?
        /** @var Field $npcField */
        $npcField =& $this->fields['newPasswordConfirm'];
        if ($this->fields['newPassword']->getData() !== $npcField->getData()) {
            $npcField->setError('The password does not match');
            $this->isValid = false;
        }
    }
}
