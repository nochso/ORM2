<?php

namespace nochso\ORM\DBA;

use PDO;

class DBA
{
    /**
     * @var PDO $pdo
     */
    private static $pdo;
    private static $log = array();

    public static function connect($dsn, $username, $password, $options = array())
    {
        $logEntry = new LogEntry(array(), "Connecting to database using DSN: " . $dsn);
        self::$pdo = new PDO($dsn, $username, $password, $options);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('nochso\ORM\DBA\PreparedStatement', array(self::$pdo)));
        $logEntry->finish();
    }

    public static function disconnect()
    {
        self::$pdo = null;
    }

    public static function prepare($sql)
    {
        return self::$pdo->prepare($sql);
    }

    /**
     * @param string $sql
     * @param array $data optional
     *
     * @return \PDOStatement
     */
    public static function execute($sql, $data = array())
    {
        $statement = self::$pdo->prepare($sql);
        $statement->execute($data);
        return $statement;
    }

    /**
     * SQLite dialog: escaping of wild card characters
     *
     * @param string $string Unsafe input
     * @param string $escapeChar
     *
     * @return string
     */
    public static function escapeLike($string, $escapeChar = '=')
    {
        return str_replace(
            array(
                $escapeChar,
                '_',
                '%'
            ),
            array(
                $escapeChar . $escapeChar,
                $escapeChar . '_',
                $escapeChar . '%'
            ),
            $string
        );
    }

    public static function escape($string)
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     * @link http://php.net/manual/en/pdo.lastinsertid.php
     * @return string
     */
    public static function lastInsertID()
    {
        return self::$pdo->lastInsertID();
    }

    public static function beginTransaction()
    {
        return self::$pdo->beginTransaction();
    }

    public static function commit()
    {
        return self::$pdo->commit();
    }

    public static function rollBack()
    {
        return self::$pdo->rollBack();
    }

    /**
     * @param LogEntry $entry
     */
    public static function addLog($entry)
    {
        self::$log[] = $entry;
    }

    /**
     * Returns all log entries and optionally removes them
     *
     * @param bool $empty
     * @return LogEntry[]
     */
    public static function getLog($empty = false)
    {
        $log = self::$log;
        if ($empty) {
            self::emptyLog();
        }
        return $log;
    }

    /**
     * Remove all log entries
     */
    public static function emptyLog()
    {
        self::$log = array();
    }
}
