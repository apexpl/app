<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web;

use Apex\Svc\App;
use Apex\Opus\Interfaces\DataTableInterface;

/**
 * Data table
 */
class DataTable
{

    // Properties
    public int $page;
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
        $search_term = $app->post('search_' . $this->divid, '');
        if ($app->hasPost('sort_col') && $app->hasPost('sort_dir')) { 
            $order_by = $app->post('sort_col') . ' ' . $app->post('sort_dir', 'asc');
        } else { 
            $order_by = '';
        }

        // Get total rows
        $this->page = (int) $app->request('page', '1');
        $this->total_rows = $table->getTotal($search_term);
        $this->rows_per_page = $table->rows_per_page ?? 25;

        // Get rows
        $start = ($this->page - 1) * $this->rows_per_page;
        if ($order_by == '') { 
            $this->rows = $table->getRows($start, $search_term);
        } else { 
            $this->rows = $table->getRows($start, $search_term, $order_by);
        }

    }

}


