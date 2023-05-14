<?php
declare(strict_types=1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\{App, Convert, HttpClient};
use Apex\App\Cli\Cli;
use Apex\App\Network\Models\LocalPackage;
use Nyholm\Psr7\Request;

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

    #[Inject(Cli::class)]
    protected Cli $cli;

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
    public function processSqlSchema(LocalPackage $pkg, string $sql)
    {

        // Save SQL files
        $pkg_alias = $pkg->getAlias();
        $table_names = $this->saveSqlFiles($pkg_alias, $sql);

print_r($table_names); exit;
    }

    /**
     * Save SQL files
     */
    private function saveSqlFiles(string $pkg_alias, string $sql):array
    {

        // Create DROP TABLE lines
        $drop_sql = '';
        $table_names = [];
        preg_match_all("/CREATE TABLE (.+?)\s/i", $sql, $sql_match, PREG_SET_ORDER);
        foreach ($sql_match as $match) {
            $drop_sql .= "DROP TABLE IF EXISTS " . $match[1] . ";\n";
            $table_names[] = $match[1];
        }

        // Append to SQL files
        $etc_dir = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title');
        file_put_contents("$etc_dir/install.sql", "\n\n$sql\n", FILE_APPEND);
        file_put_contents("$etc_dir/remove.sql", "\n\n$drop_sql\n", FILE_APPEND);

        // Return
        return $table_names;
    }

}


