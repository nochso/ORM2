<?php

use ORM\DBA\LogEntry;
use ORM\DBA\DBA;

class LogEntryTest extends PHPUnit_Framework_TestCase {

    /**
     * @covers ORM\DBA\LogEntry::__construct
     */
    public function testConstructor() {
        $data = array(':key' => 'value');
        $statement = 'SELECT * FROM user';
        $entry = new ORM\DBA\LogEntry($data, $statement);
        $this->assertEquals($entry->statement, $statement);
        $this->assertEquals($entry->data, $data);
        $this->assertGreaterThan(0, $entry->start);
    }

    /**
     * @covers ORM\DBA\LogEntry::finish
     */
    public function testFinish() {
        $data = array(':key' => 'value');
        $statement = 'SELECT * FROM user';
        $entry = new ORM\DBA\LogEntry($data, $statement);
        $entry->finish();

        $this->assertGreaterThanOrEqual($entry->start, $entry->end);

        $log = DBA::getLog();
        $lastEntry = end($log);
        $this->assertEquals($lastEntry, $entry);
    }

}
