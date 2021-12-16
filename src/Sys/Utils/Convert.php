<?php
declare(strict_Types = 1);

namespace Apex\App\Sys\Utils;

use Apex\Svc\{App, Db};
use Symfony\Component\String\UnicodeString;
use Apex\App\Attr\Inject;
use DateTime;

/**
 * Converter
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
     * Translate string to language, and merge placeholders.
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
     * Format currency
     */
    public function money(float $amount, string $currency = '', bool $include_abbr = true):string
    { 

        // Use default currency, if none specified
        if ($currency == '') { 
            $currency = $this->app->config('core.base_currency', 'USD');
        }

        // Get currency details
        $symbol = $this->app->getClient()->getCurrencySymbol();
        $decimals = $this->app->getClient()->getCurrencyDecimals();
        $is_crypto = $this->app->getClient()->getCurrencyIsCrypto();

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
     * Convert case
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
     * Last seen
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
     * Exchange money
     */
    public function exchange_money(float $amount, string $from_currency, string $to_currency, ?DateTime $date = null): ?float
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
            $amount *= $rates[$from_currency];
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

        // Return
        return($amount / $rate);
    }

}


