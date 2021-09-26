<?php
declare(strict_types = 1);

namespace Apex\App\Cli;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\{CLi, Shortcuts};

/**
 * CLI Router
 */
class CliRouter
{

    #[Inject(Container::class)]
    protected Container $cntr;

    #[Inject(Convert::class)]
    protected Convert $convert;

    // System commands
    private array $sys_commands = [
        'acl',
        'account',
        'branch',
        'create',
        'docs',
        'image',
        'migration',
        'opus',
        'package',
        'project',
        'release',
        'sys'
    ];

    // Properties
    protected array $argv = [];
    public array $orig_argv = [];
    protected string $class_name = '';
    protected bool $is_help = false;
    protected ?string $signing_password = null;

    /**
     * Determine
     * 
     * @return string Fully qualified class name of CLI command.
     */
    public function determineRoute(array $args):string
    {

        // Get args
        $this->orig_argv = $args;
        list($args, $opt) = $this->getArgs(['h','help'], true);

        // Check for help
        list($this->is_help, $args) = $this->checkIsHelp($args, $opt);
        $cmd = $args[0] ?? '';

        // Get root namespace
        if (in_array($cmd, $this->sys_commands) || count($args) < 1) { 
            $root_namespace = "\\Apex\\App\\Cli\\Commands\\";
        } else { 
            $root_namespace = "\\App\\" . $this->convert->case($args[0], 'title') . "\\Opus\\Cli\\";
            array_shift($args);
        }

        // Determine command
        list($tmp_args, $passed_args) = [$args, []];
        while (count($tmp_args) > 0) { 
            $chk_class = $root_namespace . implode("\\", array_map(fn ($a) => $this->convert->case($a, 'title'), $tmp_args));

            if (class_exists($chk_class)) { 
                $this->class_name = $chk_class;
                break;
            } elseif (class_exists($chk_class . "\\Help")) { 
                $this->class_name = $chk_class . "\\Help";
                $this->is_help = true;
                break;
            } else { 
                array_unshift($passed_args, array_pop($tmp_args));
            }
        }
        $this->argv = $passed_args;

        // Set help class, if needed
        if ($this->class_name == '') { 
            $this->class_name = "\\Apex\\App\\Cli\\Commands\\Help";
            $this->is_help = true;
        }
        $this->args = $passed_args;

        // return
        return $this->class_name;
    }

    /**
     * Check is help
     */
    private function checkIsHelp(array $args, array $opt):array
    {

        // Check options
        $is_help = $opt['help'] ?? false;
        if (isset($opt['h']) && $opt['h'] === true) { 
            $is_help = true;
        }

        // Check for help
        $first = $args[0] ?? '';
        if ($first == 'help' || $first == 'h') { 
            $is_help = true;
            array_shift($args);
        }

        // Return
        return [$is_help, $args];
    }

    /**
     * Get command line arguments and options
     */
    public function getArgs(array $has_value = [], bool $return_args = false):array
    {

        // Initialize 
        $tmp_args = $this->orig_argv;
        list($args, $options) = [[], []];

        // Apply shortcuts 
        $tmp_args = Shortcuts::apply($tmp_args);

        // Go through args
        while (count($tmp_args) > 0) { 
            $var = array_shift($tmp_args);

            // Long option with =
            if (preg_match("/^-{1,2}(\w+?)=(.+)$/", $var, $match)) { 
                $options[$match[1]] = $match[2];

            } elseif (preg_match("/^-{1,2}(.+)$/", $var, $match) && in_array($match[1], $has_value)) { 


                $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
                if ($value == '=') { 
                    $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
                }
                $options[$match[1]] = $value;

            } elseif (preg_match("/^--(.+)/", $var, $match)) { 
                $options[$match[1]] = true;

            } elseif (preg_match("/^-(\w+)/", $var, $match)) { 
                $chars = str_split($match[1]);
                foreach ($chars as $char) { 
                    $options[$char] = true;
                }

            } else { 
                $args[] = $var;
            }
        }

        // Return
        if ($return_args === true) { 
            return array($args, $options);
        } else { 
            return $options;
        }
    }

    /**
     * Get commit args
     */
    public function getCommitArgs():?array
    {

        // Get args
        $opt = $this->getArgs(['m', 'file']);
        $message = $opt['m'] ?? '';
        $file = $opt['file'] ?? '';

        // Check for file
        if ($file != '') { 

            if (file_exists(SITE_PATH . '/' . $file)) { 
                $file = SITE_PATH . '/' . $file;
            } elseif (file_exists(realpath($file))) { 
                $file = realpath($file);
            } else { 
                $cli->error("Commit file does not exist at, $file");
                return null;
            }
            $commit_args = ['--file', $file];

        // Commit message
        } elseif ($message != '') { 
            $commit_args = ['-m', $message];
        } else { 
            $commit_args = ['-m', 'No commit message defined'];
        }

        // Return
        return $commit_args;
    }

}


