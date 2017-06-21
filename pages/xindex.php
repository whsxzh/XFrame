<?php
session_start();



include "includes/config.php";
include "includes/connect.php";
include "includes/filter.php";
include "includes/common.php";

include "lib/tmp.php";
include "lib/general.php";

include_once "lib/log.php";

error_reporting(E_ALL); 
ini_set('display_errors', '1'); 
$model = !empty($_REQUEST['m']) ? $_REQUEST['m'] : 'user' ;
$act = !empty($_REQUEST['act']) ? $_REQUEST['act'] : 'login';


include "xcontrol/".$model.".php";

//增加日志操作
$content=$model."/".$act;
// print_r($_SERVER["REQUEST_URI"]);
// echo $model."/".$act;
$log=new Log();
$log->saveLog($content);

$obj=new $model;

$dt=$obj->$act();

// die;
// print_r($_SESSION);
// print_r($dt);
//if(is_array($dt))
	
	show("xview/".$model."_".$act.".html",$dt);
	
//else
//	show("xview/".$model."_".$act.".html",$obj->res);
