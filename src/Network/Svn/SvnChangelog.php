<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\App\Cli\Cli;
use Apex\App\Network\Svn\SvnRepo;
use Apex\App\Exceptions\ApexSvnRepoException;

/**
 * Changelog
 */
class SvnChangelog
{

    #[Inject(Cli::class)]
    private Cli $cli;

    /**
     * Get changelog between two releases
     */
    public function get(SvnRepo $svn, string $old_version, string $new_version = 'latest'):?array
    {

        // Get latest version, if needed
        if ($new_version == 'latest' && null === ($new_version = $svn->getLatestRelease())) {
            return null;
        }

        // Get releases
        $releases = $svn->getReleases();
        if (!in_array($old_version, $releases)) { 
            $this->cli->error("The package '$pkg_alias' does not have the release v$old_version");
            return null;
        } elseif (!in_array($new_version, $releases)) { 
            $this->cli->error("The package '$pkg_alias' does not have the release v$new_version");
            return null;
        }

        // Set svn target
        $svn->setTarget('tags/' . $old_version, 0, false, false);
        $svn_url = $svn->getSvnUrl('tags/' . $new_version);

        // Get change log
        $is_ssh = false;
        if (!$res = $svn->exec(['diff'], [$svn_url, '--summarize'])) { 
            $is_ssh = true;
            $svn->setTarget('tags/' . $old_version, 0, false, true);
            $res = $svn->exec(['diff'], [$svn->getSvnUrl('tags/' . $new_version, false), '--summarize']);
        }

        // Check for response
        if (!$res) { 
            throw new ApexSvnRepoException("Unable to retrieve change log of package, " . $svn->getPackage()->getAlias() . ", error: " . $svn->error_output);
        }
        $lines = explode("\n", $res);

        // Start results
        $result = [
            'updated' => [],
            'deleted' => []
        ];

        // Go through lines
        foreach ($lines as $line) { 

            if (!preg_match("/^(\w)\s(.+?)\/tags\/$old_version\/(.*)$/", trim($line), $m)) { 
                continue;
            }

            // Add to results
            $type = strtoupper($m[1]) == 'D' ? 'deleted' : 'updated';
            if ($type == 'updated') { 
                $svn->setTarget('tags/' . $new_version . '/' . $m[3], 0, false, $is_ssh);
                $info = $svn->info();
                if ($info['node_kind'] != 'file') { 
                    continue;
                }
            }
            $result[$type][] = $m[3];
        }

        // Return
        return $result;
    }

}


