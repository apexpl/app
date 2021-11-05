<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Utils;

use Apex\Svc\{Db, Convert, App};
use Apex\App\Attr\Inject;

/**
 * Graph utils
 */
class GraphUtils
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(App::class)]
    private App $app;

    /**
     * Get total
     */
    public function getTotalPeriods(string $start_date, string $period = 'day'):int
    {

        // Get number of days
        $days = (int) $this->db->getField("SELECT datediff(date(now()), '$start_date')");

        // Evaluate periods as necessary
        $days = match($period) {
            'week' => ceil($days / 7),
            'month' => ceil($days / 30),
            'quarter' => ceil($days / 92),
            'year' => ceil($days / 365),
            default => $days
        };

        // Return
        return (int) $days;
    }

    /**
     * Get period sql
     */
    public function getPeriodSql(string $column, int $num, string $period, string $start_date = '', bool $is_graph = false):array
    {

        // Initialize
        if ($start_date == '') {
            $start_date = date('Y-m-d');
        }

        // Subtract necessary interval
        if ($num == 0) { 
            $date = $start_date;
        } elseif ($period == 'quarter') { 
            list($date, $time) = explode(' ', $this->db->subtractTime('month', ($num * 3), $start_date), 2);
        } else { 
            list($date, $time) = explode(' ', $this->db->subtractTime($period, $num, date('Y-m-d H:i:s')), 2);
        }

        // Break down date
        $year = $this->db->getField("SELECT YEAR('$date')");
        if ($period == 'year') { 
            $where_sql = "$column BETWEEN '" . $year . "-01-01 00:00:00' AND '" . $year . "-12-31 23:59:59'";
            $display_date = 'Y' . $year;
        } elseif ($period == 'quarter') { 
            $quarter = $this->db->getField("SELECT QUARTER('$date')");
            $month = (($quarter - 1) * 3) + 1;
            $last_day = $this->db->getField("SELECT DATE(LAST_DAY(" . $year . '_' . str_pad((string) ($month + 3), 2, '0', STR_PAD_LEFT) . "-01'))");

            $where_sql = "$column BETWEEN '" . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . "-01 00:00:00 AND '" . $year . '-' . str_pad((string) ($month + 3), 2, '0', STR_PAD_LEFT) . '-' . $last_day . " 23:59:59'";
            $display_date = 'Q' . $quarter . 'Y' . $year;

        } elseif ($period == 'month') { 
            $month = str_pad((string) $this->db->getField("SELECT MONTH('$date')"), 2, '0', STR_PAD_LEFT);
            $last_day = $this->db->getField("SELECT LAST_DAY('$date')");
            $where_sql = "$column BETWEEN '" . $year . '-' . $month . "-01 00:00:00' AND '" . $last_day . " 23:59:59'";

            $date_format = $is_graph === true ? 'M, y' : 'F, Y';
            $display_date = date($date_format, mktime(0, 0, 0, (int) $month, 1, (int) $year));

        } elseif ($period == 'week') { 
            $end_date = $this->db->getField("SELECT DATE(DATE_ADD('$date', interval 7 day))");
            $where_sql = "$column BETWEEN '$date 00:00:00' AND '$end_date 23:59:59'";
            $display_date = $this->convert->date($date);

        } else {
            $where_sql = "$column BETWEEN '$date 00:00:00' AND '$date 23:59:59'";

            if ($is_graph === true) { 
                list($year, $month, $day) = explode('-', $date, 3);
                $display_date = date('M-d', mktime(0, 0, 0, (int) $month, (int) $day, (int) $year));
            } else { 
                $display_date = $this->convert->date($date . ' 00:00:00');
            }
        }

        // Return
        return [$where_sql, $display_date, $date];
    }

    /**
     * Get periods html
     */
    public function getPeriodsHtml(string $uri = ''):string
    {

        // Get URI, if needed
        if ($uri == '') { 
            $uri = $this->app->getPath();
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



}



