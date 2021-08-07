<?php
declare(strict_types = 1);

namespace Apex\App\Adapters;

use Apex\Svc\{App, View};
use Apex\Container\Di;
use Apex\Armor\Enums\{EmailMessageType, PhoneMessageType};
use Apex\Db\Interfaces\DbInterface;
use Psr\Http\Message\ServerRequestInterface;
use Apex\Mercury\Email\{Emailer, EmailMessage};
use Apex\Mercury\SMS\Nexmo;
use Apex\Armor\Auth\AuthSession;
use Apex\Armor\User\ArmorUser;
use Apex\Armor\Interfaces\{AdapterInterface, ArmorUserInterface};
use Apex\Armor\Exceptions\{ArmorOutOfBoundsException, ArmorNotImplementedException};

/**
 * Apex adapter that utilizes the apex/mercury and apex/syrus packages.
 */
class ArmorAdapter implements AdapterInterface
{

    #[Inject(App::class)]
    private App $app;

    /**
     * Get user by uuid
     */
    public function getUuid(DbInterface $db, string $uuid, bool $is_deleted = false):?ArmorUserInterface
    {

        // Get table and class name
        if (str_starts_with($uuid, 'a:')) { 
            $table_name = 'admin';
            $class_name = \App\Webapp\Admin::class;
        } else { 
            $table_name = 'user_profiles';
            $class_name = \App\Users\User::class;
        }

        // Get object
        if (!$user = $db->getObject($class_name, "SELECT * FROM $table_name, armor_users WHERE $table_name.uuid = %s AND $table_name.uuid = armor_users.uuid AND armor_users.is_deleted = %b", $uuid, $is_deleted)) {  
            return null;
        }

        // Return
        return $user;
    }


    /**
     * Send e-mail message
     */
    public function sendEmail(ArmorUserInterface $user, string $type, string $armor_code, string $new_email = ''):void
    {
file_put_contents(SITE_PATH . '/t.txt', "SEND E_MAIL $type to " . $user->getEmail() . "\n", FILE_APPEND);
        // Get e-mail message
        $msg = $this->getEmailMessage($type, ['armor_code' => $armor_code]);

        // Set recipient
        if ($new_email != '') { 
            $msg->setToEmail($new_email);
        } else { 
            $msg->setToEmail($user->getEmail());
        }

        // Send message
        $emailer = Di::get(Emailer::class);
        $emailer->send($msg);
    }

    /**
     * Send SMS
     */
    public function sendSMS(ArmorUserInterface $user, string $type, string $code, string $new_phone = ''):void
    {

        // Set message
        $message = $this->app->config('core.sms_verification_message');
        $message = str_replace('~code~', $code, $message);
        $phone = $new_phone != '' ? $new_phone : $user->getPhone();

        // Send SMS
        $nexmo = Di::make(Nexmo::class);
        $mid = $nexmo->send($phone, $message);
    }

    /**
     * Handle session status
     */
    public function handleSessionStatus(AuthSession $session, string $status):void
    {

        // Check if Syrus installed
        if (!class_exists(Syrus::class)) { 
            throw new ArmorNotImplementedException("This method has not been implemented by the adapter, and the Syrus package is not installed on this system.  Please either develop an adapter to work with your template engine, or install the Syrus Template Envrin (https://github.com/apexpl/syrus)");
        }

        // Get template file
        $file = match($status) { 
            'email' => '/members/2fa_email.html', 
            'email_otp' => '/members/2fa_email_otp.html', 
            'phone' => '/members/2fa_phone.html', 
            default => '/members/index'
        };

        // Display template
        $syrus = Di::get(Syrus::class);
        $syrus->setTemplateFile($file);
        //echo $syrus->render(); exit;
    }

    /**
     * Handle authorized two factor request
     */
    public function handleTwoFactorAuthorized(AuthSession $session, ServerRequestInterface $request, bool $is_login = false):void
    {

        // Get POST body from previous request
        $_POST = $request->getParsedBody();

        // Set template
        $syrus = Di::get(Syrus::class);
        $syrus->setTemplateFile('/members/twofactor2.html', true);

    }

    /**
     * Get e-mail message
     */
    private function getEmailMessage(string $type, array $replace = []):EmailMessage
    {

        // Set files
        $files = [
            EmailMessageType::VERIFY => 'verify_email.txt', 
            EmailMessageType::VERIFY_OTP => 'verify_email_otp.txt', 
            EmailMessageType::TWO_FACTOR => 'two_factor_email.txt', 
            EmailMessageType::TWO_FACTOR_OTP => 'two_factor_email_otp.txt', 
            EmailMessageType::RESET_PASSWORD => 'reset_password_email.txt' 
        ];

        // Ensure message exists
        if (!isset($files[$type])) { 
            throw new ArmorOutOfBoundsException("No e-mail message exists with the type, $type");
        }

        $msg = new EmailMessage();
        $msg->importFromFile(SITE_PATH . '/boot/emails/' . $files[$type]);

        // Set variables
        $subject = $msg->getSubject();
        $message = $msg->getMessage();

        // Add domain to replace
        if (!isset($replace['domain'])) { 
            $cookie = Di::get('armor.cookie') ?? [];
            $replace['domain_name'] = $cookie['domain'] ?? '127.0.0.1';
        }

        // Replace fields
        foreach ($replace as $key => $value) { 
            $subject = str_replace("~$key~", $value, $subject);
            $message = str_replace("~$key~", $value, $message);
        }

        // Set subject / message
        $msg->setSubject($subject);
        $msg->setMessage($message);

        // Return
        return $msg;
    }

    /**
     * Request initial password
     */
    public function requestInitialPassword(ArmorUserInterface $user):void
    {

    }

    /**
     * Request reset password
     */
    public function requestResetPassword(ArmorUserInterface $user):void
    {
        // Set template
        $syrus = Di::get(Syrus::class);
        $syrus->setTemplateFile('reset_password2.html', true);
    }


    /**
     * Pending password change added
     */
    public function pendingPasswordChange(ArmorUserInterface $user):void
    {

    }

    /**
     * onUpdate
     */
    public function onUpdate(ArmorUserInterface $user, string $column, string | bool $new_value)
    {

    }


}

