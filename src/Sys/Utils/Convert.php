<?php
declare(strict_Types = 1);

namespace Apex\App\Sys\Utils;

use Apex\Svc\{App, Db};
use Apex\App\Base\Lists\CurrencyList;   
use Symfony\Component\String\UnicodeString;
use Apex\App\Attr\Inject;
use DateTime;

/**
 * Convert naming convensions, amounts, dates, etc.
 */
class Convert
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Db::class)]
    private Db $db;

    // Properties
    private ?array $rates = null;

    /**
     * Translate string to active language
     *
     * @param string $text The English text to translate.
     * @param variadic ...$args Any necessary values to use to replace placeholders within the text.
    * 
     * @return Translated string.
     */
    public function tr(string $text, ...$args):string
    { 

        // Initialize
        if (isset($args[0]) && is_array($args[0])) { 
            $args = $args[0]; 
        }

        // Translate text, if available
        $lang = $this->app->getClient()->getLanguage();
        if ($lang != 'en' && $row = $this->db->getRow("SELECT * FROM internal_translations WHERE language = %s AND md5hash = %s", $lang, md5($text))) { 
            if ($row['contents'] != '') { 
                $text = base64_decode($row['contents']); 
            }
        }

        // Go through args
        list($x, $replace) = [1, []];
        foreach ($args as $key => $value) {
            if (is_array($value)) { continue; }

            if (($pos = strpos($text, "%s")) !== false) { 
                $text = substr_replace($text, (string) $value, $pos, 2);
            }

            if (is_string($key)) { $replace['{' . $key . '}'] = $value; }
            $replace['{' . $x . '}'] = filter_var($value);
        $x++; }

        // Return
        return strtr($text, $replace);
    }

    /**
     * Format date
     *
     * @param string|DateTIme $date The raw date to format, either in YYYY-MM-DD HH:II:SS format or instance of DateTime.
     * @param bool $add_time Whether or not to include time with the result.
     *
     * @return The formatted date / time.
     */
    public function date(string | DateTime $date, bool $add_time = false):string
    { 

        // Convert to datetime, if needed
        if (is_string($date)) { 
            $date = new DateTime($date);
        }

        // Get timezone data
        $offset = ($this->app->getClient()->getTimezoneOffset() * 60);
        $dst = $this->app->getClient()->getTimezoneDst();

        // Format date
        if (!$format = $this->app->config('core.date_format')) {
            $format = 'F j, Y';
        }
        $new_date = date($format, ($date->getTimestamp() + $offset));

        // Add time, if needed
        if ($add_time === true) { 
            $new_date .= ' at ' . date('H:i', ($date->getTimestamp() + $offset));
        }

        // Return
        return $new_date;
    }

    /**
     * Format amount.
     *
     * @param float|string $amount The amount to format.
     * @param string $currency Currency code to format amount to.  If blank, will use authenticated user's profile currency.
     * @param bool $include_abbr   Whether or not to include the three letter currency code in result.
     * @param bool $is_crypt Whether or not amount is a crypto-currency.
     *
     * @return The formatted amount.
     */
    public function money(float | string $amount, string $currency = '', bool $include_abbr = true, bool $is_crypto = false):string
    { 

        // Use default currency, if none specified
        if ($currency == '') { 
            $currency = $this->app->config('core.default_currency', 'USD');
        }

        // Get currency details
        if (!isset(CurrencyList::$opt[$currency])) {
            $symbol = '';
            $decimals = 8;
            $is_crypto = true;
        } else {
            $symbol = CurrencyList::$opt[$currency]['symbol'];
            $decimals = CurrencyList::$opt[$currency]['decimals'];
            $is_crypto = false;
        }

        // Format crypto currency
        if ($is_crypto === true) { 

            $amount = preg_replace("/0+$/", "", sprintf("%.8f", $amount));
            $length = strlen(substr(strrchr($amount, "."), 1));
            if ($length < 4) { 
                $amount = sprintf("%.4f", $amount);
                $length = 4;
            }

            // Format amount
            $name = number_format((float) $amount, (int) $length);
            if ($include_abbr === true) {
                $name .= ' ' . $currency;
            }

            // Return
            return $name;
        }

        // Format standard currency
        $name = $symbol . number_format((float) $amount, $decimals);
        if ($include_abbr === true) { 
            $name .= ' ' . $currency; 
        }
        return $name;
    }



    /**
     * Convert naming convention
     *
     * @param string $word The word / phrase to convert
     * @param string $case Naming convention to convert to.  Supported values are -- lower, upper, title, camel, phrase
     *
     * @return The converted word / string.
     */
    public function case(string $word, string $case = 'title'):string
    {

        // Get new case
        $word = new UnicodeString($word);
        $word = match ($case) { 
            'camel' => $word->camel(), 
            'title' => $word->camel()->title(), 
            'lower' => strtolower(preg_replace("/(.)([A-Z][a-z])/", '$1_$2', (string) $word)),
            'upper' => strtoupper(preg_replace("/(.)([A-Z][a-z])/", '$1_$2', (string) $word)), 
            'phrase' => ucwords(strtolower(preg_replace("/(.)([A-Z][a-z])/", '$1 $2', (string) $word->camel()))), 
            default => $word
        };

        // Return
        return (string) $word;
    }

    /**
     * Format seconds into presentable last seen string.
     *
     * @param string $secs Number of seconds to convert into last seen string.
     * 
     * @return The converted last seen string.
     */
    public function lastSeen(int $secs):string
    {

        // Initialize
        $seen = 'Unknown';
        $orig_secs = $secs;
        $secs = (time() - $secs);

        // Check last seen
        if ($secs < 20) {
            $seen = 'Just Now';
        } elseif ($secs < 60) {
            $seen = $secs . ' secs ago';
        } elseif ($secs < 3600) {
            $mins = floor($secs / 60);
            $seen = $mins . ' mins ' . ($secs - ($mins * 60)) . ' secs ago';
        } elseif ($secs < 86400) { 
            $hours = floor($secs / 3600);
            $mins = floor(($secs - ($hours * 3600)) / 60);
            $seen = $hours . ' hours ' . $mins . ' mins ago';
        } else { 
            $seen = date('D M dS H:i', $orig_secs);
        }

        // Return
        return $seen;
    }

    /**
     * Exchange money to another currency.
     *
     * @param float|string $amount The amount to convert.
     * @param string $from_currency Three letter currency code of the amount being converted.
     * @param string $to_currency Three letter currency code to convert amount to.
     * @param DateTime $date Optional DateTIme, and if present will use exchange rate from that time.
     *
     * @return The converted amount.
     */
    public function exchangeMoney(float | string $amount, string $from_currency, string $to_currency, ?DateTime $date = null):mixed
    { 

        // Check for same currency
        if ($from_currency == $to_currency) {
            return $amount;
        }

        // Get rates, if needed
        if ($date !== null) {
            $rates = $this->db->getHash("SELECT abbr,rate FROM transaction_rates WHERE created_at < %s ORDER BY created_at DESC LIMIT 1", $date->format('Y-m-d H:i:s'));
        } elseif ($this->rates === null) {
            $this->rates = $this->db->getHash("SELECT abbr,current_rate FROM transaction_currencies");
            $rates = $this->rates;
        } else {
            $rates = $this->rates;
        }

        // Exchange to base currency, if needed
        if ($from_currency != $this->app->config('core.default_currency')) {
            if (is_string($amount)) {
                $amount = bcmul($amount, $rates[$from_currency], 8);
            } else {
                $amount *= $rates[$from_currency];
            }
        }

        // Check for base currency
        if ($to_currency == $this->app->config('core.default_currency')) {
            return $amount;
        }

        // Convert to currency
        $rate = $rates[$to_currency];
        if ($rate == 0.00000000) {
            return null;
        }

        // Exchange currency
        if (is_string($amount)) {
            $amount = bcdiv($amount, $rate, 8);
        } else {
            $amount /= $rate;
        }

        // Return
        return $amount;
    }

    /**
     * Date interval
     *
     * @param string $interval The raw date interval (eg M1, W2, Y5, etc.)
     */
    public function dateInterval(string $interval):array
    {

        // Check format
        if (!preg_match("/^(\w)(\d+)$/", $interval, $m)) {
            throw new ApexInvalidDateIntervalException("Invalid interval, $interval.  Must be in format WDD");
        }
        $period = strtolower($m[1]);

        // Set intervals
        $names = [
            's' => 'second',
            'i' => 'minute',
            'h' => 'hour',
            'd' => 'day',
            'w' => 'week',
            'm' => 'month',
            'q' => 'quarter',
            'y' => 'year'
        ];

        // Check name
        if (!isset($names[$period])) {
            throw new ApexInvalidDateIntervalException("Invalid date interval period, $period.  Supported values are: s, i, h, d, w, m, q, y");
        }

        // Return
        return [$names[$period], (int) $m[2]];
    }

    /**
     * Full name
     */
    public function full_name(array $post = []): array
    {

        // Check post
        if (count($post) == 0) {
            $post = $this-app->getAllPost();
        }

        // Check for first and last name
        $first_name = $post['first_name'] ?? '';
        $last_name = $post['last_name'] ?? '';
        if ($first_name != '' && $last_name != '') {
            return [$first_name, $last_name];
        }

        // Check full name
        $full_name = $post['full_name'] ?? '';
        if ($full_name == '') {
            return ['', ''];
        }

        // Parse full name
        if (str_contains($full_name, ' ')) {
            list($first_name, $last_name) = explode(' ', $full_name, 2);
        } else {
            $first_name = $full_name;
        }


        // Return
        return [$first_name, $last_name];
    }

}


