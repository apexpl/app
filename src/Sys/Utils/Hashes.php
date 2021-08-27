<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Utils;

use Apex\Svc\{Db, Debugger};
use Apex\App\Exceptions\ApexHashesException;
use redis;

/**
 * Hashes
 */
class Hashes
{

    #[Inject(redis::class)]
    private redis $redis;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Debugger::class)]
    private ?Debugger $debugger = null;

    /**
     * Create option list
     */
    public function createOptions(
        string $hash_alias, 
        string | array $value = '', 
        string $form_field = 'select', 
        string $form_name = ''
    ):string {

        // Check hash
        if (!$json = $this->redis->hget('config:hash', $hash_alias)) { 
            throw new ApexHashesException("Hash does not exist, $hash_alias");
        } elseif (!$vars = json_decode($json, true)) { 
            throw new ApexHashesException("Unable to decode JSON for hash '$hash_alias', error: " . json_last_error());
        }

        // Go through all hash variables
        $html = '';
        foreach ($vars as $hkey => $hvalue) { 
            $hvalue = tr($hvalue);

            // Select list
            if ($form_field == 'select') { 
                $chk = $value == $hkey ? 'selected="selected"' : '';
                $html .= "<option value=\"$hkey\" $chk>$hvalue</option>";

            // Checkbox
            } elseif ($form_field == 'checkbox') { 
                $chk = (is_array($value) && in_array($hvalue, $value)) || ($value == $hvalue) ? 'checked="checked"' : '';
                $html .= "<input type=\"checkbox\" name=\"" . $form_name . "[]\" value=\"$hkey\" $chk> $hvalue<br />";
            // Readio list
            } elseif ($form_field == 'radio') { 
                $chk = $value == $hvalue ? 'checked="checked"' : '';
                $html .= "<input type=\"radio\" name=\"$form_name\" value=\"$hkey\" $chk> $hvalue<br />";
            }
        }

        // Debug, and return
        $this->debugger?->add(4, tr("Created hash options for the hash: {1}", $hash_alias));
        return $html;
    }

    /**
     * Get hash var
     */
    public function getVar(string $hash_alias, string $hkey):?string
    { 

        // Check hash
        if (!$json = $this->redis->hget('config:hash', $hash_alias)) { 
            throw new ApexHashesException("Hash does not exist, $hash_alias");
        } elseif (!$vars = json_decode($json, true)) { 
            throw new ApexHashesException("Unable to decode JSON for hash '$hash_alias', error: " . json_last_error());
        }

        // Debug, and return
        $this->debugger?->add(5, tr("Retrieved value of hash ariable from hash: {1}, key: {2}", $hash_alias, $hkey));
        return $vars[$hkey] ?? null;
    }

    /**
     * Parse data source
     */
    public function parseDataSource(
        string $data_source, 
        string $value = '', 
        string $form_field = 'select', 
        string $form_name = ''
    ):string {

        // Initialize
        $source = explode(".", $data_source);
        $this->debugger?->add(5, tr("Parsing hash data source, {1}", $data_source));

        // Hash
        if ($source[0] == 'hash') { 
            $hash_alias = $source[1] . '.' . $source[2];
            $html = $this->createOptions($hash_alias, $value, $form_field, $form_name);

        // Function
        } elseif ($source[0] == 'function') { 
            list($class_name, $func_name) = [$source[1], $source[2]];
            if (!class_exists($class_name)) { 
                throw new ApexHashesException("Unable to load data source '$data_source' as the class does not exist at, $class_name");
            }

            // Call function, get options
            $obj = $this->cntr->make($class_name);
            $html = $obj->$func_name($value);

        // Stdlist
        } elseif ($source[0] == 'stdlist') { 

            // Get class name
            $class_name = "\\Apex\\App\\Base\\Lists\\" . ucwords($source[1]) . "List";
            if (!class_exists($class_name)) { 
                throw new ApexHashesException("Unable to parse data source '$data_source' as source list does not exist at $class_name");
            }

            // Create HTML
            $html = '';
            foreach ($class_name::$opt as $abbr => $vars) { 
                $chk = $value == $abbr && $abbr != '' ? 'selected="selected"' : '';
                $html .= "<option value=\"$abbr\" $chk>$vars[name]</option>\n";
            }

        // Table
        } elseif ($source[0] == 'table') { 

            // Set variables
            $table_name = $source[1];
            $sort_by = $source[3] ?? 'name';
            $idcol = $source[4] ?? 'id';

            // Go through rows
            $html = '';
            $rows = $this->db->query("SELECT * FROM $source[1] ORDER BY $sort_by");
            foreach ($rows as $row) { 

                // Parse name
                $temp = $source[2] ?? '~name~';
                foreach ($row as $key => $val) { 
                    $temp = str_ireplace("~$key~", (string) $val, $temp); 
                }

                // Add to options
                $chk = $row[$idcol] == $value && $row[$idcol] != '' ? 'selected="selected"' : '';
                $html .= "<option value=\"$row[$idcol]\" $chk>$temp</option>\n";
            }
        }

        // Return
        return $html;
    }

    /**
     * Get list variable
     */
    public function getListVar(string $list, string $abbr, string $column = 'name'):?string
    { 

        // Get class
        $class_name = "\\Apex\\App\\Sys\\Lists\\" . ucwords($list) . "List";
        if (!class_exists($class_name)) { 
            throw new ApexHashesException("Standard list does not exist, $list");
        }

        // Get value
        $vars = $class_name::$opt[$abbr] ?? '';
        return $vars[$column] ?? null;
    }

}

