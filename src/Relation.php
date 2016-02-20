<?php

namespace nochso\ORM;

class Relation implements \Iterator, \ArrayAccess, \Countable
{
    // The type of relation
    const HAS_MANY = 0;
    const HAS_ONE = 1;
    const BELONGS_TO = 2;

    public $type;
    // Name of the class that is related
    public $foreignClass;
    // Instance of owning class
    public $owner;
    // Instance of the related class used for filtering/loading
    public $foreign;
    public $foreignKey;
    public $ownerKey;
    public $data;

    public function __construct(&$ownerInstance, $type, $foreignClass, $ownerKey = null, $foreignKey = null)
    {
        $this->type = $type;
        $this->foreignClass = $foreignClass;
        $this->owner = $ownerInstance;
        $this->ownerKey = $ownerKey;
        $this->foreignKey = $foreignKey;
    }

    private function init()
    {
        if ($this->foreign === null) {
            $this->foreign = new $this->foreignClass();
        }
    }

    public function fetch()
    {
        if ($this->data !== null) {
            return $this->data;
        }

        // Build a filter based on the relation
        $this->init();
        $foreignKey = $this->getForeignKey();
        $foreignValue = $this->getForeignValue();
        $filter = $this->foreign->where($foreignKey, $foreignValue);

        switch ($this->type) {
            case self::HAS_MANY:
                $this->data = $filter->all();
                break;
            case self::HAS_ONE:
            case self::BELONGS_TO:
                $this->data = $filter->one();
                break;
        }
        return $this->data;
    }

    /**
     * Returns the name of the column that will be filtered on
     *
     * @return string
     */
    public function getForeignKey()
    {
        if ($this->foreignKey !== null) {
            return $this->foreignKey;
        }
        $this->init();
        switch ($this->type) {
            case self::HAS_MANY:
            case self::HAS_ONE:
                return $this->owner->getTableName() . '_' . $this->owner->getPrimaryKey();
                break;
            case self::BELONGS_TO:
                return $this->foreign->getPrimaryKey();
                break;
        }
    }

    /**
     * Returns the value identifiying the foreign rows, i.e. the value belonging to getForeignkey()
     *
     * @return type
     */
    public function getForeignValue()
    {
        if ($this->ownerKey !== null) {
            $fk = $this->ownerKey;
            return $this->owner->$fk;
        }
        $this->init();
        switch ($this->type) {
            case self::HAS_MANY:
            case self::HAS_ONE:
                return $this->owner->getPrimaryKeyValue();
                break;

            case self::BELONGS_TO:
                $fk = $this->foreign->getTableName() . '_' . $this->foreign->getPrimaryKey();
                return $this->owner->$fk;
                break;
        }
    }

    public function __get($name)
    {
        if (isset($this->data->$name)) {
            return $this->data->$name;
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        $this->data->$name = $value;
    }

    /**
     * @param  string $name
     * @param  mixed  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->data, $name], $arguments);
    }

    /**
     * Iterator interface
     */
    public function rewind()
    {
        if (is_array($this->data)) {
            return reset($this->data);
        }
        return $this->data->rewind();
    }

    public function current()
    {
        if (is_array($this->data)) {
            return current($this->data);
        }
        return $this->data->current();
    }

    public function key()
    {
        if (is_array($this->data)) {
            return key($this->data);
        }
        return $this->data->key();
    }

    public function next()
    {
        if (is_array($this->data)) {
            return next($this->data);
        }
        return $this->data->next();
    }

    public function valid()
    {
        if (is_array($this->data)) {
            return key($this->data) !== null;
        }
        return $this->data->key() !== null;
    }

    /**
     * ArrayAccess interface
     */
    public function offsetExists($offset)
    {
        if ($this->data instanceof Model) {
            return isset($this->data->$offset);
        } else {
            return isset($this->data[$offset]);
        }
    }

    public function offsetGet($offset)
    {
        if ($this->data instanceof Model) {
            if (isset($this->data->$offset)) {
                return $this->data->$offset;
            }
        } elseif (isset($this->data[$offset])) {
            return $this->data[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Countable interface
     */
    public function count()
    {
        return count($this->data);
    }
}
