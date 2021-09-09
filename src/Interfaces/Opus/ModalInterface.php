<?php
declare(strict_types = 1);

namespace Apex\App\Interfaces\Opus;

use Apex\Svc\{App, View};

/**
 * Modal Interface
 */
interface ModalInterface
{

    /**
     * Display modal
     *
     * This function will be called when the modal is opened, and allows you to 
     * assign any necessary variables to the view to display within the modal.
     */
    public function show(View $view):void;

    /**
     * Submit
     * 
     * This function is executed upon the modal being submitted.
     */
    public function submit(App $app):void;

}

