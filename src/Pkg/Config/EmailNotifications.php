<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\Svc\Db;

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

            $this->db->insert('internal_email_notifications', [
                'controller' => $vars['controller'],
                'alias' => $alias,
                'sender' => $vars['sender'],
                'recipient' => $vars['recipient'],
                'content_type' => $vars['content_type'] ?? 'text/plain',
                'subject' => $vars['subject'],
                'contents' => base64_decode($vars['contents']),
                'condition_vars' => $condition
            ]);

        }

    }

}


