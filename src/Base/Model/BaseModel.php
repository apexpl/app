<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;

use Apex\Svc\{Db, Di, Convert};

/**
 * Base model
 */
class BaseModel
{

    #[Inject(Convert::class)]
    protected Convert $convert;

    /**
     * Get first
     */
    public static function whereFirst(string $where_sql, ...$args):?static
    {
        $dbtable = static::$dbtable;
        $db = Di::get(Db::class);
        return $db->getObject(static::class, "SELECT * FROM $dbtable WHERE $where_sql", ...$args);
    }

    /**
     * Where
     */
    public static function where(string $where_sql, ...$args):MapperIterable
    {
        $dbtable = static::$dbtable;
        $db = Di::get(Db::class);
        $stmt = $db->query("SELECT * FROM $dbtable WHERE $where_sql", ...$args);
        return new MapperIterable($stmt, static::class);
    }

    /**
     * Get id
     */
    public static function whereId(string | int $id):?static
    {
        $db = Di::get(Db::class);
        return $db->getIdObject(static::class, static::$dbtable, $id);
    }

    /**
     * Get all
     */
    public static function all(string $order_by = 'id ASC'):MapperIterable
    {
        $db = Di::get(Db::class);
        $stmt = $db->query("SELECT * FROM " . static::$dbtable . " ORDER BY $order_by");
        return new MapperIterable($stmt, static::class);
    }

    /**
     * Count
     */
    public static function count(string $where_sql = '', ...$args):int
    {
        $db = Di::get(Db::class);
        if ($where_sql == '') { 
            $count = $db->getField("SELECT count(*) FROM " . static::$dbtable);
    } else { 
        $count = $db->getField("SELECT * FROM " . static::$dbtable . " WHERE $where_sql", ...$args);
    }
        return (int) $count;
    }

    /**
     * Insert
     */
    public static function insert(array $values):?static
    {
        $db = Di::get(Db::class);
        $db->insert(static::$dbtable, $values);
        $id = $db->insertId();
        return $db->getIdObject(static::class, static::$dbtable, $id);
    }

    /**
     * Insert or update
     */
    public static function insertOrUpdate(array $criteria, array $values):?static
    {

        // Initialize
        $db = Di::get(Db::class);
        $where_sql = implode(' = %s AND ', array_keys($criteria)) . ' = %s';

        // Check if record already exists
        if ($obj = self::whereFirst($where_sql, ...(array_values($criteria)))) { 
            $obj->save($values);
            return $obj;

        // Insert new record
        } else { 
            $db->insert(static::$dbtable, array_merge($criteria, $values));
            $id = $db->insertId();
            return $db->getIdObject(static::class, static::$dbtable, $id);
        }

    }

    /**
     * Update
     */
    public static function update(array $values, string $where_sql = '', ...$args):void
    {
        $db = Di::get(Db::class);
        $db->update(static::$dbtable, $values, $where_sql, ...$args);
    }

    /**
     * Save
     */
    public function save(array $values = []):void
    {

        // Update properties, if any passed
        foreach ($values as $key => $value) { 
            $this->$key = $value;
        }

        // Add updated_at, if available
        if (isset($this->updated_at)) { 
            $this->updated_at = new DateTime();
        }

        // Save
        $db = Di::get(Db::class);
        $db->insertOrUpdate(static::$dbtable, $this);
    }

    /**
     * Delete
     */
    public function delete():void
    {
        $db = Di::get(Db::class);
        $db->delete(static::$dbtable, $this);
    }

    /**
     * toArray
     */
    public function toArray():array
    {

        $vars = [];
        foreach ($this as $key => $value) { 
            if ($key == 'convert') { 
                continue;
            }
            $vars[$key] = $value;
        }

        // Return
        return $vars;
    }

}

