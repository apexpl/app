<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Tests\Stubs;

use Apex\Svc\{Container, Convert};
use Apex\App\Cli\CliRouter;
use Apex\Armor\Auth\Operations\{Password, RandomString};

/**
 * Handles all CLI functionality for for Apex
 */
class CliStub extends CliRouter
{

    // Properties
    protected static string $stdout = '';
    protected static string $stderr = '';
    protected static array $inputs = [];
    protected static int $input_num = 0;

    /**
     * Run CLI command
     */
    public function run(array $args, array $inputs = []):string
    {

        // Set inputs
        self::$inputs = $inputs;
        self::$input_num = 0;
        self::$stdout = '';

        // Determine class
        $class_name = $this->determineRoute($args);

        // Load command class
        $cmd = $this->cntr->make($class_name);

        // Process as needed
        if ($this->is_help === true) { 
            $cmd->help($this)->render();
        } else { 
            $cmd->process($this, $this->argv);
        }

        // Return
        return self::$stdout;
    }

    /**
     * Show help
     */
    public function sendHelp(string $title = '', string $usage = '', string $desc = '', array $params = [], array $flags = []):void
    {

        // Send header
        $this->sendHeader($title);

        // Send usage and description
        if ($usage != '') { 
            $this->send("USAGE \r\n    ./apex $usage\r\n\r\n");
        }
        if ($desc != '') {
            $this->send("DESCRIPTION\r\n    " . wordwrap($desc, 75, "\r\n    ") . "\r\n\r\n");
        }

        // Params
        if (count($params) > 0) { 

            // Get max size
            $size = 0;
            foreach ($params as $key => $value) { 
                $size = strlen($key) > $size ? strlen($key) : $size;
            }
            $size += 4;

            $this->send("PARAMETERS\r\n\r\n");
            foreach ($params as $key => $value) {
                $break = "\r\n" . str_pad('', ($size + 4), ' ', STR_PAD_RIGHT);
                $line = '    ' . str_pad($key, $size, ' ', STR_PAD_RIGHT) . wordwrap($value, (75 - $size - 4), $break);
                $this->send("$line\r\n");
            }
            $this->send("\r\n");
        }

        // Flags
        if (count($flags) > 0) { 

            // Get max size
            $size = 0;
            foreach ($flags as $key => $value) { 
                $size = strlen($key) > $size ? strlen($key) : $size;
            }
            $size += 4;

            $this->send("OPTIONAL FLAGS\r\n\r\n");
            foreach ($flags as $key => $value) { 
                $break = "\r\n" . str_pad('', ($size + 6), ' ', STR_PAD_RIGHT);
                $line = '    --' . str_pad($key, $size, ' ', STR_PAD_RIGHT) . wordwrap($value, (75 - $size - 6), $break);
                $this->send("$line\r\n");
            }
            $this->send("\r\n");
        }
        $this->send("-- END --\r\n\r\n");
    }

    /**
     * Get input from the user.
     */
    public function getInput(string $label, string $default_value = '', bool $is_secret = false):string|bool|int|float
    {

        // Get value
        $name = trim($label);
        if (isset(self::$inputs[$name])) { 
            $value = self::$inputs[$name];
        } elseif (isset(self::$inputs[self::$input_num])) { 
            $value = self::$inputs[self::$input_num];
        } elseif ($default_value != '') { 
            $value = $default_value;
        } else { 
            $value = RandomString::get(12);
        }

        // Echo label, and return
        $this->send($label . $value . "\r\n");
        self::$input_num++;
        return $value;
    }

    /**
     * Get confirmation
     */
    public function getConfirm(string $message, string $default = ''):bool
    {
        $value = $this->getInput($message . " (yes/no) [$default]: ");
        return $value;
    }

    /**
     * Get password
     */
    public function getNewPassword(string $label = 'Desired Password', bool $allow_empty = false, int $min_score = 2):?string
    {

        // Get password
        $password = $this->getInput($label . ': ', '', true);
        $confirm = $this->getInput('Confirm Password: ', '', true);

        // Return
        return $password == '' ? null : $password;
    }

    /**
     * Get option from list
     */
    public function getOption(string $message, array $options, string $default_value = ''):string
    {

        // Set message
        $message .= "\r\n\r\n";
        foreach ($options as $key => $name) { 
            $message .= "    [$key] $name\r\n";
        }
        $message .= "\r\nChoose One [$default_value]: ";

        // Get option
        do {
            $opt = $this->getInput($message, $default_value);
            if (isset($options[$opt])) { 
            break;
            }
            $this->send("Invalid option, please try again.  ");
        } while (true);

        // Return
        return $opt;
    }

    /**
     * Send output to user.
     */
    public function send(string $data):void
    {

        // Wordwrap,  if needed
        if (!preg_match("/^\s/", $data)) { 
            $data = wordwrap($data, 75, "\r\n");
        }

        // Output data
        self::$stdout .= $data;
    }

    /**
     * Send header to user
     */
    public function sendHeader(string $label):void
    {
        $this->send("------------------------------\r\n");
        $this->send("-- $label\r\n");
        $this->send("------------------------------\r\n\r\n");
    }

    /**
     * Display table
     */
    public function sendTable(array $rows):void
    {

        // Return, if no rows
        if (count($rows) == 0) { 
            return;
        }

        // Get column sizes
        $sizes = [];
        for ($x=0; $x < count($rows[0]); $x++) { 

            // Get max length
            $max_size = 0;
            foreach ($rows as $row) { 
                if (strlen($row[$x]) > $max_size) { $max_size = strlen($row[$x]); }
            }
            $sizes[$x] = ($max_size + 3);
        }
        $total_size = array_sum(array_values($sizes));

        // Display rows
        $first = true;
        foreach ($rows as $row) { 

            // Go through fields
            list($x, $line) = [0, ''];
            foreach ($row as $var) { 
                $line .= str_pad(' ' . $var, ($sizes[$x] - 1), ' ', STR_PAD_RIGHT) . '|';
            $x++; }

            // Display line
            $this->send("$line\r\n");
            if ($first === true) { 
                $this->send($line = str_pad('', $total_size, '-') . "\r\n");
                $first = false;
            }
        }
        $this->send("\r\n");
    }

    /**
     * Success
     */
    public function success(string $message, array $files = []):void
    {
        $this->send("\r\n$message\r\n\r\n");
        foreach ($files as $file) { 
            $this->send("    /$file\r\n");
        }
        $this->send("\r\n");
    }

    /**
     * Error
     */
    public function error(string $message)
    {
        $this->send("ERROR: $message\r\n");
    }

    /**
     * Get signing password
     */
    public function getSigningPassword():string
    {
        return 'password12345';
    }

    /**
     * Set signing password
     */
    public function setSigningPassword():void
    {

    }

}

