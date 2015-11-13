<?php

namespace Xiaochi;

use Pdo;
use PdoException;

class DB
{

    public $lastSql = '';
    public $pdo;
    public $debug = false;
    public $profile = false;
    public $log = [];
    public $errorInfo;

    private $dsn;
    private $username;
    private $password;

    private function reconnect()
    {
        if ($this->debug) {
            error_log(__CLASS__.': Connect '.$this->dsn);
        }
        if (strpos($this->dsn, 'sqlite:') === 0) {
            $pdo = new Pdo($this->dsn);
        } else {
            $options = array(Pdo::MYSQL_ATTR_INIT_COMMAND => 'set names utf8');
            $pdo = new Pdo($this->dsn, $this->username, $this->password, $options);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
        $this->errorInfo = null;
    }
    public function __construct($dsn, $username = null, $password = null)
    {
        list($this->dsn, $this->username, $this->password) = array($dsn, $username, $password);
    }

    public function execute($sql, $values = array())
    {
        if (!$this->pdo) {
            $this->reconnect();
        }
        if (!is_array($values)) {
            throw new \Exception("no array", 1);
        }
        if (is_int(key($values))) {
            $param_arr = array();
            foreach ($values as $e) {
                $param_arr[] = $this->pdo->quote($e);
            }
            array_unshift($param_arr, str_replace('?', '%s', $sql));
            $this->lastSql = call_user_func_array('sprintf', $param_arr);
        } else {
            $print_sql = $sql;
            foreach ($values as $k => $v) {
                if ($v !== null && !is_scalar($v)) {
                    throw new \Exception("not scalar", 1);
                }
                $print_sql = str_replace(':'.$k, $v === null ? 'NULL' : $this->pdo->quote($v), $print_sql);
            }
            $this->lastSql = $print_sql;
        }
        if ($this->debug && !$this->profile) {
            error_log(__CLASS__.': '.$this->lastSql);
        }

        $this->errorInfo = null;
        $t = microtime(true);
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
                throw $e;
            }
        }
        $d = intval((microtime(true) - $t) * 1000);
        if ($this->profile) {
            $this->log[] = [$this->lastSql, $d];
            if ($this->debug) {
                error_log(sprintf(__CLASS__.': %s [%s ms]', $this->lastSql, $d));
            }
        }
        return $stmt;
    }

    public function delete($table, $where)
    {
        $func = function ($field) {
            return "`$field`=?";
        };
        $join = function ($kvs) use ($func) {
            return implode(',', array_map($func, array_keys($kvs)));
        };
        $where_str = $join($where);
        $sql = "DELETE FROM $table WHERE $where_str";
        return $this->execute($sql, array_values($where));
    }
    
    public function update($table, $set, $where)
    {
        $func = function ($field) {
            return "`$field`=?";
        };
        $join = function ($kvs, $op = ',') use ($func) {
            return implode($op, array_map($func, array_keys($kvs)));
        };
        $set_values = array();
        foreach ($set as $key => $value) {
            if (is_int($key)) {
                $set_arr[] = $value;
            } else {
                $set_values[] = $value;
                $set_arr[] = $func($key);
            }
        }
        $set_str = implode(', ', $set_arr);
        $where_str = $join($where, ' AND ');
        $sql = "UPDATE $table SET $set_str WHERE $where_str";
        return $this->execute($sql, array_merge($set_values, array_values($where)));
    }

    public function upsert($table, $values)
    {
        $keys = array_keys($values);
        $columns = implode(',', array_map(function ($field) {
            return "`$field`";
        }, $keys));
        $value_str = implode(',', array_map(function($field){
            return ":$field";
        }, $keys));
        $func = function ($field) {
            return "`$field`=:$field";
        };
        $set_values = array();
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $set_arr[] = $value;
            } else {
                $set_values[] = $value;
                $set_arr[] = $func($key);
            }
        }
        $set_str = implode(', ', $set_arr);
        $sql = "INSERT INTO `$table` ($columns) VALUES ($value_str) ON DUPLICATE KEY UPDATE $set_str";
        return $this->execute($sql, $values);
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
        $sql = "INSERT INTO `$table` ($columns) VALUES ($value_str)";
        $this->execute($sql, $values);
        return $this->lastInsertId();
    }
    public function insertMany($table, $values)
    {
        if (empty($values)) {
            return;
        }
        $keys = array_keys($values[0]);
        $columns = implode(',', array_map(function ($field) {
            return "`$field`";
        }, $keys));
        $str = implode(',', array_map(function(){
            return '?';
        }, $keys));
        $value_str = implode(',', array_map(function($field) use($str){
            return "($str)";
        }, $values));
        $vs = [];
        foreach ($values as $row) {
            foreach ($row as $v) {
                $vs[] = $v;
            }
        }
        $sql = "INSERT INTO `$table` ($columns) VALUES $value_str";
        $this->execute($sql, $vs);
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
            $keys = $matches[2];
            $where = self::buildWhereAnd($keys);
            $sql = "SELECT * from `$table` where $where limit 1000";
            return $this->queryAll($sql, $args);
        }
        if (preg_match('/^all_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            $sql = "SELECT * from `$table` limit 1000";
            return $this->queryAll($sql);
        }
        if (preg_match('/^count_(\w+)_by_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            $keys = $matches[2];
            $where = self::buildWhereAnd($keys);
            $sql = "SELECT COUNT(*) from `$table` where $where";
            return intval($this->queryScalar($sql, $args));
        }
        if (preg_match('/^count_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            $sql = "SELECT COUNT(*) from `$table`";
            return intval($this->queryScalar($sql, $args));
        }
        if (preg_match('/^get_(\w+)_by_(\w+)$/', $name, $matches)) {
            $table = $matches[1];
            $keys = $matches[2];
            $where = self::buildWhereAnd($keys);
            $sql = "SELECT * FROM `$table` WHERE $where limit 1";
            return $this->queryRow($sql, $args);
        }
        throw new \BadMethodCallException("no $name", 1);
    }
    public static function buildWhereAnd($keys)
    {
        $keys = explode('_and_', $keys);
        return $where = implode(' AND ', array_map(function($key){
            return "`$key`=?";
        }, $keys));
    }

    public function queryAll($sql, $values=array(), $mode=Pdo::FETCH_ASSOC)
    {
        $stmt = $this->execute($sql, $values);
        return $stmt->fetchAll($mode) ?: array();
    }

    public function queryRow($sql, $values=array(), $mode=Pdo::FETCH_ASSOC)
    {
        $stmt = $this->execute($sql, $values);
        return $stmt->fetch($mode);
    }

    public function queryColumn($sql, $values=array())
    {
        $stmt = $this->execute($sql, $values);
        $ret = array();
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

    public static function timestamp($time = null)
    {
        $format = 'Y-m-d H:i:s';
        if ($time === null) {
            return date($format);
        }
        return date($format, $time);
    }
    public function createCommand($sql = null)
    {
        return new SqlBuilder($this, $sql);
    }
}
