<?php

namespace nochso\ORM\DBA;

class LogEntry {

    public $data;
    public $statement;
    public $start;
    public $end;
    public $duration;
    public $trace;

    public function __construct($data, $statement) {
        $this->data = $data;
        $this->statement = $statement;
        $this->start = microtime(true);
    }

    public function finish() {
        $this->end = microtime(true);
        $this->duration = $this->end - $this->start;
        DBA::addLog($this);
    }

    public function __toString() {
        $s = round($this->duration, 3) . 's ';

        // Merge parameters into SQL statement
        $statement = $this->statement;
        foreach ($this->data as $key => $value) {
            $statement = str_replace($key, "'" . $value . "'", $statement);
        }

        $s .= ' <b>' . $statement . '</b><br />';

        foreach (array_reverse($this->trace) as $key => $trace) {
            if ($key > 0) {
                $s .= '-> ';
            } else {
                $s .= '&nbsp;&nbsp; ';
            }
            $s .= str_replace(' ', '&nbsp;', str_pad($trace['line'], 4, ' ', STR_PAD_LEFT)) . ' ' . $trace['class'] . $trace['type'] . $trace['function'] . "()<br />";
        }


        return $s;
    }

}
