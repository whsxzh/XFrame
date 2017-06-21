<?php

//面向对象的control 类
include "xcontrol/base.php";
class invite extends base
{
	



	function __construct() 
	{
       parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}

	function edit()
	{
		if(isset($_GET["id"]))
		{
			$this->res=getRow("select c.lastname,c.firstname,i.* from hb_invitecode as i,hb_customer as c where i.customer_id=c.customer_id and  i.invitecode_id=".$_GET['id']);

			return $this->res;
		}
		

		
		
	}

	function getList()
	{
		if(isset($_POST['telephone'])&&strlen($_POST['invitecode_id'])==0)//保存
		{
			//获得用户信息
			$cust=getRow("select lastname,customer_id,telephone from hb_customer where telephone='".$_POST['telephone']."'");
			if(isset($cust['customer_id']))
			{

					$_POST['customer_id']=$cust['customer_id'];
					//print_r($_POST);
				saveData("hb_invitecode",$_POST);
			}
			else
			{
				echo "这个手机没有注册";
			}
		}



		if(isset($_GET['page']))
		{
			$page=$_GET['page'];
			if($page<1)$page=1;
		}
		else	
		{
			$page=1;
		}

		$start=($page-1)*20;
		
		$wherestr="";
		if(isset($_POST['search']))
		{
			$search=$_POST['search'];
			$wherestr=" and (c.lastname like '%{$search}%' or c.firstname like '%{$search}%' or c.telephone like '%{$search}%') ";
		}

		$data=getData("select c.lastname,c.firstname,i.* from hb_invitecode as i,hb_customer as c where i.customer_id=c.customer_id $wherestr order by invitecode_id desc limit $start,20 ");
		
		$this->res['page']=$page;
		$this->res['data']=$data;
		return $this->res;
	}

	function disable()
	{
		if(isset($_GET['id']))
		{
			if($_GET["status"]=='1')
				$status='0';
			else
				$status='1';

			saveData("hb_invitecode" , array('invitecode_id' =>$_GET['id'] ,"status"=>$status ));
		}
	}

	function getDetail()
	{
		//判断是不是购买了本条信息
		if($this->userid>0)
		{
			$data=getData("select re.* from requment as re,order1 as o where o.requid=re.id and o.userid='".$this-userid."' and re.id=".$_GET['id']);
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




	
	function code(){
		/*$data=getData("SELECT COUNT(c.`customer_id`  ) as total ,c.`invitecode_id` ,c.`parent_id`,p.lastname,p.firstname   from `hb_customer` as c,`hb_customer` as p  where c.`parent_id`=p.customer_id   GROUP BY c.`invitecode_id`,c. `parent_id` ");*/
		//print_r($data);
		$data=getData("SELECT COUNT(c.`customer_id`  ) as total ,c.`invitecode_id` ,c.`parent_id`,p.lastname,p.firstname,p.telephone,i.`invitecode`   from `hb_customer` as c,`hb_customer` as p ,`hb_invitecode` as i where c.`parent_id`=p.customer_id and c.invitecode_id=i.`invitecode_id`   GROUP BY c.`invitecode_id`,c. `parent_id` ");
		$this->res['data']=$data;
		return $this->res;
	}
}

