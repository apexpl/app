<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Account;

use Apex\Svc\Db;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\AccountsStore;
use Apex\App\Network\Models\LocalAccount;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;

/**
 * Update account
 */
class Update implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $helper;

    #[Inject(AccountsStore::class)]
    private AccountsStore $store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(Db::class)]
    private Db $db;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {
        $this->db->query("UPDATE armor_users SET phone = ''");

        // Get account
        $alias = $args[0] ?? '';
        if ($alias == '' || !$acct = $this->store->get($alias)) { 
            $acct = $this->helper->get();
        }

        // Get account info
        $this->network->setAuth($acct);
        $info = $this->network->post($acct->getRepo(), 'users/get', []);

        // Display profile
        $this->helper->display($info);

        // Set update options
        $options = [
            'name' => 'Full Name',
            'email' => 'E-Mail Address',
            'phone' => 'Phone Number'
        ];

        // Get option
        $opt = $cli->getOption("Select which profile field you would like to update:", $options, '', true);
            $this->$opt($acct, $cli);
    }

    /**
    * Full name
     */
    private function name(LocalAccount $acct, Cli $cli):void
    {

        // Get new name
        $cli->send("Enter the first and last name to update the account with.\r\n\r\n");
        $first_name = $cli->getInput('First Name: ');
        $last_name = $cli->getInput('Last name: ');

        // Send request
        $res = $this->network->post($acct->getRepo(), 'users/update', [
            'field' => 'name', 
            'first_name' => $first_name,
            'last_name' => $last_name
        ]);

        // Success
        $cli->send("\r\nSuccessfully updated account name to $first_name $last_name.\r\n\r\n");
    }

    /**
     * Update e-mail
     */
    private function email(LocalAccount $acct, Cli $cli):void
    {

        // Send header
        $cli->sendHeader('Update E-Mail');
        $cli->send("\r\nEnter the e-mail address to update the account with:\r\n\r\n");

        // Get new e-mail
        do { 
            $email = $cli->getInput('E-Mail Address: ');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
                $cli->send("\r\nInvalid e-mail address, please try again.\r\n\r\n");
                continue;
            }
            break;
        } while (true);

        // Send request
        $res = $this->network->post($acct->getRepo(), 'users/update', [
            'field' => 'email', 
            'email' => strtolower($email)
        ]);

        // Confirm the e-mail address
        $cli->send("\r\n");
        $cli->send("A one-time verification code has been sent via e-mail to $email.  Please check your e-mail, and enter the code below.\r\n\r\n");

        // Get one-time code
        do { 
            $code = $cli->getInput("Verification Code: ");
            $res = $this->network->post($acct->getRepo(), 'users/confirm', [
                'field' => 'email',
                'code' => $code
            ]);

            if ($res['verified'] !== true) { 
                $cli->send("Invalid verification code, please try again.\r\n\r\n");
                continue;
            }
            break;
        } while (true);
        // Send message
        $cli->send("\r\nSuccessfully updated your e-mail address to $email\r\n\r\n");        // 
    }

    /**
     * Update phone
     */
    private function phone(LocalAccount $acct, Cli $cli):void
    {

        // Send header
        $cli->sendHeader('Update Phone');
        $cli->send("\r\nEnter the phone number with country code to update the account with:\r\n\r\n");

        // Get new phone
        do { 
            $phone = $cli->getInput('Phone Number: ');
            $phone = preg_replace("/[\s\W]/", "", $phone);

            // Validate
            try {
                $number = PhoneNumber::parse('+' . $phone);
            } catch(PhoneNumberParseException $e) { 
                $cli->send("\r\nInvalid phone number, please try again.\r\n\r\n");
                continue;
            }
            break;
        } while (true);

        // Send request
        $res = $this->network->post($acct->getRepo(), 'users/update', [
            'field' => 'phone', 
            'phone' => $phone
        ]);
        $phone_format = $number->formatForCallingFrom($phone);

        // Confirm the phone number
        $cli->send("\r\n");
        $cli->send("A one-time verification code has been sent via SMS to $phone_format.  Please check your messages, and enter the code below.\r\n\r\n");

        // Get one-time code
        do { 
            $code = $cli->getInput("Verification Code: ");
            $res = $this->network->post($acct->getRepo(), 'users/confirm', [
                'field' => 'phone',
                'code' => $code
            ]);

            if ($res['verified'] !== true) { 
                $cli->send("Invalid verification code, please try again.\r\n\r\n");
                continue;
            }
            break;
        } while (true);
        // Send message
        $cli->send("\r\nSuccessfully updated your phone number to $phone_format\r\n\r\n");        // 
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Define help
        $help = new CliHelpScreen(
            title: 'Update Account Profile',
            usage: 'account update [<ALIAS>]',
            description: 'Update profile information on your Apex account, such as name, e-mail address and phone number.',
            params: [
                'alias' => 'Optional account alias formatted as USERNAME.REPO of account to update.  If unspecified, list of all locally stored accounts will be shown to choose from.'
            ]
        );

        // Return
        return $help;
    }


}


