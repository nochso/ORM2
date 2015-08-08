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
        $s = number_format($this->duration, 3) . "s\t";
        $s .= $this->getPrettyStatement() . "\n";
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
        $assoc = array();
        $statement = $this->statement;
        foreach ($this->data as $key => $value) {
            if (is_numeric($key)) {
                $statement = $this->strReplaceOnce('?', "'" . $value . "'", $statement);
            } else {
                $assoc[] = $key;
            }
        }
        // Sort non-numeric keys descending by their string length.
        usort($assoc, function ($a, $b) {
            $alen = strlen($a);
            $blen = strlen($b);
            if ($a === $b) {
                return 0;
            }
            return $a > $b ? -1 : 1;
        });
        // Replace the longest keys first. This avoids conflicts/overlaps of shorter keys.
        foreach ($assoc as $key) {
            $statement = str_replace($key, "'" . $this->data[$key] . "'", $statement);
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
    private function strReplaceOnce($search, $replace, $subject)
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
