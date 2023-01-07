<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Migration;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Config\EmailNotifications;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Install e-mail notifications
 */
class InstallEmail implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(EmailNotifications::class)]
    private EmailNotifications $email_notifications;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            $cli->error("You did not spcify a valid package to install e-mail notifications on.");
            return;
        }

        // Install e-mails
        $yaml = $pkg->getConfig();
        $this->email_notifications->install($yaml);

        // Success
        $cli->send("Successfully installed e-mail notifications for package, " . $pkg->getAlias() . "\n\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Install E-Mail Notifications',
            description: 'Install all e-mail notifications defined within the package.yml file of a given package.',
            usage: 'migration install-email <PACKAGE>'
        );
        $help->addParam('package', 'Alias of the package to install e-mail notifications of.');
        $help->addExample('./apex migration install-email my-shop');

        // Return
        return $help;
    }

}


