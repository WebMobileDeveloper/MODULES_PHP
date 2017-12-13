<?php

define("SQL_HOST", "localhost");
define("SQL_USER", "root");
//define("SQL_PASS", "");
define("SQL_PASS", "PassRoot");
define("SQL_DBASE", "testdb");

try {
    $mysql = new PDO("mysql:dbname=" . SQL_DBASE . ";host=" . SQL_HOST, SQL_USER, SQL_PASS);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mysql->exec("SET CHARACTER SET utf8");
} catch (PDOException $e) {
    exit(json_encode(array('error' => $e->getMessage())));
}


function getQuestionMarks($count)
{
    return str_repeat('?,', $count - 1) . '?';
}
