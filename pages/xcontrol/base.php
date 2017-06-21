<?php

//面向对象的control 类

class base
{
	var $res=array("code"=>1,"msg"=>"sucess");
	var $userid=0;
	var $username="";

	function __construct() 
	{
      // parent::__construct();
		if(isset($_SESSION['userid']) )
		{
			$this->userid=$_SESSION['userid'];
		}
		else
		{
			//跳转到首页
	   		$url=linkurl("user/login");
	   		redirect($url);
			exit;
		}
		
   	}

	function who()
	{

		echo "I am ok,good!";
	}

//判断用户权限 by xi 2017-1-13
	function checkRight($controller)
	{
		$groupid=$_SESSION['user_group_id'];
		$permission=getRow("select permission from ".DB_PREFIX."user_group where user_group_id=$groupid");

		$menuid=getRow("select permission_id from ".DB_PREFIX."permissionx where controller='{$controller}'");

		if(strpos($permission['permission'], $menuid))
			return true;
		else
			return false;

		//print_r($permission);
		//echo __CLASS__."/".__METHOD__;

		/*__CLASS__　　常量返回该类被定义时的名字（区分大小写）。

　　__METHOD__　　类的方法名（PHP 5.0.0 新加）。返回该方法被定义时的名字（区分大小写）。
*/
		
	}

	function getMenu()
	{
		//if(!empty($this->res['parent']))
		//权限判断 
		global $content;
		$checkright=false;
		//	return 1;
		$groupid=$_SESSION['user_group_id'];
		
		$permission=getRow("select permission from ".DB_PREFIX."user_group where user_group_id=$groupid");
		//检查是不是有定义权限
		$right=getData("select permission_id from hb_permissionx where controller='$content'");
		if(empty($right))
			$checkright=true;

		$rep=array("["=>'',"]"=>"","{"=>"","}"=>"","\""=>"",":"=>",");
		$ids=strtr($permission['permission'],$rep);

		if(trim($ids)=="")
			msgback("您的权限不够，无法查看任何东西");

		if($groupid>1)
		$menu=getData("select * from ".DB_PREFIX."permissionx where permission_id in($ids) order by permission_id asc");
		else
			$menu=getData("select * from ".DB_PREFIX."permissionx order by permission_id asc");

		$mymenu=array();

		foreach ($menu as $key => $rec) 
		{
			
			if(trim($rec["controller"])==$content)
				$checkright=true;

			$rec["controller"]=$this->getPath($rec["controller"]);
			if($rec['parent_id']==0)
			{

				$mymenu[$rec['permission_id']]=$rec;
			}
			if(isset($mymenu[$rec['parent_id']]))
			{
				$mymenu[$rec['parent_id']]['son'][]=$rec;
			}
		}

		if(!$checkright)
		{
			msgback("您的权限不足，无法使用本模块");
		}
		//新增 cgl 2017-2-8
		//注销的链接
		$url=linkurl("user/loginout");
		$this->res['loginouturl']=$url;
		//首页的链接
		$this->res['index']=$url=linkurl("common/index");
		//查询登录者的身份
		$id=$_SESSION['userid'];
		$user=getRow("select * from ".DB_PREFIX."user where user_id='$id'");
		$merchant_id=$user['merchant_id'];
		$merchant=getRow("select * from ".DB_PREFIX."merchant where merchant_id='$merchant_id'");
		$user_group=getRow("select * from ".DB_PREFIX."user_group where user_group_id='$groupid'");
		$this->res["merchant_name"]=$merchant["merchant_name"];
		$this->res['merchant_id'] = $merchant['merchant_id'];
		$this->res["merchant_photo_url"]=empty($merchant["mer_headurl"])?"img/admin_default.png":admindefault($merchant["mer_headurl"]);
		$this->res["major_name"]=$user_group["name"];
		$this->res["menu_url"]="xindex.php?".$_SERVER['QUERY_STRING'];
		//exit;
		$this->res['parent']=$mymenu;
			//return $this->res;
		//增加判断 是否有资金权限   cgl   2017-4-12
		$is_has_money_pril=0;
		//是否有订单权限 cgl 2017-5-31
		$is_order_pril=1;

		if(!empty($mymenu)){
			foreach ($mymenu as $k => $v) {
				if($v["controller"]=="xindex.php?m=report&act=send_money"){
					$is_has_money_pril=1;
				}
				// if($v["controller"]=="xindex.php?m=sale&act=index"){
				// 	$is_order_pril=1;
				// }
			}
		}
		if($is_has_money_pril){
			//查询
			$withdraw=getRow("select count(*) as total from hb_withdraw_cash where status=1 and type=1 ");
			$withdraw_total=$withdraw["total"];
		}else{
			$withdraw_total=0;
		}
		//查询订单  未处理退款的
		if($is_order_pril){
			$return_order_total=getRow("select count(*) as total from hb_return as a,hb_order as b where a.order_id = b.order_id and  return_status_id=1 ");
			$return_order_total=$return_order_total["total"];
		}else{
			$return_order_total=0;
		}
		$this->res['return_order_total']=$return_order_total;
		$this->res['is_order_pril']=$is_order_pril;

		//查询有多少用户反馈未处理
		$feedback=getRow("SELECT count(*) as total from hb_feedback as a,hb_customer as b where a.customer_id=b.customer_id and a.status=0 ");
		$feedback_total=$feedback["total"];
		$this->res['feedback_total']=$feedback_total;

		$this->res['withdraw_total']=$withdraw_total;
		$this->res['is_has_money_pril']=$is_has_money_pril;
		
	}

	function getPages($curpage,$totlepage=100)
	{
		$pages=array();

		$path=$_SERVER["REQUEST_URI"];
		$path=preg_replace("/&page=([0-9]+)/","",$path);

		$this->res['firstPage'] = 1;//不是第一页
		if($curpage == $totlepage){

			$bpage=$curpage-2;
			if($bpage<1)
				$bpage=1;
			$epage=$curpage;
			if($epage>$totlepage)
				$epage=$totlepage;
		}else{
			$bpage=$curpage-1;
			if($bpage<1)
				$bpage=1;

			$epage=$curpage+1;
			if($epage>$totlepage)
				$epage=$totlepage;

			//选中第一页时
			$epage_1=$curpage+2;
			if($epage_1<=$totlepage && $curpage==1){
				$this->res['firstPage'] = 0;//第一页
				$epage=$epage_1;
			}
		}

		for($i=$bpage;$i<=$epage;$i++){
			$pages[$i]=array("text"=>$i,"url"=>$path."&page=".$i,"active"=>($i==$curpage?true:false));		
		}

		// 当页数超过3页时，才有"..."
		if($totlepage > 3 && ($curpage < $totlepage-1)){
			$pages[$epage+1]=array("text"=>'...',"url"=>$path."&page=".($epage+1),"active"=>false);
		}

		// 处理当前页为最后一页时，不可点击下一页
		if($curpage == $totlepage){
			$this->res['lastPage'] = 1;
			if($curpage > 3){
				array_unshift($pages,array("text"=>'...',"url"=>$path."&page=".($curpage-3),"active"=>false));
			}
		}
		//当当前页为倒数第二页，在最前面加...
		if($curpage == $totlepage-1 && $curpage > 2){
			array_unshift($pages,array("text"=>'...',"url"=>$path."&page=".($curpage-2),"active"=>false));
		}

		$this->res['pages']=$pages;
		$this->res['curpage']=$curpage;
		$this->res['pageurl']=$path;
		$this->res['totlepage'] = $totlepage;
	}

	/*
	 * wangzhichao
	 * 用于商品库，一次显示9页，并有跳转页面
	 */
	function getPage($curpage,$totlepage=100)
	{
		$pages=array();

		$path=$_SERVER["REQUEST_URI"];
		$path=preg_replace("/&page=([0-9]+)/","",$path);
		if($totlepage < 9){
			$bpage = 1;
			$epage = $totlepage;
		}else{
			if($totlepage-$curpage < 4){
				$bpage = $totlepage-8;
				$epage = $totlepage;
			}else{
				if($curpage-1<4){
					$bpage = 1;
					$epage = 9;
				}else{
					$bpage = $curpage-4;
					$epage = $curpage+4;
				}
			}
		}


		for($i=$bpage;$i<=$epage;$i++){
			$pages[$i]=array("text"=>$i,"url"=>$path."&page=".$i,"active"=>($i==$curpage?true:false));
		}

		// 处理当前页为最后一页时，不可点击下一页
		if($curpage == $totlepage){
			$this->res['lastPage'] = 1;
		}

		if($curpage > 1){
			$this->res['firstPage'] = 1;
		}

		$this->res['pages']=$pages;
		$this->res['curpage']=$curpage;
		$this->res['pageurl']=$path;
		$this->res['totlepage'] = $totlepage;
	}

	function getPath($path)
	{
		$tmp=explode("/", $path);
		if(isset($tmp[1]))
			return "xindex.php?m=".$tmp[0]."&act=".$tmp[1];
		else
			return "";
	}

	/**
	 * 获取菜单   cgl   12.29
	 */
	/*
	*12.29  ws  验证权限
	*/
	/*function  check(){

		$user_id = isset($_SESSION['userid'])?$_SESSION['userid']:null;
		$all_userinfo=getRow("select * from ".DB_PREFIX."user where user_id=$user_id");
	 	//status==1 ,状态1 为启用
	 	if($all_userinfo['status']==1){
	 		$all_groupinfo=getRow("select * from ".DB_PREFIX."user_group where user_group_id=".$all_userinfo['user_group_id']);
	 		$all_permissions=json_decode($all_groupinfo['permission']);
	 		//查询所有的权限
	 		$all=getData("select * from ".DB_PREFIX."permission order by sort ASC");
	 		$i = 0;
	 		$all_permission=array();
	 		if(!empty($all)){
	 			foreach($all as $k=>$v){
	 				$all[$k]["controller"]=linkurl($v["controller"]);
	 			}
	 		}
	 		if(!empty($all)){
	 			$all_pers=array();
	 			foreach($all as $k=>$v){
	 				if(!empty($all_permissions)){
	 					foreach($all_permissions as $kk=>$vv){
	 						$a12="";
	 						if($kk==$v["permission_id"]){
	 							$all_pers["parent"][$kk]=$v;
	 							$all_pers["parent"][$kk]["son1"]=$vv;
	 						}
	 						$i++;
	 					}
	 				}
	 			}
	 		}
	 		if(!empty($all)){
	 			foreach($all as $k=>$v){
	 				if(!empty($all_pers)){
	 					foreach($all_pers as $kk=>$vv){
	 						foreach($vv as $kkk=>$vvv){
 								if(!empty($vvv["son1"])){
 									foreach($vvv["son1"] as $kkkk=>$vvvv){
 										if($v["permission_id"]==$vvvv){
 											$all_pers["parent"][$kkk]["son"][]=$v;
 										}
 									}
 								}
 							}
	 					}
	 				}
	 			}
	 		}
			$this->res=$all_pers;
			return $this->res;
	 	}
	}*/
	function expressInfo($com,$nu){
		if(empty($com)){
			$com = 'auto';
		}
		$host = "https://ali-deliver.showapi.com";
	    $path = "/showapi_expInfo";
	    $method = "GET";
	    $appcode = "c404a4d4e17f442cbac7da89f78221e7";
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "com=$com&nu=$nu";								//com=auto,可自动识别快递单号
	    $bodys = "";
	    $url = $host . $path . "?" . $querys;

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    // curl_setopt($curl, CURLOPT_HEADER, true);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    $expressInfo = curl_exec($curl);
	    return $expressInfo;
	}

}