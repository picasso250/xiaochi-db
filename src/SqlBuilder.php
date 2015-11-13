<?php

namespace Xiaochi;

class SqlBuilder
{
    public function __construct($db, $sql = null)
    {
        $this->db = $db;
        $this->values = array();
        $this->select = '*';
        $this->sql = $sql;
    }
    public function queryScalar($values = [])
    {
        if ($this->sql) {
            return $this->db->$method($this->sql, $values);
        }
        $sql = $this->buildSql();
        return $this->db->$method($sql, $this->values);
    }
    public function __call($method, $args)
    {
        $map = array(
            'from' => 'FROM',
            'group' => 'GROUP BY',
            'order' => 'ORDER BY',
            'limit' => 'LIMIT',
            'select' => 'SELECT',
        );
        if (isset($map[$method])) {
            // echo "assgin $method $args[0]\n";
            $this->{$method} = $args[0];
            // print_r($this);
        } elseif (strpos($method, 'query') === 0 || $method === 'execute') {
            if ($this->sql) {
                return $this->db->$method($this->sql, $args[1]);
            }
            $sql = $this->buildSql();
            return $this->db->$method($sql, $this->values);
        } else {
            throw new \Exception("no mehtod $mehtod", 1);
        }
        return $this;
    }
    private function buildSql()
    {
        $sql = "SELECT $this->select from $this->from ";
        if (!empty($this->join)) {
            $sql .= " $this->join ";
        }
        if (isset($this->where)) {
            $sql .= " WHERE $this->where ";
        }
        foreach (array('group', 'order', 'limit') as $verb) {
            if (isset($this->$verb)) {
                // echo "build $this-";
                $sql .= " $map[$verb] {$this->$verb} ";
            }
        }
        return $sql;
    }
    public function where($str, $values = array()) {
        $this->where = $str;
        $this->values = $values;
        return $this;
    }
    public function join($str, $on) {
        $this->join = "JOIN $str ON $on";
        return $this;
    }
}
