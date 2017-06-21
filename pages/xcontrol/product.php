<?php
use OSS\OssClient;
use OSS\Core\OssException;

require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';
//面向对象的control 类
include_once "xcontrol/base.php";
include "lib/pagination.php";
//生成二维码
require_once('.././system/thirdlib/QRCode/'.'phpqrcode.php');


class product extends base
{

//定义一个客服功能需要的商品链接
	const SERVICE_URL='https://haiqihuocang.com/product_detail/';


	function __construct() 
	{
       parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}

	function prodViewList()
	{
		return $this->getList();

	}

   	function getList()
   	{
   		$this->getMenu();

		$get = $_GET;
		if(isset($get['product_name'])){
			str_replace("%2B","+",$get['product_name']);
		}

		if(!isset($get['status'])){
			$get['status'] = 1;
		}

		if(!isset($get['sort_type'])){
			$get['sort_type'] = 0;
		}

		$this->res['get'] = $get;

   		$page=1;
   		if(isset($_GET['page']))
   			$page=$_GET['page'];

   		$start=($page-1)*20;

   		$wherestr="";

		if(isset($get['status']) &&  $get['status']== 2){
			$wherestr .= " and p.status = 1 and p.quantity < 1";
		}else if(isset($get['status']) &&  $get['status']== 3){
			$wherestr .= " and p.status = 0";
		}else{
			$wherestr .= " and p.status = 1 and p.quantity > 0";
		}

		if(isset($get['select_name']) && isset($get['select_bijiao']) && isset($get['num'])){
			switch($get['select_name']){
				case 1:
					$tiaojian = 'p.price';
					break;
				case 2:
					$tiaojian = 'p.quantity';
					break;
				case 3:
					$tiaojian = 'p.sales-p.shua_sales';
					break;
				case 4:
					$tiaojian = 'p.sort_order';
					break;
			}
			switch($get['select_bijiao']){
				case 1:
					$tiaojian .= '>';
					break;
				case 2:
					$tiaojian .= '<';
					break;
				case 3:
					$tiaojian .= '=';
					break;
			}

			$wherestr .= " and ".$tiaojian.$get['num'];
		}

		if(isset($get['category'])){
			$wherestr .= " and pc.category_id=".$get['category'];
		}
		$data = array();
		if(isset($get['product_name'])){
			$data['name'] = "%" .$get['product_name']. "%";
			$wherestr .= " and pd.name like :name";
//			$wherestr .= " and locate('".$get['product_name']."','pd.name')>0";
		}
		//cgl 2017-6-8 修改  根据商品id可以查询

		if(isset($get['model'])){
			$wherestr .= " and (p.model like '%".$get['model']."%' or p.product_id = '".$get["model"]."') ";
		}

		switch($get['sort_type']){
			case 1:
				$order_by = " p.quantity";
				break;
			case 2:
				$order_by = " p.quantity";
				break;
			case 3:
				$order_by = " sales asc";
				break;
			case 4:
				$order_by = " sales desc";
				break;
			case 5:
				$order_by = " p.points asc ";
				break;
			case 6:
				$order_by = " p.points desc";
				break;
			case 7:
				$order_by = " p.sort_order asc ";
				break;
			case 8:
				$order_by = " p.sort_order desc";
				break;
			default :
				$order_by = "p.product_id desc ";
				break;

		}
		//by xi 20170614
		if(isset($_GET['ob']))
		{
			$order_by=$_GET['ob'];
			if(isset($_GET['od']))
			{
				$order_by.=" ".$_GET['od'];
			}
			else
			{
				$order_by.=" DESC";
			}
		}

		if(isset($_GET['od'])&&$_GET['od']=="desc")
			$this->res['od']="asc";
		else
			$this->res['od']="desc";
//		if($get['sort_type'] == 1){
//			$order_by = " p.sort_order desc,p.date_added desc ";
//		}else if($get['sort_type'] == 2){
//			$order_by = " p.sort_order asc,p.date_added desc ";
//		}else{
//			$order_by = " p.date_added desc,p.sort_order desc ";
//		}


   		$sql="SELECT p.`product_id`,
	    p.`model`,
	    p.`quantity`,
	    p.`image`,
	    p.`manufacturer_id`,
	    p.`price`, 
	    p.`points`,
	    p.sales-p.shua_sales as sales,
	    truncate((p.sales-p.shua_sales)/p.`points`*100,2) as zhuanhua,
	    p.proxyprice,
	    p.marketprice,
	    p.sort_order,
	    p.`status`,
	   	p.brand_id,
	    p.`date_modified`,
	 	pc.category_id,
	    b.name as bname,
	    pd.name,
	    c.name as cname,
	    p.robot_url,
	    p.robot_type,
	    p.derate_money,
	    p.return_add_money,
	    truncate(p.return_add_money/p.proxyprice*100,2) as yongjinrate
		FROM `hb_product` as p,hb_product_description as pd,hb_product_to_category as pc,hb_category_description as c,hb_manufacturer as b
		where  p.product_id=pd.product_id and  p.product_id=pc.product_id and pc.category_id=c.category_id
		and pc.type = 1 and p.brand_id=b.manufacturer_id $wherestr order by $order_by limit $start,20";

		$dt = getData($sql,$data);


		foreach($dt as $key=>$val){
			$dt[$key]['price'] = sprintf("%.2f",$val['price']);
			$dt[$key]['proxyprice'] = sprintf("%.2f",$val['proxyprice']);
			$dt[$key]['marketprice'] = sprintf("%.2f",$val['marketprice']);

			$dt[$key]['edit_url'] = $this->getPath('product/editIndex')."&product_id=".$val['product_id'];
			//用于列表操作选项判断
			if($get['status'] == 1){
				$dt[$key]['click_status'] = 1;
			}
			$dt[$key]['review_url'] = $this->getPath("product/getReviewList")."&product_id=".$val['product_id'];
			$dt[$key]['product_item'] = array();
			$product_item = getData("select product_item_id,product_options from hb_product_item where product_id = '" .$val['product_id']. "' and status = 0");
			if(!empty($product_item)){
				foreach($product_item as $k=>$v){
					$product_item[$k]['edit_url'] = $this->getPath("product/editIndex")."&product_id=".$val['product_id']."&item=1";
				}
				$dt[$key]['product_item'] = $product_item;
			}
		}
		$this->res['dt']=$dt;
		$this->res['cat']=$this->getCat();

		//分页
//		if(getCache("product_page".$get['status'])){
//			$total_page = getCache("product_page".$get['status']);
//		}else{
			$total = getRow("SELECT COUNT(*) as total
								FROM `hb_product` as p,hb_product_description as pd,hb_product_to_category as pc,hb_category_description as c,hb_manufacturer as b
								where  p.product_id=pd.product_id and  p.product_id=pc.product_id and pc.category_id=c.category_id
								and pc.type = 1 and p.brand_id=b.manufacturer_id $wherestr",$data);
			$total = $total['total'];
			$total_page = ceil($total/20);
//			setCache("product_page".$get['status'],$total_page,3600);
//		}
		$this->res['is_end_page'] = 1;
		if($page == $total_page){
			$this->res['is_end_page'] = 0;
		}

		$this->getPage($page,$total_page);
//		if($total_page<20)
//			$this->getPages($page,$page);
//		else
//			$this->getPages($page);
		$this->res['total_page'] = $total_page;
		$this->res['url'] = $this->getPath("product/getList");
		$this->res['shelvea_url'] = $this->getPath("product/onOffShelves");
		$this->res['showprd_url'] = $this->getPath("product/showPrd");
		$this->res['addQuantityList_url'] = $this->getPath("product/addQuantityList");
		$this->res['addQuantity_url'] = $this->getPath("product/addQuantity");
		$this->res['addLikePrd_url'] = $this->getPath("product/addLikePrd");
		$this->res['delPrd_url'] = $this->getPath("product/delPrd");
		$this->res['changeSort_url'] = $this->getPath("product/changeSort");
		return $this->res;
   	}

	/*
	 * 上下架商品
	 * wangzhichao 17.3.3
	 */
	function onOffShelves(){
		$post = $_POST;
		if(!empty($post['type']) && $post['type'] == 1){
			foreach($post['order_check'] as $key=>$val){
				exeSql("update `" .DB_PREFIX. "product` set status = '1' where product_id='" .$val. "'");
			}
		}else if(!empty($post['type']) && $post['type'] == 2){
			foreach($post['order_check'] as $key=>$val){
				exeSql("update `" .DB_PREFIX. "product` set status = '0' where product_id='" .$val. "'");
			}
		}else{
			echo 1;exit;
		}
		echo 0;exit;
	}

	/*
	 * 猜你喜欢
	 * wangzhichao 17.3.9
	 */
	function addLikePrd(){
		$post = $_POST;

		foreach($post['order_check'] as $key=>$val){
			$old_list = getRow("select * from `" .DB_PREFIX. "product_recommend` where product_id = '" .$val. "' and merchant_id = '1'");
			if(!$old_list){
				exeSql("insert into `" .DB_PREFIX. "product_recommend` set product_id = '" .$val. "',date_added = now(),merchant_id = '1'");
			}
		}
		echo 0;exit;
	}

	/*
	 * 删除商品
	 * wangzhichao 17.3.9
	 */
	function delPrd(){
		$post = $_POST;

		foreach($post['order_check'] as $key=>$val){
			exeSql("update hb_product set status = 2 where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product` where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product_description` where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product_image` where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product_item` where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product_recommend` where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product_to_activity` where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product_to_category` where product_id = '" .$val. "'");
//			exeSql("delete from `" .DB_PREFIX. "product_to_store` where product_id = '" .$val. "'");
		}
		echo 0;exit;
	}

	/*
	 * 修改商品排序
	 * wangzhichao 17.3.9
	 */
	function changeSort(){
		$post = $_POST;

		exeSql("update `" .DB_PREFIX. "product` set sort_order = '" .$post['sort_order']. "' where product_id='" .$post['product_id']. "'");
		echo 0;exit;
	}


   	/*
   	*新增商品的页面
   	*wangzhichao 2017.2.20
   	 */
   	function addindex(){
		$this->getMenu();

		//查询登录者的身份
		$id=$_SESSION['userid'];
		$user=getRow("select * from ".DB_PREFIX."user where user_id='$id'");
		$merchant_id=$user['merchant_id'];

		//商品分类
		$this->res['category'] = $this->getCat();

		 //商品品牌
		$this->res['manufacturer'] = $this->getManufacturer($merchant_id);

		 //仓库
		$this->res['warehouse'] = $this->getWarehouse($merchant_id);

		//新增商品提交路径
		$this->res['url'] = $this->getPath("product/add");

		// print_r($option);
		return $this->res;die;
   	}

   	/*
   	*新增商品
   	*wangzhichao 2017.2.20
   	 */
   	function add(){
		//用户信息和所属商户id
		$user=getRow("select * from ".DB_PREFIX."user where user_id='" .$this->userid. "'");
		$merchant_id=$user['merchant_id'];

		$post = $_POST;

		//通过category的名字查找出category的id
		$cateid=getRow("select category_id from hb_category_description where name='".$_POST['category']."'");
		$post['category']=$cateid["category_id"];
		
		//商品主图
		$image = $_FILES['image'];
		$up_image=$this->upload_img($image);

		//商品轮播图
		$product_image = $_FILES['fileselect'];
		$up_product_image=$this->upload_img($product_image);

		//规格图片
		$option_image = array();
		if(isset($_FILES['optionselect'])){
			$option_image = $_FILES['optionselect'];
			$up_option_image=$this->upload_img($option_image);
		}


		$option = array();
		//商品库存
		$quantity=0;
		if($post['is_option'] == 0){
			//无产品规格时添加的库存
			$quantity=$post['kucun'];
		}else{
			if(isset($post['option_kucun']) && isset($post['option']) &&isset($post['option_price']) &&isset($post['option_proxy_price']) && !empty($up_option_image)){
				foreach($post['option_kucun'] as $k=>$v){
					$quantity+=$v;
					$option[$k]['quantity'] = (int)$v;
				}
				foreach($post['option'] as $key=>$val){
					$option[$key]['option'] = $val;
				}

				foreach($post['option_price'] as $key=>$val){
					$option[$key]['price'] = (float)$val;
				}

				foreach($post['option_proxy_price'] as $key=>$val){
					$option[$key]['proxy_price'] = (float)$val;
				}

				foreach($up_option_image as $key=>$val){
					$option[$key]['image'] = $val;
				}
			}else{
				$quantity = 0;
			}

		}

		$is_sale = isset($post['is_sale'])?$post['is_sale']:0;
		//修改product表
		$product_sql = "INSERT INTO " . DB_PREFIX . "product SET model = '" . $post['model'] . "', quantity = '" . $quantity . "', stock_status_id = '7', image = '" . $up_image . "', manufacturer_id = '" .$post['warehouse']. "', price = '" . $post['product_price'] . "', points = '0', date_available = '" . date("Y-m-d",time()) . "', subtract = '0', sort_order = '" .(int)$post['product_sort_order']. "', status = '" . $is_sale . "',date_added=now(),date_modified=now(), sales = '0', hasinvoice = '0', marketprice = '" . (float)$post['product_market_price'] . "', freight = '0', invoicetax = '6.0', freecondition = '1', freetype = '0', merchant_id = '" . (int)$merchant_id . "', proxyprice = '" . (float)$post['product_proxy_price'] . "', derate_money = '" .(float)$post['product_derate_money']. "', is_show_for_merchant = '0', brand_id = '" . (int)$post['manufacturer'] . "', userid = '" . (int)$this->userid . "', return_add_money = '" .(float)$post['product_return_money']. "'";
//		print_r($product_sql);exit;
		exeSql($product_sql);
		$product_id = getLastId();
		//修改product_description表
//		$product_description_sql = "INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '2', name = '" . $post['product_name'] . "', description = '" . $post['description'] . "', meta_title = '', meta_description = '" . $post['description'] . "', meta_keyword = '" . $post['keyword'] . "',basic_description='" .$post['product_description']. "'";
//		exeSql($product_description_sql);
		$data = array(
				'name'=>$post['product_name'],
				'description'=>$post['description'],
				'meta_description'=>$post['description'],
				'meta_keyword'=>$post['keyword'],
				'basic_description'=>$post['product_description'],
				'product_id'=>$product_id
		);
		$product_description_sql = "INSERT INTO " . DB_PREFIX . "product_description SET product_id = :product_id, language_id = '2', name = :name, description = :description, meta_title = '', meta_description = :meta_description, meta_keyword = :meta_keyword,basic_description=:basic_description";
		exeSql($product_description_sql,$data);

		//修改product_image表
		$sort_order = 0;
		foreach($up_product_image as $val){
			$product_image_sql = "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $val . "', sort_order = '" .(int)$sort_order. "'";
			exeSql($product_image_sql);
			$sort_order++;
		}

		//修改product_to_category表
		$product_category_sql = "INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$post['category'] . "',`type`='1'";
		exeSql($product_category_sql);

		//修改product_item表，各规格的属性
		foreach($option as $val){
			if(!empty($val['option'])){
				$product_item_sql = "INSERT INTO " . DB_PREFIX . "product_item SET product_options = '" .$val['option']. "',product_id = '" . (int)$product_id . "', quantity = '" .(int)$val['quantity']. "',price = '" .(float)$val['price']. "',proxyprice = '" .(float)$val['proxy_price']. "',image = '" .$val['image']. "'";
				exeSql($product_item_sql);
			}
		}

		//添加prproduct_to_store表数据
		$prodcut_store_sql = "INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '1'";
		exeSql($prodcut_store_sql);

		//跳转到列表页
		$url=linkurl("product/getList");
		redirect($url);
	}

	/*
	 * 编辑商品的页面
	 */
	function editIndex(){
		//用于编辑页面返回列表页
		$_SESSION['url']=$_SERVER["HTTP_REFERER"];

		$url_array = explode("&",$_SESSION['url']);
		$status = "&status=1";
		foreach($url_array as $key=>$val){
			if(strpos($val,'status')===0){
				$status = '&'.$val;
			}
		}

		//查询登录者的身份
		$id=$_SESSION['userid'];
		$user=getRow("select * from ".DB_PREFIX."user where user_id='$id'");
		$merchant_id=$user['merchant_id'];

		$product_id = $_GET['product_id'];

		//一键复制
		if(isset($_GET['copy']) && $_GET['copy'] == 1 && $product_id){
			$_SESSION['url']=$this->getPath("product/getList").$status;
			//复制product表数据
			$copy_product = getRow("select * from hb_product where product_id = '" .$product_id. "'");
			$copy_product['date_added'] = date("Y-m-d H:i:s",time());
			$copy_product['date_modified'] = date("Y-m-d H:i:s",time());
			unset($copy_product['product_id']);
			saveData('hb_product',$copy_product);
			$copy_product_id = getLastId();

			//复制hb_product_description表数据
			$copy_product_description = getRow("select * from hb_product_description where product_id = '" .$product_id. "'");
			$copy_product_description['product_id'] = $copy_product_id;
			$product_description_sql = "INSERT INTO " . DB_PREFIX . "product_description SET product_id = :product_id, language_id = '2', name = :name, description = :description, meta_title = '', meta_description = :meta_description, meta_keyword = :meta_keyword,basic_description=:basic_description";
			exeSql($product_description_sql,$copy_product_description);

			//复制hb_product_image表数据
			$copy_product_image = getData("select * from hb_product_image where product_id = '" .$product_id. "'");
			if(!empty($copy_product_image)){
				foreach($copy_product_image as $key=>$val){
					$copy_product_image[$key]['product_id'] = $copy_product_id;
					unset($copy_product_image[$key]['product_image_id']);
				}
				saveDataMuti('hb_product_image',$copy_product_image);
			}

			//复制hb_product_item表数据
			$copy_product_item = getData("select * from hb_product_item where product_id = '" .$product_id. "'");
			if(!empty($copy_product_item)){
				foreach($copy_product_item as $key=>$val){
					$copy_product_item[$key]['product_id'] = $copy_product_id;
					unset($copy_product_item[$key]['product_item_id']);
				}
				saveDataMuti('hb_product_item',$copy_product_item);
			}

			//复制hb_product_to_category表数据
			$copy_product_category = getRow("select * from hb_product_to_category where product_id = '" .$product_id. "' and type = 1");
			$copy_product_category['product_id'] = $copy_product_id;
			saveData('hb_product_to_category',$copy_product_category);

			//复制hb_product_to_store表数据
			exeSql("INSERT INTO hb_product_to_store SET product_id = '" . (int)$copy_product_id . "', store_id = '1'");

			$product_id = $copy_product_id;
		}

		if($product_id){
			$product_sql = "select p.product_id,p.sort_order,p.status,p.model,p.quantity,p.image,p.manufacturer_id as warehouse,p.price,p.marketprice,p.proxyprice,p.derate_money,p.is_show_for_merchant,p.brand_id,p.return_add_money,pd.name,pd.description,pd.meta_keyword,pd.basic_description,pc.category_id from `" .DB_PREFIX. "product` p left join `" .DB_PREFIX. "product_description` pd on pd.product_id = p.product_id left join `" .DB_PREFIX. "product_to_category` pc on pc.product_id = p.product_id where p.product_id = '" .$product_id. "' and pc.type = 1";
			$product = getRow($product_sql);

			$product['price'] = sprintf("%.2f",@$product['price']);
			$product['marketprice'] = sprintf("%.2f",@$product['marketprice']);
			$product['proxyprice'] = sprintf("%.2f",@$product['proxyprice']);
			$product['derate_money'] = sprintf("%.2f",@$product['derate_money']);

			$product['description'] = htmlspecialchars_decode(@$product['description']);
			$product['description'] = str_replace('<p><br/></p>','',$product['description']);

			$product['name'] = str_replace('"',"&quot;",@$product['name']);//带双引号的商品名进行转义

			$this->res = $product;

			$this->getMenu();


			//获取商品轮播图
			$this->res['product_image'] = getData("select * from `" .DB_PREFIX. "product_image` where product_id = '" .(int)$product_id. "'");
			foreach($this->res['product_image'] as $key=>$val){
				$this->res['product_image'][$key]['key'] = $key;
			}

			//获取规格方面的信息
			$this->res['option'] = getData("select product_item_id,product_options,quantity,price,proxyprice,image from `" .DB_PREFIX. "product_item` where product_id = '" .(int)$product_id. "' and status = 0");
			if(!empty($this->res['option'])){
				$this->res['has_option'] = 1;//是否有规格，1有，0没有
				$index = 0;
				foreach($this->res['option'] as $key=>$val){
					$this->res['option'][$key]['price'] = sprintf("%.2f",$val['price']);
					$this->res['option'][$key]['proxyprice'] = sprintf("%.2f",$val['proxyprice']);
					$this->res['option'][$key]['station'] = $key+1;
					$index++;
				}
				$this->res['option_add'] = $index;//用于规格添加按钮的图片上传
			}else{
				$this->res['option_add'] = 0;
				$this->res['has_option'] = 0;
			}
			//商品分类
			$this->res['category'] = $this->getCat();
			foreach($this->res['category'] as $key_one=>$val_one){
				if(isset($val_one['son'])){
					foreach($val_one['son'] as $key_two=>$val_two){
						if(isset($val_two['son'])){
							foreach($val_two['son'] as $key_three=>$val_three){
								if($val_three['category_id'] == $product['category_id']){
									$this->res['category'][$key_one]['son'][$key_two]['son'][$key_three]['selected'] = 1;
								}
							}
						}
					}
				}
			}

			//商品品牌
			$this->res['manufacturer'] = $this->getManufacturer($merchant_id);
			foreach($this->res['manufacturer'] as $key=>$val){
				if($val['manufacturer_id'] == $product['brand_id']){
					$this->res['manufacturer'][$key]['selected'] = 1;
				}
			}

			//仓库
			$this->res['warehouse'] = $this->getWarehouse($merchant_id);
			foreach($this->res['warehouse'] as $key=>$val){
				if($val['warehouse_id'] == $product['warehouse']){
					$this->res['warehouse'][$key]['selected'] = 1;
				}
			}

			//新增商品提交路径
			$this->res['url'] = $this->getPath("product/edit");
			$this->res['del_img_url'] = $this->getPath("product/delPrdImage");
			//用于一键复制
			$this->res['copy'] = 0;
			if(isset($_GET['copy']) && $_GET['copy'] == 1){
				$this->res['copy'] = 1;
				$this->res['del_copy'] = $this->getPath("product/delPrd");
			}

			//用于规格修改
			$this->res['item'] = 0;
			if(isset($_GET['item'])){
				$this->res['item'] = 1;
			}

			return $this->res;
		}
	}

	/*
	 * 编辑商品
	 * wangzhichao 17.2.27
	 */
	function edit(){
		//用户信息和所属商户id
		$user=getRow("select * from ".DB_PREFIX."user where user_id='" .$this->userid. "'");

		$post = $_POST;

		//通过category的名字查找出category的id
		$cateid=getRow("select category_id from hb_category_description where name='".$_POST['category']."'");
		$post['category']=$cateid["category_id"];

		//商品主图
		$image = $_FILES['image'];
		if($image['error'] == 0){
			$up_image=$this->upload_img($image);
		}
;
		//商品轮播图
		$product_image = $_FILES['fileselect'];
		$up_product_image=$this->upload_img($product_image);

		//规格图片
		$option_image = array();
		$option_image_real = array();
		if(isset($_FILES['optionselect'])){
			$option_image = $_FILES['optionselect'];
			$up_option_image=$this->upload_img($option_image);

			$option_image_real = array();
			$index = 0;
			foreach($option_image['error'] as $key=>$val){
				if($val == 0){
					$option_image_real[] = $up_option_image[$index];
					$index++;
				}else{
					$option_image_real[] = '';
				}
			}

		}

		$option = array();
		//商品库存
		$quantity=0;
		if($post['is_option'] == 0){
			//无产品规格时添加的库存
			$quantity=$post['kucun'];
		}else{
			if(isset($post['item_id']) && isset($post['option_kucun']) && isset($post['option']) &&isset($post['option_price']) &&isset($post['option_proxy_price']) && !empty($option_image_real)){
				foreach($post['item_id'] as $key=>$val){
					$option[$key]['product_item_id'] = $val;
				}
				foreach($post['option_kucun'] as $k=>$v){
					$quantity+=$v;
					$option[$k]['quantity'] = (int)$v;
				}
				foreach($post['option'] as $key=>$val){
					$option[$key]['option'] = $val;
				}

				foreach($post['option_price'] as $key=>$val){
					$option[$key]['price'] = (float)$val;
				}

				foreach($post['option_proxy_price'] as $key=>$val){
					$option[$key]['proxy_price'] = (float)$val;
				}

				foreach($option_image_real as $key=>$val){
					$option[$key]['image'] = $val;
				}
			}else{
				$quantity = 0;
			}

		}

		$is_show = isset($post['is_show'])?$post['is_show']:0;

		//修改product表
		$product_sql = "UPDATE " . DB_PREFIX . "product SET model = '" . $post['model'] . "', quantity = '" . $quantity . "', stock_status_id = '7'";
		if(!empty($up_image)){
			$product_sql .= ", image = '" . $up_image . "'";
		}
		$product_sql .= ", manufacturer_id = '" . $post['warehouse'] . "', brand_id = '" . $post['manufacturer'] . "', price = '" . $post['product_price'] . "', sort_order = '" . (int)$post['product_sort_order'] . "', status = '" . (int)$post['is_sale'] . "',date_modified=now(), marketprice = '" . (float)$post['product_market_price'] . "', proxyprice = '" . (float)$post['product_proxy_price'] . "', derate_money = '" . (float)$post['product_derate_money'] . "', is_show_for_merchant = '" . (int)$is_show . "', brand_id = '" . (int)$post['manufacturer'] . "', userid = '" . (int)$this->userid . "', return_add_money = '" . (float)$post['product_return_money'] . "' where product_id = '" . (int)$post['prd'] . "'";
		exeSql($product_sql);

		//修改product_description表
		$data = array(
				'name'=>$post['product_name'],
				'description'=>$post['description'],
				'meta_description'=>$post['description'],
				'meta_keyword'=>$post['keyword'],
				'basic_description'=>$post['product_description'],
				'product_id'=>$post['prd']
		);
//		$product_description_sql = "UPDATE " . DB_PREFIX . "product_description SET name = '" . $post['product_name'] . "', description = '" . $post['description'] . "', meta_description = '" . $post['description'] . "', meta_keyword = '" . $post['keyword'] . "',basic_description='" .$post['product_description']. "' where product_id = '" . (int)$post['prd'] . "'";
		$product_description_sql = "UPDATE " . DB_PREFIX . "product_description SET name = :name, description = :description, meta_description = :meta_description, meta_keyword = :meta_keyword,basic_description=:basic_description where product_id = :product_id";
		exeSql($product_description_sql,$data);

		//修改product_image表
		if(!empty($up_product_image)){
			exeSql("delete from `" .DB_PREFIX. "product_image` where product_id = '" .(int)$post['prd']. "'");
			$sort_order = 0;
			foreach($up_product_image as $val){
				$product_image_sql = "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$post['prd'] . "', image = '" . $val . "', sort_order = '" .(int)$sort_order. "'";
				exeSql($product_image_sql);
				$sort_order++;
			}
		}


		//修改product_to_category表
		$product_category_sql = "UPDATE " . DB_PREFIX . "product_to_category SET category_id = '" . (int)$post['category'] . "' where product_id = '" . (int)$post['prd'] . "' and `type`='1'";
		exeSql($product_category_sql);

		//删除已经删除的规格
		$old_option = getData("select product_item_id from `" .DB_PREFIX. "product_item` where product_id = '" .(int)$post['prd']. "' and status = '0'");
		foreach($old_option as $key=>$val){
			foreach($option as $k=>$v){
				if(!empty($v['product_item_id']) && $v['product_item_id']==$val['product_item_id']){
					unset($old_option[$key]);
				}
			}
		}
		foreach($old_option as $key=>$val){
			$product_item_sql = "UPDATE " . DB_PREFIX . "product_item SET status = '1' where product_item_id = '" .$val['product_item_id']. "' limit 1";
			exeSql($product_item_sql);
		}
		//修改product_item表，各规格的属性
		foreach($option as $val){
			if(!empty($val['option'])){
				if($val['product_item_id'] != ''){
					$product_item_sql = "UPDATE " . DB_PREFIX . "product_item SET product_options = '" .addslashes($val['option']). "',product_id = '" . (int)$post['prd'] . "', quantity = '" .(int)$val['quantity']. "',price = '" .(float)$val['price']. "',proxyprice = '" .(float)$val['proxy_price']. "'";
					if(!empty($val['image'])){
						$product_item_sql .= ",image = '" .$val['image']. "'";
					}
					$product_item_sql  .= " where product_item_id = '" .(int)$val['product_item_id']. "' limit 1";
					exeSql($product_item_sql);
				}else{
					$product_item_sql = "INSERT INTO " . DB_PREFIX . "product_item SET product_options = '" .addslashes($val['option']). "',product_id = '" . (int)$post['prd'] . "', quantity = '" .(int)$val['quantity']. "',price = '" .(float)$val['price']. "',proxyprice = '" .(float)$val['proxy_price']. "',image = '" .$val['image']. "', `status`=0"; // lcb 5-24  `status`=0 不加这个 默认为 null
					exeSql($product_item_sql);
				}
			}
		}
        //var_dump($option);exit();
		//跳转到列表页
		if($_SESSION['url']){
			//redirect($_SESSION['url']);
            // lcb 6-2
            if(false === stripos($_SESSION['url'], '#item-') && $post['prd']){
                $_SESSION['url'] .= '#item-'.$post['prd'];
            }
            redirect($_SESSION['url']);
		}
		$url=linkurl("product/getList");
		redirect($url);
	}


	/*
	 * 删除商品的轮播图
	 * wangzhichao 17.2.28
	 */
	function delPrdImage(){
		$image = $_POST;
		$sql = "delete from `" .DB_PREFIX. "product_image` where product_image_id = '" .(int)$image['product_image_id']. "' and image = '" .$image['name']. "'";
		exeSql($sql);
		echo 0;exit;
	}

   	/*
   	*获取商品品牌
   	*wangzhichao 2017.2.20
   	 */
   	function getManufacturer($merchant_id){
   		// 修改：新增和编辑商品时，品牌让商户管理人员自行选择平台预先设定的品牌
   		// $manufacturer = getData("select manufacturer_id,name from `" .DB_PREFIX. "manufacturer` where merchant_id = '" .(int)$merchant_id. "' and status = '0' order by sort_order asc");
   		$manufacturer = getData("select manufacturer_id,name from `" .DB_PREFIX. "manufacturer` where status = '0' order by sort_order asc");
   		
   		return $manufacturer;
   	}

   	/*
   	*获取仓库列表
   	*wangchichao 2017.2.20
   	 */
   	function getWarehouse($merchant_id){
   		$warehouse = getData("select warehouse_id,name from `" .DB_PREFIX. "warehouse` where merchant_id = '" .(int)$merchant_id. "' order by date_added desc");
   		
   		return $warehouse;
   	}

   	/*
   	*获取商品规格
   	*wangzhichao 2017.2.20
   	 */
   	function getOption($merchant_id){
   		$option = getData("select o.option_id,od.name from `" .DB_PREFIX. "option` o left join `" .DB_PREFIX. "option_description` od on od.option_id = o.option_id order by sort_order asc");

   		$option_value = getData("select ov.option_value_id,ov.option_id,ovd.name from `" .DB_PREFIX. "option_value` ov left join `" .DB_PREFIX. "option_value_description` ovd on ovd.option_value_id = ov.option_value_id order by ov.sort_order asc");

   		foreach($option_value as $key=>$value){
   			foreach ($option as $k => $v) {
   				if($v['option_id'] == $value['option_id']){
   					$option[$k]['son'][] = $value;
   				}
   			}
   		}

   		return $option;
   	}

	function disable()
	{
		if(isset($_GET['id']))
		{
			if($_GET["status"]=='1')
				$status='0';
			else
				$status='1';

			saveData("hb_customer" , array('customer_id' =>$_GET['id'] ,"status"=>$status ));
		}
	}

	function detail()
	{
		$this->getMenu();
		//echo json_encode($this->res);
		if(isset($_GET["id"]))
		{
				$sql="SELECT p.`customer_id`,
			    g.name as customer_group_name, 
			    c.customer_group_id,
			    p.`firstname`,
			    p.`lastname`,
			    p.`email`,
			    p.`telephone`,
			    p.`cart`, 
			    p.`ip`,
			    p.`status`,
			    p.`approved`, 
			    p.`date_added`,
			    p.`headurl`,
			    p.`card`, 
			    p.`isdisturb`,
			    p.`sharetimes`,
			    p.`remark`,
			    p.firstname as parent_name,
			    c.parent_id 
				FROM (`hb_customer` as c,hb_customer_group_description as g) left join `hb_customer` as p on c.parent_id=p.customer_id where c.customer_group_id=g.customer_group_id and c.customer_id=:id";
			$this->res+=getRow($sql,$_GET);
		}
		

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

	

	function getIds($cat,$id,$istrue=false)
	{
		$str="0";

		if($istrue)
		{
			$str.=",".$id;
			if(isset($cat['son']))
			{
				foreach ($cat['son'] as $key => $value) {
					# code...
					$str.=getIds($value,$value['category_id'],true);

				}
			}
			return $str;
		}

		foreach ($cat as $key => $value) {
				if($key==$id)
				{
					$str.=",".$key;
					if(isset($value['son']))
					{
						$str.=getIds($value,$value['category_id'],true);
					}
				}
				if(isset($value['son']))
					$str+=getIds($value['son'],$id);
			}
			# code...
		return $str;
	}

	//封装图片上传    文件名
	function upload_img($file){
		//图片上传
		if(!empty($file)){
			//只有一张图片
			if(!is_array($file["name"])){
				$name = $file['name'];
				if(!empty($name)){
					$filename=explode(".",$name);
					$filename[0]=rand(1,100000000); //设置随机数长度
					$name=implode(".",$filename);
					//$name1=$name.".Mcncc";
					$uploadfile=date("Ymd").time().$name;

					try{
						$accessKeyId = OSS_ACCESS_KEY_ID;
						$accessKeySecret = OSS_ACCESS_KEY_SECRET;
						$endpoint = OSS_ENDPOINT;  //注意域名前不能加bucket的名字
						$bucket = OSS_BUCKET;
						$filename=explode(".",$file['name']);
						$filename[0]=rand(1,100000000); //设置随机数长度
						$name=implode(".",$filename);

						$uploadfile=date("Ymd").time().$name;
						$object = "product_img/".$uploadfile;
						$file_local_path = $file["tmp_name"];
						$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
						$ossClient->multiuploadFile($bucket,$object,$file_local_path);  //上传至阿里云OSS

						$img_url = OSS_IMG_ENDPOINT."/".$object;
						return $img_url;


					}catch(OssException $e){
						// print $e->getMessage();
						return '';
					}
				}else{
					return '';
				}
			}else{
				$name1="";
				$name = $file['name'];
				if(!empty($name)){
					foreach($name as $k=>$v){
						$name1=$v;
						$filename=explode(".",$name1);
						$filename[0]=rand(1,100000000); //设置随机数长度
						$name1=implode(".",$filename);
						//$name1=$name.".Mcncc";
						$uploadfile[$k]=date("Ymd").time().$name1;

					}
				}else{
					return '';
				}

				if(!empty($file["tmp_name"])){
					$arr=array();
					foreach($file["tmp_name"] as $k=>$v){
						//     		if(move_uploaded_file($v,$upload_path.$uploadfile[$k])){
						//   $arr[]='catalog/gd/product/'.$uploadfile[$k];
						// }else{
						//    // echo "<script>alert('上传图片失败');</script>";
						//    return '';
						// }
						try{
							$accessKeyId = OSS_ACCESS_KEY_ID;
							$accessKeySecret = OSS_ACCESS_KEY_SECRET;
							$endpoint = OSS_ENDPOINT;  //注意域名前不能加bucket的名字
							$bucket = OSS_BUCKET;
							$object = "product_img/".$uploadfile[$k];
							$file_local_path = $file["tmp_name"][$k];
							$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
							$ossClient->multiuploadFile($bucket,$object,$file_local_path); //上传至阿里云OSS
							// $content = $ossClient->PutObject($bucket,$object,$file);    //也行
							$arr[] = OSS_IMG_ENDPOINT."/".$object;

						}catch(OssException $e){
							// print $e->getMessage();
							//return '';
						}

					}
					return $arr;
				}else{
					return '';
				}
			}
		}else{
			return '';
		}
	}

	/*
	 * 商品评价列表
	 * wangzhichao 17.3.6
	 */
	function getReviewList()
	{
		$this->getMenu();

		$get = $_GET;
		//用于编辑页面返回列表页
		$url = $_SERVER["HTTP_REFERER"];
		if(strpos($url, "m=product&act=getList")){
			$_SESSION['url']=$url;
		}

		if (!isset($get['status'])) {
			$get['status'] = 0;
		}

		$this->res['get'] = $get;


		$page = 1;
		if (isset($_GET['page']))
			$page = $_GET['page'];

		$start = ($page - 1) * 20;
		$wherestr = "";
		if ($get['status'] == 1) {
			$wherestr .= " and r.rating=5";
		} else if ($get['status'] == 2) {
			$wherestr .= " and r.rating > 1 and r.rating < 5";
		} else if ($get['status'] == 3) {
			$wherestr .= " and r.rating < 2";
		}

		$first_review = getData("SELECT r.`order_id`,r.`isadditional`, r.`review_id`,r.status, r.`customer_id`, r.`rating`, r.`date_added`, r.`text`, r.`isanon`,c.`firstname`,c.telephone FROM `hb_review` AS r,`hb_customer` as c WHERE r.customer_id = c.customer_id and r.`product_id` = '" . (int)$get['product_id'] . "' AND r.`isadditional` = '0'" . $wherestr . " ORDER BY r.`date_added` DESC LIMIT " . $start . ",20");

		$review = array();
		if ($first_review) {
			$index = 0;
			foreach ($first_review as $key => $val) {
				$review[$index] = $val;
				$review[$index]['image'] = getData("SELECT image FROM hb_review_image WHERE review_id = '" . (int)$val['review_id'] . "'");
				$index++;
				if (!empty($val['order_id'])) {
					$review[$index] = getRow("SELECT r.`order_id`,r.`isadditional`, r.`review_id`,r.status, r.`customer_id`, r.`rating`, r.`date_added`, r.`text`, r.`isanon`,c.`firstname`,c.telephone FROM `hb_review` AS r,`hb_customer` as c WHERE r.customer_id = c.customer_id and r.`product_id` = '" . (int)$get['product_id'] . "' AND r.`isadditional` = '1' and r.order_id = '" . $val['order_id'] . "'");
					if ($review[$index]) {
						$review[$index]['image'] = getData("SELECT image FROM hb_review_image WHERE review_id = '" . (int)$val['review_id'] . "'");
						$index++;
					}
				}
			}
		}

		if (sizeof($first_review) < 20)
			$this->getPages($page, $page);
		else {
			$this->getPages($page);
		}
		$this->res['review'] = $review;
		$this->res['url'] = $this->getPath("product/getReviewList") . "&product_id=" . $get['product_id'];
		$this->res['delReview_url'] = $this->getPath("product/delReview");
		if($_SESSION['url']){
			$this->res['getList_url'] = $_SESSION['url'];
		}else{
			$this->res['getList_url'] = $this->getPath("product/getList");
		}

		return $this->res;
	}


	/*
	 * 通过或屏蔽
	 */
	function delReview(){
		$post = $_POST;
		exeSql("update `" .DB_PREFIX. "review` set `status`='" .$post['type']. "' where review_id = '" .$post['id']. "'");
		echo 0;exit;
	}

	/*
	 * 推广商品
	 * wangzhichao 17.3.6
	 */
	function showPrd(){
		$product_id = $_POST['product_id'];
		$value = self::SERVICE_URL.$product_id; //二维码内容
		print_r(json_encode(array('url'=>$value)));exit;
	}

	/*
	 * 补充库存--获取商品库存或各规格库存
	 * wangzhichao 17.3.8
	 */
	function addQuantityList(){
		$product_id = $_POST['product_id'];

		$sql = "select product_item_id,product_options as `option`,quantity from `" .DB_PREFIX. "product_item` where product_id = '" .$product_id. "' and status = '0'";
		$option = getData($sql);

		if(!$option){
			$sql = "select quantity from `" .DB_PREFIX. "product` where product_id = '" .$product_id. "'";
			$option = getData($sql);
			$option[0]['option'] = '默认属性';
			$option[0]['product_item_id'] = '0';
		}
		print_r(json_encode($option));exit;
	}

	/*
	 * 补充库存--修改商品库存
	 * wangzhichao 17.3.8
	 */
	function addQuantity(){
		$post = $_POST;
		$item = array();
		$all_quantity = 0;
		foreach($post['product_item_id'] as $key=>$val){
			foreach($post['quantity'] as $k=>$v){
				if($key==$k){
					$item[]=array(
						'product_item_id'=>$val,
						'quantity'=>$v
					);
					$all_quantity += $v;
				}
			}
		}
		//type=1上架，type=2下架
		if($post['type'] == 1){
			$sql = "update `" .DB_PREFIX. "product` set quantity=quantity+".$all_quantity.",status=1 where product_id = '" .$post['product_id']. "'";
		}else{
			$sql = "update `" .DB_PREFIX. "product` set quantity=quantity+".$all_quantity.",status=0 where product_id = '" .$post['product_id']. "'";
		}
		exeSql($sql);//修改商品表库存

		foreach($item as $key=>$val){
			if($val['product_item_id'] > 0){
				$item_sql = "update `" .DB_PREFIX. "product_item` set quantity=quantity+".$val['quantity']." where product_item_id = '" .$val['product_item_id']. "'";
				exeSql($item_sql);//修改商品规格表库存
			}
		}

		echo 0;exit;
	}

}

