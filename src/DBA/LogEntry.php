<?php

namespace nochso\ORM\DBA;

class LogEntry {

    public $data;
    public $statement;
    public $start;
    public $end;
    public $duration;

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
        $s .= ' <b>' . $this->getPrettyStatement() . '</b><br />';
        return $s;
    }

    /**
     * @return mixed
     */
    public function getPrettyStatement()
    {
        // Merge parameters into SQL statement
        $statement = $this->statement;
        foreach ($this->data as $key => $value) {
            if (is_numeric($key)) {
                $statement = $this->str_replace_once('?', "'" . $value . "'", $statement);
            } else {
                $statement = str_replace($key, "'" . $value . "'", $statement);
            }
        }
        return $statement;
    }

    private function str_replace_once($search, $replace, $subject) {
        $firstChar = strpos($subject, $search);
        if($firstChar !== false) {
            $beforeStr = substr($subject, 0, $firstChar);
            $afterStr = substr($subject, $firstChar + strlen($search));
            return $beforeStr . $replace . $afterStr;
        } else {
            return $subject;
        }
    }

}
