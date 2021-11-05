<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Boot;

use Apex\Svc\{Di, App, Logger, Debugger, View};

/**
 * Exception / error handlers
 */
class ErrorHandlers
{

    /**
     * Exception handler
     */
    public function handleException($e):void
    {
        $this->error(0, $e->getMessage(), $e->getFile(), $e->getLine());
    }

    /**
     * Error
     */
    public function error(int $errno, string $message, string $file, int $line):void
    {

        // Format file
        $file = str_replace(SITE_PATH, '', $file);
        if (preg_match("/\/\.apex\/svn\/(.+?)\/(.+?)\/(.+)$/", $file, $m)) { 
            $pkg_alias = ucwords($m[1]);
            $type = $m[2];
            $parts = explode('/', $m[3]);
            if (in_array($parts[0], ['src','etc','docs','tests'])) { 
                $parts[0] = ucwords($parts[0]);
        }

            $file = match(true) { 
                (in_array($type, ['src','etc','docs','tests'])) ? true : false => $type . '/' . $pkg_alias . '/' . implode('/', $parts), 
                ($type == 'views') ? true : false => preg_replace("/^\/\.apex\/svn/", "", $file),
                ($type == 'ext') ? true : false => implode('/', $parts), 
                ($type == 'share' && $parts[0] == 'HttpControllers') ? true : false => 'src/HttpControllers/' . $parts[1], 
                default => $file
            };
        }

        // Get container items
        $this->app = Di::get(App::class);
        $this->logger = Di::get(Logger::class);
        $this->debugger = Di::get(Debugger::class);

        // Get level of log message
        $level = match($errno) {
            2, 32, 512 => 'warning',
            8, 1024 => 'notice',
            64, 128, 256, 4096 => 'error',
            2048, 8192, 16384 => 'info',
            1, 4, 16 => 'critical',
            default => 'error'
        };

        // Add log
        $log_line = '(' . $file . ':' . $line . ') ' . $message;
        $this->logger?->$level($log_line);

        // Finish debug session
        $this->debugger?->finish();

        // Render
        if (php_sapi_name() == "cli") { 
            $this->renderCli($message, $file, $line);
        } elseif ($this->app->getContentType() == 'application/json') { 
            $this->renderJson($message, $file, $line);
        } else { 
            $this->renderHtml($message, $file, $line);
        }

        // Exit
        exit(0);
    }

    /**
     * Render CLI
     */
    private function renderCli(string $message, string $file, int $line):void
    {

        // Send message.
        fputs(STDOUT, 'ERROR: ' . $message . "\r\n\r\n");
        fputs(STDOUT, "    File: $file\r\n");
        fputs(STDOUT, "    Line: $line\r\n\r\n");

        // Exit
        exit(0);
    }

    /**
     * Render JSON
     */
    private function renderJson(string $message, string $file, int $line):void
    {

        // Set vars
        $vars = [
            'status' => 'error',
            'message' => $message,
            'data' => [
                'file' => $file,
                'line' => $line
            ]
        ];

        // Echo output
        header("Content-type: application/json");
        http_response_code(500);
        echo json_encode($vars);
        exit(0);
    }

    /**
     * Render HTML
     */
    private function renderHtml(string $message, string $file, int $line):void
    {

        // Get template file
        $parts = explode('/', $this->app->getPath());
        $template_file = $parts[0] == 'admin' || $this->app->config('core.mode') == 'devel' ? '500.html' : '500_generic.html';

        // Get template dir
        if (isset($parts[0]) && file_exists(SITE_PATH . '/views/html/' . $parts[0] . '/' . $template_file)) { 
            $template_file = $parts[0] . '/' . $template_file;
        }

        // Get view, assign variables
        $view = Di::get(View::class);
        $view->assign('error_message', $message);
        $view->assign('error_file', $file);
        $view->assign('error_line', (string) $line);

        // Parse view
        $view->setRpcEnabled(false);
        echo $view->render($template_file);
        exit(0);
    }

}


