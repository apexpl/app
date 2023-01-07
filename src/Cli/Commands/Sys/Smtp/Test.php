<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Sys\Smtp;

use Apex\Svc\{Container, Db, Emailer};
use Apex\App\Cli\{Cli, CliHelpscreen};
use Apex\Mercury\Email\EmailMessage;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Test SMTP server
 */
class Test implements CliCommandInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Emailer::class)]
    private Emailer $emailer;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $smtp_alias = $args[0] ?? '';
        $email = $args[1] ?? '';

        // Perform checks
        if ($smtp_alias == '') {
            $cli->error("No SMTP alias defined");
            return;
        } elseif ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $cli->error("Invalid e-mail address, $email");
            return;
        }

        // Get admin
        if (!$admin = $this->db->getRow("SELECT * FROM armor_users WHERE type = 'admin' ORDER BY created_at LIMIT 1")) {
            $cli->error("You do not have any admistrators created on this system.  Please create an administrator first, and try again.");
            return;
        }

        // Create e-mail message
        $message = $this->cntr->make(EmailMessage::class, [
            'to_email' => $email,
            'from_email' => $admin['email'],
            'from_name' => 'Apex Test',
            'subject' => 'Apex Test Message',
            'text_message' => "Hi there,\n\nThis is a test message from Apex.  If you receive this message, congrats, your SMTP service is properly configured within Apex!\n\n",
            'html_message' => "<p>Hi there,<br /><br />This is a test message from Apex.  If you receive this message, congrats, your SMTP service is properly configured within Apex!<br /></p>\n"
        ]);

        // Send e-mail
        $this->emailer->send($message);

        // User message
        $cli->send("Successfully sent test e-mail message via the $smtp_alias server to $email\n\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Get help screen
        $help = new CliHelpScreen(
            title: 'Test SMTP Connection',
            usage: 'sys smtp test <SMTP_ALIAS> <EMAIL_ADDRESS>',
        );

        // Params
        $help->addParam('smtp_alias', 'The alias of the SMTP server to test.');
        $help->addParam('EMAIL_ADDRESS', 'The e-mail address to send a test message to.');
        $help->addExample('apex sys smtp test gmail me@mydomain.com');

        // Return
        return $help;
    }

}


