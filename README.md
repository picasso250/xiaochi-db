# Simple Pdo Wrapper

Simple Pdo Wrapper enfast your development.

## System Requirements

* PHP >= 5.4

## What it can do?

- update or insert or upsert
- select one line, many lines, a column or a scalar

## Example

**Init**

```php
// 使用dsn，用户名，密码初始化，自动 set names utf8
$db = new DB('mysql:host=localhost;dbname=test', 'username', 'password');
// 返回的对象除了下面要说的方法之外，还可以使用pdo所有的方法
```

**Query data**

```php
// 查询一行数据
// 第一个参数是 SQL 语句，第二个参数（可省略）是一个数组，作为SQL语句的参数
// 参数数组也可以配合 :foo 而使用关联数组
$db->queryRow('SELECT * FROM topic WHERE id = ? LIMIT 1', [3]);

// 查询所有数据，返回一个关联数组的数组
$db->queryAll('SELECT * FROM topic WHERE id > ?', [3]);

// 查询一个值，注意返回的总是字符串
$db->queryScalar('SELECT COUNT(*) FROM topic WHERE id > ?', [3]);
```

**Insert**

```php
// 参数为表名和数据
// 返回最新的id
$id = $db->insert('topic', ['title' => 'bar', 'body' => 'foo']);
```

**Update**

```php
// 参数为表名、数据、where条件，生成的sql如下图所示
// UPDATE topic SET title='bar', body='foo' WHERE id='3'
$db->update('topic', ['title' => 'bar', 'body' => 'foo'], ['id' => 3]);
// 返回 PdoStatement 对象，可以调用 rowCount() 方法查看更改的行数
```

**Raw query**

```php
// 执行任何查询，返回 PdoStatement 对象
$db->execute('UPDATE t SET foo=foo+1 WHERE id=?', [3]);
$db->execute('UPDATE t SET foo=foo+1 WHERE id=:id', ['id' => 3]);
```

Some helpful methods.

```php
// SELECT * from `user` WHERE `id`='3' limit 1
$db->get_user_by_id(3);

// SELECT * from `user` WHERE `type`='admin'
$db->all_user_by_type('admin');

// SELECT COUNT(*) from `user` WHERE `type`='admin'
$db->count_user_by_type('admin');
```

You can get last excuted Sql by

```php
echo $db->lastSql;
```

If you set debug to be true, all SQLs executed will send to `error_log()`

If you set profile to be true, SQL time can be accessed from array `$db->log`.

```php
$db->debug = true;
$db->profile = true;
```

Debug and profile is set to `false` by default.

Other methods are inherited from [PDO class](https://php.net/manual/en/class.pdo.php).

## License ##

(MIT License)

Copyright (c) 2015 wangxiaochi cumt.xiaochi@gmail.com

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
