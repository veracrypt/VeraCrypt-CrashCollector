<?php

namespace Veracrypt\CrashCollector\Storage\Database;

class ForeignKey
{
    /**
     * @param string[] $columns
     * @param string[] $parentColumns
     */
    public function __construct(
        public readonly array $columns,
        public readonly string $parentTable,
        public readonly array $parentColumns,
        public readonly ForeignKeyAction $onDelete = ForeignKeyAction::NoAction,
        public readonly ForeignKeyAction $onUpdate = ForeignKeyAction::NoAction,
    )
    {}
}
