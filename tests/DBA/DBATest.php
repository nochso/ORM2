<?php

namespace nochso\ORM\Test\DBA;

use nochso\ORM\DBA\DBA;
use nochso\ORM\DBA\LogEntry;
use PHPUnit_Framework_TestCase;

class DBATest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        DBA::connect('sqlite::memory:', '', '');
        DBA::execute(
            'CREATE TABLE user (
				id      INTEGER PRIMARY KEY AUTOINCREMENT
						NOT NULL,
				name    VARCHAR NOT NULL,
				role_id INTEGER NOT NULL
			);'
        );
    }

    public function insertUser()
    {
        DBA::execute("INSERT INTO user (id, name, role_id) VALUES (1, 'user', 1)");
    }

    public function testConnect()
    {
        DBA::disconnect();
        DBA::connect('sqlite::memory:', '', '');
        $this->assertNotNull($this->getPDO());
    }

    public function testBeginTransaction()
    {
        $this->assertTrue(DBA::beginTransaction());
        $this->insertUser();

        // Test that data is available within transaction
        $statement = DBA::execute('SELECT * FROM user WHERE id = 1');
        $row = $statement->fetch();
        $statement->closeCursor();
        $this->assertEquals($row['id'], '1');
    }

    public function testCommit()
    {
        DBA::beginTransaction();
        $this->insertUser();
        DBA::commit();

        $statement = DBA::execute('SELECT * FROM user');
        $row = $statement->fetch();
        $this->assertEquals($row['id'], '1');
    }

    public function testRollBack()
    {
        DBA::beginTransaction();
        $this->insertUser();
        DBA::rollBack();

        // Test that data is actually rolled back
        $statement = DBA::execute('SELECT * FROM user WHERE id = 1');
        $row = $statement->fetch();
        $this->assertFalse($row);
    }

    public function testLastInsertID()
    {
        $this->insertUser();
        $this->assertEquals(DBA::lastInsertID(), 1);
    }

    public function getPDO()
    {
        $reflection = new \ReflectionProperty(DBA::class, 'pdo');
        $reflection->setAccessible(true);
        return $reflection->getValue();
    }

    /**
     * @expectedException \PDOException
     */
    public function testConnectException()
    {
        DBA::connect('fail', '', '');
    }

    public function testDisconnect()
    {
        $this->assertNotNull($this->getPDO());
        DBA::disconnect();
        $this->assertNull($this->getPDO());
    }

    public function testGetLog()
    {
        // Log has entries
        $log = DBA::getLog();
        $this->assertTrue(is_array($log));
        $this->assertGreaterThan(0, count($log));

        // Getting a log and flushing it
        $log = DBA::getLog(true);
        $this->assertTrue(is_array($log));
        $this->assertGreaterThan(0, count($log));

        // After it has been flushed it should be empty
        $log = DBA::getLog(true);
        $this->assertTrue(is_array($log));
        $this->assertEquals(0, count($log));
    }

    public function testAddLog()
    {
        $data = ['foo'];
        $statement = 'SELECT * FROM test';
        $entry = new LogEntry($data, $statement);
        $entry->finish();

        $log = DBA::getLog();
        $lastEntry = end($log);
        $this->assertEquals($lastEntry->duration, $entry->duration);
        $this->assertEquals($lastEntry->statement, $statement);
        $this->assertEquals($lastEntry->data[0], $data[0]);
        $this->assertEquals(count($lastEntry->data), 1);
    }

    public function testEmptyLog()
    {
        $this->assertGreaterThan(0, count(DBA::getLog()));
        DBA::emptyLog();
        $this->assertEquals(count(DBA::getLog()), 0);
    }

    public function testPrepare()
    {
        $sql = 'SELECT * FROM user';
        $statement = DBA::prepare($sql);
        $this->assertEquals($sql, $statement->queryString);
    }

    /**
     * @expectedException \PDOException
     */
    public function testPrepareException()
    {
        $statement = DBA::prepare('SELECT * FROM missingtable');
    }

    public function testExecute()
    {
        // INSERT and check it has been logged
        $username = 'username';
        $sql = "INSERT INTO user (name, role_id) VALUES (?, ?)";
        DBA::execute($sql, [$username, 1]);
        $log = DBA::getLog();
        $lastEntry = end($log);
        $this->assertEquals($lastEntry->statement, $sql);

        // Check the inserted row can be retrieved
        $statement = DBA::execute("SELECT * FROM user");
        $row = $statement->fetch();
        $this->assertEquals($row['name'], $username);
    }

    /**
     * @expectedException \PDOException
     */
    public function testExecuteException()
    {
        DBA::execute("THIS SHOULD FAIL");
    }

    public function testEscapeLike()
    {
        $this->assertEquals("=_=%", DBA::escapeLike("_%"));
    }

    public function testEscape()
    {
        $this->assertEquals("''", DBA::escape("'"));
    }
}
