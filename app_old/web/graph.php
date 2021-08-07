<?php
declare(strict_types = 1);

namespace apex\app\web;

use apex\app;
use apex\libc\{db, debug, redis, date};
use apex\app\exceptions\ApexException;


/**
 * Class that handles the various functionality to display 
 * graphical charts.
 */
class graph
{

    // Properties
    public array $graph_data = [];
    public array $labels = [];
    public string $border_color = 'white';
    public string $border_width = '0';
    public array $background_colors = ["#ffeb3b", "#63FF84", "#84FF63", "#8463FF", "#6384FF", "#ff5722", "#673ab7"];

    // Interval hash
    public static array $intervals = [
        'day' => 'D', 
        'week' => 'W', 
        'month' => 'M', 
        'quarter' => 'M', 
        'year' => 'Y'
    ];

/**
 * Add data set
 */
final public function add_dataset(array $data, string $label = ''):void
{

    // Add to data
    if ($label == '') { 
        $this->graph_data[] = $data;
    } else { 
        $this->graph_data[$label] = $data;
    }

}

/**
 * Set labels
 *
 * @param array $labels The labels to set for the graph.
 */
public function set_labels(array $labels):void
{
    $this->labels = $labels;
}

/**
 * Add single label.
 *
 * @param string $label The label to add.
 */
public function add_label(string $label):void
{
    $this->labels[] = $label;
}

/**
 * Get periods HTML
 *
 * @param string $uri Optional URI to use within links.  If blank, current page URI is used.
 *
 * @return string The resulting HTML of date periods.
 */
public static function get_periods_html(string $uri = ''):string
{

    // Get URI, if needed
    if ($uri == '') { 
        $uri = '/' . app::get_uri();
    }

    // Get HTML
    $html = "<p><b>View By:</b> ";
    $html .= "<a href=\"" . $uri . "?period=day\">Day</a> | ";
    $html .= "<a href=\"" . $uri . "?period=week\">Week</a> | "; 
    $html .= "<a href=\"" . $uri . "?period=month\">Month</a> | "; 
    $html .= "<a href=\"" . $uri . "?period=quarter\">Quarter</a> | ";
    $html .= "<a href=\"" . $uri . "?period=year\">Year</a></p>";

    // return
    return $html;

}

/**
 * Get total number of periods from a specific date.
 *
 * @param string $start_date The starting date to work from.
 * @param string $period The period to evaluate (day, week, month, quarter, year).
 *
 * @return int The total number of date periods available.
 */
public static function get_total_periods(string $start_date, string $period = 'day'):int
{

    // Get number of days
    $days = (int) db::get_field("SELECT datediff(date(now()), '$start_date')");

    // Evaluate periods as necessary
    if ($period == 'week') { $days = ceil($days / 7); }
    elseif ($period == 'month') { $days = ceil($days / 30); }
    elseif ($period == 'quarter') { $days = ceil($days / 92); }
    elseif ($period == 'year') { $days = ceil($days / 365); }

    // Return
    return (int) $days;

}

/**
 * Get period SQL
 *
 * @param string $column The database table column to use within the SQL.
 * @param int $num The number of periods to subtract from.
 * @param string $period The period being used (date, week, month, quarter, year)
 * @param string $start_date Optional start date of the result set as a whole.
 * @param bool $is_grapth Whether or not this is for a graphical chart, and if true changes the resulting display_date.
 *
 * @return array Two elements, one being the where SQL caluse, and the second being the display date.
 */
public static function get_period_sql(string $column, int $num, string $period, string $start_date = '', bool $is_graph = false):array
{

    // Initialize
    if ($start_date == '') { $start_date = date('Y-m-d'); }

    // Subtract necessary interval
    if ($num == 0) { 
        $date = $start_date;
    } elseif ($period == 'quarter') { 
        list($date, $time) = explode(' ', date::subtract_interval('M' . ($num * 3), $start_date), 2);
    } else { 
        list($date, $time) = explode(' ', date::subtract_interval(self::$intervals[$period] . $num), 2);
    }

    // Break down date
    $year = db::get_field("SELECT YEAR('$date')");
    if ($period == 'year') { 
        $where_sql = "$column BETWEEN '" . $year . "-01-01 00:00:00' AND '" . $year . "-12-31 23:59:59'";
        $display_date = 'Y' . $year;
    } elseif ($period == 'quarter') { 
        $quarter = db::get_field("SELECT QUARTER('$date')");
        $month = (($quarter - 1) * 3) + 1;
        $last_day = db::get_field("SELECT DATE(LAST_DAY(" . $year . '_' . str_pad((string) ($month + 3), 2, '0', STR_PAD_LEFT) . "-01'))");

        $where_sql = "$column BETWEEN '" . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . "-01 00:00:00 AND '" . $year . '-' . str_pad((string) ($month + 3), 2, '0', STR_PAD_LEFT) . '-' . $last_day . " 23:59:59'";
        $display_date = 'Q' . $quarter . 'Y' . $year;

    } elseif ($period == 'month') { 
        $month = str_pad((string) db::get_field("SELECT MONTH('$date')"), 2, '0', STR_PAD_LEFT);
        $last_day = db::get_field("SELECT LAST_DAY('$date')");
        $where_sql = "$column BETWEEN '" . $year . '-' . $month . "-01 00:00:00' AND '" . $last_day . " 23:59:59'";

        $date_format = $is_graph === true ? 'M, y' : 'F, Y';
        $display_date = date($date_format, mktime(0, 0, 0, (int) $month, 1, (int) $year));

    } elseif ($period == 'week') { 
        $end_date = db::get_field("SELECT DATE(DATE_ADD('$date', interval 7 day))");
        $where_sql = "$column BETWEEN '$date 00:00:00' AND '$end_date 23:59:59'";
        $display_date = fdate($date);

    } else {
        $where_sql = "$column BETWEEN '$date 00:00:00' AND '$date 23:59:59'";

        if ($is_graph === true) { 
            list($year, $month, $day) = explode('-', $date, 3);
            $display_date = date('M-d', mktime(0, 0, 0, (int) $month, (int) $day, (int) $year));
        } else { 
            $display_date = fdate($date . ' 00:00:00');
        }
    }

    // Return
    return array($where_sql, $display_date, $date);

}

}

