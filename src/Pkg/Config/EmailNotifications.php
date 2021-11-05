<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\Svc\Db;
use Apex\App\Attr\Inject;

/**
 * E-mail notifications
 */
class EmailNotifications
{

    #[Inject(Db::class)]
    private Db $db;

    /**
     * Install
     */
    public function install(array $yaml):void
    {

        // Go through e-mails
        $emails = $yaml['email_notifications'] ?? [];
        foreach ($emails as $vars) { 

            // Get condition
            $condition = $vars['condition'] ?? [];
            $condition = json_encode($condition);
            $alias = $vars['alias'] ?? '';

            // Get sender
            $sender = $vars['sender'] ?? 'admin';
            if ($sender == 'admin') {
                if (!$sender = $this->db->getField("SELECT uuid FROM admin ORDER BY uuid LIMIT 1")) {
                    $sender = 'a:1';
                }
            }

            // Get recipient
            $recipient = $vars['recipient'] ?? 'user';
            if ($recipient == 'admin') {
                if (!$recipient = $this->db->getField("SELECT uuid FROM admin ORDER BY uuid LIMIT 1")) {
                    $recipient = 'a:1';
                }
            }

            // Add to database
            $this->db->insert('internal_email_notifications', [
                'controller' => $vars['controller'],
                'alias' => $alias,
                'sender' => $sender,
                'recipient' => $recipient,
                'content_type' => $vars['content_type'] ?? 'text/plain',
                'subject' => $vars['subject'],
                'contents' => base64_decode($vars['contents']),
                'condition_vars' => $condition
            ]);

        }

    }

}


