<?php
session_start();

error_reporting(E_ALL); 
ini_set('display_errors', '1'); 

include "includes/config.php";
include "includes/connect.php";
include "includes/filter.php";
include "includes/common.php";

include "lib/tmp.php";
include "lib/general.php";
$model=$_REQUEST['m'];
$act=$_REQUEST['act'];


include "xcontrol/".$model.".php";

$key="";
//增加缓存 by xi
if(in_array($model."_".$act, array("home_gethomecontent","customer_getHeadmanInfo1","selelimit_index","groupbuy_groupNewList","groupbuy_groupProductList")))
{
	$mypost=$_REQUEST;

	//查询是不是会员


	if(isset($mypost['customerid']))
		unset($mypost['customerid']);	
	if(isset($mypost['passkey']))
		unset($mypost['passkey']);

	//print_r($mypost);//判断是不是会员
	if(isset($_REQUEST['customerid'])&&$_REQUEST['customerid']>0)
		$isV=isVip($_REQUEST['customerid']);
	else
		$isV=false;

	$key=md5(implode("|", $mypost).$isV);

	if($dt=getCache($key))
	{
		echo json_encode($dt);
		exit;
	}

}


$obj=new $model;

$dt=$obj->$act();


// print_r($_SESSION);
// print_r($dt);
$message=$_REQUEST;
//cgl 2017-6-13  增加API的请求日志

// if($_SERVER["REMOTE_ADDR"]!="::1" && $_SERVER["REMOTE_ADDR"]!="127.0.0.1" && $_SERVER["REMOTE_ADDR"]!="localhost" ){
// 	@file_put_contents(__DIR__.'/RequestLog/Log_'.date('Ymd').'.txt','请求参数['.date('Y-m-d H:i:s').']'.(is_array($message) ? var_export($message, true) : $message)."\n",FILE_APPEND);
// }

// print_r($_REQUEST);

echo json_encode($dt);

if($key!="")
	setCache($key,$dt,600);

// show("xview/".$model."_".$act.".html",$dt);
