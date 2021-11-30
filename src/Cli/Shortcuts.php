<?php
declare(strict_types = 1);

namespace Apex\App\Cli;

/**
 * Cli Shortcuts
 */
class Shortcuts
{

    // Skip top level
    public static array $skip_top_level = [
        'register' => ['account', 'register'],
        'add' => ['package', 'add'],
        'checkout' => ['package', 'checkout'],
        'commit' => ['package', 'commit'],
        'install' => ['package', 'install'],
        'merge' => ['package', 'merge'],
        'pull' => ['package', 'pull'],
        'scan' => ['package', 'scan'],
        'search' => ['package', 'search'],
        'rm' => ['package', 'rm'],
        'test' => ['package', 'test'],
        'upgrade' => ['package', 'upgrade'],
        'switch' => ['branch', 'switch'],
        'sql' => ['sys', 'sql'],
        'svn' => ['sys', 'svn'],
        'get-config' => ['sys', 'get-config'],
        'set-config' => ['sys', 'set-config'],
        'crontab' => ['sys', 'crontab'],
        'listen' => ['sys', 'listen'],
        'scan-classes' => ['sys', 'scan-classes']
    ];

    // Top-level shortcuts
    public static array $top_level = [
        'acct' => 'account',
        'br' => 'branch',
        'c' => 'create',
        'mig' => 'migration',
        'pkg' => 'package',
        'prj' => 'project',
        'rel' => 'release'
    ];

    // Second level shortcuts
    public static array $second_level = [

        'account' => [
            'del' => 'delete',
            'list' => 'ls',
            'reg' => 'register'
        ],

        'acl' => [
            'list' => 'ls'
        ],

        'branch' => [
            'c' => 'create',
            'd' => 'delete',
            'list' => 'ls',
            'switch' => 'sw'
        ], 

        'migration' => [
            'c' => 'create',
            'h' => 'history',
            'i' => 'install',
            'm' => 'migrate',
            'r' => 'rollback',
            's' => 'status'
        ],

        'package' => [
            'list' => 'ls',
            'require' => 'require-package'
        ],

        'release' => [
            'c' => 'create',
            'log' => 'change-log',
            'd' => 'delete',
            'list' => 'ls'
        ],

        'sys' => [
            'list' => 'ls'
        ]
    ];

    /**
     * Apply
     */
    public static function apply(array $args):array
    {

        // Check for zero
        if (count($args) == 0) { 
            return [];
        }

        // Check for help
        $is_help = false;
        if ($args[0] == 'help' || $args[0] == 'h') { 
            array_shift($args);
            $is_help = true;
        }

        // Check for skip
        $first = $args[0] ?? '';
        if (isset(self::$skip_top_level[$first])) { 
            array_unshift($args, self::$skip_top_level[$first][0]);
            $args[1] = self::$skip_top_level[$first][1];
        }

        // Replace top level
        $first = $args[0] ?? '';
        if (isset(self::$top_level[$first])) { 
            $args[0] = self::$top_level[$args[0]];
        }

        // Replace secondary, if needed
        if (isset($args[1])) { 
            $secondary = self::$second_level[$args[0]] ?? [];
            if (isset($secondary[$args[1]])) { 
                $args[1] = $secondary[$args[1]];
            }
        }

        // Add help, if needed
        if ($is_help === true) { 
            array_unshift($args, 'help');
        }

        // Return
        return $args;
    }

    /**
     8 Get shortcuts
     */
    public static function get(string $first, string $second):array
    {

        // Check
        $shortcuts = [];
        $commands = self::$second_level[$first] ?? [];
        $commands = array_flip($commands);

        // Check if command exists
        if (isset($commands[$second])) {

            // Replace first argumant, if possible.
            $top = array_flip(self::$top_level);
            $tmp_first = $top[$first] ?? $first;

            // Add to shortcuts
            $shortcuts[] = $tmp_first . ' ' . $commands[$second];
        }


        // Check ship top level
        foreach (self::$skip_top_level as $source => $dest) {

            // Check
            if ($dest[0] != $first || $dest[1] != $second) {
                continue;
            }
            $shortcuts[] = $source;
        }

        // Return
        return $shortcuts;
    }

}


