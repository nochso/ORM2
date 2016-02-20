<?php

namespace nochso\ORM\DBA;

use PDO;
use PDOStatement;

class PreparedStatement extends PDOStatement
{
    protected function __construct(PDO $pdo)
    {
    }

    public function execute($data = [])
    {
        $logEntry = new LogEntry($data, $this->queryString);
        parent::execute($data);
        $logEntry->finish();
        return true;
    }
}
