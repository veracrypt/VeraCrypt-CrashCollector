<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Form\Field as Basefield;

/**
 * @todo should this field _avoid_ trimming leading/trailing whitespace?
 */
class Password extends Basefield
{
    public function __construct(string $label, string $inputName, array $constraints = [], ?string $value = null, bool $isReadOnly = false)
    {
        parent::__construct('password', $label, $inputName, $constraints, $value, true, $isReadOnly);
    }
}
