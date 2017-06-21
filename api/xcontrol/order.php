<?php
	//面向对象的control 类
include "xcontrol/base.php";
class order extends base
{
	/* 定义一个客服功能需要的商品链接   2017-3-3  cgl
     */
    const SERVICE_URL='https://haiqihuocang.com/product_detail/';

	/**
	 * cgl  2017 -1-12
	 * pc请求订单的状态
	 */
	function __construct() 
	{
       parent::__construct();
       //print "In SubClass constructor\n";
       if(isset($_SESSION['userid']))
       {
       	$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
       }
		
   	}


	function index(){
		if(!empty($_POST["out_trade_no"])){
			$order=isset($_POST["out_trade_no"])?$_POST["out_trade_no"]:null;//"1015342";//以逗号隔开
			$orders=getData("select * from hb_order as a join hb_order_product as b on a.order_id= b.order_id  where a.order_num = '".$order."' ");
			if(!empty($orders)){
				if($orders[0]["order_status_id"]==2){
					//支付成功
					$this->res["retcode"]=0;
				}else{
					//支付失败
					$this->res["retcode"]=1001;	
				}
			}else{
				$this->res["retcode"]=1130;
				// echo json_decode("retcode"=>"1000");	
			}
		}else{
			$this->res["retcode"]=1000;
			//echo json_decode("retcode"=>"1000");
		}
		return $this->res;
	}
	//订单明细 by xi 2017-1-12
	function detail()
	{
		if(!isset($_POST['order_id']))
		{
			$this->res=array("retcode"=>1,'msg'=>"订单id必须输入");
			return $this->res;
		}

		//订单信息//1.支付宝2 微信 3混合支付 `payment_method`,  `shipping_custom_field`, 收货人电话
		$this->res['order']=getRow("SELECT o.`order_id`,
      		o.`payment_method`,
		    o.`shipping_firstname`,
		    o.`shipping_lastname`,
		    o.`shipping_company`,
		    o.`shipping_address_1`,
		    o.`shipping_address_2`,
		    o.`shipping_city`,
		    o.`shipping_postcode`,
		    o.`shipping_country`,
		    o.`shipping_country_id`,
		    o.`shipping_zone`,
		    o.`shipping_zone_id`,
		    o.`shipping_address_format`,
		    o.`shipping_custom_field`,
		    o.`shipping_method`,
		    o.`shipping_code`,
		    o.`comment`,
		    o.`total`,
		    o.`order_status_id`,
		    o.`currency_id`,
		    o.`currency_code`,
		    o.`currency_value`,
		    o.`date_added`,
		    o.`date_modified`,
		    o.`status`,
		    o.`ship_order_no`,
		    o.`ship_id`,
		    o.`image`,
		    o.`isback`,
		    o.`isreview`,
		    o.`order_num`,
		    o.`is_blance`,
		    o.`freight`,
		    o.`invoicefee`,
		    o.`balance_money`,
		    o.`merchant_id`,
		    o.`ship_date`,
		    o.`warehouse_id`,
		    o.`order_pay_money`,
		    o.`order_return_code`,
		    o.`order_pay_trade_code`,
		    o.`is_share`,
		    o.`derate_money`,
		    o.`return_add_money`,
		    w.name as warehouse_name,
		    o.order_type,
		    o.order_type_status,
		    o.relate_id,
		    o.coupon_discount
 
		FROM `hb_order` as o ,hb_warehouse as w
		where o.warehouse_id=w.warehouse_id and   o.order_id=:order_id",$_POST);
		$tmp=@json_decode($this->res['order']['shipping_custom_field'],true);
		$this->res['order']['shipping_telephone']=$tmp['1'];
		//cgl  2017-4-11  优惠券字段
		
		//催发货
        if(isset($this->res['order'])){
            $push=getRow("select * from hb_remind where order_id=:order_id order by remind_id desc limit 0,1",$this->res['order']);
        }

		if(!isset($push['date_added'])||$push['date_added']<date("Y-m-d",strtotime("- 1 days")))
			$this->res['order']['ispush']="1";
		else
			$this->res['order']['ispush']="0";
		//收货信息
		//退款状态
		$returnStatus=getRow("SELECT return_status_id FROM hb_return where order_id=:order_id",$_POST);
		$this->res['order']['return_status']='0';
		if(isset($returnStatus['return_status_id']))
			$this->res['order']['return_status']=$returnStatus['return_status_id'];

		//收货时间
		$recivedt=getRow("SELECT date_added FROM hb_order_history where order_id=:order_id and order_status_id=4",$_POST);
		if(isset($recivedt['date_added']))
		$this->res['order']['recive_date']=strtotime($recivedt['date_added']);
		else
			$this->res['order']['recive_date']='';

		//账户余额 availabe_balance
		$availabe_balance=getRow("select availabe_balance from hb_balance where customer_id=:customerid",$_POST);
		if(isset($availabe_balance['availabe_balance']))
			$this->res['order']['availabe_balance']=$availabe_balance['availabe_balance'];
		else
			$this->res['order']['availabe_balance']=0;

		//商品信息 修改商品为左联接
		$sql="SELECT op.order_product_id,op.name,op.price,op.quantity,op.total,op.model,op.product_item_id,op.product_item_name,p.proxyprice,p.price, p.image,p.marketprice,p.manufacturer_id,w.name as warehouse_name,p.product_id  FROM hb_order_product as op left join hb_product as p on p.product_id=op.product_id left join hb_warehouse as w on p.manufacturer_id=w.warehouse_id where op.order_id=:order_id";
		$this->res['order']['product']=getData($sql,$_POST);
		foreach ($this->res['order']['product'] as $key => $value) {
			if($value['product_item_id'] > 0){
				$sql = "select product_options as name,image from hb_product_item where product_item_id = :product_item_id";
				$product_item_name=getRow($sql,$value);
				$this->res['order']['product'][$key]['product_item_name'] = $product_item_name['name'];
			}else{
				$this->res['order']['product'][$key]['product_item_name'] = '';
				$sql="SELECT oo.name,oo.type,oo.value,po.image FROM hb_order_option as oo,hb_product_option_value as po where oo.product_option_value_id=po.product_option_value_id and  oo.order_product_id=:order_product_id";
				$this->res['order']['product'][$key]['option']=getData($sql,$value);
			}

            if(isset($value["price"]) && $value["price"])$this->res['order']['product'][$key]['price']=sprintf('%.2f',$value["price"]);
            
            //cgl 2017-6-19  修改价格
            $order_price=getRow("SELECT price from hb_order_product where order_id = '".$_POST['order_id']."' ");

            $this->res['order']['product'][$key]['price']=sprintf("%.2f",@$order_price["price"]);

            if(isset($value["proxyprice"]) && $value["proxyprice"])$this->res['order']['product'][$key]['proxyprice']=sprintf('%.2f',$value["proxyprice"]);

			//新增前端需要的链接
			$this->res['order']['product'][$key]['service_url']=self::SERVICE_URL.$value["product_id"];
		}

		//团购 新增  cgl 2017-3-8
		if($this->res['order']["order_type_status"]==2){
			$item = json_decode($this->res['order']["relate_id"],"json");
			$group_type = $item['group_type'];//1为参团，2为开团
			$product_id = $item['product_id'];
			$join_id = $item['join_id'];
			$group_id = $item['group_id'];
			$group=getRow("select *,count(gji.join_id) as total from hb_groupby as g left join hb_group_join as gj on g.group_id=gj.group_id 
				left join hb_group_join_info as gji on gj.join_id=gji.join_id
				where g.group_id ='".$group_id."' and g.product_id = '".$product_id."' and g.group_status=1 and gj.group_id ='".$group_id."' and gj.product_id = '".$product_id."' and gj.join_status=1 and gji.join_id = '".$join_id."' and gji.status=1 ");
			$this->res['order']["short_num"]=@$group["groupnum"]-@$group["total"];
			if($_SERVER["SERVER_NAME"]=="test.haiqihuocang.com"){
                  $url_name="http://test.haiqihuocang.com";
            }else{
                  $url_name="http://haiqihuocang.cn";
            }
			$this->res['order']["invite_url"]=$url_name."/web/buy_group/share_buy.html?product_id=".$product_id."&join_id=".$join_id."&group_id=".$group_id;
		}else{
			$this->res['order']["short_num"]=0;
			$this->res['order']["invite_url"]="";
		}




		return $this->res;
		
		//订单状态 物流单号 物流公司 收货人 电话 地址 发货仓库 
		//商品名称 数量 价格
		//订单价格
		//订单编号 支付方式
		//下单时间

	}

	/*
	 * 获取买入的订单列表
	 * 17.3.24 wangzhichao
	 */
	function getOrderIn(){
		$post = $_POST;
		$offset = isset($post['offset'])?$post['offset']:0;
		$status = isset($post['status'])?$post['status']:0;
		$limit = 10;

		$order_sql = '';
		if($status == 0){
			$order_sql .= " AND o.order_status_id > 0 AND o.status = 0";
		}elseif($status == 1){
			$order_sql .=" AND o.order_status_id = 1 AND o.status = 0";
		}elseif($status == 2){
			$order_sql .= " AND o.order_status_id = 2 AND o.status = 0";
		}elseif($status == 3){
			$order_sql .= " AND (o.order_status_id = 3 OR o.order_status_id = 11) AND o.status = 0 ";
		}elseif($status == 4){
			$order_sql .= " AND o.order_status_id = 4 AND o.status = 0 AND (o.isreview = 0 OR o.isreview = 1) AND (r.return_status_id = 4 OR o.isback = 0)";
		}else{
			$order_sql .= " AND o.order_status_id > 0 AND o.status = 0 AND o.isback = 1";
		}

		$order = getData("select o.order_id as orderid,
						   o.date_added as `timestamp`,
						   o.customer_id,
						   o.merchant_id,
						   o.order_status_id as status,
						   o.total,
						   o.freight,
						   o.is_share,
						   o.derate_money,
						   o.isback,
						   o.isreview,
						   o.order_type,
						   o.order_type_status,
						   IFNULL(o.relate_id,'0') as relate_id,
						   o.image as productimg,
						   op.product_id as productid,
						   op.name as productname,
						   op.price as originalprice,
						   op.quantity as number,
						   IFNULL(op.product_item_name,'') as product_item_name,
						   p.marketprice,
						   p.price,
						   p.proxyprice,
						   IFNULL(r.return_status_id,'') as return_status_id,
						   w.warehouse_id,
						   w.name as repertory,
						   IFNULL(ms.telephone,'') AS telephone
 						   from `hb_order` as o
						   inner join `hb_order_product` as op on op.order_id = o.order_id
						   inner join `hb_product` as p on p.product_id = op.product_id
						   inner join `hb_warehouse` as w on w.warehouse_id = o.warehouse_id
						   left join `hb_return` as r on r.order_id = o.order_id
						   left join `hb_merchant_service` as ms on ms.merchant_id = o.merchant_id
						   where o.customer_id = '" .(int)$this->customer_id. "' $order_sql and is_member_status=0 order by o.date_added desc limit $offset,$limit
						  ");

		//and o.order_type_status <> 1  cgl 2017-4-3  修改  增加会员订单  is_member_status=0
		foreach($order as $key=>$val){
			$order[$key]['total'] = sprintf("%.2f", $val['total']);
			$order[$key]['originalprice'] = sprintf("%.2f", $val['originalprice']);
			$order[$key]['derate_money'] = sprintf("%.2f", $val['derate_money']);

			$availabe_balance=getRow("select availabe_balance from hb_balance where customer_id = '".$this->customer_id."' ");

			$order[$key]['availabe_balance'] = sprintf("%.2f", @$availabe_balance['availabe_balance']);
			$order[$key]['marketprice'] = sprintf("%.2f", $val['marketprice']);

            $order[$key]['price'] = sprintf("%.2f", $val['price']);
            $order[$key]['proxyprice'] = sprintf("%.2f", $val['proxyprice']);

			//待收货状态判读是否提醒发货
			if($val['status'] == 2){
				$remind = getRow("SELECT * FROM `hb_remind` WHERE  order_id = " .(int)$val['orderid']);

				$order[$key]['ispush'] = 1;
				if($remind){
					$order[$key]['ispush'] = time();
					//每隔24小时能推送一次
					$time = (time() - strtotime($remind['date_added']))/3600;
					if($time < 24){
						$order[$key]['ispush'] = 2;
					}
				}else{
					$order[$key]['ispush'] = 1;
				}
			}

			//当订单状态为收货的情况下返回收货时间
			if($val['status'] == 4){
				$history = getRow("SELECT date_added FROM hb_order_history WHERE order_id = " .$val['orderid']. " and order_status_id = 4");
				$order[$key]['accept_timestamp'] = strtotime(@$history['date_added']);
			}

			//存在退款时
			if($val['isback'] == 1){
				$order[$key]['isback'] = $val['return_status_id'];
			}
			unset($order[$key]['return_status_id']);

			//团购
			//查询
			if($val["order_type_status"]!=1){  //cgl 2017-4-3  修改
				$order[$key]=$order[$key];
				if($val["order_type"]==2 && $val["order_type_status"]==2 ){
					$item = json_decode($val["relate_id"],"json");

					$group_type = @$item['group_type'];//1为参团，2为开团
					$product_id = @$item['product_id'];
					$join_id = @$item['join_id'];
					$group_id = @$item['group_id'];
					$group=getRow("select * from hb_groupby as gb  where (gb.group_status=1 or gb.group_status=4) and gb.product_id='".$product_id."' and gb.group_id='".$group_id."' ");
					$number=getRow("select count(*) as total from hb_group_join_info as gi left join hb_customer as c on gi.customer_id=c.customer_id where gi.join_id= '".$join_id."' and gi.group_id = '".$group_id."' and gi.product_id= '".$product_id."' and gi.status=1");
					$num=@$group["groupnum"]-@$number["total"];
					$order[$key]["short_num"]=$num;
					if($_SERVER["SERVER_NAME"]=="test.haiqihuocang.com"){
		                  $url_name="http://test.haiqihuocang.com";
		            }else{
		                  $url_name="http://iwant-u.cn";
		            }
					$order[$key]["invite_url"]=$url_name."/web/buy_group/share_buy.html?product_id=".$product_id."&join_id=".$join_id."&group_id=".$group_id;
				}else{
					$order[$key]["short_num"]=0;
					$order[$key]["invite_url"]="";
				}
				
			}
			unset($order[$key]["relate_id"]);

			//客服功能需要的前端商品链接
			$order[$key]['service_url'] = self::SERVICE_URL.$val['productid'];
		}

        $this->res['retcode'] = 0;
        $this->res['msg'] = 'success';
		$this->res['data'] = $order;
		return $this->res;
	}
	/*
	 * 团购订单
	 * 2017-5-3 cgl
	 * 0:全部
	 * 1:待付款
	 * 2：拼团中
	 * 3：待发货
	 * 4：待收货
	 */
	function getGroupOrder(){
		$post = $_POST;
		$offset = isset($post['offset'])?$post['offset']:0;
		$status = isset($post['status'])?$post['status']:0;
		$limit = 10;

		$order_sql = '';
		if($status == 0){
			$order_sql .= " AND o.order_status_id > 0 AND o.status = 0";
		}elseif($status == 1){
			$order_sql .=" AND o.order_status_id = 1 and order_type_status=1 AND o.status = 0";
		}elseif($status == 2){
			$order_sql .= " AND o.order_status_id = 2 and order_type_status=2 AND o.status = 0";
		}elseif($status == 3){
			$order_sql .= " AND o.order_status_id = 2 and order_type_status=3 AND o.status = 0 ";
		}elseif($status == 4){
			$order_sql .= " AND (o.order_status_id = 3 OR o.order_status_id = 11 ) AND o.status = 0 AND (o.isreview = 0 OR o.isreview = 1) AND (r.return_status_id = 4 OR o.isback = 0) and order_type_status=3";
		}else{
			$order_sql .= " AND o.order_status_id > 0 AND o.status = 0 AND o.isback = 1";
		}
		//cgl 2017-5-3 团购订单
		$order_sql .=" and o.order_type=2";

		$order = getData("select o.order_id as orderid,
						   o.date_added as `timestamp`,
						   o.customer_id,
						   o.merchant_id,
						   o.order_status_id as status,
						   o.total,
						   o.freight,
						   o.is_share,
						   o.derate_money,
						   o.isback,
						   o.isreview,
						   o.order_type,
						   o.order_type_status,
						   IFNULL(o.relate_id,'0') as relate_id,
						   o.image as productimg,
						   op.product_id as productid,
						   op.name as productname,
						   op.price as originalprice,
						   op.quantity as number,
						   IFNULL(op.product_item_name,'') as product_item_name,
						   p.marketprice,
						   p.price,
						   p.proxyprice,
						   IFNULL(r.return_status_id,'') as return_status_id,
						   w.warehouse_id,
						   w.name as repertory,
						   IFNULL(ms.telephone,'') AS telephone
 						   from `hb_order` as o
						   inner join `hb_order_product` as op on op.order_id = o.order_id
						   inner join `hb_product` as p on p.product_id = op.product_id
						   inner join `hb_warehouse` as w on w.warehouse_id = o.warehouse_id
						   left join `hb_return` as r on r.order_id = o.order_id
						   left join `hb_merchant_service` as ms on ms.merchant_id = o.merchant_id
						   where o.customer_id = '" .(int)$this->customer_id. "' $order_sql and is_member_status=0 order by o.date_added desc limit $offset,$limit
						  ");

		//and o.order_type_status <> 1  cgl 2017-4-3  修改  增加会员订单  is_member_status=0
		foreach($order as $key=>$val){
			$order[$key]['total'] = sprintf("%.2f", $val['total']);
			$order[$key]['originalprice'] = sprintf("%.2f", $val['originalprice']);
			$order[$key]['derate_money'] = sprintf("%.2f", $val['derate_money']);

			$availabe_balance=getRow("select availabe_balance from hb_balance where customer_id = '".$this->customer_id."' ");

			$order[$key]['availabe_balance'] = sprintf("%.2f", @$availabe_balance['availabe_balance']);
			$order[$key]['marketprice'] = sprintf("%.2f", $val['marketprice']);

            $order[$key]['price'] = sprintf("%.2f", $val['price']);
            $order[$key]['proxyprice'] = sprintf("%.2f", $val['proxyprice']);

			//待收货状态判读是否提醒发货
			if($val['status'] == 2){
				$remind = getRow("SELECT * FROM `hb_remind` WHERE  order_id = " .(int)$val['orderid']);

				$order[$key]['ispush'] = 1;
				if($remind){
					$order[$key]['ispush'] = time();
					//每隔24小时能推送一次
					$time = (time() - strtotime($remind['date_added']))/3600;
					if($time < 24){
						$order[$key]['ispush'] = 2;
					}
				}else{
					$order[$key]['ispush'] = 1;
				}
			}

			//当订单状态为收货的情况下返回收货时间
			if($val['status'] == 4){
				$history = getRow("SELECT date_added FROM hb_order_history WHERE order_id = " .$val['orderid']. " and order_status_id = 4");
				$order[$key]['accept_timestamp'] = strtotime(@$history['date_added']);
			}

			//存在退款时
			if($val['isback'] == 1){
				$order[$key]['isback'] = $val['return_status_id'];
			}
			unset($order[$key]['return_status_id']);

			//团购
			//查询
			// if($val["order_type_status"]!=1){  //cgl 2017-4-3  修改
				// $order[$key]=$val;
				if($val["order_type"]==2 && $val["order_type_status"]==2 ){
					$item = json_decode($val["relate_id"],"json");

					$group_type = @$item['group_type'];//1为参团，2为开团
					$product_id = @$item['product_id'];
					$join_id = @$item['join_id'];
					$group_id = @$item['group_id'];
					$group=getRow("select * from hb_groupby as gb  where (gb.group_status=1 or gb.group_status=4) and gb.product_id='".$product_id."' and gb.group_id='".$group_id."' ");
					$number=getRow("select count(*) as total from hb_group_join_info as gi left join hb_customer as c on gi.customer_id=c.customer_id where gi.join_id= '".$join_id."' and gi.group_id = '".$group_id."' and gi.product_id= '".$product_id."' and gi.status=1");
					$num=@$group["groupnum"]-@$number["total"];
					$order[$key]["short_num"]=$num;
					if($_SERVER["SERVER_NAME"]=="test.haiqihuocang.com"){
		                  $url_name="http://test.haiqihuocang.com";
		            }else{
		                  $url_name="http://iwant-u.cn";
		            }
					$order[$key]["invite_url"]=$url_name."/web/buy_group/share_buy.html?product_id=".$product_id."&join_id=".$join_id."&group_id=".$group_id;
				}else{
					$order[$key]["short_num"]=0;
					$order[$key]["invite_url"]="";
				}
				
			// }
			unset($order[$key]["relate_id"]);

			//客服功能需要的前端商品链接
			$order[$key]['service_url'] = self::SERVICE_URL.$val['productid'];
		}

        $this->res['retcode'] = 0;
        $this->res['msg'] = 'success';
		$this->res['data'] = $order;
		return $this->res;
	}

	/*
	 * 获取卖出订单列表
	 * wangzhichao  17.3.24
	 */
	function getOrderOut(){
		$post = $_POST;

		$page = isset($post['page'])?$post['page']:1;
		$status = isset($post['status'])?$post['status']:0;
		$limit = 10;
		$start = ($page-1)*$limit;

		//0全部，2待发货，3待收货，-1退款
		$order_sql = '';
		if($status == 0){
			$order_sql .= "  AND o.order_status_id > 0 AND o.order_status_id <> 1 AND o.order_status_id <>6";
		}elseif($status == 2){
			$order_sql .= " AND o.order_status_id = 2";
		}elseif($status == 3){
			$order_sql .= " AND (o.order_status_id = 3 OR o.order_status_id = 11)";
		}else{
			$order_sql .= " AND o.isback = 1 AND o.order_status_id > 0";
		}



		$order = getData("select ot.order_id as orderid,
							ot.data_added as `time`,
 							o.order_status_id status,
 							o.total,
 							o.freight,
 							o.image as productimg,
 							o.isback,
 							o.isreview,
 							o.date_modified,
 							op.product_id as productid,
 							op.name as productname,
 							op.price as originalprice,
 							IFNULL(op.product_item_name,'') as product_item_name,
 							op.quantity as number,
 							w.warehouse_id,
						   	w.name as repertory,
						   	IFNULL(r.return_status_id,'') as return_status_id,
						   	IFNULL(ms.telephone,'') AS telephone
							from `hb_orderout` as ot
					  		inner join `hb_order` as o on o.order_id = ot.order_id
					  		inner join `hb_order_product` op on op.order_id = ot.order_id
					  		inner join `hb_warehouse` as w on w.warehouse_id = o.warehouse_id
					  		left join `hb_merchant_service` as ms on ms.merchant_id = o.merchant_id
					  		left join `hb_return` as r on r.order_id = ot.order_id
							where ot.customer_id = '" .(int)$this->customer_id. "' and ot.status = 0 $order_sql order by ot.data_added desc limit $start,$limit
						");

		foreach($order as $key=>$val){
			$order[$key]['total'] = sprintf("%.2f", $val['total']);
			$order[$key]['freight'] = sprintf("%.2f", $val['freight']);
			$order[$key]['originalprice'] = sprintf("%.2f", $val['originalprice']);

			//待收货状态判读是否提醒发货
			if($val['status'] == 2){
				$remind = getRow("SELECT * FROM `hb_remind` WHERE  order_id = " .(int)$val['orderid']);

				$order[$key]['ispush'] = 1;
				if($remind){
					$order[$key]['ispush'] = time();
					//每隔24小时能推送一次
					$time = (time() - strtotime($remind['date_added']))/3600;
					if($time < 24){
						$order[$key]['ispush'] = 2;
					}
				}
			}

			//发货超过六天，分享人可以代买家确认收货，显示确认收货按钮
			$date_modified = strtotime($val['date_modified']) + 6*24*3600;
			$time = time();
			if($val['status'] == 3 && $date_modified > $time){
				$order[$key]['is_hidden'] = 1;
			}

			//当订单状态为收货的情况下返回收货时间
			if($val['status'] == 4){
				$history = getRow("SELECT date_added FROM hb_order_history WHERE order_id = " .$val['orderid']. " and order_status_id = 4");
				$order[$key]['accept_timestamp'] = strtotime(@$history['date_added']);
			}

			//存在退款时
			if($val['isback'] == 1){
				$order[$key]['isback'] = $val['return_status_id'];
			}
			unset($order[$key]['return_status_id']);
		}

		$this->res['data'] = $order;
		return $this->res;
	}

	/*
	 * 查看退款详情
	 * wangzhichao 17.3.
	 */
	function checkReturnDetail(){
		$post = $_POST;
		if(!isset($post['orderid'])){
			$this->res = array(
				'retcode'	=>1000,
				'msg		'=>'参数错误'
			);
			return $this->res;
		}

		$orderprds = getRow("select o.image as productimg,
								op.product_id as productid,
								op.name as productname,
								op.price as originalprice,
								op.quantity as number,
								op.product_item_name,
								o.freight,
								o.invoicefee,
								o.merchant_id,
								o.total,
								o.order_status_id
								from `hb_order` as o inner join `hb_order_product` as op on op.order_id = o.order_id
								where o.order_id = '" .(int)$post['orderid']. "'");

		//新增一个客服功能需要的前端的链接
		$orderprds['service_url'] = self::SERVICE_URL.$orderprds['productid'];

		$return = getRow("SELECT * FROM `hb_return` WHERE order_id = " .$post['orderid']);

		//客服电话
		$merchant_service = getRow("select * from `hb_merchant_service` where merchant_id = " .(int)$orderprds['merchant_id']);
		//cgl 2017-6-5 修改退款详情

		$returncash = (float)$orderprds['total'];//-(float)$orderprds['freight']-(float)$orderprds['invoicefee'];
		$returninfo = array(
				'return_id'=>$return['return_id'],
				'returncash'=>sprintf("%.2f",$returncash),
				'applydata'=>$return['date_added'],
				'reason'=>$return['returnreason'],
				'status'=>$return['return_status_id'],
				'telephone'=>isset($merchant_service)?$merchant_service['telephone']:''
		);

		if($orderprds['order_status_id'] > 2 && $orderprds['order_status_id'] != 4){
			$returninfo['apllyagain'] = 0;//已经发货
		}else{
			$returninfo['apllyagain'] = 1;
		}

		$orderinfo = getRow("SELECT * FROM hb_order_history WHERE order_id = " .$post['orderid']." AND order_status_id = 4");//获取完成订单的历史记录
		if($orderinfo){
			date_default_timezone_set("Asia/Shanghai");
			$time = (int)strtotime($orderinfo['date_added']) + 7*24*3600;
		}else{
			$time = time()+3600;
		}
		if(time() < $time && $returninfo['status'] == 4){
			$returninfo['apllyagain'] = 1;
		}else{
			$returninfo['apllyagain'] = 0;
		}


		//卖家信息
		$seller = getRow("SELECT * FROM `hb_merchant` WHERE merchant_id = " .$orderprds['merchant_id']);
		//买家信息
		$customer = getRow("SELECT * FROM hb_customer WHERE customer_id = '" . (int)$return['customer_id'] . "'");
		//退款的历史信息
		$returnhistory = getData("SELECT * FROM `hb_return_history` WHERE return_id = " . $return['return_id'] . " ORDER BY date_added DESC");
		foreach($returnhistory as $key=>$val){
			if($val['return_status_id'] == 1){
				$returnprocess[$key]['processdate'] = $val['date_added'];
				$returnprocess[$key]['info'] = "买家（".$customer['firstname']."）创建了退款申请，".$val['comment'];
			}elseif($val['return_status_id'] == 3){
				$returnprocess[$key]['processdate'] = $val['date_added'];
				$returnprocess[$key]['info'] = "卖家（".$seller['merchant_name']."）已经同意了申请，交易款项".sprintf("%.2f", $returncash)."元已归还至".$return['firstname']."账户";//$orderprds['price']*$orderprds['quantity']
			}elseif($val['return_status_id'] == 4){
				$returnprocess[$key]['processdate'] = $val['date_added'];
				$returnprocess[$key]['info'] = "卖家（".$seller['merchant_name']."）已经拒绝了退款申请，".$val['comment'];
			}
		}
		unset($orderprds['merchant_id'],$orderprds['total'],$orderprds['order_status_id'],$returninfo['return_id']);
		$orderprds['originalprice'] = sprintf("%.2f",$orderprds['originalprice']);

		$this->res['data'] = array(
			'prdinfo'=>$orderprds,
			'returninfo'=>$returninfo,
			'returnprocess'=>$returnprocess
		);
		return $this->res;
	}

	function closeOrder(){
		$post = $_POST;

		if(!isset($post['orderid'])){
			$this->res = array(
					'retcode'	=>1000,
					'msg		'=>'参数错误'
			);
			return $this->res;
		}

		$order = getRow("select o.`order_id`,o.order_status_id,op.product_id,op.product_item_id from `hb_order` as o, `hb_order_product` as op,`hb_product` as p
							where op.order_id = o.order_id and p.product_id = op.product_id and o.order_id = '" .$post['orderid']. "' and o.customer_id = '" .$this->customer_id. "'");

		if(empty($order) || $order['order_status_id'] != 1){
			$this->res = array(
					'retcode'	=>1000,
					'msg		'=>'参数错误'
			);
			return $this->res;
		}
//
		exeSql("update `hb_order` set order_status_id = 6 where order_id = '" .$order['order_id']. "'");
		exeSql("insert into `hb_order_history` set order_id = " .$order['order_id']. ",order_status_id = 6,comment = '已关闭订单',date_added = now()");
		exeSql("update `hb_product` set quantity = quantity+1 where product_id = '" .$order['product_id']. "'");
		if(!empty($order['product_item_id']) && $order['product_item_id'] > 0){
			exeSql("update `hb_product_item` set quantity = quantity+1 where product_item_id = '" .$order['product_item_id']. "'");
		}

		return $this->res;
	}
}
?>