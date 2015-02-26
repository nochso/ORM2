<?php

namespace nochso\ORM\DBA;

use PDO;
use PDOStatement;

//use ORM\DBA as DBA;
//use ORM\DBA\LogEntry;

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
