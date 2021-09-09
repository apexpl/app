<?php
declare(strict_types = 1);

namespace Apex\App\Interfaces;

use Apex\Mercury\Email\EmailContact;

/**
 * Notifications controller interface
 */
interface EmailNotificationControllerInterface
{

    /**
     * Get conditional form fields.
     *
     * These form fields are displayed when the administrator is creating a new 
     * e-mail notification with this controller, and allow the 
     * administrator to define the conditionals of the e-mail.
     */
    public function getConditionFormFields(FormFieldsCreator $creator):array;

    /**
     * Get available senders
     *
     * When the administrator is creating an e-mail notification, they can select 
     * who appears as the sender.  If any senders are available aside from 'user' and 'admin', 
     * return them here in an associative array.  Otherwise, return null.
     */
    public function getAvailableSenders():?array;

    /**
     * Get available recipients
     *
     * When the administrator is creating an e-mail notification, they can select 
     * who is the recipient.  If any recipients are available aside from 'user' and 'admin', 
     * return them here in an associative array.  Otherwise, return null.
     */
    public function getAvailableRecipients():?array;


    /**
     * Get merge fields
     *
     * Define the additional merge fields available within this notification controller 
     * that may be used to personalize e-mail messages aside 
     * from standard user profile information.
     */
    public function getMergeFields():array;

    /**
     * Get merge vars
     *
     * Obtain the personalized information to send an individual e-mail.
     * This should generate the necessary array of personalized information 
     * for all fields defined within the getMergeFields() method of this class.
     */
    public function getMergeVars(string $uuid, array $data = []):array;

    /**
     * Get sender
     *
     * If additional senders are available other than 'admin' and 'user', 
     * This should return the sender name and e-mail address as a 
     * EmailContact object.  Otherwise, return null.
     */
    public function getSender(string $sender, string $uuid, array $data = []):?EmailContact;

    /**
     * Get recipient
     *
     * If additional recipients are available other than 'admin' and 'user', 
     * This should return the recipient name and e-mail address as a 
     * EmailContact object.  Otherwise, return null.
     */
    public function getRecipient(string $recipient, string $uuid, array $data = []):?EmailContact;

    /**
     * Get reply-to
     *
     * If necessary, you can return the Reply-TO e-mail address of the message here.  
     * Otherwise, return null.
     */
    public function getReplyTo(array $data = []):?string;

    /**
     * Get cc
     *
     * If necessary, you can return the Cc e-mail address of the message here.  
     * Otherwise, return null.
     */
    public function getCc(array $data = []):?string;

}

