<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\Svc\{Convert, Container, Db};

/**
 * Dashboard items
 */
class DashboardItems
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Db::class)]
    private Db $db;


    /**
     * Install
     */
    public function install(string $pkg_alias):void
    {

        // Check directory
        $dir_name = SITE_PATH . '/src/' . $this->convert->case($pkg_alias, 'title') . '/Opus/DashboardItems';
        if (!is_dir($dir_name)) {
            return;
        }

        // Go through items
        $files = scandir($dir_name);
        foreach ($files as $file) { 

            // Check for .php
            if (!preg_match("/^(.+)\.php$/", $file, $m)) {
                continue;
            }

            // Load class
            $class_name = "App\\" . $this->convert->case($pkg_alias, 'title') . "\\Opus\\DashboardItems\\" . $m[1];
            $obj = $this->cntr->make($class_name);

            // Check is_default
            $is_default = $obj->is_default ?? false;
            if ($is_default !== true) {
                continue;
            }

            // Add to existing profiles
            $profiles = $this->db->getColumn("SELECT id FROM dashboard_profiles WHERE area = %s", $obj->area);
            foreach ($profiles as $profile_id) {

                // Add to database
                $this->db->insert('dashboard_profiles_items', [
                    'profile_id' => $profile_id,
                    'type' => $obj->type,
                    'class_name' => $class_name
                ]);

            }

        }

    }

    /**
     * Remove
     */
    public function remove(string $pkg_alias):void
    {
        $namespace = "App\\" . $this->convert->case($pkg_alias, 'title') . "\\Opus\\DashboardItems";
        $this->db->query("DELETE FROM dashboard_profiles_items WHERE class_name LIKE %ls", $namespace);
    }

}


