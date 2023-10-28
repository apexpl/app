<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\Svc\{App, HttpClient, Convert, Db, Container};
use Apex\App\Cli\Cli;
use Apex\App\Network\Stores\PackagesStore;
use Nyholm\Psr7\Request;

/**
 * GPT Client
 */
abstract class GptClient
{

    #[Inject(App::class)]
    protected App $app;

    #[Inject(Cli::class)]
    protected Cli $cli;

    #[Inject(HttpClient::class)]
    protected HttpClient $http;

    #[Inject(Container::class)]
    protected Container $cntr;

    #[Inject(Convert::class)]
    protected Convert $convert;

    #[Inject(Db::class)]
    protected Db $db;

    #[Inject(PackagesStore::class)]
    protected PackagesStore $pkg_store;

    // Component types
    protected array $component_types = [
        'auto-complete',
        'form',
        'table',
        'graph',
        'tab-control',
        'tab-page',
        'box',
        'foreach loop',
        'paragraph describing page'
    ];

    // View types
    protected array $view_types_multi = [
        '1' => 'List all items in a foreach loop, allowing for more flexible design',
        '2' => 'List all items within a data table restricting design',
        '3' => 'List all items within a data table, plus add creation form at the bottom',
        '4' => 'Tab control with one tab page for each option within an enum column listing all items of each value.',
        '5' => 'Unknown, blank page.'
    ];

    protected array $view_types_single = [
        '6' => 'Page displaying all details on single item.',
        '7' => 'Page with HTML form of item',
        '8' => 'Unknown, blank page.'
    ];

    /**
     * Get prompt
     */
    public function getPrompt(string $message = ''):string
    {

        // Get message
        if ($message == '') {
            $message = "In plain text, define the functionality you would like developed.  This will automatically generate the necessary database schema, models, views, menus, and controllers\n\n";
        }

        // Send message
        $this->cli->send("$message.\n\n");
        $this->cli->send("Once done, enter a single period on a new line and press enter to start the code generation.\n\n");

        // Get input
        $prompt = $this->cli->getInput("What are you developing? ");
        do {
            $line = $this->cli->getInput("");
            if (trim($line) == '.') {
                break;
            }
            $prompt .= "\n\n" . $line;
        } while (true);

        // Return
        return $prompt;
    }

    /**
     * Send API request to OpenAI
     */
    public function send(string $message, array $chat = []):?string
    {

        // Add message to chat, if needed
        if ($message != '') {
            $chat[] = ['role' => 'user', 'content' => $message];
        }

        // Generate JSON request
        $json = json_encode([
            'messages' => $chat,
            'max_tokens' => 3900,
            'temperature' => 0.5,
            'model' => 'gpt-3.5-turbo-16k'
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
     8 Initialize chat
     * Initialize chat
     */
    public function initChat(string $pkg_alias,  ?string $description = null):array
    {

        // Get description, if needed
        if ($description === null) {

            // Get package
            if (!$pkg = $this->pkg_store->get($pkg_alias)) {
                throw new \Exception("Package does not exist with alias, $pkg_alias");
            }
            $description = $pkg->getGptDescription();
        }

        // Get system message
        $sys_message = "Conversation is read by a machine, not a human.  All responses should omit all descriptions, text, comments, and only provide the code / list items asked for.";
        if ($description !== null) {
            $sys_message .= "  Answers should be in context of developing the software system:\n\n$description\n";
        }

        // Return
        return [
            ['role' => 'system', 'content' => $sys_message]
        ];
    }

    /**
     * Get filename
     */
    public function getFilename(string $search_text, array $files):?string
    {

        // Search
        $result = array_filter($files, function ($file) use ($search_text) { return str_contains($file, $search_text); });
        $res = count($result) > 0 ? '/' . reset($result) : null;

        // Return
        return $res;
    }

    /**
     * Get item description
     */
    public function getItemDescription(string $dbtable, bool $add_tildes = false):array
    {

        // Get columns
        $cols = $this->db->getColumnDetails($dbtable);
        $column_names = implode("\n", array_keys($cols));

        // Get columns to display in select list
        $item_desc = $this->send("Generate a one-line summarized item name by using the database table columns below.  Select one or more columns if it's feasible to combine multiple, and return a one-line description that will be displayed within a html select list.  Do not include any additional text, comments or description, only respond with the one line summarized name.  For example, just \"name\" or \"#id - name\" if 'id' and 'name' are columns, or \"city, state, country\" if 'city', 'state' and 'country' are columns.  Columns are:\n\n$column_names\n");
        $sort_by = $this->send("Using the below database table columns, select one column that is best suited to sort records by for display (ie. alphabetical order).  Only reply with the column name with no additional text, description or comments.\n\n$column_names\n");

        // Add tildes, if needed
        if ($add_tildes === true) {
            foreach (array_keys($cols) as $col) {
                $item_desc = str_replace($col, "~$col~", $item_desc);
            }
        }

        // Return
        return [$item_desc, $sort_by];
    }

    /**
     * Get package table names
     */
    public function getPackageTableNames(string $pkg_alias):array
    {

        // Go through install.sql file
        $install_file = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title') . '/install.sql';
        $install_sql = file_get_contents($install_file);

        // Get tables
        $tables = [];
        preg_match_all("/CREATE TABLE (.+?)\s/i", $install_sql, $match, PREG_SET_ORDER);
        foreach ($match as $m) {
            $tables[] = trim(trim($m[1], "`"), "'");
        }

        // Return
        return $tables;
    }

    /**
     * Get database table
     */
    public function getDatabaseTable(string $pkg_alias, string $item_name, ?array $chat = null):string
    {

        // Get table names
        $tables = $this->getPackageTableNames($pkg_alias);
        if ($chat === null) {
            $chat = $this->initChat($pkg_alias);
        }

        // Determine table
        $chk = $this->send("Using teh context the package name '$pkg_alias' above and the database table names below, select one database the followin item pertains to:\n\n$item_name\n\nThe database table names are:\n\n" . implode("\n", $tables) . "\n\nRespond with only the name of one database table, and if unable to determine the property table name reply with only 'unknown'.", $chat);
        if (in_array(trim($chk), $tables)) {
            return trim($chk);
        }

        // Create options
        $options = [];
        foreach ($tables as $table) { 
            $options[$table] = $table;
        }

        // Get table
        $this->cli->send("Unable to determine database table of the operation: $item_name\n\n");
        $dbtable = $this->cli->getOption("Please select one of the database tables below that pertains to this operation:", $options, '', true);
        return $dbtable;
    }

    /**
     * Get model class by dbtable
     */
    public function getModelByTable(string $pkg_alias, string $dbtable):?string
    {

        // Scan directory
        $pkg_title = $this->convert->case($pkg_alias, 'title');
        $files = scandir(SITE_PATH . "/src/$pkg_title/Models/");

        // Go through files
        $dbtable_class = null;
        foreach ($files as $file) {

            // Check for .php
            if (!preg_match("/\.php$/", $file)) {
                continue;
            }

            // Load class
            $class_name = "App\\$pkg_title\\Models\\" . preg_replace("/\.php$/", "", $file);
            if (!class_exists($class_name)) {
                continue;
            }
            $obj = new \ReflectionClass($class_name);

            // Chck dbtable property
            if (!$prop = $obj->getProperty('dbtable')) {
                continue;
            } elseif ($prop->getDefaultValue() != $dbtable) {
                continue;
            }

            // Set class
            $dbtable_class = $class_name;
            break;
        }

        // Return
        return $dbtable_class;
    }

}


