<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

/**
 * GPT - Controller
 */
class GptController extends GptClient
{

    /**
     * Modify controller and change 'uuid' to 'username'
 */
    public function changeUuidToUsername(string $controller_class):void
    {

        // Get code
        $code = file_get_contents(SITE_PATH . $controller_class);

        // Add use declaration
        $code = str_replace(
            "use Apex\\App\\Base\\Model\\ModelIterator;\n", 
            "use Apex\\App\\Base\\Model\\ModelIterator;\nuse App\\Users\\User;\nuse App\\Users\\Exceptions\\UserNotExistsException;\n", 
            $code
        );

        // Replace 'uuid' in create() method
        $uuid_search = preg_quote("'uuid' => \$post['uuid']");
        $code = preg_replace("/$uuid_search/", "'uuid' => \$post['user_id']", $code, 1);

        // GEt user when updating record
        $code = str_replace(
            "        // Update record\n",
            base64_decode('ICAgICAgICAvLyBHZXQgdXNlcgogICAgaWYgKCEkdXNlciA9IFVzZXI6OmxvYWRVc2VybmFtZSgkcG9zdFsndXNlcm5hbWUnXSkpIHsKICAgICAgICAgICAgdGhyb3cgbmV3IFVzZXJOb3RFeGlzdHNFeGNlcHRpb24oIk5vIHVzZXIgZXhpc3RzIHdpdGggdGhlIHVzZXJuYW1lLCAkcG9zdFt1c2VybmFtZV0iKTsKICAgICAgICB9CgogICAgICAgIC8vIFVwZGF0ZSByZWNvcmQK'),
            $code
        );

        // Replace uuid in update() method
        $code = preg_replace("/$uuid_search/", "'uuid' => \$user->getUuid()", $code, 1);

        // Save file
        file_put_contents(SITE_PATH . $controller_class, $code);
    }

}



