<?php

namespace Veracrypt\CrashCollector\Form;

// NB: we could use an enum for this, but using its cases as array keys and within switch statements is too verbose
final class FieldConstraint
{
    const Required = 1;
    const MaxLength = 2;
    const MinLength = 4;
    const RateLimit = 8;
    const Custom = 16;
}
