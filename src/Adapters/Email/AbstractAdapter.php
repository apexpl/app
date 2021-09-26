<?php
declare(strict_types = 1);

namespace Apex\App\Adapters\Email;

use redis;

/**
 * Abstract e-mail adapter
 */
class AbstractAdapter
{

    #[Inject(redis::class)]
    protected redis $redis;

    /**
     * Get SMTP vars
     */
    public function getSmtpVars():?array
    {

        // Check for SMTP server alias
        if (!$alias = $this->redis->rpoplpush('config:mercury.smtp_servers', 'config:mercury.smtp_servers')) {
            return null;
        }

        // Get info
        if (!$info = $this->redis->hgetall('config:mercury.smtp_servers.' . $alias)) {
            return null;
        }

        // Return
        return $info;
    }

}

