<?php
include './vendor/autoload.php';

$connectionParams = array(
        'dbname' => 'scrawtest',
        'user' => 'root',
        'password' => 'root1432',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    );

$db = new \Scrawler\Arca\Database($connectionParams);

$book = $db->create('book');
$book->name = 'The Lord of the Rings';
$book->author = 'J.R.R. Tolkien';
$book->save();
