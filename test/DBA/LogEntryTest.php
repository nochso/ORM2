<?php

use nochso\ORM\DBA\DBA;
use nochso\ORM\DBA\LogEntry;

class LogEntryTest extends PHPUnit_Framework_TestCase {

    /**
     * @covers \nochso\ORM\DBA\LogEntry::__construct
     */
    public function testConstructor() {
        $data = [':key' => 'value'];
        $statement = 'SELECT * FROM user';
		$entry = new LogEntry($data,$statement);
        $this->assertEquals($entry->statement, $statement);
        $this->assertEquals($entry->data, $data);
        $this->assertGreaterThan(0, $entry->start);
    }

    /**
     * @covers \nochso\ORM\DBA\LogEntry::finish
     */
    public function testFinish() {
        $data = [':key' => 'value'];
        $statement = 'SELECT * FROM user';
        $entry = new LogEntry($data, $statement);
        $entry->finish();

        $this->assertGreaterThanOrEqual($entry->start, $entry->end);

        $log = DBA::getLog();
        $lastEntry = end($log);
        $this->assertEquals($lastEntry, $entry);
    }

}
