<?php
	//面向对象的control 类
	include "xcontrol/base.php";
	include "xcontrol/product.php";
	include_once "lib/pagination.php";
	class groupbuy extends base
	{
		/**
		 * 获取全部团购活动商品
		 * 2017-2-23
		 * cgl
		 */
		function getList(){
			//菜单
			$this->getMenu();
			if(isset($_GET["page"]) && @$_GET["page"]>=1 ){
		      $page=$_GET["page"];
		    }else{
		      $page=1;
		    }
		    $limit=20;
		    $start=($page-1)*$limit.",".$limit;
			//获取到所有的团购商品
			$sql="SELECT group_id,product_id,groupname,groupnum,groupprice,groupoptionid,start_time,end_time,group_status from ".DB_PREFIX."groupby";
			$dt=getData($sql);
			$status=isset($_GET["status"])?$_GET["status"]:1;
			if(!in_array($status,array(1,2,3))){
				$status=1;
			}
			$sql="SELECT *,
				(SELECT product_options from ".DB_PREFIX."product_item as p where p.product_item_id=g.groupoptionid ) as groupoption,
				(SELECT count(*) from ".DB_PREFIX."group_join_info as gi where gi.group_id=g.group_id ) as group_order_total,
				(SELECT count(*) from ".DB_PREFIX."group_join as gj where gj.group_id=g.group_id ) as group_total
				from ".DB_PREFIX."groupby as g where g.group_status='".$status."' and g.group_status!=4  and g.merchant_id='".$_SESSION["merchant_id"]."' order by g.date_added desc limit $start ";
			$data=getData($sql);
			if(sizeof($data )<20)
		      $this->getPages($page,$page);
		    else
		      $this->getPages($page);
			if(!empty($data)){
				foreach($data as $k=>$v){
					if($v["start_time"]<date("Y-m-d H:i:s")){
						$data[$k]["is_start"]=1;//已开始
					}else{
						$data[$k]["is_start"]=0;
					}
				}
			}

			$this->res["groupby"]=$data;
			$this->res["status"]=$status;
			//获取全部团购活动商品
			$this->res["getlist"]=linkurl("groupbuy/getList");
			//正在出售
			$this->res["onsale"]=linkurl("groupbuy/onSale");
			$this->res["closeGroup"]=linkurl("groupbuy/closeGroup");//关闭团购
			$this->res["deleteGroup"]=linkurl("groupbuy/deleteGroup");//删除团购
			return $this->res;
		}
		/**
		 * 查询正在出售的商品
		 * 2017-2-23
		 * cgl
		 */
		function onSale(){
			//菜单
			$this->getMenu();
			$page=1;
	   		if(isset($_GET['page']))
	   			$page=$_GET['page']<=0?1:$_GET["page"];
	   		$start=($page-1)*20;
			$sql="SELECT p.`product_id`,
			    p.`model`,
			    p.`quantity`,
			    p.`image`,
			    p.`manufacturer_id`,
			    p.`proxyprice` as price, 
			    p.`points`,
			    p.sales,
			    p.proxyprice,
			    p.sort_order,
			    p.`status`,
			   	p.brand_id,
			    p.`date_modified`,
			 	pc.category_id,
			    b.name as bname,
			    pd.name,
			    c.name as cname 
				FROM `hb_product` as p,hb_product_description as pd,hb_product_to_category as pc,hb_category_description as c,hb_manufacturer as b where  p.product_id=pd.product_id and  p.product_id=pc.product_id and pc.category_id=c.category_id and p.brand_id=b.manufacturer_id and p.merchant_id='".$_SESSION["merchant_id"]."' and p.status=1 and pc.type=1 ";
			if(isset($_GET["name"]) && !empty($_GET["name"]) ){
				$sql.= " and pd.name like '%".$_GET["name"]."%' ";
			}
			if(isset($_GET['category']) && !empty($_GET["category"])){
				$sql .= " and pc.category_id=".$_GET['category'];
			}
			$sql.= " order by p.sort_order desc limit $start,20";
			$dt = getData($sql);

			if(!empty($dt)){
				foreach($dt as $k=>$v){
					$row=getRow("select * from hb_groupby where group_status=1 and product_id = '".$v["product_id"]."' ");
					if(!empty($row)){
						$dt[$k]["is_join"]=1;
					}else{
						$dt[$k]["is_join"]=0;
					}
				}
			}


		 	$this->res['dt']=$dt;
		 	if(sizeof($dt)<20)
		 		$this->getPages($page,$page);
		 	else
		 		$this->getPages($page);
			//获取全部团购活动商品
			$this->res["getlist"]=linkurl("groupbuy/getList");
			//正在出售
			$this->res["onsale"]=linkurl("groupbuy/onSale");
			//加入团购
			$this->res["joinGroup"]=linkurl("groupbuy/joinGroup");
			$this->res['cat']=$this->getCat();
			$this->res["category"]=@$_GET["category"];

			return $this->res;
		}
		function getCat()
		{
			// 修改：新增和编辑商品时，商品分类信息由商户管理人员自行选择平台预先设定好的分类
			// $dt=getData("select c.category_id,cd.name,c.parent_id,c.sort_order,c.image from hb_category as c left join hb_category_description as cd on c.category_id=cd.category_id where  c.status=1 and c.type=0 and c.merchant_id=".$_SESSION['merchant_id']." order by c.category_id desc");
			$dt=getData("select c.category_id,cd.name,c.parent_id,c.sort_order,c.image from hb_category as c left join hb_category_description as cd on c.category_id=cd.category_id where  c.status=1 and c.type=0 order by c.category_id desc");

			$cat=array();
			foreach ($dt as $key => $value) {
				
					$cat[$value['category_id']]=$value;
			}

			krsort($cat);

			//print_r($cat);

			foreach ($cat as $key => $value) {
					
					if($value['parent_id']>0)
						$cat[$value['parent_id']]['son'][]=$cat[$key];
			}

			$menu=array();

			//print_r($cat);
			
			foreach ($cat as $key => $value) {
				//sprint_r($value);

				if(isset($value['parent_id']) && $value['parent_id']==0)
					$menu[$key]=$value;
				
					
			}

			//print_r($menu);

			return $menu;
		}
		/**
		 * 加入商品到团购去
		 */
		function joinGroup(){
			//菜单
			$this->getMenu();
			//获取到三个get的数据
			$product_id=isset($_GET['product_id'])?$_GET['product_id']:"";
			//通过获取到的product_id去获取信息
			$res2=getRow("select pd.name,p.image,p.product_id,p.proxyprice as price from ".DB_PREFIX."product_description pd left join ".DB_PREFIX."product p on pd.product_id=p.product_id where p.product_id=".$product_id." and p.status=1 and p.merchant_id='".$_SESSION["merchant_id"]."' ");
			if($res2){
				//获取到所有的产品型号
				$sql="SELECT product_item_id,product_options from ".DB_PREFIX."product_item where status=0 and product_id='".$res2["product_id"]."' ";
				$product_item=getData($sql);
				$this->res['product_item']=$product_item;
				$this->res['price']=sprintf("%.2f",$res2["price"]);
				$this->res['product_id']=$product_id;
				$this->res["none"]=0;

			}else{
				$this->res["none"]=1;//没有该商品或者已经下架
			}
			$this->res['saveGroupMessage']=linkurl("groupbuy/saveGroupMessage");
			$this->res['getList']=linkurl("groupbuy/getList");
			return $this->res;
		}

		/**
		 * 保存信息到团购表
		 */
		function saveGroupMessage(){
			$json=array();
			$merchant_id=$_SESSION['merchant_id'];
			$kstime=empty($_POST['kstime'])?date("Y-m-d H:i:s",time()):$_POST['kstime'];
			$jstime=$_POST['jstime'];
			$date_added=date("Y-m-d H:i:s");
			$date_modified=date("Y-m-d H:i:s");
			$is_open_free=empty($_POST["is_open_free"])?0:$_POST["is_open_free"];
			if(isset($_POST["is_open_free"])){
				if($_POST["is_open_free"]!=0 && $_POST["is_open_free"]!=1){
					$_POST["is_open_free"]=0;
				}
			}
			if($kstime>$jstime){
				$json["msg"]="结束时间必须大于开始时间";
			}else if($kstime<date("Y-m-d H:i:s",time()) ){
				$json["msg"]="开始时间必须大于当前时间";
			}else{
				//通过获取到的product_id判断该商品有没有加入团购
				$sql="SELECT group_id from ".DB_PREFIX."groupby where product_id='".$_POST['product_id']."' and group_status=1 ";
				$res1=getRow($sql);
				if(!$res1){
					//判断该商品是否下架
					$res2=getRow("select pd.name,p.image,p.product_id from ".DB_PREFIX."product_description pd left join ".DB_PREFIX."product p on pd.product_id=p.product_id where p.product_id=".$_POST['product_id']." and p.status=1 and p.merchant_id='".$_SESSION["merchant_id"]."' ");
					//判断该商品价格是否大于零售价
					$product_price=getRow("select * from hb_product where product_id = '".$_POST['product_id']."' ");
					

					if(!$res2){
						$json["msg"]="该商品不存在或已下架";
					}else if($product_price["proxyprice"]<$_POST['ktjg']){
						$json["msg"]="该商品的团购价格不能高于原价";
					}else if($product_price["quantity"]<$_POST['ktrs']){
						$json["msg"]="该商品的数量不足";
					}else{
						$status=1;
						if($_POST['radio']==3){
							$status=3;
						}
						$data=array("product_id"=>$_POST['product_id'],"groupname"=>$res2["name"],"groupimage"=>$res2["image"],"groupnum"=>$_POST['ktrs'],"groupprice"=>$_POST['ktjg'],"groupoptionid"=>$_POST['select'],"merchant_id"=>$merchant_id,"date_added"=>$date_added,"date_modified"=>$date_modified,"start_time"=>$kstime,"end_time"=>$jstime,"group_status"=>$status,"is_open_free"=>$_POST["is_open_free"]);
						$res=saveData(DB_PREFIX."groupby",$data);
						$json["msg"]="操作成功";
						$json["retcode"]=0;
					}
				}else{
					$json["msg"]="该商品已经加入团购";
				}
			}
			echo json_encode($json);
			exit();
		}
		/**
		 * cgl 2017-6-14 查看详情
		 */
		function detail(){
			//菜单
			$this->getMenu();
			$id=@$_GET["id"];

			//获取到三个get的数据
			$product_id=isset($_GET['product_id'])?$_GET['product_id']:"";
			//通过获取到的product_id去获取信息
			$res2=getRow("select pd.name,p.image,p.product_id,p.proxyprice as price from ".DB_PREFIX."product_description pd left join ".DB_PREFIX."product p on pd.product_id=p.product_id where p.product_id=".$product_id." and p.status=1 and p.merchant_id='".$_SESSION["merchant_id"]."' ");
			if($res2){
				//获取到所有的产品型号
				$sql="SELECT product_item_id,product_options from ".DB_PREFIX."product_item where status=0 and product_id='".$res2["product_id"]."' ";
				$product_item=getData($sql);
				$this->res['product_item']=$product_item;
				$this->res['price']=sprintf("%.2f",$res2["price"]);
				$this->res['product_id']=$product_id;
				$this->res["none"]=0;
				//查询团购详情
				$group_detail=getRow("SELECT * from hb_groupby where group_id = '".$id."' ");
				// print_r($group_detail);
				if($group_detail){
					$this->res['group_detail']=$group_detail;
					$this->res['group_status']=$group_detail["group_status"];
					$this->res['is_open_free']=$group_detail["is_open_free"];

				}else{
					$this->res["none"]=2;//没有该商品或者已经下架
				}
				$this->res['editGroupMessage']=linkurl("groupbuy/editGroupMessage");
				$this->res['getList']=linkurl("groupbuy/getList");

			}else{
				$this->res["none"]=1;//没有该商品或者已经下架
			}



			return $this->res;
		}
		/**
		 * 编辑团购的
		 */
		function editGroupMessage(){
			$json=array();
			$merchant_id=$_SESSION['merchant_id'];
			$kstime=empty($_POST['kstime'])?date("Y-m-d H:i:s",time()):$_POST['kstime'];
			$jstime=$_POST['jstime'];
			$date_added=date("Y-m-d H:i:s");
			$date_modified=date("Y-m-d H:i:s");
			$is_open_free=empty($_POST["is_open_free"])?0:$_POST["is_open_free"];
			if(isset($_POST["is_open_free"])){
				if($_POST["is_open_free"]!=0 && $_POST["is_open_free"]!=1){
					$_POST["is_open_free"]=0;
				}
			}
			if($kstime>$jstime){
				$json["msg"]="结束时间必须大于开始时间";
			}else if($kstime<date("Y-m-d H:i:s",time()) ){
				$json["msg"]="开始时间必须大于当前时间";
			}else{
				//通过获取到的product_id判断该商品有没有加入团购
				$sql="SELECT group_id from ".DB_PREFIX."groupby where product_id='".$_POST['product_id']."' and group_id = '".$_POST["group_id"]."' ";
				$res1=getRow($sql);
				if($res1){
					//判断该商品是否下架
					$res2=getRow("select pd.name,p.image,p.product_id from ".DB_PREFIX."product_description pd left join ".DB_PREFIX."product p on pd.product_id=p.product_id where p.product_id=".$_POST['product_id']." and p.status=1 and p.merchant_id='".$_SESSION["merchant_id"]."' ");
					//判断该商品价格是否大于零售价
					$product_price=getRow("select * from hb_product where product_id = '".$_POST['product_id']."' ");
					

					if(!$res2){
						$json["msg"]="该商品不存在或已下架";
					}else if($product_price["proxyprice"]<$_POST['ktjg']){
						$json["msg"]="该商品的团购价格不能高于原价";
					}else if($product_price["quantity"]<$_POST['ktrs']){
						$json["msg"]="该商品的数量不足";
					}else{
						$status=1;
						if($_POST['radio']==3){
							$status=3;
						}
						$data=array("product_id"=>$_POST['product_id'],"groupname"=>$res2["name"],"groupimage"=>$res2["image"],"groupnum"=>$_POST['ktrs'],"groupprice"=>$_POST['ktjg'],"groupoptionid"=>$_POST['select'],"merchant_id"=>$merchant_id,"date_added"=>$date_added,"date_modified"=>$date_modified,"start_time"=>$kstime,"end_time"=>$jstime,"group_status"=>$status,"is_open_free"=>$_POST["is_open_free"],"group_id"=>$_POST["group_id"]);
						$res=saveData(DB_PREFIX."groupby",$data);

						$json["msg"]="操作成功";
						$json["retcode"]=0;
					}
				}else{
					$json["msg"]="该团购不存在";
				}
			}
			echo json_encode($json);

			die;
		}

		/**
		 * cgl 2017-2-24  关闭团购商品
		 */
		function closeGroup(){
			$groupid=isset($_POST["groupid"])?$_POST["groupid"]:null;
			$json=array();
			if($groupid){
				//判断是否存在
				$data=getData("select * from ".DB_PREFIX."groupby where merchant_id='".$_SESSION["merchant_id"]."' and group_status!=3 and group_id in (".$groupid.") ");
				if(!empty($data)){
					//根据团购商品来查询团
					foreach($data as $k=>$v){
						$tuan=getData("select * from hb_group_join where product_id='".$v["product_id"]."' and group_id='".$v["group_id"]."' and join_status=1 ");
						//查询哪些人买了这个商品
						if(!empty($tuan)){
							foreach($tuan as $kk=>$vv){
								$join_customer=getData("select * from hb_group_join_info where product_id='".$vv["product_id"]."' and group_id='".$vv["group_id"]."' and status=1 and join_id= '".$vv["join_id"]."'  ");
								//查询参加人的订单
								if(!empty($join_customer)){
									foreach($join_customer as $kkk=>$vvv){
										$order=getData("select * from hb_order where customer_id= '".$vvv["customer_id"]."' and join_id= '".$vvv["join_id"]."' and order_type=2 ");
										if(!empty($order)){
											foreach($order as $kkkk=>$vvvv){
												//修改状态
												exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '7',date_modified=NOW(),order_type_status=4 WHERE order_id = '" . $vvvv["order_id"] . "' and order_type=2 ");
												// //增加订单历史记录
												saveData(DB_PREFIX."order_history",array("order_id"=>$vvvv["order_id"],"order_status_id"=>"7","notify"=>"0","comment"=>"后台团购关闭了","date_added"=>date("Y-m-d H:i:s")));
												//退款返利的
												$merchant_id = $vvvv['merchant_id'];
									            $return_money = $vvvv['total'] - $vvvv['freight']-$vvvv['invoicefee'];
									            $customer_id=$vvvv["customer_id"];
									            $order_id=$vvvv["order_id"];
									            //将商户余额中的该订单金额退还
              									$money = $return_money;
              									//将商户余额中的该订单金额退还
									            exeSql("update `" .DB_PREFIX. "merchant` set money = money-".(float)$money." where merchant_id = " .(int)$merchant_id);
									            // //退还退款人的资金
									            exeSql("UPDATE `" .DB_PREFIX. "balance` SET balance = balance+" .$return_money. ",availabe_balance = availabe_balance+" .$return_money. ",date_modified = NOW() WHERE customer_id = " .$customer_id);
              									//增加退款的商户资金明细记录  cgl   2017-3-13  增加
              									saveData(DB_PREFIX."merchant_money_record",
									              array("money"=>"-".(float)$money,
									                    "merchant_id"=>$merchant_id,
									                    "add_time"=>date("Y-m-d H:i:s",time()),
									                    "remark"=>"订单退款:".$order_id,
									                    "relate_id"=>$order_id,
									                    "type"=>5,
									                    "customer_id"=>$customer_id
									                )
									            );
									            //增加退款用户的资金记录
									            $this->insertmoney($return_money,$customer_id,$vvvv["order_id"],$vvvv["merchant_id"]);
									            //退款返利的价格  cgl 2017-2-15 
									            $this->add_return_money(array("customer_id"=>$customer_id,"order_id"=>$vvvv["order_id"],"merchant_id"=>$vvvv["merchant_id"],"return_add_money"=>$vvvv["return_add_money"]));

											}
										}
										//修改参团的状态
										exeSql("UPDATE `" . DB_PREFIX . "group_join_info` SET status = '2',date_modified=NOW() WHERE product_id='".$vvv["product_id"]."' and group_id='".$vvv["group_id"]."' and join_id= '".$vvv["join_id"]."'  ");
									}
								}
								//修改团的状态
								exeSql("UPDATE `" . DB_PREFIX . "group_join` SET join_status = '2',date_modified=NOW() WHERE product_id='".$vv["product_id"]."' and group_id='".$vv["group_id"]."' and join_id= '".$vv["join_id"]."'  ");
							}
						}
						exeSql("update ".DB_PREFIX."groupby set group_status=3 where merchant_id='".$_SESSION["merchant_id"]."' and group_status!=3 and group_id in (".$groupid.") ");
							
						// else{
						// 	$json["msg"]="关闭失败，没有失败的团";
						// }
						$json["msg"]="关闭成功";
					}
					
				}else{
					$json["msg"]="数据错误";
				}
			}else{
				$json["msg"]="数据为空";
			}
			echo json_encode($json);
			die;
		}
		/**
		 * cgl 2017-3-29  删除团购商品
		 */
		function deleteGroup(){
			$groupid=isset($_POST["groupid"])?$_POST["groupid"]:null;
			$json=array();
			if($groupid){
				$data=getData("select * from ".DB_PREFIX."groupby where merchant_id='".$_SESSION["merchant_id"]."' and group_status!=4 and group_id in (".$groupid.") ");
				if(!empty($data)){
					exeSql("update ".DB_PREFIX."groupby set group_status=4 where merchant_id='".$_SESSION["merchant_id"]."' and group_status!=4 and group_id in (".$groupid.") ");
					$json["msg"]="删除成功";
				}else{
					$json["msg"]="数据错误";
				}
			}else{
				$json["msg"]="数据为空";
			}
			echo json_encode($json);
			die;
		}

		/*
		*处理时间，并拼装
		*/
		function updateTime($time){
			$y_site=strrpos($time, "/");
			$d_site=strpos($time, "/");
			$ks_y=substr($time,$y_site+1);
			$ks_d=substr($time,0,$d_site);
			$ks_m=substr($time,$d_site+1,$y_site-3);
			$ks_time=$ks_y."-".$ks_d."-".$ks_m;
			return $ks_time;
		}
		/**
	   * 增加用户的退款记录  
	   * cgl  2017-2-15 新增
	   */
	  function insertmoney($return_money,$customer_id,$order_id,$merchant_id){
	    //查询该用户的资金
	    $balance=getRow("select * from `".DB_PREFIX."balance` where customer_id='".$customer_id."' ");
	    
	    $balance_money=$balance["balance"];
	    //余额
	    $availabe_balance=$balance["availabe_balance"];

	    //增加用户资金记录
	    saveData(DB_PREFIX."customer_transaction",
	            array("customer_id"=>$customer_id,
	                  "order_id"=>$order_id,
	                  "description"=>"退款:".$return_money,
	                  "amount"=>"+".$return_money,
	                  "date_added"=>date("Y-m-d H:i:s",time()),"status"=>2,
	                  "balance_change"=>"+".$return_money,
	                  "availabe_balance_change"=>"+".$return_money,
	                  "date_modified"=>date("Y-m-d H:i:s",time()),
	                  "pingzhen"=>0,
	                  "pingzhen_type"=>0,
	                  "relate_merchant_id"=>$merchant_id,
	                  "last_balance"=>$balance_money,
	                  "last_availabe_balance"=>$availabe_balance,
	                  "type"=>8)
	            );
	  }
	  /**
	   * cgl  2017-2-15  增加  退款返利的价格
	   */
	  function add_return_money($val){
	    $customer=getRow("SELECT * FROM `" . DB_PREFIX . "customer` WHERE customer_id = '".$val['customer_id']."' ");
	        //是否有上级会员
	        $is_has=$customer["parent_id"];
	        if($is_has!=null && $is_has!="" && $customer["merchant_id"]==1 && $is_has!=0){
	          //根据订单号查询
	          $quantitys=getRow("SELECT * FROM `hb_order_product` WHERE order_id = " .$val["order_id"]);

	            //只适合嗨企货仓的会员   返利的资金
	            $money_return=$val["return_add_money"]*(float)$quantitys["quantity"];
	            //增加上级会员的资金   和资金记录
	            $sql3 = "UPDATE `" .DB_PREFIX. "balance` SET balance = balance-'".$money_return."', date_modified = NOW() WHERE customer_id = " .$is_has;
	            exeSql($sql3);
	            //查询上级的资金
	            $on=getRow("SELECT * FROM `" . DB_PREFIX . "balance` WHERE customer_id = '".$is_has."' ");

	            $description="购买人：".$val["customer_id"].",返利退款：".$money_return;
	            saveData(DB_PREFIX."customer_transaction",
	            array("customer_id"=>@$is_has,
	                  "order_id"=>$val["order_id"],
	                  "description"=>$description,
	                  "amount"=>"-".$money_return,
	                  "date_added"=>date("Y-m-d H:i:s",time()),"status"=>2,
	                  "balance_change"=>"-".$money_return,
	                  "availabe_balance_change"=>0,
	                  "date_modified"=>date("Y-m-d H:i:s",time()),
	                  "pingzhen"=>'',
	                  "pingzhen_type"=>'',
	                  "relate_merchant_id"=>$val["merchant_id"],
	                  "last_balance"=>$on["balance"],
	                  "last_availabe_balance"=>$on["availabe_balance"],
	                  "type"=>7)
	            );
	            // $this->db->query($insert_money1);
	            //扣除商户的资金   增加商户的资金记录
	            exeSql("update `" .DB_PREFIX. "merchant` set money = money+" .(float)$money_return ." where merchant_id = " .(int)$val['merchant_id']);
	            saveData(DB_PREFIX."merchant_money_record",
	              array("money"=>"+".(float)$money_return,
	                    "merchant_id"=>$val["merchant_id"],
	                    "add_time"=>date("Y-m-d H:i:s",time()),
	                    "remark"=>"订单：".$val['order_id'],
	                    "relate_id"=>$val["order_id"],
	                    "type"=>6,
	                    "customer_id"=>@$is_has
	                )
	            );
	        }
	  }


	}
?>