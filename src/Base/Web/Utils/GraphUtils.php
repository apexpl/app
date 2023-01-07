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
        if (str_ends_with($this->db::class, 'PostgreSQL')) {
            $days = (int) $this->db->getField("SELECT date(now()) - '$start_date'");
        } else {
            $days = (int) $this->db->getField("SELECT datediff(date(now()), '$start_date')");
        }

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
        if (str_ends_with($this->db::class, 'PostgreSQL')) {
            $year = $this->db->getField("SELECT extract(YEAR FROM TIMESTAMP '$date')");
        } else {
            $year = $this->db->getField("SELECT YEAR('$date')");
        }
        if ($period == 'year') { 
            $where_sql = "$column BETWEEN '" . $year . "-01-01 00:00:00' AND '" . $year . "-12-31 23:59:59'";
            $display_date = 'Y' . $year;
        } elseif ($period == 'quarter') { 

            if (str_ends_with($this->db::class, 'PostgreSQL')) {
                $quarter = $this->db->getField("SELECT extract(QUARTER FROM TIMESTAMP '$date')");
                $month = (($quarter - 1) * 3) + 1;
            $last_date = $year . '_' . str_pad((string) ($month + 3), 2, '0', STR_PAD_LEFT) . '-01';
                $last_day = $this->db->getField("SELECT (date_trunc('month', '$last_date'::date) + interval '1 month' - interval '1 day')::date");
                $last_day = $this->db->getField("SELECT extract(DAY FROM TIMESTAMP '$last_day')");
            } else {
                $quarter = $this->db->getField("SELECT QUARTER('$date')");
                $month = (($quarter - 1) * 3) + 1;
                $last_day = $this->db->getField("SELECT DATE(LAST_DAY(" . $year . '_' . str_pad((string) ($month + 3), 2, '0', STR_PAD_LEFT) . "-01'))");
            }
            $where_sql = "$column BETWEEN '" . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . "-01 00:00:00 AND '" . $year . '-' . str_pad((string) ($month + 3), 2, '0', STR_PAD_LEFT) . '-' . $last_day . " 23:59:59'";
            $display_date = 'Q' . $quarter . 'Y' . $year;

        } elseif ($period == 'month') { 

            if (str_ends_with($this->db::class, 'PostgreSQL')) {
                $month = str_pad((string) $this->db->getField("SELECT extract(MONTH FROM TIMESTAMP '$date')"), 2, '0', STR_PAD_LEFT);
                $last_day = $this->db->getField("SELECT (date_trunc('month', '$date'::date) + interval '1 month' - interval '1 day')::date");
                //$last_day = $this->db->getField("SELECT extract(DAY FROM TIMESTAMP '$last_day')");
            } else {
                $month = str_pad((string) $this->db->getField("SELECT MONTH('$date')"), 2, '0', STR_PAD_LEFT);
                $last_day = $this->db->getField("SELECT LAST_DAY('$date')");
            }
            $where_sql = "$column BETWEEN '" . $year . '-' . $month . "-01 00:00:00' AND '" . $last_day . " 23:59:59'";

            $date_format = $is_graph === true ? 'M, y' : 'F, Y';
            $display_date = date($date_format, mktime(0, 0, 0, (int) $month, 1, (int) $year));

        } elseif ($period == 'week') { 
            list($end_date, $end_time) = explode(' ', $this->db->addTime('day', 7, $date), 2);
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
            $uri = $this->app->getPath() . '?' . http_build_query($this->app->getAllGet());
        }
        if (!str_contains($uri, '?')) {
            $uri .= '?';
        }

        // Get HTML
        $html = "<p><b>View By:</b> ";
        $html .= "<a href=\"" . $uri . "&period=day\">Day</a> | ";
        $html .= "<a href=\"" . $uri . "&period=week\">Week</a> | "; 
        $html .= "<a href=\"" . $uri . "&period=month\">Month</a> | "; 
        $html .= "<a href=\"" . $uri . "&period=quarter\">Quarter</a> | ";
        $html .= "<a href=\"" . $uri . "&period=year\">Year</a></p>";

        // return
        return $html;
    }

    /**
     * Get intervals html
     */
    public function getIntervalsHtml(string $selected = 'D3', string $uri = ''):string
    {

        // Set periods
        $periods = ['H1', 'H3', 'H24', 'D3', 'W1', 'W2', 'M1', 'M3', 'M6', 'Y1', 'Y3', 'Y10'];

        // Get URI, if needed
        if ($uri == '') { 
            $uri = $this->app->getPath();
        }
        $uri .= '?interval=';

        // Get html
        $html = '';
        foreach ($periods as $period) {
            if ($period == $selected) {
                $html .= strtolower($period) . ' | ';
            } else {
                $html .= "<a href=\"" . $uri . $period . "\">" . strtolower($period) . "</a> | ";
            }
        }
        $html = rtrim($html, ' | ');

        // Return
        return $html;
    }

}



