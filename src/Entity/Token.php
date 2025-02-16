<?php

namespace Veracrypt\CrashCollector\Entity;

use DateTimeImmutable;
use DateTimeInterface;

abstract class Token
{
    private int $dateCreated;
    private ?int $expirationDate;

    public function __construct(
        public readonly ?int $id,
        public readonly string $hash,
        int|DateTimeInterface $dateCreated,
        null|int|DateTimeInterface $expirationDate,
    ) {
        if (is_int($dateCreated)) {
            $this->dateCreated = $dateCreated;
        } else {
            $this->dateCreated = $dateCreated->getTimestamp();
        }
        if (null === $expirationDate || is_int($expirationDate)) {
            $this->expirationDate = $expirationDate;
        } else {
            $this->expirationDate = $expirationDate->getTimestamp();
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'dateCreated':
                return $this->dateCreated;
            case 'dateCreatedDT':
                return new DateTimeImmutable("@{$this->dateCreated}");
            case 'expirationDate':
                return $this->expirationDate;
            case 'expirationDateDT':
                return $this->expirationDate === null ? null : new DateTimeImmutable("@{$this->expirationDate}");
            default:
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                trigger_error('Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' .
                    $trace[0]['line'], E_USER_ERROR);
        }
    }

    public function __isset($name)
    {
        switch ($name) {
            case 'dateCreated':
            case 'dateCreatedDT':
                return true;
            case 'expirationDate':
            case 'expirationDateDT':
                return isset($this->expirationDate);
            default:
                return false;
        }
    }
}
