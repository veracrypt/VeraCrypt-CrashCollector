<?php

namespace Veracrypt\CrashCollector\Repository;

// NB: we could use an enum for this, but using its cases as array keys and within switch statements is too verbose
final class FieldConstraint
{
    const Length = 1;
    const NotNull = 2;
    const Unique = 4;
    const PK = 8;
    const Autoincrement = 16;
    const Default = 32;
}
