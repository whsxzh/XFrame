<?php
	//面向对象的control 类
	include "xcontrol/base.php";
	include "lib/pagination.php";
	class report extends base
	{
		function __construct() 
		{
	       parent::__construct();
	       //print "In SubClass constructor\n";
			$this->userid=$_SESSION['userid'];
			$this->username=$_SESSION['username'];
	   	}
	   	/**
	   	 * cgl 2017-2-20   提现记录
	   	 */
	   	function send_money(){
	   		// require 'lib/pagination.php';
    		$pagination=new pagination();
	   		$this->getMenu();
	   		if(isset($_GET["page"]) && @$_GET["page"]>=1 ){
		      $page=$_GET["page"];
		    }else{
		      $page=1;
		    }
		    $limit=20;
		    $start=($page-1)*$limit.",".$limit;

	   		$sql = "SELECT *,a.status as trade_status,c.balance as lan_money,c.availabe_balance as av_money,a.date_added,a.customer_id FROM `" . DB_PREFIX . "withdraw_cash` as  a left join ".DB_PREFIX.'customer as b on a.customer_id=b.customer_id left join hb_balance as c on b.customer_id=c.customer_id ';
	   		if(isset($_GET["status"]) && $_GET["status"]!=0){
				$sql.=" where a.status = ".$_GET["status"];
				if(isset($_GET["name"])){
					$sql.=" and b.firstname like '%".$_GET["name"]."%'";
				}
			}else{
				if(isset($_GET["name"])){
					$sql.=" where  b.firstname like '%".$_GET["name"]."%'";
				}
			}
			$sql .= " ORDER BY a.date_modified DESC limit $start";
	   		$all_record=getData($sql);
	   		/* zxx 2017-4-1 ↓*/
	  //  		$sql1 = "SELECT count(*) as count FROM `" . DB_PREFIX . "withdraw_cash` as  a join ".DB_PREFIX.'customer as b on a.customer_id=b.customer_id left join hb_balance as c on b.customer_id=c.customer_id ';

	  //  		if(isset($_GET["status"]) && $_GET["status"]!=0){
			// 	$sql1.=" where a.status = ".$_GET["status"];
			// 	if(isset($_GET["name"])){
			// 		$sql1.=" and b.firstname like '%".$_GET["name"]."%'";
			// 	}
			// }else{
			// 	if(isset($_GET["name"])){
			// 		$sql1.=" where  b.firstname like '%".$_GET["name"]."%'";
			// 	}
			// }
			if(sizeof($all_record )<20)
		      $this->getPages($page,$page);
		    else
		      $this->getPages($page);


			// $total=getRow($sql1,60);
	  //  		$total=$total['count'];
	  //       $total_page = ceil($total/20);
	  //       $this->res['is_end_page'] = 1;
	  //       if($page == $total_page){
	  //         $this->res['is_end_page'] = 0;
	  //       }

	  //       $this->getPages($page,$total_page);
	        /* zxx 2017-4-1 ↑*/
	   		


	   		// if(sizeof($all_record )<20)
		    //   $this->getPages($page,$page);
		    // else
		    //   $this->getPages($page);
	   		$data=array();
	   		if(!empty($all_record)){
				foreach ($all_record as $result) {
					$a="";
					if($result["trade_status"]==1){
						$a="已申请";
					}
					if($result["trade_status"]==2){
						$a="已到账";
					}
					if($result["trade_status"]==3){
						$a="到账失败";
					}
					$data[] = array(
						"id"=>$result["withdraw_cash_id"],
						"name"=>$result["firstname"],
						'customer_id'     => $result['customer_id'],
						'account'   => $result['account'],
						'money'        => $result['money'],
						'status'      => $a,
						"type"        => $result["type"]==1?"支付宝":"微信",
						"date_added"  => $result["date_added"],
						"date_modified"=>$result["date_modified"],
						"true_name"    =>$result["true_name"],
						"date_added"   =>$result["date_added"],
						"order_num"    =>$result["order_num"],
						"lan_money"=>$result["lan_money"],
						"av_money"=>$result["av_money"],      
					);
				}
			}
			$this->res["name"]=@$_GET["name"];
			$this->res["money_record"]=$data;
			$this->res["url"]=linkurl("report/send_money");
			$this->res["send_money"]=linkurl("report/send_money");
			$this->res["status"]=isset($_GET["status"])?$_GET["status"]:0;
			$this->res['alipay_trade']=linkurl("report/alipay_trade");
			$this->res['sendSome']=linkurl("report/sendSome");

	   		return $this->res;
	   	}
	   	/**
		 * 支付宝批量转账接口  cgl  2017-2-20
		 */
		function sendSome(){
			$data=isset($_POST["ids"])?$_POST["ids"]:null;

			$url='';
			if(empty($data)){
				echo json_encode(array("msg"=>"数据为空"));
				die;
			}else{
				$all=$data;
				// foreach($data as $k=>$v){
				// 	$all.=$v.",";
				// }
				// $all=substr($all,0,-1);
				//根据id获取查询的信息
				
				$batch_no1="";
				$arr=explode(",", $data);
				foreach($arr as $k=>$v){
					$rand=mt_rand(111111,999999);
					//批次号
	            	$batch_no = date("Ymd",time()).$rand;
	            	$ba[$k]=$batch_no;
	            	
	            	$batch_no1.=$batch_no.",";
				}
				//根据订单号修改批次号
	            // echo "UPDATE " . DB_PREFIX . "withdraw_cash SET order_num = '".$batch_no."',date_modified=NOW() where withdraw_cash_id  in (".$all.") and type=1 and status=1 ";

	            exeSql("UPDATE " . DB_PREFIX . "withdraw_cash SET order_num = '".$batch_no."',date_modified=NOW() where withdraw_cash_id in (".$all.") and type=1 and status=1 ");

				// $batch_no1=substr($batch_no1,0,-1);
				$datas=getData("select * from ".DB_PREFIX."withdraw_cash where withdraw_cash_id in (".$all.") and type = 1 and status=1");
				

				if(!empty($datas)){
					foreach($datas as $k=>$v){
						//不能为空
						if($v["account"]==null || $v["true_name"]==null ){
							echo json_encode(array("msg"=>"转账账户或者真实姓名没有"));
							die;
						}else{
							//调用支付宝批量转账接口
							$data_trade[]=array(
								"free"=>$v["money"]-1,
								"detail"=>date("Ymd",time()).$ba[$k]."^".$v["account"]."^".$v["true_name"]."^".floatval($v["money"]-1)."^"."嗨企来提现订单".$v["order_num"]
								// "detail"=>date("Ymd",time()).$v["order_num"]."^".$v["account"]."^".$v["true_name"]."^".floatval($v["money"])."^"."嗨企来提现订单".$v["order_num"]
							);
							//流水号1^收款方帐号1^真实姓名^付款金额1^备注说明1|流水号2^收款方帐号2^真实姓名^付款金额2^备注说明2

						}
					}
					if(!empty($data_trade)){
						$count=count($data_trade);
						$fee=0;
						$detail='';
						for($i=0;$i<$count;$i++){
							$fee+=$data_trade[$i]["free"];
							$detail.=$data_trade[$i]["detail"]."|";
							// print_r($data_trade[$i]);
						}
						$detail=substr($detail,0,-1);
						$all_fee=array(
							"free"=>floatval($fee),
							"detail"=>$detail,
							"count"=>$count,
							"order_num"=>$batch_no
						);
						// print_r($all_fee);
						echo json_encode($all_fee);
						die;
					}

				}else{
					echo json_encode(array("msg"=>"没有要转账的数据"));
					die;
				}
			}
		}
	   	/**
		 * 支付宝转账接口  cgl  2017-2-20
		 */
		function alipay_trade(){
			require_once("lib/alipay_transfer/alipay.config.php");
			require_once("lib/alipay_transfer/lib/alipay_submit.class.php");
			// $this->load->model("report/send_money");
			// $this->load->model("customer/customer");

			/**************************请求参数**************************/
			$data=isset($_POST["ids"])?$_POST["ids"]:null;

			$all="";
			foreach($data as $k=>$v){
				$all.=$v.",";
			}
			$all=substr($all,0,-1);

			$datas=getData("select * from ".DB_PREFIX."withdraw_cash where withdraw_cash_id in (".$all.") and type = 1 and status=1");
			//提现资金
			$money=array();
			$a=0;
			if(!empty($datas)){
				foreach($datas as $k=>$v){
					$withdraw_cash_id[$k]=$v["withdraw_cash_id"];
					//支付宝提现-1  cgl  2017-1-17
					$money[$k]=$v["money"]-1;
					//查询每个提现用户的资金
					$cus_balance[$k]=getRow("SELECT * from `" .DB_PREFIX. "balance` where customer_id = '" .$v["customer_id"]. "' ");//$this->model_customer_customer->getCusMoney($v["customer_id"]);
				}
				foreach($cus_balance as $k=>$v){
					if($v["availabe_balance"]<$money[$k]){
						//余额不够
						$a=1;
						//删除提现订单
						exeSql("UPDATE ".DB_PREFIX."withdraw_cash set status=3 where withdraw_cash_id in (".$withdraw_cash_id[$k].") and type = 1");
						//$this->model_report_send_money->deleteWith($withdraw_cash_id[$k]);
					}
				}
			}

			if($a==1){
				echo "其中有一个会员的余额不够，请重新操作";
				die;
			}
	        //服务器异步通知页面路径
	        $notify_url = TEST_IP."/alipay_trade.php";//"http://120.26.114.254/high/alipay_trade.php";
	        //需http://格式的完整路径，不允许加?id=123这类自定义参数

	        //付款账号
	        $email = 'haiqilaiiwant@163.com';
	        //必填

	        //付款账户名
	        $account_name = '杭州盛世东方网络科技有限公司';
	        //必填，个人支付宝账号是真实姓名公司支付宝账号是公司名称

	        //付款当天日期
	        $pay_date = date("Y/m/d",time());
	        // //必填，格式：年[4位]月[2位]日[2位]，如：20100801
	        // $rand=rand(111111,999999);
	        //批次号
	        $batch_no = $_POST["order_num"];//date("Ymd",time()).$rand;
	        //必填，格式：当天日期[8位]+序列号[3至16位]，如：201008010000001
	        //根据订单号修改批次号
	        // $this->model_report_send_money->updateBynum(array("order_num"=>$batch_no,"id"=>$all));

	        //付款总金额
	        $batch_fee = $_POST["WIDbatch_fee"];
	        //必填，即参数detail_data的值中所有金额的总和

	        //付款笔数
	        $batch_num = $_POST["WIDbatch_num"];
	        //必填，即参数detail_data的值中，“|”字符出现的数量加1，最大支持1000笔（即“|”字符出现的数量999个）

	        //付款详细数据
	        $detail_data = $_POST["WIDdetail_data"];
	        //必填，格式：流水号1^收款方帐号1^真实姓名^付款金额1^备注说明1|流水号2^收款方帐号2^真实姓名^付款金额2^备注说明2....haiqilaiiwant@163.com


			/************************************************************/

			//构造要请求的参数数组，无需改动
			$parameter = array(
					"service" => "batch_trans_notify",
					"partner" => trim($alipay_config['partner']),
					"notify_url"	=> $notify_url,
					"email"	=> $email,
					"account_name"	=> $account_name,
					"pay_date"	=> $pay_date,
					"batch_no"	=> $batch_no,
					"batch_fee"	=> $batch_fee,
					"batch_num"	=> $batch_num,
					"detail_data"	=> $detail_data,
					"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
			);
			//建立请求
			$alipaySubmit = new AlipaySubmit($alipay_config);
			$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
			// print_r($parameter);
			// die;
			echo $html_text;
			die;
		}
		/**
		 * cgl  2017-4-13  增加系统日志  
		 */
		function userlog(){
			$this->getMenu();

    		$pagination=new pagination();
    		if(isset($_GET["page"]) && @$_GET["page"]>=1 ){
		      $page=$_GET["page"];
		    }else{
		      $page=1;
		    }
		    $limit=20;
		    $start=($page-1)*$limit.",".$limit;

			$sql="select * from hb_user_log";
			$sql1="select count(*) as count from hb_user_log";
			if($_SESSION["merchant_id"]!=1){
				$sql.=" where merchant_id ='".$_SESSION["merchant_id"]."' ";
				$sql1.=" where merchant_id ='".$_SESSION["merchant_id"]."' ";
			}
			$sql.=" order by log_id desc ";
			$all_count=0;
			if(getCache("user_log_count")){
		      $all_count=getCache("user_log_count");
		    }else{
		      $all_num=getRow($sql1);
		      setCache("user_log_count",$all_num["count"],3600);
		      $all_count=getCache("user_log_count");
		    }
		    $last=ceil($all_count/20);

		    //查询全部订单的数量
		    $is_end_page=0;
		    if($page==$last){
		      $is_end_page=1;//是最后一页
		    }
		    $this->res["is_end_page"]=$is_end_page;
		    $this->res["total_page"]=$last;

		    $sql.=" limit $start";
			$data=getData($sql);

			if(sizeof($data )<20)
		      $this->getPages($page,$page);
		    else
		      $this->getPages($page);
		  	// $str='wency.shi在2017-04-13 14:58:48操作(coupon/releaseCoupon),请求参数：{"m":"coupon","act":"releaseCoupon","ids_str":"3650","counts":"1","send_flag":"user_defined","coupon_id":"545"}';
		  	
		  	if(!empty($data)){
		  		foreach($data as $k=>$v){
		  			$str=strstr($v["content"],"在");
				  	$pos=strpos($str,",请求参数");
				  	$str=substr($str,0,$pos);
		  			$data[$k]["content"]=$v["username"]."  ".$str."模块";

		  		}
		  	}

			$this->res["userlog"]=$data;
			return $this->res;
		}
		/**
		 * 资金详情  cgl  2017-4-13
		 */
		function detail(){
			$this->getMenu();
			if(!isset($_GET["id"]) || @$_GET["id"]==""){
				echo "<script>alert('参数错误');history.back();</script>";
			}else{
				$sql="select *,b.name as product,a.date_added from hb_customer_transaction as a left join hb_order_product as b on a.order_id=b.order_id where customer_id ='".$_GET["id"]."'  ";
				$sql.=" order by a.customer_transaction_id desc";
				$some=getData($sql);
				$total=getRow("select SUM(amount) as total from hb_customer_transaction as a left join hb_order_product as b on a.order_id=b.order_id where customer_id ='".$_GET["id"]."' AND type in (1,3,6,7,8) and status=2 order by a.order_id desc");


				$jian_total=getRow("select SUM(availabe_balance_change) as total from hb_customer_transaction as a left join hb_order_product as b on a.order_id=b.order_id where customer_id ='".$_GET["id"]."' and type in (2,9) order by a.order_id desc");

				$fan=getRow("select SUM(amount) as total from hb_customer_transaction as a left join hb_order_product as b on a.order_id=b.order_id where customer_id ='".$_GET["id"]."' and type=5 and status=2 order by a.order_id desc");

				$lockfan=getRow("select SUM(amount) as total from hb_customer_transaction as a left join hb_order_product as b on a.order_id=b.order_id where customer_id ='".$_GET["id"]."' and type in (1,3) and status=1 order by a.order_id desc");

				$customer=getRow("select * from hb_customer where customer_id ='".$_GET["id"]."' ");
				$this->res["firstname"]=@$customer["firstname"];
				$this->res["telephone"]=@$customer["telephone"];
				$this->res["money1"]=sprintf("%.2f",@$_GET["money"]);
				//查询余额
				$balance=getRow("select * from hb_balance where customer_id = '".$_GET["id"]."' ");

				$this->res["balance"]=sprintf("%.2f",@$balance["balance"]);
				$this->res["availabe_balance"]=sprintf("%.2f",@$balance["availabe_balance"]);
				if(!empty($some)){
					foreach($some as $k=>$v){
						$some[$k]["amount1"]=0;
						$some[$k]["amount2"]=0;
						if($v["type"]==1){
							$some[$k]["order_type_sa"]="分享收入";
						}else if($v["type"]==3){
							$some[$k]["order_type_sa"]="返利收入";
						}else if($v["type"]==8){
							$some[$k]["order_type_sa"]="购买退款";
						}else if($v["type"]==2){
							$some[$k]["order_type_sa"]="购买支出";
						}else if($v["type"]==4){
							$some[$k]["order_type_sa"]="提现";
							$some[$k]["amount2"]=abs($v["amount"]);
						}else if($v["type"]==5){
							$some[$k]["order_type_sa"]="资金解锁";
							$some[$k]["amount1"]=abs($v["amount"]);
						}else if($v["type"]==7){
							$some[$k]["order_type_sa"]="返利退款";
						}else if($v["type"]==9){
							$some[$k]["order_type_sa"]="会员订单";
						}
						//$some[$k]["date_added"]=date("Y-m-d H:i:s",$v["date_added"]);
						$some[$k]["amount"]=abs($v["amount"]);
					}
				}
				//提现记录
				$history=getData("select * from hb_withdraw_cash where customer_id='".$_GET["id"]."' order by date_modified desc ");
				$money=getRow("select SUM(money) as total from hb_withdraw_cash where customer_id='".$_GET["id"]."' ");
				if(!empty($history)){
					foreach($history as $k=>$v){
						if($v["type"]==1){
							$history[$k]["type_remark"]="支付宝";
						}else if($v["type"]==2){
							$history[$k]["type_remark"]="微信";
						}
						if($v["status"]==1){
							$history[$k]["status"]="已申请";
						}else if($v["status"]==2){
							$history[$k]["status"]="已到账";					
						}else if($v["status"]==3){
							$history[$k]["status"]="已失败";
						}
						$history[$k]["money"]=sprintf("%.2f",$v["money"]);
					}
				}
				$this->res["money"]=sprintf("%.2f",$money["total"]);
				$this->res["history"]=$history;
				$this->res["total"]=sprintf("%.2f",$total["total"]);
				$this->res["send_money"]=linkurl("report/detail",array("id"=>$_GET["id"]));

				$this->res["name"]=@$_GET["name"];

				$this->res["jian_total"]=sprintf("%.2f",abs($jian_total["total"]));
				$this->res["fan"]=sprintf("%.2f",$fan["total"]);
				$this->res["lockfan"]=sprintf("%.2f",$lockfan["total"]);

				$this->res["transaction"]=$some;

			}
			return $this->res;
		}
		/**
		 * cgl 2017-5-16
		 * 账户资金管理
		 */
		function accountMoney(){
			$this->getMenu();
			$pagination=new pagination();
    		if(isset($_GET["page"]) && @$_GET["page"]>=1 ){
		      $page=$_GET["page"];
		    }else{
		      $page=1;
		    }
		    $limit=20;
		    $start=($page-1)*$limit.",".$limit;
		    $sql="select 
				firstname,
				customer_id,
				lastname,
				date_added
				from hb_customer as a ";
			if(isset($_GET["name"])){
				$sql.=" where a.firstname like '%".$_GET["name"]."%' or a.lastname like '%".$_GET["name"]."%' ";
			}
			$sql.="order by a.customer_id desc limit $start";

			$all_customer=getData($sql);
			if(!empty($all_customer)){
				foreach($all_customer as $k=>$v){
					$customer=getRow("select * from hb_balance where customer_id = '".$v["customer_id"]."' ");
					$all_customer[$k]["availabe_balance"]=@$customer["availabe_balance"];
					$all_customer[$k]["balance"]=@$customer["balance"];
				}
			}
			// print_r($all_customer);

			if(sizeof($all_customer )<20)
		      $this->getPages($page,$page);
		    else
		      $this->getPages($page);
		  	$this->res["customer"]=$all_customer;
		  	$this->res['is_end_page'] = 1;
		  	$this->res["name"]=@$_GET["name"];

		  	$this->res["send_money"]=linkurl("report/accountMoney");
			return $this->res;
		}
		/**
		 * cgl 2017-5-17
		 * 用户反馈
		 */
		function feedback(){
			$this->getMenu();
			$pagination=new pagination();
    		if(isset($_GET["page"]) && @$_GET["page"]>=1 ){
		      $page=$_GET["page"];
		    }else{
		      $page=1;
		    }
		    $limit=20;
		    $start=($page-1)*$limit.",".$limit;
		    $sql="SELECT *,a.status as deal_status,a.date_modified as deal_time,
		    	(SELECT lastname FROM hb_customer as b where  a.customer_id=b.customer_id ) as lastname
		     from hb_feedback as a  ";
		    if(isset($_GET["status"])){
		    	$sql.=" and a.status=".$_GET["status"];  //0为未处理
		    }
		    //limit $start
		    $sql.=" order by a.id desc  ";
		    $feedback=getData($sql);

		    if(!empty($feedback)){
		    	foreach($feedback as $k=>$v){
		    		// print_r($v);
		    		$feedback[$k]["imgurl"]=json_decode(htmlspecialchars_decode($v["image_urls"]),"json");
		    	}
		    }
		    // print_r($feedback);

		    if(sizeof($feedback )<20)
		      $this->getPages($page,$page);
		    else
		      $this->getPages($page);
		    $this->res['is_end_page'] = 1;
		    $this->res["feedback"]=$feedback;

			return $this->res;	
		}
		/**
		 * cgl 2017-6-7 消息管理页面
		 */
		function message(){
			$this->getMenu();
			$push=getData("SELECT push_id,title,content,type_id,FROM_UNIXTIME(date_added,'%Y-%m-%d %H:%i:%d') as date_added,type_info from hb_push where type in (7,8,9,10) and type_id in (1,2,3,4) and type_info in (21,22,23) ");

			$this->res["data"]=$push;
			return $this->res;
		}
		/**
		 * cgl 添加消息 后台的消息
		 */
		function addMsg(){
			$title=$_POST["title"];  //标题
			$content=$_POST["content"]; //内容
			$type=$_POST["type"]; //类型 1版本更新，2版本未更新，更新内容

			if($type!=1 && $type!=2){
				$json["msg"]="请求消息类型错误";
			}else{
				if($type==2){
					$msg_type=22;
				}else if($type==1) {
					$msg_type=21;
				}
				$data=array("title"=>$title,"content"=>$content,"type_info"=>$msg_type,"type_id"=>4,"date_added"=>time(),"date_modified"=>time(),"type"=>9);
				saveData("hb_push",$data);
				$json["msg"]="添加成功";
			}
			echo json_encode($json);
			die;
		}
		/**
		 * 查询回复详情  cgl 2017-6-7
		 */
		function feedDetail(){
			$this->getMenu();
			//查询反馈内容
			$feed_content=getData("SELECT *,(SELECT username from hb_user as a where a.user_id= b.customer_id) as username,FROM_UNIXTIME(date_added,'%Y-%m-%d %H:%i:%d') as date_added from hb_push as b where item_id= '".@$_GET["id"]."' and type_id = 4 and type_info = 23 ");

			$this->res["data"]=$feed_content;

			return $this->res;
		}
		/**
		 * cgl 2017-6-7  去回复的详情
		 */
		function feckback(){
			$this->getMenu();
			//根据id 查询反馈详情
			$feed=getRow("SELECT * from hb_feedback where id = '".@$_GET["id"]."' ");
			$this->res["id"]=@$_GET["id"];
			$this->res["deal_status"]=@$feed["status"];
			$this->res["data"]=$feed;
			return $this->res;
		}
		/**
		 * cgl 2017-6-7 反馈回复
		 */
		function goFeedback(){
			if($_POST["type"]!=1 && $_POST["type"]!=2 && isset($_POST["id"]) ){
				$json["msg"]="反馈消息类型错误";
			}else{
				if(!empty($_POST["content"])){
					//修改处理的状态  2处理中，1已完成
					exeSql("UPDATE hb_feedback set status = '".$_POST["type"]."',date_modified=NOW() where id = '".$_POST["id"]."' ");
					//查询回复的用户
					$customer=getRow("SELECT customer_id from hb_feedback where id = '".$_POST["id"]."' ");
					$customer_id=@$customer["customer_id"];

					//插入到消息的记录去
					$data=array("item_id"=>$_POST["id"],"date_modified"=>time(),"date_added"=>time(),"type"=>9,"type_info"=>23,"type_id"=>4,"customer_id"=>$_SESSION["userid"],"target_id"=>$customer_id,"content"=>$_POST["content"],"status"=>0,
						"title"=>"嗨，您的反馈已有回复！","feed_status"=>$_POST["type"]);
					saveData("hb_push",$data);
					$json["msg"]="回复成功";
				}else{
					$json["msg"]="必须输入回复内容哦！";
				}
			}
			echo json_encode($json);
			die;
		}



	}
?>