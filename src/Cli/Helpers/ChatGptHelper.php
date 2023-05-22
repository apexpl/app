<?php
declare(strict_types=1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\{App, Convert, HttpClient, Db};
use Apex\App\Cli\Cli;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Pkg\Helpers\PackageConfig;
use Apex\Opus\Builders\CrudBuilder;
use Nyholm\Psr7\Request;
use Symfony\Component\Yaml\Yaml;

/**
 * ChatGTP Helper
 */
class ChatGptHelper
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(HttpClient::class)]
    private HttpClient $http;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Cli::class)]
    protected Cli $cli;

    #[Inject(CrudBuilder::class)]
    private CrudBuilder $crud_builder;

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;

    /**
     * Send API request to OpenAI
     */
    public function send(string $message):?string
    {

        // Generate JSON request
        $json = json_encode([
            'messages' => [[
                'content' => $message,
                'role' => 'user'
            ]],
            'max_tokens' => 3900,
            'temperature' => 0.5,
            'model' => 'gpt-3.5-turbo'
        ]);

        // Create http request
        $url = "https://api.openai.com/v1/chat/completions";
        $headers = [
            'Content-type' => 'application/json',
            'Authorization' =>  'Bearer ' . $this->app->config('core.openai_apikey')
        ];
        $req = new Request('POST', $url, $headers, $json);

        // Send the JSON request
        $res = $this->http->sendRequest($req);

        // Check response
        if (!$json = json_decode($res->getBody()->getContents(), true)) {
            $this->cli->error("Did not receive valid JSON as a response from OpenAI.  Received: " . $res->getBody()->getContents());
            return null;
        } elseif (isset($json['error'])) {
            $this->cli->error("OpenAI API gave an error: " . $json['error']['message']);
            return null;
        }

        // Return
        $message = $json['choices'][0]['message'];
        return $message['content'];
    }

    /**
     * Process SQL schema
    */
    public function processSqlSchema(LocalPackage $pkg, string $sql):array
    {

        // Save SQL files
        $pkg_alias = $pkg->getAlias();
        $table_names = $this->saveSqlFiles($pkg_alias, $sql);

        // Create drop sql
        $top_level_tables = $this->createDropSql($pkg_alias, $table_names);

        // Add menus
        $this->addMenus($pkg_alias, $top_level_tables);

        // Generate CRUD for top level tables
        $files = [];
        foreach ($top_level_tables as $table) {
            $alias = str_replace(str_replace('-', '_', $this->convert->case($pkg_alias, 'lower')) . '_', '', $table);
            $alias = $this->crud_builder->applyFilter($alias, 'single');
            $filename = 'src/' . $this->convert->case($pkg_alias, 'title') . '/Models/' . $this->convert->case($alias, 'title') . '.php';

            // Get view alias
            $view = 'admin/' . str_replace('-', '_', $this->convert->case($pkg_alias, 'lower')) . '/' . $this->convert->case($alias, 'lower');

            // Create crud
            $tmp_files = $this->crud_builder->build($filename, $table, $view, true, SITE_PATH, true); 
            array_push($files, ...$tmp_files);
        }

        // Return
        return $files;
    }

    /**
     * Save SQL files
     */
    private function saveSqlFiles(string $pkg_alias, string $sql):array
    {

        // Create DROP TABLE lines
        $table_names = [];
        preg_match_all("/CREATE TABLE (.+?)\s/i", $sql, $sql_match, PREG_SET_ORDER);
        foreach ($sql_match as $match) {
            $table_names[] = $match[1];
        }

        // Append to SQL files
        $etc_dir = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title');
        file_put_contents("$etc_dir/install.sql", "\n\n$sql\n", FILE_APPEND);

        // Execute SQL
        $this->db->executeSqlFile(SITE_PATH . '/chat_gpt.sql');

        // Return
        return $table_names;
    }

    /**
     * Create drop sql statement
     */
    private function createDropSql(string $pkg_alias, array $table_names):array
    {

        // Initialize
        $drop_tables = [];
        $top_level_tables = [];

        // GO through tables
        foreach ($table_names as $table) {

            // Check foreign keys
            $foreign_keys = $this->db->getForeignKeys($table);
            if (count($foreign_keys) == 0) {
            $top_level_tables[] = $table; 
                array_unshift($drop_tables, $table);
                continue;
            }

            // Get position of table name
            if (false === ($index = array_search($table, $drop_tables))) {
                array_unshift($drop_tables, $table);
                continue;
            }
            array_splice($drop_tables, $index, 0, $table);
        }

        // Create SQL
        $sql = '';
        foreach ($drop_tables as $table) {
            $sql .= "DROP TABLE IF EXISTS " . $table . ";\n";
        }

        // Save to SQL file
        $filename = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title') . '/remove.sql';
        file_put_contents($filename, "\n\n$sql\n\n", FILE_APPEND);

        // Return
        return $top_level_tables;
    }

    /**
     * Add menus
     */
    private function addMenus(string $pkg_alias, array $top_level_tables):void
    {

        // Load config
        $yaml = $this->pkg_config->load($pkg_alias);
        if (!isset($pkg_alias['menus'])) {
            $yaml['menus'] = [];
        }

        // Create sub-menus
        $submenus = [];
        $replace = str_replace("-", "_", $this->convert->case($pkg_alias, 'lower') . '_');
        foreach ($top_level_tables as $table) {
            $table = str_replace($replace, '', $table);
            $alias = $this->crud_builder->applyFilter($table, 'single');
            $submenus[$alias] = $this->convert->case($table, 'phrase');
        }

        // Add to config
        $name = 'admin_' . $this->convert->case($pkg_alias, 'lower') . '_menus';
        $yaml['menus'][$name] = [
            'area' => 'admin',
            'position' => 'after users',
            'type' => 'parent',
            'icon' => 'fa fa-fw fa-bricks',
            'alias' => str_replace('-', '_', $this->convert->case($pkg_alias, 'lower')),
            'name' => $this->convert->case($pkg_alias, 'phrase'),
            'menus' => $submenus
        ];

        // Save Yml file
        $filename = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title') . '/package.yml';
        file_put_contents($filename, Yaml::dump($yaml, 6));

        // Savn package.yml file
        $this->pkg_config->install($pkg_alias);
    }

}


