<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\App\Pkg\Helpers\PackageConfig;
use Apex\Opus\Builders\CrudBuilder;
use Symfony\Component\Yaml\Yaml;
use Apex\App\Pkg\Gpt\{GptForm, GptTable, GptModel};

/**
 * GPT - Admin menus
 */
class GptAdminMenus extends GptClient
{

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;

    #[Inject(CrudBuilder::class)]
    private CrudBuilder $crud_builder;

    #[Inject(GptForm::class)]
    private GptForm $gpt_form;

    #[Inject(GptTable::class)]
    private GptTable $gpt_table;

    #[Inject(GptModel::class)]
    private GptModel $gpt_model;

    /**
     * Generate
     */
    public function generate(string $pkg_alias, array $tables, array $hashes):array
    {

        // Determine menus to create
        $menu_tables = $this->determineMenus($pkg_alias, $tables);

        // Add menus to config
        $this->addMenus($pkg_alias, $menu_tables);

        // Generate iews with CRUD functionality
        $files = $this->generateCrud($pkg_alias, $menu_tables, $hashes);

        // Return
        return $files;
    }

    /**
     * Determine menus to generate
     */
    private function determineMenus(string $pkg_alias, array $tables):array
    {

        // Initialize
        $selected = [];
        $nums = [];
        $pkg_title = $this->convert->case($pkg_alias, 'phrase');

        // Send message
        $this->cli->sendHeader('Admin Panel Menus');
        $this->cli->send("Specify a comma delimited list (eg. 1,3,4) of the sub-menus you would like the new '$pkg_title' menu within the admin panel to contain.  Leave blank and press enter to accept the default shown below.\n\n");

        // GO through tables
        $x=1;
        foreach ($tables as $name => $type) {
            $selected[] = (string) $x;
            $this->cli->send("    [$x] " . $this->convert->case(str_replace($pkg_alias . '_', '', $name)) . "\n");
            $nums[(string) $x] = $name; 
        $x++; }
        $this->cli->send("\n");

        // Get input
        $sel = implode(", ", $selected);
        $input = $this->cli->getInput("Generate Menus [$sel]: ", $sel);

        // Get final menus
        $menus = [];
        foreach (explode(",", $input) as $x) {
            $num = trim((string) $x);
            $menus[] = $nums[$num];
        }

        // Return
        return $menus;
    }

    /**
     * Add menus to package config
     */
    private function addMenus(string $pkg_alias, array $menu_tables):void
    {

        // Load config
        $yaml = $this->pkg_config->load($pkg_alias);
        $menus = $yaml['menus'] ?? [];

        // Create sub-menus
        $submenus = [];
        $replace = str_replace("-", "_", $this->convert->case($pkg_alias, 'lower') . '_');
        foreach ($menu_tables as $table) {
            $table = str_replace($replace, '', $table);
            $alias = $this->crud_builder->applyFilter($table, 'single');
            $submenus[$alias] = $this->convert->case($table, 'phrase');
        }

        // Get a font awesome icon
        $icon_alias = $this->send("Give one and only one Font Awesome icon alias without additional text, commands or description that convets \"" . $this->convert->case($pkg_alias, 'phrase') ."\".  Only rpely with the icon alias, and nothing else.");

        // Add to config
        $name = 'admin_' . $this->convert->case($pkg_alias, 'lower') . '_menus';
        $menus[$name] = [
            'area' => 'admin',
            'position' => 'after users',
            'type' => 'parent',
            'icon' => 'fa fa-fw ' . $icon_alias,
            'alias' => str_replace('-', '_', $this->convert->case($pkg_alias, 'lower')),
            'name' => $this->convert->case($pkg_alias, 'phrase'),
            'menus' => $submenus
        ];
        $yaml['menus'] = $menus;

        // Save Yml file
        $filename = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title') . '/package.yml';
        file_put_contents($filename, Yaml::dump($yaml, 6));

        // Scan package.yml file
        $this->pkg_config->install($pkg_alias);
        echo "\nAdding menus to package configuration... done.\n";
    }

    /**
     * Generate views with CRUD functionality
     */
    private function generateCrud(string $pkg_alias, array $menu_tables, array $hashes):array
    {

        // Go through menus
        $files = [];
        foreach ($menu_tables as $table) {
            $alias = str_replace(str_replace('-', '_', $this->convert->case($pkg_alias, 'lower')) . '_', '', $table);
            $alias = $this->crud_builder->applyFilter($alias, 'single');

            // Get model and view filenames
            $filename = 'src/' . $this->convert->case($pkg_alias, 'title') . '/Models/' . $this->convert->case($alias, 'title') . '.php';
            $view = 'admin/' . str_replace('-', '_', $this->convert->case($pkg_alias, 'lower')) . '/' . $this->convert->case($alias, 'lower');

            // Create crud
            echo "Generating CRUD functionality for '$table'... ";
            $tmp_files = $this->crud_builder->build($filename, $table, $view, true, SITE_PATH, true); 
            array_push($files, ...$tmp_files);

            // Fix form / table classes
            $new_files = $this->fixOpusClasses($pkg_alias, $table, $tmp_files, $hashes);
            echo "done.\n";
        array_push($files, ...$new_files);
        }

        // Return
        return $files;
    }

    /**
     * Fix Opus classes
     */
    private function fixOpusClasses(string $pkg_alias, string $dbtable, array $tmp_files, array $hashes):array
    {

        // Get form / table classes
        $form_class = $this->getFilename('Opus/Forms/', $tmp_files);
        $table_class = $this->getFilename('Opus/DataTables/', $tmp_files);
        $controller_class = $this->getFilename('/Controllers/', $tmp_files);
        $model_class = $this->getModelByTable($pkg_alias, $dbtable);
        $new_files = [];

        // Form class
        $files = $this->gpt_form->initial($pkg_alias, $form_class, $controller_class, $dbtable, $hashes);
        array_push($new_files, ...$files);

        // Table class
        $this->gpt_table->initial($pkg_alias, $table_class, $dbtable, $hashes);

        // Model class
        if ($model_class !== null) {
            $this->gpt_model->addToDisplayArray($model_class);
        }

        // Return
        return $new_files;
    }

}

