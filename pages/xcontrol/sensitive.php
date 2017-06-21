<?php

//面向对象的control 类
include "xcontrol/base.php";

class sensitive extends base
{
	



	function __construct() 
	{
       parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}
   	//敏感词管理
	function controlWords(){
		$this->getMenu();
		$sql = "select id,userid,edit_date,text from hb_sensitive where 1 ";
		$info = getRow($sql);
		if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){

			if(!empty($_POST['text'])){
				$table_name = 'hb_sensitive';
				$_POST['userid'] = $_SESSION['userid'];
				$_POST['edit_date'] = date('Y-m-d H:i:s');

				saveData($table_name,$_POST);
				echo 1;die;
			}
			
		}
		$this->res["sid"]=$info['id'];
		$this->res["text"]=$info['text'];
		$this->res["sen_url"]=linkurl("sensitive/controlWords");
		$this->res["home_url"]=linkurl("common/index");
		return $this->res;
	}
	//过滤敏感词
	function filterWords(){
		// $words = $_POST['words'];
		$words = "cc aadd";
		$sql = " select text from hb_sensitive where id = 1 ";
		$data = getRow($sql);
		$text = trim($data['text']);
		$text = explode(" ",$text);
		for($i=0;$i<count($text);$i++){
			$tar = $text[$i];
			if(preg_match("/$tar/", $words)){
				$words = str_replace($tar,"***",$words);	
			}
		}
		// print_r($words);die;
		// $json['recode']  = 2;   //没有匹配到敏感词
		// print_r(json_encode($json));die;
	}
	
}