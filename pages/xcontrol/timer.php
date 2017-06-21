<?php

//面向对象的control 类
include "xcontrol/base.php";
//定时任务
class timer extends base
{
	



	function __construct() 
	{
   /*    parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	*/
   	}

	function moneyUnlock()//
	{

		$sql="SELECT * FROM `hb_customer_transaction` where status=1 and type in('1','3') limit 0,1000";

		//1 销售收入(分享收入) 2 购买支出 3分成收入(返利) 5.资金解锁 4.提现 6.分享退款，7.返利退款8购买退款
		//订单号 
		

	}

	function orderCancel()//2小时内不付款取消订单  每5分钟执行一次  需要对data_added  order_status_id字段加索引
	{
		//$sql="select order_id from hb_order where order_status_id=1 and date_added<'".strtotime("- 2 hours")."'";
		//echo $sql;

		$sql="update hb_order set order_status_id=6 where order_status_id=1 and date_added<'".strtotime("- 2 hours")."'";
		if(exeSql($sql))
			file_put_contents("/var/log/log_ordercancel.log", date("Y-m-d H:i:s")."ok:".$sql."\n");
		else
			file_put_contents("/var/log/log_ordercancel.log", date("Y-m-d H:i:s")."No:"$sql."\n");

		exit();

	}
}

