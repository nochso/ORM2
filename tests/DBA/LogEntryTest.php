<?php

use nochso\ORM\DBA\DBA;
use nochso\ORM\DBA\LogEntry;

class LogEntryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \nochso\ORM\DBA\LogEntry::__construct
     */
    public function testConstructor()
    {
        $data = array(':key' => 'value');
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
        $data = array(':key' => 'value');
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
     * @covers \nochso\ORM\DBA\LogEntry::str_replace_once
     */
    public function testToString()
    {
        $data = array(':key' => 'value');
        $statement = 'SELECT * FROM user WHERE name = :key';
        $entry = new LogEntry($data, $statement);
        $entry->finish();
        $this->assertContains("SELECT * FROM user WHERE name = 'value'", (string)$entry);
    }

    /**
     * @covers \nochso\ORM\DBA\LogEntry::__toString
     * @covers \nochso\ORM\DBA\LogEntry::getPrettyStatement
     * @covers \nochso\ORM\DBA\LogEntry::str_replace_once
     */
    public function testToStringIndex()
    {
        $data = array('value', 'value2');
        $statement = 'SELECT * FROM user WHERE name = ?';
        $entry = new LogEntry($data, $statement);
        $entry->finish();
        $this->assertContains("SELECT * FROM user WHERE name = 'value'", (string)$entry);
    }
}
