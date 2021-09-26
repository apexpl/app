<?php
declare(strict_types = 1);

namespace Apex\App\Adapters\Email;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Apex\Mercury\Email\EmailMessage;
use Apex\Mercury\Interfaces\EmailerInterface;
use Apex\App\Adapters\Email\AbstractAdapter;
use Apex\App\Exceptions\ApexEmailerException;

/**
 * PHP Mailer Adapter
 */
class SymfonyAdapter extends AbstractAdapter implements EmailerInterface
{


    /**
     * Send e-mail message
     */
    public function send(EmailMessage $msg, bool $is_persistent = false):bool
    {

        // Get transport
        // Set SMTP info, if we have it.
        if ($smtp = $this->getSmtpVars()) {
            $dsn = 'smtp://' . $smtp['user'] . ':' . $smtp['password'] . '@' . $smtp['host'] . ':' . $smtp['port'];
            $transport = Transport::fromDsn($dsn);
        } else { 
            $transport = Transport::fromDsn('sendmail');
        }
        $mailer = new Mailer($transport);

        // Set e-mail message
        $email = (new Email())->from($msg->getFromEmail())
            ->to($msg->getToEmail())
            ->subject($msg->getSubject())
            ->text($msg->getMessage())
            ->html($msg->getMessage());

        // Add reply to
        if ($msg->getReplyTo() != '') {
            $email = $email->replyTo($msg->getReplyTo());
        }

        // Add cc
        foreach ($msg->getCc() as $cc) {
            $email = $email->cc($cc);
        }

        // Add bcc
        foreach ($msg->getBcc() as $bcc) {
            $email = $email->bcc($bcc);
        }

        // Send message
        $mailer->send($email);
        return true;
    }

}


