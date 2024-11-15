<?php

namespace Veracrypt\CrashCollector\Entity;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * @property-read int $dateReported
 * @property-read DateTimeImmutable $dateReportedDT
 */
class CrashReport
{
    // we store the timestamp as property instead of the datetime in order to be able to use PDO automatic hydration to object
    private int $dateReported;

    public function __construct(
        public readonly ?int $id,
        int|DateTimeInterface $dateReported,
        public readonly string $programVersion,
        public readonly string $osVersion,
        public readonly string $hwArchitecture,
        public readonly string $executableChecksum,
        public readonly string $errorCategory,
        public readonly string $errorAddress,
        public readonly string $callStack
    ) {
        if (is_int($dateReported)) {
            $this->dateReported = $dateReported;
        } else {
            $this->dateReported = $dateReported->getTimestamp();
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'dateReported':
                return $this->dateReported;
            case 'dateReportedDT':
                return new DateTimeImmutable("@{$this->dateReported}");
            default:
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                trigger_error('Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' .
                    $trace[0]['line'], E_USER_ERROR);
        }
    }

    public function __isset($name)
    {
        return match ($name) {
            'dateReported','dateReportedDT' => true,
            default => false
        };
    }
}
