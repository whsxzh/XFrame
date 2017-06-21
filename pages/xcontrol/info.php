<?php

//面向对象的control 类

class info
{
	var $res=array("code"=>1,"msg"=>"sucess");
	var $userid=0;
	var $username="";

	function __construct() 
	{
      // parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}

	function putinfo()
	{

		saveData("requment",$_POST);
		echo json_encode($this->res);
	}

	function getList()
	{
		if(isset($_GET['page']))
		{
			$page=$_GET['page'];
			if($page<1)$page=1;

			$start=($page-1)*20;
		}
		$data=getData("slect * from requment order by id desc limit $start,20");
		$this->res['data']=$data;
		
	}

	function getDetail()
	{
		//判断是不是购买了本条信息
		if($this->userid>0)
		{
			$data=getData("slect re.* from requment as re,order1 as o where o.requid=re.id and o.userid='".$this-userid."' and re.id=".$_GET['id']);
			$this->res['data']=$data;
		}
		else
		{
			$this->res=array("code"=>0,"msg"=>"您还没有登录");
		}

		echo json_encode($this->res);
	}

	function login()
	{
		if(isset($_POST['name'])&&$_POST['name']&&$_POST['passwd'])
		{
			$_POST['pws']=md5("egg".$_POST['passwd']);
			if($row=getRow("select * from user where name=:name and pws=:pws",$_POST))
			{
				$_SESSION['username']=$row['name'];
				$_SESSION['userid']=$row['id'];
			}
		}
	}

	function regist()
	{
		if(isset($_POST['name'])&&$_POST['name']&&$_POST['passwd']==$_POST['passwd1'])
		{
			$_POST['pws']=md5("egg".$_POST['passwd']);
			saveData("user",$_POST);
		}
	}

}