<?php
	include "xcontrol/base.php";
	class proxy extends base
	{
		var $res=array("retcode"=>0,'msg'=>'success');
		function __construct() 
		{
	       $this->passkey=@$_SESSION["default"]['passkey'];
		   $this->customer_id=@$_SESSION["default"]['customer_id'];
	   	}
	   	/**
	     * 成为会员  申请  cgl  2017-4-6
	     */
	    function becomeMember(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		$customer=getRow("select * from hb_customer where customer_id= '".$this->customer_id."' ");
	    		$invitecode=isset($_POST["invitecode"])?$_POST["invitecode"]:NULL;//cgl  2017-4-10

	    		//判断该用户是否是会员
	    		if($customer){
	    			if($customer["merchant_id"]!=0){
	    				$this->res["retcode"]=1011;
		       			$this->res["msg"]="该用户已是会员";
	    			}else{
	    				//如果有邀请码
	    				if(!empty($invitecode)){
	    					//检查邀请码
	    					$is_right_code=getRow("SELECT * FROM `hb_invitecode` WHERE `invitecode` = '". $invitecode ."' AND `status` = '0' ");
	    					if(isset($is_right_code) && !empty($is_right_code['customer_id'])){
	    						$parent_id=$is_right_code['customer_id'];
	    						$invitecode_id=$is_right_code['invitecode_id'];
	    						exeSql("UPDATE `hb_customer` SET `merchant_id` = '1',parent_id = '" .(int)$parent_id. "',invitecode_id = '" .$invitecode_id. "',proxy_time = now() WHERE `customer_id` = '". (int)$this->customer_id ."'");
	    						exeSql("update `hb_invitecode` set times = times+1 where invitecode_id = '" .(int)$invitecode_id. "'");
	    						$this->res['url']=!empty($is_right_code['url'])?$is_right_code['url']:'';
	    						//刷新缓存 by xi
	    						isVip((int)$this->customer_id,true);
	    					}else{
	    						$this->res["retcode"]=1112;
			       				$this->res["msg"]="你的邀请码错误";
	    					}
	    				}else{
	    					$is_buy=getRow("select * from hb_order where customer_id ='".$this->customer_id."' and is_member_status=1 ");
				    		$isbuy_status=0;
				    		if($is_buy){
				    			$isbuy_status=1;//已经购买
				    		}
				    		//是否消费满  8888元
				    		$sale=getRow("select count(*) as count,SUM(total) as total from hb_order where customer_id ='".$this->customer_id."' and order_status_id in (4,5) ");
				    		$is_enough=0;//是否消费满8888
				    		if(@(int)$sale["total"]>=8888){
				    			$is_enough=1;
				    		}
				    		$is_order=0;//是否下单满50
				    		if(@$sale["count"]>=50){
				    			$is_order=1;
				    		}
				    		//是否满足条件  点击申请
				    		$is_apply=0;
				    		if($isbuy_status || $is_enough || $is_order){
				    			$is_apply=1;//满足条件
				    		}
				    		if($is_apply==1){
				    			//可以申请成为会员
				    			exeSql("update hb_customer set merchant_id=1,proxy_time=NOW(),proxy_status=0 where customer_id= '".$this->customer_id."'  ");
				    			//刷新缓存 by xi
	    						isVip((int)$this->customer_id,true);
				    		}else{
				    			$this->res["retcode"]=1230;
			       				$this->res["msg"]="该用户不满足成为会员条件";
				    		}
	    				}
	    			}
	    		}else{
	    			$this->res["retcode"]=1101;
		       		$this->res["msg"]="没有该用户";
	    		}
    		}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
	    }
	    /**
	     * 查询这个会员是否满足申请的条件
	     */
	    function memberCondition(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		// $customer=getRow("select * from hb_customer where customer_id= '".$this->customer_id."' ");
	    		//判断是否购买成为会员
	    		$is_buy=getRow("select * from hb_order where customer_id ='".$this->customer_id."' and is_member_status=1 and order_status_id=2 and order_type=4 ");
	    		$isbuy_status=0;
	    		if($is_buy){
	    			$isbuy_status=1;//已经购买
	    		}
	    		//是否消费满  8888元
	    		$sale=getRow("select count(*) as count,SUM(total) as total from hb_order where customer_id ='".$this->customer_id."' and order_status_id in (4,5) ");
	    		$is_enough=0;//是否消费满8888
	    		if(@(int)$sale["total"]>=8888){
	    			$is_enough=1;
	    		}
	    		$is_order=0;//是否下单满50
	    		if(@$sale["count"]>=50){
	    			$is_order=1;
	    		}
	    		//是否满足条件  点击申请
	    		$is_apply=0;
	    		if($isbuy_status || $is_enough || $is_order){
	    			$is_apply=1;//满足条件
	    		}
	    		$data["is_apply"]=$is_apply;
	    		$data["buy_condition"]=array(
	    			"member_money"=>188,
	    			"buy_status"=>$isbuy_status
	    			);//购买条件
	    		$data["order_condition"]=array(
	    			"order_number"=>50,
	    			"order_accept_number"=>empty($sale["count"])?0:(int)$sale["count"],
	    			"order_status"=>$is_order
	    			);//订单条件
	    		$data["shop_condition"]=array(
	    			"member_money"=>8888,
	    			"shop_money"=>empty($sale["total"])?0:(float)$sale["total"],
	    			"enough_status"=>$is_enough
	    			);//消费条件
	    		$this->res["data"]=$data;

	    		// echo "是否购买会员：".$isbuy_status."<br/>";
	    		// echo "是否消费满8888：".$is_enough."<br/>";
	    		// echo "是否消费满50单：".$is_order;
	    		
    		}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
	    }

		/*
		 * 成为会员
		 * wangzhichao 17.4.15
		 */
		function becomeProxy(){
			$base = (array)new base();
			if($base['res']['retcode'] > 0){
				return $base['res'];
			}

			$customer=getRow("select * from hb_customer where customer_id= '".$base['customer_id']."' ");

			$invitecode=isset($_POST["invitecode"])?$_POST["invitecode"]:NULL;
			$this->res['data'] = array();
			//判断该用户是否存在
			if(empty($customer)){
				$this->res = array(
					"retcode"	=>1101,
					"msg"		=>"没有该用户"
				);
				return $this->res;
			}

			//判断该用户是否是会员
			if($customer["merchant_id"]!=0){
				$this->res = array(
					"retcode"	=>1011,
					"msg"		=>"该用户已是会员"
				);
				return $this->res;
			}

			//如果有邀请码
			if(!empty($invitecode)){
				//检查邀请码
				$is_right_code=getRow("SELECT * FROM `hb_invitecode` WHERE `invitecode` = '". $invitecode ."' AND `status` = '0' ");
				if(isset($is_right_code) && !empty($is_right_code['customer_id'])){
					$parent_id=$is_right_code['customer_id'];
					$invitecode_id=$is_right_code['invitecode_id'];
					exeSql("UPDATE `hb_customer` SET `merchant_id` = '1',parent_id = '" .(int)$parent_id. "',invitecode_id = '" .$invitecode_id. "',proxy_time = now() WHERE `customer_id` = '". (int)$base['customer_id'] ."'");
					exeSql("update `hb_invitecode` set times = times+1 where invitecode_id = '" .(int)$invitecode_id. "'");
					$this->res['data']['url']=!empty($is_right_code['url'])?$is_right_code['url']:'';
				}else{
					$this->res = array(
						"retcode"	=>1112,
						"msg"		=>"你的邀请码错误"
					);
				}
			}else{
				$is_buy=getRow("select * from hb_order where customer_id ='".$base['customer_id']."' and is_member_status=1 ");
				$isbuy_status=0;
				if($is_buy){
					$isbuy_status=1;//已经购买
				}
				//是否消费满  8888元
				$sale=getRow("select count(*) as count,SUM(total) as total from hb_order where customer_id ='".$base['customer_id']."' and order_status_id in (4,5) ");
				$is_enough=0;//是否消费满8888
				if(@(int)$sale["total"]>=8888){
					$is_enough=1;
				}
				$is_order=0;//是否下单满50
				if(@$sale["count"]>=50){
					$is_order=1;
				}
				//是否满足条件  点击申请
				$is_apply=0;
				if($isbuy_status || $is_enough || $is_order){
					$is_apply=1;//满足条件
				}
				if($is_apply==1){
					//可以申请成为会员
					exeSql("update hb_customer set merchant_id=1,proxy_time=NOW(),proxy_status=0 where customer_id= '".$base['customer_id']."'  ");
				}else{
					$this->res = array(
						"retcode"	=>1230,
						"msg"		=>"该用户不满足成为会员条件"
					);
				}
			}
			return $this->res;
		}

	}
?>