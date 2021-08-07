
public function setup_test(string $uri, string $method = 'GET', array $post = [], array $get = [], array $cookie = [])
{

    // Set URI
    self::$uri_locked = false;
    self::set_uri($uri);
    self::$uri_original = $uri;

    // Set other input variables
    self::$method = $method;
    self::$reqtype = 'test';
    self::$verified_2fa = false;

    // Set request
    self::override_request($post, $get, $cookie);

    // Ensure all POST / GET variables are strings
    foreach (self::$post as $key => $value) { 
        if (is_array($value)) { continue; }
        self::$post[$key] = (string) $value; 
    }
    foreach (self::$get as $key => $value) { 
        if (is_array($value)) { continue; }
        self::$get[$key] = (string) $value; 
    }

    // Reset needed objects
    view::reset();
    self::$event_queue = [];

}

public static function override_request(array $post = [], array $get = [], array $cookie = [], array $files = []):void
{
    // Set other input variables
    self::$post = $post;
    self::$get = $get;
    //self::$cookie = $cookie;
    //self::$files = $files;
    self::$action = self::$post['submit'] ?? '';

}


public static function get_tzdata(string $timezone = '')
{

    // Check for no redis
    if (!GetEnv('redis_host')) { return array(0, 0); }
    if ($timezone == '') { $timezone = self::$timezone; }

    // Get timezone from db
    if (!$value = redis::hget('std:timezone', $timezone)) {
        return array(0, 0);
    }
    $vars = explode("::", $value);

    // Return
    return array($vars[1], $vars[2]);

}


public static function get_currency_data(string $currency):array
{

    // Get currency data
    if (!$data = redis::hget('std:currency', $currency)) {

        // Check for crypto
        if (!redis::sismember('config:crypto_currency', $currency)) {
            throw new ApexException('critical', "Currency does not exist in database, {1}", $currency);
        }

        // Return
        $vars = array(
            'symbol' => '',
            'decimals' => 8,
            'is_crypto' => 1
        );
        return $vars;
    }
    $line = explode("::", $data);

    // Set vars
    $vars = array(
        'symbol' => $line[1],
        'decimals' => $line[2],
        'is_crypto' => 0
    );

    // Return
    return $vars;

}


