<?php

namespace Veracrypt\CrashCollector\Mailer;

/**
 * A bare-bones implementation of a mailing system, used to isolate the application from the details of the actual mailing
 * transport layer in use. API inspired by the Smfony mailer component.
 */
class Mailer
{
    /**
     * @throws \RuntimeException
     */
    public function send(Email $message): void
    {
        $additionalHeaders = [
            'From' => $message->getFrom(),
        ];
        // replace single \n chars with \r\n
        $text = preg_replace('/(^|[^\\r])\\n/', "\\1\r\n", $message->getText());
        if (!mail($message->getTo(), $message->getSubject(), $text, $additionalHeaders)) {
            throw new \RuntimeException("Mail delivery failed");
        }
    }
}
