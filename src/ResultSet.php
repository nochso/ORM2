<?php

namespace nochso\ORM;

use nochso\ORM\Relation;

class ResultSet implements \Iterator, \ArrayAccess, \Countable
{
    protected $rows = array();
    private $className;

    /**
     * Creating and filling a result set
     */
    public function __construct($className, $rows = array())
    {
        $this->className = $className;
        $this->rows = $rows;
    }

    public function setRows($rows)
    {
        $this->rows = $rows;
    }

    /**
     * Functions to operate on all elements of the ResultSet at once
     */
    public function delete()
    {
        if (count($this) == 0) {
            return;
        }
        $ids = $this->getPrimaryKeyList();
        $hitman = new $this->className();
        $hitman->in($hitman->getPrimaryKey(), $ids)->delete();
    }

    /**
     * Update all entries in this ResultSet with the same values
     */
    public function update($data)
    {
        if (count($this) == 0) {
            return;
        }
        $ids = $this->getPrimaryKeyList();
        $updater = new $this->className();
        $updater->in($updater->getPrimaryKey(), $ids)->update($data);
    }

    /**
     * Saves all entries in this ResultSet using a single query.
     * It uses the actual unique values held by the Model instances,
     * unlike update() which takes the values as an argument and uses them for every Model instance
     */
    public function save()
    {
        if (count($this) == 0) {
            return;
        }
        $helper = reset($this->rows);
        $qb = new QueryBuilder($helper->getTableName());
        $qb->setQueryType(QueryBuilder::QUERY_TYPE_UPDATE);
        $qb->setModelData($this);
        $qb->addWhere($helper->getPrimaryKey(), 'IN', $this->getPrimaryKeyList());
        $statement = $qb->getStatement();
        $statement->closeCursor();
    }

    /**
     * Returns an array of all primary key values contained in the ResultSet
     *
     * @return array
     */
    public function getPrimaryKeyList()
    {
        $ids = array();
        if (count($this) > 0) {
            foreach ($this as $row) {
                $ids[] = $row->getPrimaryKeyValue();
            }
        }
        return $ids;
    }

    public function fetchRelations()
    {
        if (count($this) == 0) {
            return $this;
        }

        // Get list of relevant relations
        $helperInstance = reset($this->rows);
        $relations = $helperInstance->getRelations();

        // Fill $filterIDs with the name of the foreign key to be filtered on
        // Fill $filters with model instances to build filters with
        $filterIDs = array();
        $filters = array();
        foreach ($relations as $property => $relation) {
            $filterIDs[$property]['foreignKey'] = $helperInstance->$property->getForeignKey();
            $filters[$property] = new $relation[1]();
        }

        $groups = array();
        foreach ($this->rows as $key => $row) {
            foreach ($relations as $property => $relation) {
                // Fill $filterIDs with the values of the foreign keys to be filtered on
                $filterIDs[$property]['ids'][] = $row->$property->getForeignValue();

                // Group primary key values of all the rows by the foreign keys we're looking up in the foreign rows.
                // That way we can quickly find an owner instance or it's primary key value by look-up via the foreign key.
                $groups[$property][$row->$property->getForeignValue()][] = $row->getPrimaryKeyValue();
            }
        }

        foreach ($filters as $property => $filter) {
            // Filter the foreign key on all selected IDs and return all instances
            $foreignSet = $filter->in($filterIDs[$property]['foreignKey'], array_unique($filterIDs[$property]['ids']))->all();

            // The key name by which to identify the corresponding parent instance
            $foreignKey = $filterIDs[$property]['foreignKey'];
            $relationType = $relations[$property][0];

            foreach ($foreignSet as $row) {
                // Value of the primary key by which the parent instance is identified
                $foreignKeyValue = $row->$foreignKey;

                foreach ($groups[$property][$foreignKeyValue] as $primaryPosition) {
                    if ($relationType == Relation::HAS_MANY) {
                        $this->rows[$primaryPosition]->$property->data[$row->getPrimaryKeyValue()] = $row;
                    } else {
                        $this->rows[$primaryPosition]->$property->data = $row;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Iterator interface
     */
    public function rewind()
    {
        return reset($this->rows);
    }

    public function current()
    {
        return current($this->rows);
    }

    public function key()
    {
        return key($this->rows);
    }

    public function next()
    {
        return next($this->rows);
    }

    public function valid()
    {
        return key($this->rows) !== null;
    }

    /**
     * ArrayAccess interface
     */
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->rows[$offset])) {
            return $this->rows[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->rows[] = $value;
        } else {
            $this->rows[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->rows[$offset]);
    }

    /**
     * Countable interface
     */
    public function count()
    {
        return count($this->rows);
    }
}
