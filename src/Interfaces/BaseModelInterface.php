<?php

namespace Apex\App\Interfaces;

use Apex\App\Base\Model\ModelIterator;

/**
 * Base model interface
 */
interface BaseModelInterface
{


    /**
     * Get first
     */
    public static function whereFirst(string $where_sql, ...$args):?static;


    /**
     * Where
     */
    public static function where(string $where_sql, ...$args):ModelIterator;


    /**
     * Where id
     */
    public static function whereId(string | int $id):?static;


    /**
     * All rows
     */
    public static function all(string $sort_by = 'id', string $sort_dir = 'asc', int $limit = 0, int $offset = 0):ModelIterator;


    /**
     * Count
     */
    public static function count(string $where_sql = '', ...$args):int;



    /**
     * Insert record
     */
    public static function insert(array $values):?static;


    /**
     * Insert or update record
     */
    public static function insertOrUpdate(array $criteria, array $values):?static;


    /**
     * Update
     */
    public static function update(array $values, string $where_sql = '', ...$args):void;


    /**
     * Save
     */
    public function save(array $values = []):void;


    /**
     * Delete single record
     */
    public function delete():void;


    /**
     * toArray
     */
    public function toArray():array;


    /**
     * Get children
     */
    public function getChildren(string $foreign_key, string $class_name, string $sort_by = 'id', string $sort_dir = 'asc', int $limit = 0, int $offset = 0):ModelIterator;

    /**
     * Delete many
     */
    public static function deleteMany(string $where_sql, ...$args):int;


    /**
     * Purge
     */
    public static function purge():void;

}


