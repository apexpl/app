<?php
declare(strict_types = 1);

namespace Apex\App\Cli;

use Apex\Svc\Di;
use Apex\App\Cli\CLi;
use Apex\App\Attr\Inject;

/**
 * CLI Help Screen
 */
class CliHelpScreen
{

    // Properties
    private string $params_title = 'PARAMETERS';
    private string $flags_title = 'OPTIONAL FLAGS';

    /**
     * Constructor
     */
    public function __construct(
        public string $title, 
        public string $usage, 
        public string $description = '',
        public array $params = [], 
        public array $flags = [], 
        public array $examples = []
    ) { 

    }

    /**
     * Set title
     */
    public function setTitle(string $title):void
    {
        $this->title = $title;
    }

    /**
     * Set usage
     */
    public function setUsage(string $usage):void
    {
        $this->usage = $usage;
    }

    /**
     * Set description
     */
    public function setDescription(string $desc):void
    {
        $this->description = $desc;
    }

    /**
     * Add param
     */
    public function addParam(string $param, string $description):void
    {
        $this->params[$param] = $description;
    }

    /**
     * Add flag
     */
    public function addFlag(string $flag, string $description):void
    {
        $this->flags[$flag] = $description;
    }

    /**
     * Add example
     */
    public function addExample(string $example):void
    {
        $this->examples[] = $example;
    }

    /**
     * Set params title
     */
    public function setParamsTitle(string $title):void
    {
        $this->params_title = $title;
    }

    /**
     * Set flags title
     */
    public function setFlagsTitle(string $title):void
    {
        $this->flags_title = $title;
    }

    /**
     * Render
     */
    public function render():void
    {

        // Initialize
        $this->cli = Di::get(Cli::class);
        $cli = $this->cli;

        // Check for shortcuts
        list($shortcuts, $usage_options) = [[], ''];
        if (preg_match("/^([a-z]+)\s([a-z]+)(.*)$/", $this->usage, $m)) {
            $shortcuts = Shortcuts::get($m[1], $m[2]);
            $usage_options = $m[3];
        }

        // Send header
        $cli->sendHeader($this->title);

        // Send usage
        $cli->send("USAGE \r\n    ./apex $this->usage\r\n");
        foreach ($shortcuts as $shortcut) {
            $cli->send("    ./apex $shortcut $usage_options\r\n");
        }
        $cli->send("\r\n");

        // Send description
        if ($this->description != '') { 
            $cli->send("DESCRIPTION\r\n");
            $cli->send("    " . wordwrap($this->description, 75, "\r\n    ") . "\r\n\r\n");
        }

        // Params
        if (count($this->params) > 0) { 
            $cli->send($this->params_title . "\r\n\r\n");
            $this->cli->sendArray($this->params);
        }

        // Flags
        if (count($this->flags) > 0) { 
            $cli->send($this->flags_title . "\r\n\r\n");
            $this->cli->sendArray($this->flags);
        }

        // Examples
        if (count($this->examples) > 0) { 
            $cli->send("EXAMPLES\r\n\r\n");
            foreach ($this->examples as $example) { 
                $cli->send("    $example\r\n\r\n");
            }
        }

        // End
        $cli->send("-- END --\r\n\r\n");
    }

}


