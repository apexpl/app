<?php
declare(strict_types = 1);

namespace Apex\App\Boot;

use Apex\Svc\{App, Logger, Debugger};

/**
 * Exception / error handlers
 */
class ErrorHandlers
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Logger::class)]
    private ?Logger $logger = null;

    #[Inject(Debugger::class)]
    private ?Debugger $debugger = null;

    /**
     * Exception handler
     */
    public function handleException(\Exception $e):void
    {

        // Get trace
        $trace = $e->getTrace();
        $file = str_replace(SITE_PATH, '', $e->getFile());
        $line = $e->getLine();

        // Get level of exception
        $level = $e->level ?? 'error';
        $this->logger->$level($e->getMessage());
        // Log error
        $this->report();

    }

    /**
     * Report
     */
    



