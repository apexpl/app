<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Tests;

use Apex\Svc\{App, Di, Container};
use Apex\App\Sys\Tests\CustomAssertions;
use Apex\App\Sys\Tests\Stubs\CliStub;
use Nyholm\Psr7\Response;

/**
 * APex test case
 */
class ApexTestCase extends CustomAssertions
{

    // Properties
    protected App $app;
    protected Container $cntr;
        private ?CliStub $cli = null;

    /**
     * Constructor
     */
    public function __construct()
    {

        // Load phpUnit
        parent::__construct();

        // Init app
        $this->app = Di::get(App::class);
        $this->cntr = $this->app->getContainer();

    }

    /**
     * Send CLI Command
     */
    protected function apex(string $command, array $inputs = [], bool $do_confirm = true)
    {

        /// Check if loaded
        if ($this->cli === null) {
            $this->cli = $this->cntr->make(CliStub::class);
            $this->cntr->set(\Apex\App\Cli\Cli::class, $this->cli);
        }

        // Get response
        $args = explode(' ', $command);
        $res = $this->cli->run($args, $inputs, $do_confirm);
        return $res;
    }

}


