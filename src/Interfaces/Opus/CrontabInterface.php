<?php

namespace Apex\App\Interfaces\Opus;

/**
 * Crontab job interface
 */
interface CrontabInterface
{

    /**
     * Process the crontab job
     */
    public function process():void;

}


