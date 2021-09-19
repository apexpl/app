<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;

use Apex\Svc\{Db, Di, Convert};
use Apex\App\Base\Model\ModelIterator;
use Apex\App\Interfaces\BaseModelInterface;
use Apex\App\Exceptions\ApexForeignKeyNotExistsException;

/**
 * Base model
 */
class BaseModel implements BaseModelInterface
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
    public static function where(string $where_sql, ...$args):ModelIterator
    {
        $dbtable = static::$dbtable;
        $db = Di::get(Db::class);
        $stmt = $db->query("SELECT * FROM $dbtable WHERE $where_sql", ...$args);
        return new ModelIterator($stmt, static::class);
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
    public static function all(string $sort_by = 'id', string $sort_dir = 'asc', int $limit = 0, int $offset = 0):ModelIterator
    {

        // Initialize
        $db = Di::get(Db::class);
        if ($sort_by == '') { 
            $sort_by = $db->getPrimaryColumn(static::$dbtable);
        }

        // Start SQL
        $sql = "SELECT * FROM " . static::$dbtable . " ORDER BY %s";
        $args = [$sort_by . ' ' . $sort_dir];

        // Add limit
        if ($limit > 0) { 
            $sql .= " LIMIT %i";
            $args[] = $limit;
        }

        // Add offset
        if ($offset > 0) { 
            $sql .= " OFFSET %i";
            $args[] = $offset;
        }

        // Execute query, and return
        $stmt = $db->query($sql, ...$args);
        return new ModelIterator($stmt, static::class);
    }

    /**
     * Get all
     */
    public function getChildren(string $foreign_key, string $class_name, string $sort_by = 'id', string $sort_dir = 'asc', int $limit = 0, int $offset = 0):ModelIterator
    {

        // Initialize
        $db = Di::get(Db::class);

        // Get foreign key
        $keys = $db->getReferencedForeignKeys(static::$dbtable);
        if (!isset($keys[$foreign_key])) { 
            throw new ApexForeignKeyNotExistsException("No foreign key of '$foreign_key' exists on the database table " . static::$dbtable);
        }
        $key = $keys[$foreign_key];

        // Get parent_id
        $column = $key['column'];
        $parent_id = $this->$column;

        // Get sort_by
        if ($sort_by == '') { 
            $sort_by = $db->getPrimaryColumn($key['ref_table']);
        }

        // Start SQL
        $sql = "SELECT * FROM " . $key['ref_table'] . " WHERE $key[ref_column] = %s ORDER BY %s";
        $args = [$parent_id, $sort_by . ' ' . $sort_dir];

        // Add limit
        if ($limit > 0) { 
            $sql .= " LIMIT %i";
            $args[] = $limit;
        }

        // Add offset
        if ($offset > 0) { 
            $sql .= " OFFSET %i";
            $args[] = $offset;
        }

        // Execute query, and return
        $stmt = $db->query($sql, ...$args);
        return new ModelIterator($stmt, $class_name);
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

            // Check for DateTime
            if (is_object($value) && $value::class == 'DateTime') { 
                $value = $value->format('Y-m-d H:i:s');
            }
            $vars[$key] = $value;
        }

        // Return
        return $vars;
    }

}


