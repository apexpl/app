<?php

namespace Apex\App\Interfaces\Opus;

/**
 * Form interface
 */
interface FormInterface
{

    /**
     * Get fields
     */
    public function getFields(array $attr = []):array;

    /**
     * Get record
     */
    public function getRecord(string $record_id):array;

    /**
     * Validate
     */
    public function validate(array $attr = []):bool;

}




