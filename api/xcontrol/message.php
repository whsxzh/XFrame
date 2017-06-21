<?php
	include "xcontrol/base.php";
	require_once( '../system/thirdlib/' . 'RMServerAPI.php');
	/**
	 * cgl 2017-5-18
	 * 消息模块  要修改也要注意点改  小心坑。。。。也别坑。。。。
	 */
	class message extends base{
		function __construct() 
		{
	       if(isset($_POST["customerid"])){
	           $customer_id = $_POST["customerid"];
	        }else{
	           $customer_id=@$_SESSION["default"]["customer_id"];
	        }
	        if(isset($_POST["passkey"])){
	           $req_key=@$_POST["passkey"];
	        }else{
	           $req_key=@$_SESSION["default"]["passkey"];
	        } 
		   $this->passkey=$req_key;
		   $this->customer_id=$customer_id;
	   	}
	   	/**
	   	 * 获取消息类型
	   	 * cgl 2017-5-18 
	   	 */
		function getMsgCategory(){
			if($_SERVER['REQUEST_METHOD']=="POST"){
				if(isset($this->customer_id)){
					$merchant=getRow("SELECT merchant_id from hb_customer where customer_id = '".$this->customer_id."' ");
				if(!empty($merchant["merchant_id"]) ){
						if($merchant["merchant_id"]==0){
							$merchant_id=1;
						}else{
							$merchant_id=$merchant["merchant_id"];		
						}
					}else{
						$merchant_id=1;
					}
				}else{
					$merchant_id=1;
				}
				$cat=getData("select type_id,title,image,remark as new_title from hb_push_msg_type where status = 1 and merchant_id = '".$merchant_id."' order by sort_order desc ");
				if(!empty($cat)){
					foreach($cat as $k=>$v){
						if($v["type_id"]==1 || $v["type_id"]==2 || $v["type_id"]==3 || $v["type_id"]==4 ){
							

							//查询是否有最新的消息  status
							$is_new=getRow("select title,date_added,status from hb_push where type_id = '".$v["type_id"]."' and target_id = '".$this->customer_id."' and status!=2 and type in (7,8,9,10) order by date_added desc ");
							if(!empty($is_new)){
								$cat[$k]["new_title"]=$is_new["title"];
								if($is_new["status"]==0){
									$cat[$k]["is_new"]=1;	
								}else{
									$cat[$k]["is_new"]=0;
									if($v["type_id"]==4){ //企业公告
										$is_qiye=getRow("select title,date_added,status from hb_push where type_id = '".$v["type_id"]."' and type_info in (21,22) order by date_added desc");
										if(@$is_qiye["date_added"]>$is_new["date_added"]){
											$is_new["date_added"]=$is_qiye["date_added"];
											$cat[$k]["new_title"]=$is_qiye["title"];
										}
									}
									if($v["type_id"]==3){
										//where add_time>=DATE_SUB(NOW(),INTERVAL 60 MINUTE) and add_time <=DATE_ADD(NOW(),INTERVAL 60 MINUTE)
										$is_new_system=getRow("SELECT push_id,title,add_time from hb_push_system  order by add_time desc ");
										if($is_new_system && @$is_new_system["add_time"]>=$is_new["date_added"] ){
											$is_new["date_added"]=strtotime($is_new_system["add_time"]);//date("Y.m.d");

											$is_read=getRow("SELECT read_id from hb_push_system_read where customer_id = '".$this->customer_id."' and push_system_id = '".$is_new_system["push_id"]."' ");
											if($is_read){
												$cat[$k]["is_new"]=0;
											}else{
												$cat[$k]["is_new"]=1;
											}
											$cat[$k]["new_title"]=$is_new_system["title"];
										}else{
											$cat[$k]["new_date"]=time();//date("Y.m.d");
											$cat[$k]["is_new"]=0;
										}
									}
								}
								$cat[$k]["new_date"]=$is_new["date_added"];
							}else{
								$cat[$k]["is_new"]=1;
								//查询是否有最新的活动
								if($v["type_id"]==3){
									//where add_time>=DATE_SUB(NOW(),INTERVAL 60 MINUTE) and add_time <=DATE_ADD(NOW(),INTERVAL 60 MINUTE)
									$is_new_system=getRow("SELECT push_id,title,add_time from hb_push_system  order by add_time desc ");
									if($is_new_system){
										$cat[$k]["new_date"]=strtotime($is_new_system["add_time"]);//date("Y.m.d");

										$is_read=getRow("SELECT read_id from hb_push_system_read where customer_id = '".$this->customer_id."' and push_system_id = '".$is_new_system["push_id"]."' ");
										if($is_read){
											$cat[$k]["is_new"]=0;
										}else{
											$cat[$k]["is_new"]=1;
										}
										$cat[$k]["new_title"]=$is_new_system["title"];
									}else{
										$cat[$k]["new_date"]=time();//date("Y.m.d");
										$cat[$k]["is_new"]=0;
									}
								}else{
									$cat[$k]["is_new"]=0;
									// $cat[$k]["new_date"]=time();
									if($v["type_id"]==4){ //企业公告
										$is_qiye=getRow("select title,date_added,status from hb_push where type_id = '".$v["type_id"]."' and type_info in (21,22) order by date_added desc");
										if(!empty($is_qiye)){
											$cat[$k]["new_date"]=$is_qiye["date_added"];
											// echo date("Y-m-d H:i:s",$is_qiye["date_added"]);
											$cat[$k]["new_title"]=$is_qiye["title"];
										}else{
											$cat[$k]["new_date"]=0;
										}
									}
								}

								if($v["type_id"]!=4 && $v["type_id"]!=3 ){
									unset($cat[$k]);
								}
								// else{
								// 	$cat[$k]["new_date"]=time();//date("Y.m.d");
								// 	$cat[$k]["is_new"]=0;
								// }
							}
						}else{
							$cat[$k]["new_date"]=0;//time();//date("Y.m.d");   为客服消息
							$cat[$k]["is_new"]=0;
						}
					}
				}
				//根据消息时间排序
				$sort = array(  
				    'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				    'field'     => 'new_date',       //排序字段  
				);
				$arrSort = array();
				if($cat){
					foreach($cat AS $uniqid => $row){  
					    foreach($row AS $key=>$value){  
					        $arrSort[$key][$uniqid] = $value;  
					    }
					}
				}
				if($sort['direction']){  
				    @array_multisort($arrSort[$sort['field']], constant($sort['direction']), $cat);  
				} 	

				$arr=array();
				$cat=array_merge($cat,$arr);

				if(!empty($cat)){
					foreach($cat as $k=>$v){
						if($v["type_id"]==5 || $v["new_date"]==0){
							$cat[$k]["new_date"]=time();  //客服时间为当前时间
						}
					}
				}

				$this->res["data"]=$cat;
			}else{
				$this->res["retcode"]=1180;
	       		$this->res["msg"]="请求方式错误";
			}
			return $this->res;
		}
		/**
		 * 获取消息列表   根据消息类型获取
		 * cgl 2017-5-8
		 * 请求参数：type =>1：订单消息,2：财务消息,3：群消息(APP做),4：活动消息,5:企业公告，6：客服消息(APP做)
		 * 订单消息：1:发货,待收货,2:购买成功（完成付款）
		 * 3，已收货,待评价,4：评价之后,是否再评价（后期做，2017-5-22，需求说明会上，产品说了的）
		 * 6:团购成功,7:团购失败,10:退款申请,待处理,11:退款状态（拒绝,同意）,
		 * 财务消息：16:分享收入,17:返利收入,18:资金解锁,19:提现申请,20:提现状态,24:购买会员
		 * 活动消息：25:商品,26:推荐分类,27:H5链接,34:支付宝红包领取成功
		 * 29:给他发放了或者领取了优惠券,30:优惠券过期提醒    =>翔哥做  2017-5-18  现在是我做了,把smsCoupon推送里面的要注释掉 5-22
		 * 32:翻牌子中奖,33:大转盘中奖,38:限时抢购通知
		 * 群 消 息：35：申请加群通知,36:被谁邀请加入了群   ---2017-5-22  需求定，安卓和IOS自己做
		 * 企业公告：37：版本更新,38:新上线功能、模块（版本未更新）,12:反馈APP消息回复,31:通过邀请码成为会员
		 */
		/**
		 * 正式说明：
		 * 订单消息）1:发货,待收货;2:购买成功(完成付款);3:团购成功(已成团);4:团购失败;5:申请退款;6:退款处理状态;
		 * 财务消息）7:分享收入,8:返利收入;9:资金解锁;10:提现申请;11:提现处理;12:购买会员支出;16:支付宝红包领取成功;25:大转盘中奖
		 * 活动消息）13:活动的商品;14:活动的分类;15:H5链接;17:给他发放了或者领取了优惠券;18:优惠券过期提醒;19:翻牌子中奖;20:限时抢购提醒;
		 * 企业公告）21:版本更新;22:新上线功能、模块(版本未更新);23:反馈APP消息回复;24:通过邀请码成为会员
		 */


		function getMsgList(){
			if($_SERVER['REQUEST_METHOD']=="POST"){
				//请求的类型  1：订单消息,2：财务消息,3：活动消息,4:企业公告
				$type=isset($_POST["type"])?$_POST["type"]:1;
				//请求页码
				if(isset($_POST["page"]) && @$_POST["page"]>0){
					$page=$_POST["page"];
				}else{
					$page=1;
				}
				$limit=20;
				//限制20条
				$start=($page-1)*$limit.",".$limit;

				if(isset($this->customer_id)){
					$merchant=getRow("SELECT merchant_id from hb_customer where customer_id = '".$this->customer_id."' ");
					if(!empty($merchant["merchant_id"]) ){
						$merchant_id=$merchant["merchant_id"];
					}else{
						$merchant_id=0;
					}
				}else{
					$merchant_id=0;
				}

				$list=array();
				$order=getData("SELECT type_info,title,content,status,item_id,date_added as date,display_mode,push_id from hb_push where target_id = '".$this->customer_id."' and status!=2 and type_id = '".$type."' and type_info!=21 and type_info!=22 and type in (7,8,9,10)  order by date_added desc limit $start ");

				switch ($type) {
					
					case '1':
						# code...  订单
						//$display_mode=3;
						//查询该登录者的订单消息
						if(!empty($order) ){
							foreach ($order as $k => $v) {
								//查询商品
								$product=getRow("SELECT a.image,c.total as total,c.payment_method from hb_product as a join hb_order_product as b on a.product_id = b.product_id join hb_order as c on b.order_id = c.order_id  where c.order_id = '".$v["item_id"]."' ");
								$order[$k]["image"]=@$product["image"];
								$order[$k]["date"]=$v["date"];
								$order[$k]["link"]=""; //保留链接
								$order[$k]["money"]=sprintf("%.2f",@$product["total"]);//金额
								$pay_type="其他";
								if(@$product["payment_method"] ==1 ){
									$pay_type="支付宝";
								}else if(@$product["payment_method"] ==2){
									$pay_type="微信";
								}else if(@$product["payment_method"] ==3){
									$pay_type="余额";
								}else if(@$product["payment_method"] ==4){
									$pay_type="支付宝&余额";
								}else if(@$product["payment_method"] ==5){
									$pay_type="微信&余额";
								}else if(@$product["payment_method"] ==6){
									$pay_type="H5微信";
								}else if(@$product["payment_method"] ==7){
									$pay_type="H5支付宝";
								}
								$order[$k]["pay_type"]=$pay_type;//付款方式

								$order[$k]["system_type"]=0;//是否是系统消息？1:是，0：否
							}
						}

						$list=$order;

						break;
					case '2':
						# code...  财务消息,资金明细
						//$display_mode=2;
						if(!empty($order) ){
							foreach ($order as $k => $v) {
								//1 销售收入(分享收入)  3分成收入(返利) 5.资金解锁 4.提现 9会员订单
								//查询资金记录
								$pay_type="其他";
								$order[$k]["image"]="";
								$order[$k]["link"]=""; //保留链接	
								if($v["type_info"]==10 || $v["type_info"]==11 ){//提现申请
									$withdraw=getRow("select type,money from hb_withdraw_cash where customer_id = '".$this->customer_id."' and withdraw_cash_id = '".$v["item_id"]."' ");

									$order[$k]["money"]=sprintf("%.2f",abs(@$withdraw["money"]));//金额
									if(@$withdraw["type"]==1){
										$pay_type="支付宝";
									}else if(@$withdraw["type"]==2){
										$pay_type="微信";
									}
								}else{
									$sql="SELECT a.amount,c.payment_method,type from hb_customer_transaction as a join hb_order as c on a.order_id = c.order_id  where c.order_id = '".$v["item_id"]."' ";
									//and a.type in (1,3,4,5,9)
									if($v["type_info"]==7){//分享
										$sql.=" and a.type=1";
									}
									if($v["type_info"]==8){//返利
										$sql.=" and a.type=3";
									}
									if($v["type_info"]==9){//资金解锁
										$sql.=" and a.type=5";
									}
									if($v["type_info"]==12){//购买会员
										$sql.=" and a.type=9";
									}

									$money=getRow($sql);
									$order[$k]["money"]=sprintf("%.2f",abs(@$money["amount"]));//金额
									if(@$money["payment_method"] ==1 ){
										$pay_type="支付宝";
									}else if(@$money["payment_method"] ==2){
										$pay_type="微信";
									}else if(@$money["payment_method"] ==3){
										$pay_type="余额";
									}else if(@$money["payment_method"] ==4){
										$pay_type="支付宝&余额";
									}else if(@$money["payment_method"] ==5){
										$pay_type="微信&余额";
									}else if(@$money["payment_method"] ==6){
										$pay_type="H5微信";
									}else if(@$money["payment_method"] ==7){
										$pay_type="H5支付宝";
									}
								}
								//16:支付宝红包领取成功;25:大转盘中奖   cgl 待做。。。。。。
								$order[$k]["pay_type"]=$pay_type;//付款方式

								$order[$k]["system_type"]=0;//是否是系统消息？1:是，0：否
							}
						}
						$list=$order;

						break;
					case '3':
						# code...   活动消息  
						//$display_mode=1;
						
						$limit=20-count($order);
						//限制20条
						$start1=($page-1)*$limit.",".$limit;
						

						$activity=getData("SELECT * from hb_push_system order by push_id desc limit $start1  ");
						$pay_type="";

						if($activity){
							foreach($activity as $k=>$v){
								//判断当前登录者的身份
								if($v["object"]==2 && $merchant_id==1){
									//会员
									$is_user=1;
								}else if($v["object"]==3 && $merchant_id==0){
									//普通用户
									$is_user=2;
								}else{
									//全部用户
									$is_user=1;
								}
								if($is_user==1){
									
									//查询方式 type_info  0打开商品详情，1打开活动，2打开H5链接，3打开H5链接
									//13:活动的商品;14:活动的分类;15:H5链接;
									if($v["type"]==0){
										$order1[$k]["type_info"]="13";
									}else if($v["type"]==1){
										$order1[$k]["type_info"]="14";
									}else if($v["type"]==2){
										$order1[$k]["type_info"]="15";
									}else if($v["type"]==3){
										$order1[$k]["type_info"]="15";
									}
									$order1[$k]["title"]=$v["title"];
									$order1[$k]["content"]=$v["content"];
									//status  //是否已读
									$is_read=getRow("SELECT read_id from hb_push_system_read where customer_id = '".$this->customer_id."' and push_system_id = '".$v["push_id"]."' ");
									if($is_read){
										$order1[$k]["status"]=1;
									}else{
										$order1[$k]["status"]=0;
									}
									if($v["link"]==null || $v["link"]==""){
										$v["link"]="";
									}
									if($v["push_image"]==null || $v["push_image"]==""){
										$v["push_image"]="";
									}

									$order1[$k]["link"]=$v["link"];
									$order1[$k]["item_id"]=$v["id"];
									$order1[$k]["date"]=strtotime($v["add_time"]);
									$order1[$k]["display_mode"]="1";
									$order1[$k]["push_id"]=$v["push_id"];
									$order1[$k]["image"]=$v["push_image"];
									$order1[$k]["link"]=$v["link"];
									$order1[$k]["money"]="";
									$order1[$k]["pay_type"]=$pay_type;

									$order1[$k]["system_type"]=1;//是否是系统消息？1:是，0：否
								}else{
									$order1=array();
								}
							}
						}else{
							$order1=array();
						}
						//17:给他发放了或者领取了优惠券;18:优惠券过期提醒;19:翻牌子中奖;20:限时抢购提醒;
						if($order){
							foreach($order as $k=>$v){
								$pay_type="";
								$order[$k]["image"]="";
								$order[$k]["link"]=""; //保留链接
								$order[$k]["pay_type"]=""; 
								$order[$k]["system_type"]=0;//是否是系统消息？1:是，0：否
							}
						}else{
							$order=array();
						}
						$order1=array_merge($order1,$order);
						$sort = array(  
						    'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
						    'field'     => 'date',       //排序字段  
						);
						$arrSort = array();
						if($order1){
							foreach($order1 AS $uniqid => $row){  
							    foreach($row AS $key=>$value){  
							        $arrSort[$key][$uniqid] = $value;  
							    }
							}
						}
						if($sort['direction']){  
						    @array_multisort($arrSort[$sort['field']], constant($sort['direction']), $order1);  
						}  					

						$list=$order1;
						break;
					case '4':
						# code...   企业公告
						//$display_mode=1;
						//21:版本更新;22:新上线功能、模块(版本未更新);23:反馈APP消息回复;
						//24:通过邀请码成为会员
						if(!empty($order) ){
							foreach ($order as $k => $v) {
								$pay_type="其他";
								$order[$k]["image"]="";
								$order[$k]["link"]=""; //保留链接
								$order[$k]["pay_type"]=$pay_type;//付款方式
								$order[$k]["system_type"]=0;//是否是系统消息？1:是，0：否
							}
						}
						//查询 版本更新 新上线功能、模块(版本未更新);  反馈APP消息回复;
						$version=getData("SELECT type_info,title,content,status,item_id,date_added as date,display_mode,push_id from hb_push where status!=2 and type_id = '".$type."' and type_info in (21,22) and type in (7,8,9,10) order by date_added desc limit $start ");

						if(!empty($version)){
							foreach($version as $k=>$v){
								//版本更新
								// if($v["type_info"]==21 || $v["type_info"]==22){//22:新上线功能、模块(版本未更新); 21版本更新
									
								// }else if($v["type_info"]==23){//23:反馈APP消息回复;
									
								// }
								$pay_type="其他";
								$version[$k]["image"]="";
								$version[$k]["link"]=""; //保留链接
								$version[$k]["pay_type"]=$pay_type;//付款方式
								$version[$k]["system_type"]=0;//是否是系统消息？1:是，0：否
								$version[$k]["status"]=0;
							}
						}else{
							$version=array();
						}
						$order=array_merge($order,$version);

						$sort = array(  
						    'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
						    'field'     => 'date',       //排序字段  
						);
						$arrSort = array();
						if($order){
							foreach($order AS $uniqid => $row){  
							    foreach($row AS $key=>$value){  
							        $arrSort[$key][$uniqid] = $value;  
							    }
							}
						}
						if($sort['direction']){  
						    @array_multisort($arrSort[$sort['field']], constant($sort['direction']), $order);  
						}


						

						$list=$order;
						break;
				}
				$this->res["data"]=$list;
			}else{
				$this->res["retcode"]=1180;
	       		$this->res["msg"]="请求方式错误";
			}
			return $this->res;
		}
		/**
		 * cgl 2017-5-23
		 * 修改为已读状态或者删除状态
		 * 1:已读
		 * 2:删除
		 */
		function updatePushStatus(){
			if($_SERVER['REQUEST_METHOD']=="POST"){
				$type=isset($_POST["type"])?$_POST["type"]:null;
				//$push_id=isset($_POST["push_id"])?$_POST["push_id"]:null;
				//是否是系统消息？1:是，0：否
				//$system_type=isset($_POST["system_type"])?$_POST["system_type"]:0;

				if(!empty($type)){
					exeSql("UPDATE hb_push set status=1 where target_id = '".$this->customer_id."' and type_id = '".$type."' ");

					$is_new=getRow("select title,date_added,status from hb_push where type_id = '".$type."' and target_id = '".$this->customer_id."' and status!=2 order by date_added desc ");
					//!$is_new &&
					if( $type==3){
						//没有最新消息  去查询营销消息
						//where add_time>=DATE_SUB(NOW(),INTERVAL 60 MINUTE) and add_time <=DATE_ADD(NOW(),INTERVAL 60 MINUTE)
						$is_new_system=getRow("SELECT push_id,title,add_time from hb_push_system  order by add_time desc ");
						if($is_new_system){
							$push_id=$is_new_system["push_id"];
							$is_read=getRow("SELECT push_system_id from hb_push_system_read where customer_id = '".$this->customer_id."' and push_system_id = '".$push_id."' ");
							if($is_read){
								exeSql("UPDATE hb_push_system_read set read_time=read_time+1 where customer_id = '".$this->customer_id."' and push_system_id = '".$push_id."' ");
							}else{
								//添加记录
								$data=array(
									"push_system_id"=>$push_id,
									"customer_id"=>$this->customer_id
									);
								saveData("hb_push_system_read",$data);
							}
						}
					}

				}else{
					$this->res["retcode"]=1100;
	       			$this->res["msg"]="请求参数错误";
				}
				

			}else{
				$this->res["retcode"]=1180;
	       		$this->res["msg"]="请求方式错误";
			}
			return $this->res;	
		}
		/**
		 * cgl 2017-5-23
		 * 修改为全部已读状态
		 */

		function updateAllStatus(){
			if($_SERVER['REQUEST_METHOD']=="POST"){
				exeSql("UPDATE hb_push set status=1 where target_id = '".$this->customer_id."' ");
				//没有最新消息  去查询营销消息
				//where add_time>=DATE_SUB(NOW(),INTERVAL 60 MINUTE) and add_time <=DATE_ADD(NOW(),INTERVAL 60 MINUTE)
				$is_new_system=getRow("SELECT push_id,title,add_time from hb_push_system  order by add_time desc ");
				if($is_new_system){
					$push_id=$is_new_system["push_id"];
					$is_read=getRow("SELECT push_system_id from hb_push_system_read where customer_id = '".$this->customer_id."' and push_system_id = '".$push_id."' ");
					if($is_read){
						exeSql("UPDATE hb_push_system_read set read_time=read_time+1 where customer_id = '".$this->customer_id."' and push_system_id = '".$push_id."' ");
					}else{
						//添加记录
						$data=array(
							"push_system_id"=>$push_id,
							"customer_id"=>$this->customer_id
							);
						saveData("hb_push_system_read",$data);
					}
				}
				
			}else{
				$this->res["retcode"]=1180;
	       		$this->res["msg"]="请求方式错误";
			}
			return $this->res;	
		}

		//已完毕  请继续其他方法
	}
?>