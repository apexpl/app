<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, debug};
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;
use apex\core\notification;
use apex\app\exceptions\ApexException;


/**
 * Handles default notifications package configuration.
 */
#[used_by(config::class)]
class notifications extends config
{


/**
 * Install notificationsl.  Only executed during initial package install, and 
 * never again. 
 * 
 * @param mixed $pkg The loaded package configuration.
 */
protected static function install():void
{ 

    // Go through notifications
    $notifications = $this->pkg->notifications ?? [];
    foreach ($notifications as $data) { 
        $data['contents'] = base64_decode($data['contents']);

        $client = app::make(notification::class);
        $client->create($data);
    }

}

}


