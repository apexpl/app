<?php

namespace Apex\App\Interfaces\Opus;

/**
 * Dashboard item interface
 */
interface DashboardItemInterface
{

    /**
     * Render the dashboard item.
     *
     * @return The HTML contents of the dashboard item.
     */
    public function render():string;

}


