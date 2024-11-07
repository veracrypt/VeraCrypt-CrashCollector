<?php

namespace Veracrypt\CrashCollector\Mailer;

class Email
{
    protected string $from;
    protected string $to;
    protected string $subject;
    protected string $text;

    public function from(string $from): Email
    {
        $this->from = $from;
        return $this;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function to(string $to): Email
    {
        $this->to = $to;
        return $this;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function subject(string $subject): Email
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function text(string $text): Email
    {
        $this->text = $text;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
