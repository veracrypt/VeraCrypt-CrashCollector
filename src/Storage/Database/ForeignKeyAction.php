<?php

namespace Veracrypt\CrashCollector\Storage\Database;

enum ForeignKeyAction: string
{
    case Cascade = 'CASCADE';
    case NoAction = 'NO ACTION';
    case restrict = 'RESTRICT';
    case SetDefault = 'SET DEFAULT';
    case SetNull = 'SET NULL';
}
