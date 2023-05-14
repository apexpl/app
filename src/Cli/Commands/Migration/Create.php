<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Migration;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\Migrations\Handlers\ClassManager;
use Apex\Migrations\Adapters\DoctrineAdapter;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Create new migration
 */
class Create implements CliCommandInterface
{

    #[Inject(Convert::class)]   
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(ClassManager::class)]
    private ClassManager $manager;

    #[Inject(DoctrineAdapter::class)]
    private DoctrineAdapter $doctrine_adapter;


    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();

        // Initialize
        $opt = $cli->getArgs();
        $alias = $args[1] ?? '';
        $diff = $opt['diff'] ?? false;
        $dump = $opt['dump'] ?? false;

        // Get type
        $type = 'apex';
        if (isset($opt['doctrine']) && $opt['doctrine'] === true) {
            $type = 'doctrine';
        } elseif (isset($opt['eloquent']) && $opt['eloquent'] === true) {
            $type = 'eloquent';
        }

        // Get current branch
        $svn = $pkg->getSvnRepo();
        $branch = $svn === null ? 'trunk' : $svn->getCurrentBranch();

        // Check
        if ($alias != '' && !preg_match("/^[a-zA-Z0-9_]/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        }

        // Create migration
        if ($type == 'doctrine' && $diff === true) { 
            $dirname = $this->doctrine_adapter->diff($pkg_alias);
        } elseif ($type == 'doctrine' && $dump === true) { 
            $dirname = $this->doctrine_adapter->dump($pkg_alias);
        } else { 
            $dirname = $this->manager->create($pkg_alias, $alias, $branch, $type);
        }

        // Send message
        $dirname = str_replace(SITE_PATH, '', $dirname);
        $cli->success("Successfully created new migration, which can be found at:", [$dirname]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Migration',
            usage: 'migration create <PKG_ALIAS> [<ALIAS>] [--doctrine|eloquent] [--diff] [--dump]',
            description: "Create new database migration."
        );

        // Params
        $help->addParam('pkg_alias', 'The package alias to create a migration for.');
        $help->addParam('alias', 'Optional alias / name of the database migration.  Not applicable for Doctrine migrations.');
        $help->addFlag('--doctrine', 'If present, will create a Doctrine migration.');
        $help->addFlag('--eloquent', 'If present, will create an Eloquent migration.');
        $help->addFlag('--diff', 'Only applicable if creating a Doctrine migration and will diff the database schema.');
        $help->addFlag('--dump', 'Only applicable if creating a Doctrine migration, and will dump the full database schema.');
        $help->addExample('./apex migration create myshop');
        $help->addParam('./apex migration create myshop --type doctrine --dump');

        // Return
        return $help;
    }

}


