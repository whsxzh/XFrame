<?php

header("Content-type: text/html; charset=utf-8");
include "./config.php";
$dbhost = DB_HOSTNAME;

$dbuser = DB_USERNAME;
$dbpass = DB_PASSWORD;
//$dbuser = 'root';
//$dbpass = 'leDian8GoMysqL';


//$dbname = 'appws';
//!!!!!
$dbname = DB_DATABASE;

$timezone = 'PRC';

$appname = '';

date_default_timezone_set($timezone);

$pdoInfo = 'mysql:host=' . $dbhost . ';port=3306;dbname=' . $dbname;




//include "filter.php";//敏感词过滤