<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;

use Apex\Svc\{Db, Di, Convert, App};
use Apex\App\Base\Model\ModelIterator;
use Apex\App\Interfaces\BaseModelInterface;
use Apex\App\Exceptions\ApexForeignKeyNotExistsException;
use Apex\App\Attr\Inject;
use DateTime;

/**
 * Create, manage and retrieve records from the assigned model class that implements this class.
 */
abstract class BaseModel implements BaseModelInterface
{

    #[Inject(Convert::class)]
    protected Convert $convert;

    /**
     * Get a model instance of the first record found for a given where clause.
     */
    public static function whereFirst(string $where_sql, ...$args):?static
    {
        $dbtable = static::$dbtable;
        $db = Di::get(Db::class);
        return $db->getObject(static::class, "SELECT * FROM $dbtable WHERE $where_sql", ...$args);
    }

    /**
     * Select multiple records from the database table that is assigned to the model class.
     */
    public static function where(string $where_sql, ...$args):ModelIterator
    {
        $dbtable = static::$dbtable;
        $db = Di::get(Db::class);
        $stmt = $db->query("SELECT * FROM $dbtable WHERE $where_sql", ...$args);
        return new ModelIterator($stmt, static::class);
    }

    /**
     * Select a single model instance based on the value of the primary key / id column.
     */
    public static function whereId(string | int $id):?static
    {
        $db = Di::get(Db::class);
        return $db->getIdObject(static::class, static::$dbtable, $id);
    }

    /**
     * Get all records from the database table assigned to the model class.
     */
    public static function all(string $sort_by = 'id', string $sort_dir = 'asc', int $limit = 0, int $offset = 0):ModelIterator
    {

        // Initialize
        $db = Di::get(Db::class);
        if ($sort_by == '') { 
            $sort_by = $db->getPrimaryKey(static::$dbtable);
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
     * Get child records based on a foreign key constraint.
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
            $sort_by = $db->getPrimaryKey($key['ref_table']);
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
     * Get the number of records within the database table with optional where clause.
     */
    public static function count(string $where_sql = '', ...$args):int
    {
        $db = Di::get(Db::class);
        if ($where_sql == '') { 
            $count = $db->getField("SELECT count(*) FROM " . static::$dbtable);
    } else { 
        $count = $db->getField("SELECT count(*) FROM " . static::$dbtable . " WHERE $where_sql", ...$args);
    }
        return (int) $count;
    }

    /**
     * Insert a new record into the database table assigned to the model class.
     */
    public static function insert(object | array $values):?static
    {
        $db = Di::get(Db::class);
        $db->insert(static::$dbtable, $values);
        $id = $db->insertId();
        return $db->getIdObject(static::class, static::$dbtable, $id);
    }

    /**
     * Insert from form
     */
    public static function insertFromForm():static
    {

        // Initialize
        $app = Di::get(App::class);
        $db = Di::get(Db::class);
        $columns = $db->getColumnNames(static::$dbtable);

        // Gather values
        $values = [];
        foreach ($columns as $alias) {
            if (!$app->hasPost($alias)) {
                continue;
            }
            $values[$alias] = $app->post($alias);
        }

        // Insert, and return
        $obj = self::insert($values);
        return $obj;
    }

    /**
     * Insert or update a record within the database table assigned to the model class.
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
     * Update multiple records within the database table assigned to the model class.
     */
    public static function update(array $values, string $where_sql = '', ...$args):void
    {
        $db = Di::get(Db::class);
        $db->update(static::$dbtable, $values, $where_sql, ...$args);
    }

    /**
     * Save and  update an individual model instance.
     */
    public function save(array $values = []):void
    {

        // Update properties, if any passed
        foreach ($values as $key => $value) { 

            // Check
            if (is_bool($this->$key) && $value == 1) {
                $this->$key = true;
            } elseif (is_bool($this->$key) && $value == 0) {
                $this->$key = false;
            } elseif (is_integer($this->$key) && is_string($value) && preg_match("/^\d+$/", $value)) {
            $this->$key = (int) $value;
            } else { 
                $this->$key = $value;
            }

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
     * Delete a single model instance from the database table.
     */
    public function delete():void
    {
        $db = Di::get(Db::class);
        $db->delete(static::$dbtable, $this);
    }

    /**
     * Delete multiple records from the database table assigned to the model class.
     */
    public static function deleteMany(string $where_sql, ...$args):int
    {
        $dbtable = static::$dbtable;
        $db = Di::get(Db::class);
        $stmt = $db->query("DELETE FROM $dbtable WHERE $where_sql", ...$args);
        return $db->numRows($stmt);
    }

    /**
     * Purge and delete all records within the database table assigned to the model class.
     */
    public static function purge():void
    {
        $db = Di::get(Db::class);
        $db->truncate(static::$dbtable);
    }

    /**
     * Get all properties of the model instance returned in an array.
     */
    public function toArray():array
    {

        $vars = [];
        foreach ($this as $key => $value) { 

            // Check for DateTime
            if (is_object($value) && $value::class == 'DateTime') { 
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value) && enum_exists($value::class)) {
                $value = $value->value;
            } elseif (is_object($value)) {
                continue;
            }
            $vars[$key] = $value;
        }

        // Return
        return $vars;
    }

    /**
     * toFormattedArray
     */
    public function toFormattedArray():array
    {

        // Init
        $data = $this->toArray();
        $vars = [];

        // Format
        foreach ($data as $key => $value) {

            // Format var
            if (is_bool($value)) {
                $value = $value === true ? 'Yes' : 'No';
            } elseif (GetType($this->$key) == 'DateTime') {
                $value = $this->convert->date($value, true);
            }
            $vars[$key] = $value;
        }

        // Return
        return $vars;
    }


}

