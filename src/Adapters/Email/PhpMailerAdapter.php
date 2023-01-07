<?php
declare(strict_types = 1);

namespace Apex\App\Adapters\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Apex\Mercury\Email\EmailMessage;
use Apex\Mercury\Interfaces\EmailerInterface;
use Apex\App\Adapters\Email\AbstractAdapter;
use Apex\App\Exceptions\ApexEmailerException;

/**
 * PHP Mailer Adapter
 */
class PhpMailerAdapter extends AbstractAdapter implements EmailerInterface
{


    /**
     * Send e-mail message
 */
    public function send(EmailMessage $msg, bool $is_persistent = false):bool
    {

        $mailer = new PhpMailer(true);

        // Set SMTP info, if we have it.
        if ($smtp = $this->getSmtpVars()) {
            $mailer->isSMTP();
            $mailer->Host = $smtp['host'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $smtp['user'];
            $mailer->Password = $smtp['password'];
            $mailer->Port = $smtp['port'];
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        // Set sender
        $mailer->setFrom($msg->getFromEmail(), $msg->getFromName());
        $mailer->addAddress($msg->getToEmail(), $msg->getToName());
        if ($msg->getReplyTo() != '') {
            $mailer->addReplyTo($msg->getReplyTo());
        }

        // Add CC addresses
        foreach ($msg->getCc() as $email) {
            $mailer->addCC($email);
        }

        // Add Bcc addresses
        foreach ($msg->getBCC() as $email) {
            $mailer->addBCC($email);
        }

        // Add attachments
        $tmp_files = [];
        foreach ($msg->getAttachments() as $filename => $contents) {
            $tmp_file = sys_get_temp_dir() . '/apex-' . uniqid();
            file_put_contents($tmp_file, $contents);
            $tmp_files[] = $tmp_file;
            $mailer->addAttachment($tmp_file, $filename);
        }

        // Set message contents
        if ($msg->getHtmlContents() != '') {
            $mailer->isHTML(true);
            $mailer->Body = $msg->getHtmlMessage();
            $mailer->AltBody = $msg->getTextMessage();
        } else {
            $mailer->Body = $msg->getTextMessage();
        }
        $mailer->Subject = $msg->getSubject();

        // Send the message
        try {
            $mailer->send();
        } catch (\Exception $e) {
            throw new ApexEmailerException("Unable to send the e-mail message via SMTP $smtp[host] to " . $msg->getToEmail() . ", error received: " . $e->getMessage());
        }

        // Delete tmp files
        foreach ($tmp_files as $file) {
            unlink($file);
        }

        // Return
        return true;
    }

}


