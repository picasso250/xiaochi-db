<?php

require 'vendor/autoload.php';

use Testify\Testify;
use Xiaochi\Model;
use Xiaochi\DB;

$tf = new Testify("Danmu Test Suite");

$tf->test("Testing the get_sub() method", function($tf) {
    $db = new DB('mysql:host=localhost;dbname=danmu', 'root', 'root');
    $raw = $db->queryAll("desc `av`");
    Model::setValidateInfo('av', $raw);
    $tf->assertTrue(Model::validate('av', ['url' => 'hello']));
});
$tf();
