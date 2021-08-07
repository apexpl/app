<?php
declare(strict_types = 1);

namespace Apex\App\Interfaces\Opus;

/**
 * Data table interface
 */
interface DataTableInterface
{

    /**
     * Get total rows in data set - used for pagination.
     */
    public function getTotal(string $search_term = ''):int;


    /**
     * Get rows to display on current page.
     *
     * Should return an array with each element being an associative array representing one table row.
     */ 
    public function getRows(int $start = 0, string $search_term = '', string $order_by = 'id asc'):array;


    /**
     * Format individual row for display to browser.
     */
    public function formatRow(array $row):array;

}









