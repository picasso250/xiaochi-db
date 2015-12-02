<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Testify\Testify;
use Xiaochi\Model;
use Xiaochi\DB;

$tf = new Testify("Danmu Test Suite");
$tf->gfw = true;

$tf->before(function($tf) {
    $tf->db = $db = new DB('mysql:host=localhost;dbname=test', 'root', 'root');
    $db->execute("CREATE TABLE IF NOT EXISTS test (
        id INT unsigned not null AUTO_INCREMENT primary key,
        col VARCHAR(80) not null)");
});

// $tf->test("Testing the get_sub() method", function($tf) {
//     $raw = $db->queryAll("desc `av`");
//     Model::setValidateInfo('av', $raw);
//     $tf->assertTrue(Model::validate('av', ['url' => 'hello']));
// });

$tf->test("Testing insert()", function ($tf) {
    $text = 'a'.uniqid();
    $tf->db->insert("test", ['col' => $text]);
    $rows = $tf->db->all_test_by_col($text);
    $tf->assertEquals(count($rows), 1, "Insert a raw at a time");
});

$tf->after(function ($tf) {
    $tf->db->execute("DROP TABLE test");
});

$tf();
