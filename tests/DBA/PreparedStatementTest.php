<?php

class PreparedStatementTest extends PHPUnit_Framework_TestCase
{
    public function getPDO()
    {
        $pdo = new PDO('sqlite::memory:', '', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['\nochso\ORM\DBA\PreparedStatement', [$pdo]]);
        return $pdo;
    }

    /**
     * @covers nochso\ORM\DBA\PreparedStatement::__construct
     */
    public function testConstructor()
    {
        $pdo = $this->getPDO();
        $statement = $pdo->prepare('SELECT name FROM sqlite_master');
        
        $this->assertEquals(get_class($statement), 'nochso\ORM\DBA\PreparedStatement');
    }

    /**
     * @covers nochso\ORM\DBA\PreparedStatement::execute
     */
    public function testExecute()
    {
        $pdo = $this->getPDO();
        $sql = 'SELECT name FROM sqlite_master';
        $statement = $pdo->prepare($sql);
        $ret = $statement->execute();
        $this->assertTrue($ret);

        $log = \nochso\ORM\DBA\DBA::getLog();
        $lastEntry = end($log);
        $this->assertEquals($lastEntry->statement, $sql);
    }

    /**
     * @covers nochso\ORM\DBA\PreparedStatement::execute
     * @expectedException PDOException
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
