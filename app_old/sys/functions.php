<?php
declare(strict_types = 1);

use apex\app;
use apex\libc\{db, debug, date, redis, log, view};
use apex\app\sys\container;
use apex\app\exceptions\ApexException;


/**
 * Handle all exceptions
 *
 * @param Exception $e The exception being handled.
 */
function handle_exception($e)
{

    // ApexException
    if ($e instanceof ApexException) { 
        $e->process();

    // RPC timeout
    } elseif ($e instanceof PhpAmqpLib\Exception\AMQPTimeoutException || $e instanceof WebSocket\ConnectionException) { 

        // Log error
        debug::add(1, "The internal RPC / web socket server is down.  Please start by using the init script at /bootstrap/apex.", 'emergency');
        debug::finish_session();

        // Display template
        app::set_res_http_status(504);
        app::set_uri('504', true);
        echo view::parse();

    // Give standard error
    } else { 
        error(0, $e->getMessage(), $e->getFile(), $e->getLine());
    }

    // Exit
    exit(0);

}

/**
 * Error handler
 *
 * @param int $errno The error number.
 * @param string $message The error message
 * @param string $file The filename where the error occurred.
 * @param int $line The line number the error occurred on. 
 */
function error(int $errno, string $message, string $file, int $line) 
{
    $file = trim(str_replace(SITE_PATH, '', $file), '/');
    if (preg_match("/fsockopen/", $message)) { return; }
    if (preg_match("/Required parameter (.*?) follows optional parameter/", $message)) { return; }

    // Get level of log message
    if (in_array($errno, array(2, 32, 512))) { $level = 'warning'; }
    elseif (in_array($errno, array(8, 1024))) { $level = 'notice'; }
    elseif (in_array($errno, array(64, 128, 256, 4096))) { $level = 'error'; }
    elseif (in_array($errno, array(2048, 8192, 16384))) { $level = 'info'; }
    elseif (in_array($errno, array(1, 4, 16))) { $level = 'critical'; }
    else { $level = 'alert'; }

    // Add log
    log::add_system_log($level, 1, $file, $line, $message);

    // Finish session
    debug::finish_session();

// Check for command line usage
    if (php_sapi_name() == "cli") { 
        echo "ERROR: $message\n\nFile: $file\nLine: $line\n\n";
        exit(0);

    // JSON error
    } elseif (preg_match("/\/json$/", app::get_res_content_type())) { 
        $response = array(
            'status' => 'error', 
            'errmsg' => $message, 
            'file' => $file, 
            'line' => $line
        );
        echo json_encode($response);
        exit(0);
    }

    // Check if .tpl file exists
    $tpl_file = app::_config('core:mode') == 'devel' ? '500' : '500_generic'; 
    if (!file_exists(SITE_PATH . '/views/tpl/' . app::get_area() . '/' . $tpl_file . '.tpl')) {
        echo "<b>ERROR!</b>  We're sorry, but an unexpected error occurred preventing this software system from running.  Additionally, a 500.tpl template was not found.<br /><blockquote>\n";
        if (app::_config('core:mode') == 'devel') { 
            echo "<b>Message:</b> $message<br /><br />\n<b>File:</b> $file<br />\n<b>Line:</b> $line<br />\n";
        }
        exit(0);
    }

// Set registry variables
    app::set_res_http_status(500);
    app::set_uri($tpl_file, true);

    // Template variables
    view::assign('err_message', $message);
    view::assign('err_file', $file);
    view::assign('err_line', $line);

    // Parse template
    app::set_res_body(view::parse());
    app::Echo_response();

    // Exit
    exit(0);

}



/**
 * Format decimal into amount with correct currency 
 *
 * @param float $amount The decimal to format.
 * @param string $currency The 3 character ISO currency to format to.
 * @param bool $include_abbr Whether or not to add the 3 character ISO currency abbreviation.
 *
 * @return string The formatted amount.
 */
/**
 * Exchange funds into another currency, and return the formatted value of 
 * resulting amount. 
 *
 * @param float $amount The amount to exchange
 * @param string $from_currency The currency the amount is currently in
 * @param string $to_currency The currency to exchange the funds into
 *
 * @return string The resulting amount after exchange
 */
function fexchange(float $amount, string $from_currency, string $to_currency)
{ 

    // Exchange money
    $amount = exchange_money($amount, $from_currency, $to_currency);

    // Format and return
    return fmoney((float) $amount, $to_currency);

}

/**
 * Exchange funds into another currency. 
 *
 * @param float $amount The amount to exchange
 * @param string $from_currency The currency the amount is currently in
 * @param string $to_currency The currency to exchange the funds into
 *
 * @return float The resulting amount after exchange
 */
function exchange_money(float $amount, string $from_currency, string $to_currency)
{ 

    // Echange to base currency, if needed
    if ($from_currency != app::_config('transaction:base_currency')) { 
        $rate = db::get_field("SELECT current_rate FROM transaction_currencies WHERE abbr = %s", $from_currency);
        $amount *= $rate;
    }

    // Check for base currency
    if ($to_currency == app::_config('transaction:base_currency')) { 
        return $amount;
    }

    // Convert to currency
    $rate = db::get_field("SELECT current_rate FROM transaction_currencies WHERE abbr = %s", $to_currency);
    if ($rate == 0.00) { return 0; }
    return($amount / $rate);

}


/**
 * Format a data type
 *
 * @param mixed $value The value to format.
 * @param string $type The type to format it to (bool, int, float, string).
 *
 * @return mixed The newly formatted value.
 */
function ftype($value, string $type)
{

    // Format
    if ($type == 'bool') { $value = (bool) $value; }
    elseif ($type == 'int') { $value = (int) $value; }
    elseif ($type == 'float') { $value = (float) $value; }
    else { $value = (string) $value; }

    // Return
    return $value;

}


