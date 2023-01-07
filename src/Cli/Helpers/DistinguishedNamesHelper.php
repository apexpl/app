<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\App\Cli\Cli;
use Apex\App\Network\Stores\DistinguishedNamesStore;
use Apex\Armor\x509\DistinguishedName;
use Apex\App\Attr\Inject;

/**
 * x.509 Certificates
 */
class DistinguishedNamesHelper
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(DistinguishedNamesStore::class)]
    private DistinguishedNamesStore $store;

    /**
     * Get DN
     */
    public function get():?DistinguishedName
    {

        // Get existing DNs
        $names = $this->store->list();

        // List accounts, if we have them
        if (count($names) > 0) { 
            $this->cli->send("The following distinguished names have been found:\r\n\r\n");
            foreach ($names as $alias => $name) {
                $this->cli->send("    [$alias] $name\r\n");
            }
            $this->cli->send("\r\nIf you would like to use one of the above, enter its alias below.  If not, leave blank to create a new entry.\r\n\r\n");
            $alias = $this->cli->getInput('Alias: ');

            // Load DN and return, if defined
            if ($alias != '') { 
                $dn = $this->store->load($alias);
                $this->cli->send("\r\n");
                return $dn;
            }
        }

        // Generate DN
        $dn = null;
        do { 
            $dn = $this->create();
        } while ($dn === null);

        // Ask to save DN
        $this->cli->send("You may optionally save this distinguished name for future use by entering a desired alias below.  Leave this field blank and press Enter to not save the distinguished name.\r\n\r\n");
        $alias = $this->cli->getInput("Save as alias []: ");
        if ($alias != '') { 
            $this->store->save(strtolower($alias), $dn);
            $this->cli->send("\r\nSuccessfully saved distinguished name as '$alias'.\r\n\r\n");
        } else { 
            $this->cli->send("\r\nNot saving distinguished name.\r\n\r\n");
        }

        // Return
        return $dn;
    }

    /**
     * Get DNS from input
     */
    public function create(bool $send_header = true):?DistinguishedName
    {

        // Send header
        if ($send_header === true) { 
            $this->cli->send("Please answer the below questions as desired.  This information does not necessarily have to be legitimate,, but is how you will be identified throughout the network.\r\n\r\n");
            $this->cli->send("NOTE: Don't worry, you will only need to do this once, and never again.\r\n\r\n");
        }

        // Get input fields
        $country = $this->cli->getInput('Country Code [AU]: ', 'AU');
        $province = $this->cli->getInput('Province / State Name: ');
        $locality = $this->cli->getInput('City / Locality Name: ');
        $org_name = $this->cli->getInput('Organization / Full Name [Not Named]: ', 'Not Named');
        $org_unit = $this->cli->getInput('Organization Unit [Dev Team]: ', 'Dev Team');
        $email = $this->cli->getInput('E-Mail Address []: ');

        // Send details for review
        $this->cli->send("\r\nThe below details will be included in all commits and releases you publish to the network:\r\n\r\n");
        $this->cli->send("    $org_name ($org_unit)\r\n");
        $this->cli->send("    $locality, $province, $country\r\n");
        $this->cli->send("    $email\r\n\r\n");

        // Confirm details
        if ($this->cli->getConfirm('Is this correct?') !== true) { 
            $this->cli->send("Ok, it's not correct.  Regenerating details...\r\n\r\n");
            return null;
        }
        $this->cli->send("\r\n");

        // Get DN
        $dn = new DistinguishedName(
            country: $country, 
            province: $province, 
            locality: $locality, 
            org_name: $org_name, 
            org_unit: $org_unit, 
            email: $email
        );

        // Return
        return $dn;
    }

}

