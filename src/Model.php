<?php

namespace Xiaochi;

/**
* 
*/
class Model
{
    public static $validateInfo;
    public static function validateField($key, $value, $Field)
    {
        $Type = $Field['Type'];
        if (preg_match('/^([a-z]int)\(\d+\)( unsigned)/', $Type, $matches)) {
            $t = $matches[1];
            $map = [
                'bigint' => ['min_range' => -9223372036854775808, 'max_range' => 9223372036854775807],
                'int' => ['min_range' => -2147483648, 'max_range' => 2147483647],
                'smallint' => ['min_range' => -32768, 'max_range' => 32767],
                'tinyint' => ['min_range' => -128, 'max_range' => 127],
            ];
            $options = $map[$t];
            if (isset($matches[2])) {
                $options['min_range'] = 0;
                $options['max_range'] = $options['max_range'] * 2 + 1;
            }
            if (false === filter_var($value, FILTER_VALIDATE_INT, ['options' => $options])) {
                throw new \InvalidArgumentException("$key not valid as $t", 3);
            }
            return $value;
        }
        if (preg_match('/^(?:var)?char\((\d+)\)$/', $Type, $matches)) {
            $len = $matches[1];
            if (strlen($value) > $len) {
                throw new \InvalidArgumentException("$key should not exceed $len", 3);
            }
            return $value;
        }
        return $value;
    }
    public static function setValidateInfo($table, $raw)
    {
        foreach ($raw as $key => $value) {
            self::$validateInfo[$table][$value['Field']] = $value;
        }
        return;
    }
    public static function validate($table, $data)
    {
        $validate = self::$validateInfo[$table];
        foreach ($data as $key => &$value) {
            if (!isset($validate[$key])) {
                throw new \InvalidArgumentException("there is not $key", 3);
            }
            $value = self::validateField($key, $value, $validate[$key]);
        }
        return true;
    }
    public static function saveTable($table, $data)
    {
        if ($msg = self::validate($table, $data)) {
            throw new \InvalidArgumentException($msg, 2);
        }
        if (isset($data['id'])) {
            return self::insert($table, $data);
        } else {
            return self::update($table, $data, ['id' => $data['id']]);
        }
    }
    public static function __callStatic($name, $args)
    {
        if (preg_match('/^save_([\w_]+)$/', $name, $matches)) {
            return self::saveTable($matches[1], $args[0]);
        }
        throw new \BadMethodCallException("no $name", 1);
    }
}
