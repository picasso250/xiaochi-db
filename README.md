# Simple Pdo Wrapper

Simple Pdo Wrapper enfast your development.

## System Requirements

* PHP >= 5.3

## What it can do?

- update or insert
- select one line, many lines, a columns or a scalar

## Example

**Init**

```php
$db = new DB('mysql:host=localhost;dbname=test', 'username', 'password');
```

**Query data**

```php
$db->queryAll('SELECT * FROM topic WHERE id > ?', [3]);
$db->queryRow('SELECT * FROM topic WHERE id = ? LIMIT 1', [3]);
$db->queryScalar('SELECT COUNT(*) FROM topic WHERE id > ?', [3]);
```

**Insert**

```php
$id = $db->insert('topic', ['title' => 'bar', 'body' => 'foo']);
```

**Update**

```php
// UPDATE topic SET title='bar', body='foo' WHERE id='3'
$db->update('topic', ['title' => 'bar', 'body' => 'foo'], ['id' => 3]);
```

**Raw query**

```php
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

If you set debug to be true, all Sqls executed will send to `error_log()`

If you set profile to be true, SQL time can be accessed from logs

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
