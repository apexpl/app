<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Utils;

use Apex\Svc\App;
use Apex\App\Interfaces\Opus\DataTableInterface;
use Apex\App\Attr\Inject;

/**
 * Data table
 */
class DataTableDetails
{

    // Properties
    public int $page;
    public int $start;
    public int $total_rows;
    public int $rows_per_page;
    public array $rows;

    /**
     * Constructor
     */
    public function __construct(
        private App $app, 
        private string $divid, 
        private DataTableInterface $table
    ) { 

        // Set variables
        $search_term = $app->request('search_' . $this->divid, '');
        if ($app->hasRequest('sort_col') && $app->hasRequest('sort_dir')) { 
            $order_by = $app->request('sort_col') . ' ' . $app->request('sort_dir', 'asc');
        } else { 
            $order_by = '';
        }

        // Get total rows
        $this->page = (int) $app->request('page', '1');
        $this->total_rows = $table->getTotal($search_term);
        $this->rows_per_page = $table->rows_per_page ?? 25;

        // Get rows
        $this->start = ($this->page - 1) * $this->rows_per_page;
        if ($order_by == '') { 
            $this->rows = $table->getRows($this->start, $search_term);
        } else { 
            $this->rows = $table->getRows($this->start, $search_term, $order_by);
        }

    }

}


