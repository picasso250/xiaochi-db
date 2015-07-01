<?php

namespace Xiaochi;

class SqlBuilder
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->values = array();
        $this->select = '*';
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
        } elseif (strpos($method, 'query') === 0) {
            $sql = "SELECT $this->select from $this->from ";
            if (isset($this->where)) {
                $sql .= " WHERE $this->where ";
            }
            foreach (array('group', 'order', 'limit') as $verb) {
                if (isset($this->$verb)) {
                    // echo "build $this-";
                    $sql .= " $map[$verb] {$this->$verb} ";
                }
            }
            var_dump($sql);
            return $this->db->$method($sql, $this->values);
        } else {
            throw new \Exception("no mehtod $mehtod", 1);
        }
        return $this;
    }
    public function where($str, $values) {
        $this->where = $str;
        $this->values = $values;
        return $this;
    }
}
