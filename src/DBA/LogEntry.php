<?php

namespace nochso\ORM\DBA;

class LogEntry
{
    /** @var array $data */
    public $data;
    
    /** @var string $statement */
    public $statement;

    /** @var float $start */
    public $start;

    /** @var float $end */
    public $end;

    /** @var float $duration */
    public $duration;

    /**
     * Create and begin a new log entry
     *
     * @param array $data Hash map with parameter names as keys
     * @param string $statement SQL statement optionally with parameters
     */
    public function __construct($data, $statement)
    {
        $this->data = $data;
        $this->statement = $statement;
        $this->start = microtime(true);
    }

    /**
     * Add the finished entry to the log
     */
    public function finish()
    {
        $this->end = microtime(true);
        $this->duration = $this->end - $this->start;
        DBA::addLog($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $s = round($this->duration, 3) . 's ';
        $s .= ' <b>' . $this->getPrettyStatement() . '</b><br />';
        return $s;
    }

    /**
     * Returns a readable SQL statement with the parameters merged inline.
     * Both numeric and hashed arrays work, but they can't be mixed.
     *
     * @return string
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

    /**
     * Replace only the first occurrence of a string
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    private function str_replace_once($search, $replace, $subject)
    {
        $firstChar = strpos($subject, $search);
        if ($firstChar !== false) {
            $beforeStr = substr($subject, 0, $firstChar);
            $afterStr = substr($subject, $firstChar + strlen($search));
            return $beforeStr . $replace . $afterStr;
        } else {
            return $subject;
        }
    }
}
