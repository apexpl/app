<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\App\Network\Models\LocalPackage;
use Apex\App\Exceptions\ApexInvalidArgumentException;

/**
 * SVN File Converter
 */
class SvnFileConverter
{

    /**
     * SVN to load
     */
    public function toLocal(LocalPackage $pkg, string $svn_file):string
    {

        // Parse dir
        $parts = explode('/', ltrim($svn_file, '/'));
        $type = array_shift($parts);

        // Get local file
        $local_file = match(true) { 
            (in_array($type, ['src','etc','docs','tests'])) ? true : false => $type . '/' . $pkg->getAliasTitle() . '/' . implode('/', $parts), 
            ($type == 'views') ? true : false => $svn_file, 
            ($type == 'ext') ? true : false => implode('/', $parts), 
            ($type == 'share' && $parts[0] == 'HttpControllers') ? true : false => 'src/HttpControllers/' . $parts[1], 
            default => null
        };

        // Check for null
        if ($local_file === null) { 
            throw new ApexInvalidArgumentException("Unable to convert SVN filename to local, $svn_file");
        }

        // return
        return $local_file;
    }

    /**
     * to SVN
     */
    public function toSvn(LocalPackage $pkg, string $local_file, bool $is_registry = false):string
    {

        // Parse file
        $parts = explode('/', $local_file);
        $type = array_shift($parts);

        // Get svn file
        if ($type == 'src' && $parts[0] == 'HttpControllers') { 
            $svn_file = 'share/HttpControllers/' . $parts[1];
        } elseif (in_array($type, ['src','etc','tests','docs'])) { 
            array_shift($parts);
            $svn_file = $type . '/' . implode('/', $parts);
        } elseif ($type == 'views' && $is_registry === true) { 
            $svn_file = $local_file;
        } else { 
            $svn_file = 'ext/' . $local_file;
        }

        // return
        return $svn_file;
    }

}

