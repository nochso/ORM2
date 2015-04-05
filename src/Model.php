<?php

namespace nochso\ORM;

use nochso\ORM\DBA\DBA;
use PDO;
use \nochso\ORM\Relation;

class Model
{

    /**
     * Static properties shared by Model classes
     */
    protected static $_tableName;
    protected static $_primaryKey = 'id';
    protected static $_relations = array();

    /**
     * Properties used by Model instances
     */
    private $_isNew = true;
    private $_queryBuilder;
    private $_extra = array();

    /**
     *
     * @param array|string $columns
     * @return static
     */
    public static function select($columns = null)
    {
        $caller = get_called_class();
        $new = new $caller();
        $new->_queryBuilder = new QueryBuilder($new->getTableName());
        if ($columns !== null) {
            $new->_queryBuilder->addSelectColumn($columns);
        }
        return $new;
    }

    /**
     *
     * @return static
     */
    public function dispense()
    {
        $caller = get_called_class();
        return new $caller();
    }

    public function __construct($id = null)
    {
        if (static::$_tableName === null) {
            static::$_tableName = self::classNameToTableName(get_class($this));
        }

        if ($id !== null) {
            $this->_queryBuilder = new QueryBuilder($this->getTableName());
            $this->eq(self::$_primaryKey, $id)->limit(1);
            $statement = $this->_queryBuilder->getStatement();
            if ($row = $statement->fetch(PDO::FETCH_OBJ)) {
                $this->hydrate($row);
                $this->_isNew = false;
            }
            $statement->closeCursor();
        }

        foreach (static::$_relations as $key => $rel) {
            if (!isset($rel[2])) {
                $rel[2] = null;
            }
            if (!isset($rel[3])) {
                $rel[3] = null;
            }
            $this->setRelation($rel[0], $key, $rel[1], $rel[2], $rel[3]);
        }
    }

    private static function classNameToTableName($className)
    {
        return strtolower(preg_replace(
                        array('/\\\\/', '/(?<=[a-z])([A-Z])/', '/__/'), array('_', '_$1', '_'), ltrim($className, '\\')
        ));
    }

    public function getTableName()
    {
        return static::$_tableName;
    }

    public function getPrimaryKey()
    {
        return static::$_primaryKey;
    }

    public function getPrimaryKeyValue()
    {
        $primaryKey = $this->getPrimaryKey();
        if (isset($this->$primaryKey)) {
            return $this->$primaryKey;
        } else {
            return null;
        }
    }

    public function setRelation($type, $property, $targetClass, $ownerKey = null, $foreignKey = null)
    {
        $this->$property = new Relation($this, $type, $targetClass, $ownerKey, $foreignKey);
    }

    public function getRelations()
    {
        return static::$_relations;
    }

    public function fetchRelations()
    {
        foreach (static::$_relations as $key => $rel) {
            $this->$key->fetch();
        }
    }

    private function addWhere($column, $op, $value)
    {
        if ($this->_queryBuilder === null) {
            $this->_queryBuilder = new QueryBuilder($this->getTableName());
        }
        $this->_queryBuilder->addWhere($column, $op, $value);
        return $this;
    }

    /**
     * Public query building
     */

    /**
     *
     * @param int|string $id optional
     * @return static One model instance or null if nothing was found.
     */
    public function one($id = null)
    {
        if ($id !== null) {
            $this->eq(self::$_primaryKey, $id);
        }
        $this->_queryBuilder->setLimit(1);
        $one = null;
        $statement = $this->_queryBuilder->getStatement();
        if ($row = $statement->fetch(PDO::FETCH_OBJ)) {
            $one = $this->dispense()->hydrate($row);
            $one->_isNew = false;
        }
        $statement->closeCursor();
        return $one;
    }

    /**
     * @param $sql
     * @param null|array $params Optional parameter array for PDO statement
     * @return static One model instance or null if nothing was found.
     */
    public function oneSQL($sql, $params = null)
    {
        if ($params !== null) {
            $statement = DBA::execute($sql, $params);
        } else {
            $statement = DBA::execute($sql);
        }
        $one = null;
        if ($row = $statement->fetch(PDO::FETCH_OBJ)) {
            $one = $this->dispense()->hydrate($row);
            $one->_isNew = false;
        }
        $statement->closeCursor();
        return $one;
    }

    /**
     *
     * @param string $sql optional
     * @param array $params optional
     * @return \nochso\ORM\ResultSet
     */
    public function all($sql = null, $params = null)
    {
        if ($sql !== null && $params !== null) {
            $statement = DBA::execute($sql, $params);
        } else {
            $statement = $this->_queryBuilder->getStatement();
        }
        $rows = array();
        while ($row = $statement->fetch(PDO::FETCH_OBJ)) {
            $one = $this->dispense()->hydrate($row);
            $one->_isNew = false;
            $rows[$one->getPrimaryKeyValue()] = $one;
        }
        $statement->closeCursor();
        $set = new ResultSet(get_called_class());
        $set->setRows($rows);
        return $set;
    }

    public function delete()
    {
        if ($this->_queryBuilder === null) {
            $this->_queryBuilder = new QueryBuilder($this->getTableName());
        }
        
        $this->_queryBuilder->setQueryType(QueryBuilder::QUERY_TYPE_DELETE);

        // If this is called on an instance with primary key, delete only this instance
        if ($this->getPrimaryKeyValue() !== null) {
            $this->eq($this->getPrimaryKey(), $this->getPrimaryKeyValue());
        }

        $statement = $this->_queryBuilder->getStatement();
        $statement->closeCursor();
    }

    public function save()
    {
        if ($this->_queryBuilder === null) {
            $this->_queryBuilder = new QueryBuilder($this->getTableName());
        }
        if ($this->_isNew) {
            $this->_queryBuilder->setQueryType(QueryBuilder::QUERY_TYPE_INSERT);
        } else {
            if ($this->getPrimaryKeyValue() === null) {
                throw new \Exception('Can not update existing row of table ' . $this->getTableName() . ' without knowing the primary key.');
            }
            // Update exactly this instance, nothing else
            $this->_queryBuilder->setQueryType(QueryBuilder::QUERY_TYPE_UPDATE);
            $this->eq($this->getPrimaryKey(), $this->getPrimaryKeyValue());
        }

        $this->_queryBuilder->setModelData($this->toAssoc());
        $statement = $this->_queryBuilder->getStatement();
        if ($this->_isNew) {
            $primaryKey = $this->getPrimaryKey();
            $this->$primaryKey = DBA::lastInsertID();
            $this->_isNew = false;
        }
        $statement->closeCursor();
    }

    public function update($data)
    {
        $this->_queryBuilder->setQueryType(QueryBuilder::QUERY_TYPE_UPDATE);
        $this->_queryBuilder->setModelData($data);
        $statement = $this->_queryBuilder->getStatement();
        $statement->closeCursor();
    }

    /**
     * Returns an associative array from this object excluding private variables and Relation objects
     *
     * @return array
     * @todo Figure out generic and decent way to convert from e.g. DateTime() to SQL-friendly data.
     */
    public function toAssoc()
    {
        $params = array();
        foreach (get_object_vars($this) as $key => $value) {
            if ($key[0] != '_' && !($value instanceof Relation)) {
                $params[$key] = $value;
            }
        }
        if ($this->getPrimaryKeyValue() === null) {
            unset($params[$this->getPrimaryKey()]);
        }
        return $params;
    }

    /**
     * Sets the properties of this object using an associative array,
     * where key is the property name and value is the property value.
     *
     * Properties that do not exist in the current context are ignored.
     *
     * @param array $data Associative array
     * @param bool $removePrimaryKey Optional: If true, the primary key of the
     * model will be unset. This is useful for hydrating a new object
     * and the source ($_POST) erroneously supplies a primary key.
     *
     * Default: false
     * @return static
     * @todo Figure out generic and decent way to convert from SQL data to DateTime() or similar
     */
    public function hydrate($data, $removePrimaryKey = false)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                /* if (strpos($key, 'date') !== false) {
                    try {
                        $value = \Carbon\Carbon::createFromFormat(\Carbon\Carbon::RFC3339, $value);
                        $value->setTimezone(date_default_timezone_get());
                    } catch (Exception $e) {
                    }
                } */
                $this->$key = $value;
            } else {
                $this->_extra[$key] = $value;
            }
        }
        if ($removePrimaryKey) {
            $key = $this->getPrimaryKey();
            $this->$key = null;
        }
        return $this;
    }

    public function extra($key)
    {
        if (!isset($this->_extra[$key])) {
            return null;
        }
        return $this->_extra[$key];
    }

    public function setExtra($key, $value)
    {
        $this->_extra[$key] = $value;
    }

    /**
     * Query building
     */

    /**
     * Filter where column equals value
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function eq($column, $value)
    {
        return $this->addWhere($column, '=', $value);
    }

    /**
     * Filter where column equals value
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function where($column, $value)
    {
        return $this->eq($column, $value);
    }

    /**
     * Filter where column does not equal value
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function neq($column, $value)
    {
        return $this->addWhere($column, '!=', $value);
    }

    /**
     * Filter where column is less than value
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function lt($column, $value)
    {
        return $this->addWhere($column, '<', $value);
    }

    /**
     * Filter where column is less than or equal value
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function lte($column, $value)
    {
        return $this->addWhere($column, '<=', $value);
    }

    /**
     * Filter where column is greater than value
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function gt($column, $value)
    {
        return $this->addWhere($column, '>', $value);
    }

    /**
     * Filter where column is greater than or equal value
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function gte($column, $value)
    {
        return $this->addWhere($column, '>=', $value);
    }

    /**
     * Filter where column matches list of values
     *
     * @param string $column
     * @param array $values
     * @return static
     */
    public function in($column, $values)
    {
        return $this->addWhere($column, 'IN', $values);
    }

    /**
     * Filter where column does not match any of the value
     *
     * @param string $column
     * @param array $values
     * @return static
     */
    public function notIn($column, $values)
    {
        return $this->addWhere($column, 'NOT IN', $values);
    }

    /**
     * Filter where column matches value using the LIKE operator
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function like($column, $value)
    {
        return $this->addWhere($column, 'LIKE', $value);
    }

    /**
     * Filter where column does not match value using the LIKE operator
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function notLike($column, $value)
    {
        return $this->addWhere($column, 'NOT LIKE', $value);
    }

    /**
     * Filter where column is SQL NULL
     *
     * @param string $column
     * @return static
     */
    public function isNull($column)
    {
        return $this->addWhere($column, 'IS NULL', '');
    }

    /**
     * Filter where column is not SQL NULL
     *
     * @param string $column
     * @return static
     */
    public function notNull($column)
    {
        return $this->addWhere($column, 'IS NOT NULL', '');
    }

    /**
     * Limit the amount of maximum rows returned
     *
     * @param int $limit
     * @param int $offset
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $this->_queryBuilder->setLimit($limit, $offset);
        return $this;
    }

    /**
     * Offset the result in combination with $this->limit()
     *
     * @param int $offset
     * @return static
     */
    public function offset($offset)
    {
        $this->_queryBuilder->setOffset($offset);
        return $this;
    }

    /**
     * Sort the results by ascending order
     *
     * @param string $column
     * @return static
     */
    public function orderAsc($column)
    {
        $this->_queryBuilder->addOrderAsc($column);
        return $this;
    }

    /**
     * Sort the results by descending order
     *
     * @param string $column
     * @return static
     */
    public function orderDesc($column)
    {
        $this->_queryBuilder->addOrderDesc($column);
        return $this;
    }

    /**
     * Return the average value
     *
     * @param string $column
     * @return static
     */
    public function avg($column)
    {
        return $this->_queryBuilder->getAggregateColumn('AVG', $column);
    }

    /**
     * Return the sum of values
     *
     * @param string $column
     * @return static
     */
    public function sum($column)
    {
        return $this->_queryBuilder->getAggregateColumn('SUM', $column);
    }

    /**
     * Return the count of values
     *
     * @param string $column
     * @return static
     */
    public function count($column = '*')
    {
        return $this->_queryBuilder->getAggregateColumn('COUNT', $column);
    }

    /**
     * Return the minimum of values
     *
     * @param string $column
     * @return static
     */
    public function min($column)
    {
        return $this->_queryBuilder->getAggregateColumn('MIN', $column);
    }

    /**
     * Return the maximum of values
     *
     * @param string $column
     * @return static
     */
    public function max($column)
    {
        return $this->_queryBuilder->getAggregateColumn('MAX', $column);
    }
}
