<?php

namespace ptf;

use Pdo;
use PdoException;

class DB
{

    public $lastSql = '';
    public $pdo;
    public $debug = false;

    private $dsn;
    private $username;
    private $password;

    private function reconnect()
    {
        $options = [Pdo::MYSQL_ATTR_INIT_COMMAND => 'set names utf8'];
        $pdo = new Pdo($this->dsn, $this->username, $this->password, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
    }
    public function __construct($dsn, $username, $password)
    {
        list($this->dsn, $this->username, $this->password) = [$dsn, $username, $password];
        $this->reconnect();
    }

    public function execute($sql, $values = array())
    {
        if (is_int(key($values))) {
            $param_arr = array_map(function($e) {
                return $this->quote($e);
            }, $values);
            array_unshift($param_arr, str_replace('?', '%s', $sql));
            $this->lastSql = call_user_func_array('sprintf', $param_arr);
        } else {
            $print_sql = $sql;
            foreach ($values as $k => $v) {
                $print_sql = str_replace(':'.$k, $this->quote($v), $print_sql);
            }
            $this->lastSql = $print_sql;
        }
        if ($this->debug) {
            error_log(__CLASS__.': '.$this->lastSql);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
        } catch (PdoException $e) {
            $errorInfo = $e->errorInfo;
            if ($errorInfo[1] === 2006 && $errorInfo[0] === 'HY000') {
                $this->reconnect();
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($values);
            } else {
                print_r($errorInfo);
                throw $e;
            }
        }
        return $stmt;
    }

    public function update($table, $set, $where)
    {
        $func = function ($field) {
            return "`$field`=?";
        };
        $join = function ($kvs) use ($func) {
            return implode(',', array_map($func, array_keys($kvs)));
        };
        $set_str = $join($set);
        $where_str = $join($where);
        $sql = "update $table set $set_str where $where_str";
        return $this->execute($sql, array_merge(array_values($set), array_values($where)));
    }

    public function insert($table, $values)
    {
        $keys = array_keys($values);
        $columns = implode(',', array_map(function ($field) {
            return "`$field`";
        }, $keys));
        $value_str = implode(',', array_map(function($field){
            return ":$field";
        }, $keys));
        $sql = "insert into $table ($columns)values($value_str)";
        $this->execute($sql, $values);
        return $this->lastInsertId();
    }

    public static function buildWhere($where)
    {
        $func = function ($field) {
            return "`$field`=:$field";
        };
        $join = function ($kvs) use ($func) {
            return implode(',', array_map($func, array_keys($kvs)));
        };
        return $where_str = $join($where);
    }

    public function __call($name, $args)
    {
        if (method_exists($this->pdo, $name)) {
            return call_user_func_array(array($this->pdo, $name), $args);
        }
        if (preg_match('/^all_(\w+)_by_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            $key = $matches[2];
            return $this->queryAll("SELECT * from `$table` where `$key` = ? limit 1000", $args);
        }
        if (preg_match('/^get_all_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            return $this->queryAll("SELECT * from `$table` limit ".intval($args[0]));
        }
        if (preg_match('/^count_(\w+)_by_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            $key = $matches[2];
            $sql = "SELECT COUNT(*) from `$table` where `$key` = ?";
            return intval($this->queryScalar($sql, $args));
        }
        if (preg_match('/^get_(\w+)_by_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            $key = $matches[2];
            return $this->queryRow("SELECT * from `$table` where `$key` = ? limit 1", $args);
        }
        throw new \BadMethodCallException("no $name", 1);
    }

    public function queryAll($sql, $values=array(), $mode=Pdo::FETCH_ASSOC)
    {
        $stmt = $this->execute($sql, $values);
        return $stmt->fetchAll($mode);
    }

    public function queryRow($sql, $values=array(), $mode=Pdo::FETCH_ASSOC)
    {
        $stmt = $this->execute($sql, $values);
        return $stmt->fetch($mode);
    }

    public function queryColumn($sql, $values=array())
    {
        $stmt = $this->execute($sql, $values);
        $ret = [];
        while (($s = $stmt->fetchColumn()) !== false) {
            $ret[] = $s;
        }
        return $ret;
    }
    public function queryScalar($sql, $values=array())
    {
        $stmt = $this->execute($sql, $values);
        return $stmt->fetchColumn();
    }

    public static function timestamp()
    {
        return date('Y-m-d H:i:s');
    }
}