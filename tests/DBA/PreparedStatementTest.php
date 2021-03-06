<?php

namespace nochso\ORM\Test\DBA;

use nochso\ORM\DBA\DBA;
use nochso\ORM\DBA\PreparedStatement;

class PreparedStatementTest extends \PHPUnit_Framework_TestCase
{
    public function getPDO()
    {
        $pdo = new \PDO('sqlite::memory:', '', '');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [PreparedStatement::class, [$pdo]]);
        return $pdo;
    }

    public function testConstructor()
    {
        $pdo = $this->getPDO();
        $statement = $pdo->prepare('SELECT name FROM sqlite_master');
        
        $this->assertEquals(get_class($statement), PreparedStatement::class);
    }

    public function testExecute()
    {
        $pdo = $this->getPDO();
        $sql = 'SELECT name FROM sqlite_master';
        $statement = $pdo->prepare($sql);
        $ret = $statement->execute();
        $this->assertTrue($ret);

        $log = DBA::getLog();
        $lastEntry = end($log);
        $this->assertEquals($lastEntry->statement, $sql);
    }

    /**
     * @expectedException \PDOException
     * @expectedExceptionMessage General error: 17 database schema has changed
     */
    public function testExecuteException()
    {
        $pdo = $this->getPDO();
        $sql = 'CREATE TABLE user (
				id      INTEGER PRIMARY KEY AUTOINCREMENT
						NOT NULL,
				name    VARCHAR NOT NULL,
				role_id INTEGER NOT NULL
			);';
        $statement = $pdo->prepare($sql);
        $ret = $statement->execute();
        $ret = $statement->execute();
    }
}
