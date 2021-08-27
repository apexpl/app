<?php
declare(strict_types = 1);

namespace Apex\App\Adapters\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Apex\Mercury\Email\EmailMessage;
use Apex\Mercury\Interfaces\EmailerInterface;
use redis;

/**
 * PHP Mailer Adapter
 */
class PhpMailerAdapter implements EmailerInterface
{

    #[Inject(redis::class)]
    private redis $redis;


    /**
     * Send e-mail message
 */
    public function send(EmailMessage $msg, bool $is_persistent = false):bool
    {

        $mailer = new PhpMailer(true);


    }

}


