<?php

namespace nochso\ORM\Test\DBA;

use nochso\ORM\DBA\DBA;
use nochso\ORM\DBA\LogEntry;
use nochso\ORM\Test\Model\User;

class LogEntryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \nochso\ORM\DBA\LogEntry::__construct
     */
    public function testConstructor()
    {
        $data = [':key' => 'value'];
        $statement = 'SELECT * FROM user';
        $entry = new LogEntry($data, $statement);
        $this->assertEquals($entry->statement, $statement);
        $this->assertEquals($entry->data, $data);
        $this->assertGreaterThan(0, $entry->start);
    }

    /**
     * @covers \nochso\ORM\DBA\LogEntry::finish
     */
    public function testFinish()
    {
        $data = [':key' => 'value'];
        $statement = 'SELECT * FROM user';
        $entry = new LogEntry($data, $statement);
        $entry->finish();

        $this->assertGreaterThanOrEqual($entry->start, $entry->end);

        $log = DBA::getLog();
        $lastEntry = end($log);
        $this->assertEquals($lastEntry, $entry);
    }

    /**
     * @covers \nochso\ORM\DBA\LogEntry::__toString
     * @covers \nochso\ORM\DBA\LogEntry::getPrettyStatement
     * @covers \nochso\ORM\DBA\LogEntry::strReplaceOnce
     */
    public function testToString()
    {
        $data = [':key' => 'value'];
        $statement = 'SELECT * FROM user WHERE name = :key';
        $entry = new LogEntry($data, $statement);
        $entry->finish();
        $this->assertContains("SELECT * FROM user WHERE name = 'value'", (string)$entry);
    }

    /**
     * @covers \nochso\ORM\DBA\LogEntry::__toString
     * @covers \nochso\ORM\DBA\LogEntry::getPrettyStatement
     * @covers \nochso\ORM\DBA\LogEntry::strReplaceOnce
     */
    public function testToStringIndex()
    {
        $data = ['value', 'value2'];
        $statement = 'SELECT * FROM user WHERE name = ?';
        $entry = new LogEntry($data, $statement);
        $entry->finish();
        $this->assertContains("SELECT * FROM user WHERE name = 'value'", (string) $entry);
    }

    public function testToStringMany()
    {
        $ids = range(1, 12);
        $users = User::select()->in('id', $ids)->all();
        $log = DBA::getLog();
        $last = end($log);
        $expected = "0.000s	SELECT * FROM `user` WHERE id IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12')\n";
        $this->assertEquals($expected, (string) $last);
    }
}
