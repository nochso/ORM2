<?php

namespace nochso\ORM\DBA;

use PDO;
use PDOStatement;

class PreparedStatement extends PDOStatement
{
    private $pdo;

    protected function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute($data = array())
    {
        $logEntry = new LogEntry($data, $this->queryString);
        parent::execute($data);
        $logEntry->finish();
        return true;
    }
}
