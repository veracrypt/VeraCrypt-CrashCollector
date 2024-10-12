<?php

namespace Veracrypt\CrashCollector\Form;

// NB: we could use an enum for this, but using its cases as array keys and within switch statements is too verbose
final class FieldConstraint
{
    const Required = 1;
    const MaxLength = 2;
}