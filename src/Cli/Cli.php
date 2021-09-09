<?php
declare(strict_types = 1);

namespace Apex\App\Cli;

use Apex\Svc\{Container, Convert};
use Apex\Armor\Auth\Operations\Password;

/**
 * Handles all CLI functionality for for Apex
 */
class Cli extends CliRouter
{

    /**
     * Run CLI command
     */
    public function run():void
    {

        // Initialize
        global $argv;
        array_shift($argv);

        // Determine route
        $class_name = $this->determineRoute($argv);

        // Load command class
        $cmd = $this->cntr->make($class_name);

        // Process as needed
        if ($this->is_help === true) { 
            $cmd->help($this)->render();
        } else { 
            $cmd->process($this, $this->argv);
        }

    }

    /**
     * Get input from the user.
     */
    public function getInput(string $label, string $default_value = '', bool $is_secret = false):string
    { 

        // Echo label
        $this->send($label);
        if ($is_secret === true) { 
            exec('stty -echo');
        }

        // Get input
        $value = trim(fgets(STDIN));
        if ($value == '') { 
            $value = $default_value; 
        }

        // Re-enable sheel
        if ($is_secret === true) { 
            exec('stty echo');
            $this->send("\r\n");
        }

        // Check quit / exist
        if (in_array($value, ['q', 'quit', 'exit'])) { 
            $this->send("Ok, goodbye.\n\n");
            exit(0);
        }

        // Return
        return $value;
    }

    /**
     * Get confirmation
     */
    public function getConfirm(string $message, string $default = ''):bool
    {

        do {
            $ok = strtolower($this->getInput($message . " (yes/no) [$default]: ", $default));
            if (in_array($ok, ['y','n','yes','no'])) { 
                $confirm = $ok == 'y' || $ok == 'yes' ? true : false;
                break;
            }
            $this->send("Invalid answer, please try again.  ");
        } while (true);

        // Return
        return $confirm;
    }

    /**
     * Get password
     */
    public function getNewPassword(string $label = 'Desired Password', bool $allow_empty = false, int $min_score = 2):?string
    {

        // Get password
        $ok = false;
        do {

            // Get inputs 
            $password = $this->getInput($label . ': ', '', true);
            $confirm = $this->getInput('Confirm Password: ', '', true);
            $score = Password::getStrength($password);

            // Check
            if ($password == '' && $allow_empty === false) { 
                $this->send("\r\nYou did not specify a password and one is required.  Please specify your desired password.\r\n\r\n");
                continue;
            } elseif ($password != $confirm) { 
                $this->send("\r\nPasswords do not match.  Please try again.\r\n\r\n");
                continue;
            } elseif ($min_score > $score && $allow_empty === false) { 
                $this->send("\r\nPassword is not strong enough.  Please try again.\r\n\r\n");
                continue;
            }
            $ok = true;

        } while ($ok !== true);

        // Return
        $this->send("\r\n");
        return $password == '' ? null : $password;

    }

    /**
     * Get signing password
     */
    public function getSigningPassword():?string
    {
        return $this->signing_password;
    }

    /**
     * Set signing password
     */
    public function setSigningPassword(string $password):void
    {
        $this->signing_password = $password;
    }

    /**
     * Get option from list
     */
    public function getOption(string $message, array $options, string $default_value = '', bool $add_numbering = false):string
    {

        // Set message
        $map = [];
        $message .= "\r\n\r\n";

        // Go through options
        $x=0;
        foreach ($options as $key => $name) { 
            if ($add_numbering === true) { 
                $map[(string) ++$x] = $key;
                if ($key == $default_value) { 
                    $default_value = (string) $x;
                }
                $key = (string) $x;
            }
            $message .= "    [$key] $name\r\n";
        }
        $message .= "\r\nChoose One [$default_value]: ";
        if ($add_numbering === true) { 
            $options = $map;
        }

        // Get option
        do {
            $opt = $this->getInput($message, $default_value);
            if (isset($options[$opt])) { 
            break;
            }
            $this->send("Invalid option, please try again.  ");
        } while (true);

        // Get mapped option, if needed
        if ($add_numbering === true) { 
            $opt = $map[$opt];
        }

        // Return
        return (string) $opt;
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
        fputs(STDOUT, $data);
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
        list($first, $break_line) = [true, ''];
        foreach ($rows as $row) { 

            // Go through fields
            list($x, $line, $break_line) = [0, '', ''];
            foreach ($row as $var) { 
                $line .= str_pad(' ' . $var, ($sizes[$x] - 1), ' ', STR_PAD_RIGHT) . '|';
                $break_line .= str_pad('', ($sizes[$x] - 1), '-', STR_PAD_RIGHT) . '+';
            $x++; }

            // Display line
            $this->send("$line\r\n");
            if ($first === true) { 
                $this->send("$break_line\r\n");
                $first = false;
            }
        }
        $this->send("$break_line\r\n\r\n");
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
        $this->send("ERROR: $message\r\n\r\n");
    }

    /**
     * Render array
     */
    public function sendArray(array $inputs):void
    {

        // Get max size
        $size = 0;
        foreach ($inputs as $key => $value) { 
            $size = strlen($key) > $size ? strlen($key) : $size;
        }
        $size += 4;

        // Go through inputs
        foreach ($inputs as $key => $value) { 
            $break = "\r\n" . str_pad('', ($size + 4), ' ', STR_PAD_RIGHT);
            $line = '    ' . str_pad($key, $size, ' ', STR_PAD_RIGHT) . wordwrap($value, (75 - $size - 4), $break);
            $this->send("$line\r\n");
        }
        $this->send("\r\n");
    }


}

