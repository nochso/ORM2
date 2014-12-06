<?php

namespace nochso\ORM;

use nochso\ORM\DBA\DBA as DBA;

class QueryBuilder {

    private $tableName;
    private $where = array();
    private $limit;
    private $offset;
    private $order = array();
    private $parameters = array();
    private $parameterCount = 0;
    private $sql;
    private $queryType;
    private $modelData;
    private $selectColumns = array();

    const QUERY_TYPE_SELECT = 0;
    const QUERY_TYPE_DELETE = 1;
    const QUERY_TYPE_UPDATE = 2;
    const QUERY_TYPE_INSERT = 4;

    public function __construct($tableName) {
        $this->tableName = $tableName;
        $this->queryType = self::QUERY_TYPE_SELECT;
    }

    /**
     * Visible functions to help define and build a query
     */
    public function addWhere($column, $op, $value) {
        $this->where[] = array('column' => $column, 'op' => $op, 'value' => $value);
    }

    public function setLimit($limit, $offset = null) {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
    }

    public function getAggregateColumn($function, $column) {
        $this->selectColumns = array("$function($column)");
        $statement = $this->getStatement();
        if ($row = $statement->fetch(\PDO::FETCH_NUM)) {
            return $row[0];
        }
        $statement->closeCursor();
    }

    public function addSelectColumn($column) {
        if (is_array($column)) {
            foreach ($column as $col) {
                $this->addSelectColumn($col);
            }
        } else {
            $this->selectColumns[] = $column;
        }
    }

    public function setOffset($offset) {
        $this->offset = $offset;
    }

    public function addOrderAsc($column) {
        $this->order[] = $column . ' ASC';
    }

    public function addOrderDesc($column) {
        $this->order[] = $column . ' DESC';
    }

    public function setQueryType($type) {
        $this->queryType = $type;
    }

    // Used by UPDATE and INSERT
    public function setModelData($data) {
        $this->modelData = $data;
    }

    public function getStatement() {
        $sql = $this->getSQL();
        $statement = DBA::execute($sql, $this->parameters);
        $this->reset();
        return $statement;
    }

    private function reset() {
        $this->queryType = self::QUERY_TYPE_SELECT;
        $this->where = array();
        $this->limit = null;
        $this->offset = null;
        $this->order = array();
        $this->parameters = array();
        $this->parameterCount = 0;
        $this->sql = null;
        $this->queryType = null;
        $this->modelData = null;
        $this->selectColumns = array();
    }

    /**
     * Private functions to help build the SQL statement named parameter bindings
     */
    private function getSQL() {
        $sql = $this->getTypeSQL()
                . $this->getWhereSQL()
                . $this->getOrderSQL()
                . $this->getLimitSQL()
                . $this->getOffsetSQL();
        return $sql;
    }

    private function getTypeSQL() {
        $sql = '';
        switch ($this->queryType) {
            case self::QUERY_TYPE_SELECT:
                $sql = 'SELECT ' . $this->getSelectColumnsSQL() . ' FROM `' . $this->tableName . '`';
                break;

            case self::QUERY_TYPE_DELETE:
                $sql = 'DELETE FROM `' . $this->tableName . '`';
                break;

            case self::QUERY_TYPE_UPDATE:
                $sql = 'UPDATE `' . $this->tableName . '` SET ';
                if ($this->modelData instanceof \ORM\ResultSet) {
                    $sql .= $this->getMultiUpdateSetsSQL();
                } else {
                    $sql .= $this->getUpdateSetsSQL();
                }
                break;

            case self::QUERY_TYPE_INSERT:
                $columnNames = '`' . implode('`, `', array_keys($this->modelData)) . '`';
                $parameters = array();
                foreach ($this->modelData as $key => $value) {
                    $parameters[] = $this->addParameter($value);
                }
                $parameters = implode(', ', $parameters);
                $sql = 'INSERT INTO `' . $this->tableName . '` (' . $columnNames . ') VALUES (' . $parameters . ')';
                break;
        }
        return $sql;
    }

    private function getUpdateSetsSQL() {
        $sets = array();
        foreach ($this->modelData as $key => $value) {
            $sets[] = $key . ' = ' . $this->addParameter($value);
        }
        return implode(', ', $sets);
    }

    private function getMultiUpdateSetsSQL() {
        // For each property, map the primary key of the row to the new value of said property
        $map = array();
        foreach ($this->modelData as $primaryKey => $row) {
            $assoc = $row->toAssoc();
            foreach ($assoc as $property => $value) {
                if ($property != $row->getPrimaryKey()) {
                    $map[$property][$row->getPrimaryKeyValue()] = $value;
                }
            }
        }
        $model = $this->modelData->rewind();
        $sql = '';
        foreach ($map as $property => $values) {
            $sql .= "\n" . $property . ' = CASE ' . $model->getPrimaryKey() . "\n";
            foreach ($values as $primaryKey => $value) {
                $sql .= "\tWHEN " . $this->addParameter($primaryKey) . " THEN " . $this->addParameter($value) . "\n";
            }
            $sql .= "END,";
        }

        return rtrim($sql, ',');
    }

    private function getSelectColumnsSQL() {
        if (count($this->selectColumns) == 0) {
            return '*';
        } else {
            return implode(', ', $this->selectColumns);
        }
    }

    private function getWhereSQL() {
        $where = '';
        $parts = array();
        if (count($this->where) > 0) {
            foreach ($this->where as $where) {
                $parts[] = $this->buildWherePart($where['column'], $where['op'], $where['value']);
            }
            $where = ' WHERE ' . implode(' AND ', $parts);
        }
        return $where;
    }

    private function buildWherePart($column, $op, $value) {
        switch ($op) {
            case 'IN':
            case 'NOT IN':
                $identifiers = array();
                foreach ($value as $v) {
                    $identifiers[] = $this->addParameter($v);
                }
                return "$column $op (" . implode(', ', $identifiers) . ")";
                break;

            case 'IS NULL':
            case 'IS NOT NULL':
                return "$column $op";
                break;

            case 'LIKE':
                return "$column $op " . $this->addParameter($value) . " ESCAPE '='";
                break;

            default:
                return "$column $op " . $this->addParameter($value);
        }
    }

    private function addParameter($value) {
        $identifier = ':_' . $this->parameterCount;
        $this->parameterCount++;
        $this->parameters[$identifier] = $value;
        return $identifier;
    }

    private function getLimitSQL() {
        if ($this->limit !== null) {
            return ' LIMIT ' . $this->limit;
        }
        return '';
    }

    private function getOffsetSQL() {
        if ($this->offset !== null) {
            return ' OFFSET ' . $this->offset;
        }
        return '';
    }

    private function getOrderSQL() {
        if (count($this->order) > 0) {
            return ' ORDER BY ' . implode(', ', $this->order);
        }
        return '';
    }

}
