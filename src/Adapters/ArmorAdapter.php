<?php
declare(strict_types = 1);

namespace Apex\App\Adapters;

use Apex\Svc\{App, View, Container, SmsClient, Emailer};
use Apex\Db\Interfaces\DbInterface;
use Psr\Http\Message\ServerRequestInterface;
use Apex\Mercury\Email\EmailMessage;
use Apex\Armor\Auth\AuthSession;
use Apex\Armor\User\ArmorUser;
use Apex\Armor\Interfaces\{AdapterInterface, ArmorUserInterface};
use App\Webapp\Notifications\ArmorController;
use App\Webapp\Models\EmailNotification;
use Apex\Armor\Exceptions\{ArmorOutOfBoundsException, ArmorNotImplementedException};
use Apex\App\Attr\Inject;
use Nyholm\Psr7\Response;
use redis;

/**
 * Apex adapter that utilizes the apex/mercury and apex/syrus packages.
 */
class ArmorAdapter implements AdapterInterface
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(DbInterface::class)]
    private DbInterface $db;

    #[Inject(View::class)]
    private View $view;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Get user by uuid
     */
    public function getUuid(DbInterface $db, string $uuid, bool $is_deleted = false):?ArmorUserInterface
    {

        // Get table and class name
        $yaml = $this->app->getRoutesConfig('site.yml');
        $user_types = $yaml['user_types'] ?? [];
        $prefix = strtolower(substr($uuid, 0, 1));

        // Get table and class name
        foreach ($user_types as $type => $vars) { 

            // Skip, if needed
            if (!str_starts_with($type, $prefix)) { 
                continue;
            }

            // Set variables
            $table_name = $vars['table'];
            $class_name = $vars['class'];
            break;
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

        // Get e-mail message
        if (!$email = EmailNotification::whereFirst('controller = %s AND alias = %s', ArmorController::class, $type)) {
            throw new ArmorOutOfBoundsException("No e-mail message exists with the type, $type");
        }

        // Create merge vars
        $replace = $user->toArray();
        $replace['site_name'] = $this->app->config('core.site_name');
        $replace['domain_name'] = $this->app->config('core.domain_name');
        $replace['armor_code'] = $armor_code;

        // Replace as needed in e-mail message
        list($subject, $contents, $html_contents) = [$email->subject, $email->text_contents, $email->html_contents];
        foreach ($replace as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $subject = str_replace("~$key~", (string) $value, $subject);
            $contents = str_replace("~$key~", (string) $value, $contents);
            $html_contents = str_replace("~$key~", (string) $value, $html_contents);
        }

        // Get sender
        if (!$sender = $this->getUuid($this->db, $email->sender)) {
            return;
        }

        // Create e-mail message
        $msg = $this->cntr->make(EmailMessage::class, [
            'to_email' => $new_email == '' ? $user->getEmail() : $new_email,
            'from_email' => $sender->getEmail(),
            'subject' => $subject,
            'text_message' => $contents,
            'html_message' => $html_contents
        ]);

        // Send e-mail
        $emailer = $this->cntr->get(Emailer::class);
        $emailer->send($msg);
    }

    /**
     * Send SMS
     */
    public function sendSMS(ArmorUserInterface $user, string $type, string $code, string $new_phone = ''):void
    {

        // Check if Nexmo configured
        if ($this->app->config('core.nexmo_api_key') == '') {
            return;
        }

        // Set message
        $message = $this->app->config('core.sms_verification_message');
        $message = str_replace('~code~', $code, $message);
        $phone = $new_phone != '' ? $new_phone : $user->getPhone();

        // Send SMS
        $nexmo = $this->cntr->get(SmsClient::class);
        $mid = $nexmo->send($phone, $message);
    }

    /**
     * Handle session status
     */
    public function handleSessionStatus(AuthSession $session, string $status):void
    {

        // Initialize
        $type = $session->getUser()->getType() == 'user' ? 'members' : $session->getUser()->getType() . 's';
        if ($type == 'admins') {
            $type = 'admin';
        }

        // Get template file
        $file = match($status) { 
            'email' => '/auth/twofactor_email.html', 
            'email_otp' => 'auth/twofactor_email_otp.html', 
            'phone' => '/auth/twofactor_phone.html',
            'pgp' => '/auth/twofactor_pgp.html',
            'verify_email' => 'auth/verify_email',
            'verify_email_otp' => 'auth/verify_email_otp',
            'verify_phone' => 'auth/verify_phone',
            default => '/' . $type . '/index'
        };
        $file = $type . '/' . $file;

        // Template variables
        $this->view->assign('profile', $session->getUser()->toDisplayArray());
        if ($session->getStatus() == 'pgp') {
            $message = $this->redis->get('armor:pgp:' . $session->getUuid()); 
            $this->view->assign('message', $message);
        }
        $this->view->assign('area', $type);

        // Display template
        $this->view->setTemplateFile($file);
        echo $this->view->render();
        exit(0);
    }

    /**
     * Handle authorized two factor request
     */
    public function handleTwoFactorAuthorized(AuthSession $session, ServerRequestInterface $request, bool $is_login = false):void
    {

        // Set request
        $this->app->setRequest($request);
        $this->app->setSession($session);
        $this->view->setTemplateFile('');
        $type = $session->getUser()->getType() == 'user' ? 'members' : $session->getUser()->getType() . 's';

        // Check for login
        if ($is_login === true) {
            $url = '/' . $type . '/index';
            header("Location: $url");
            exit(0);
        }

        // Handle request
        $html = $this->view->render();
        $response = new Response(00, [], $html);
        $this->app->outputResponse($response);
        exit(0);
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
        $this->view->setTemplateFile('forgot_password2.html', true);
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

