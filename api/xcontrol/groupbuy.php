<?php
class groupbuy{
	var $res=array("retcode"=>0,'msg'=>'success');
	function __construct() 
	{
       // parent::__construct();
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
	 * cgl  修改  2017-2-28  zxx  新增  团购列表
	 */
	function groupBuyList(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			if(isset($_SESSION["default"]["customer_id"])){
				$merchant=getRow("SELECT merchant_id from hb_customer where customer_id = '".$_SESSION["default"]["customer_id"]."' ");
				if(!empty($merchant["merchant_id"])){
					$merchant_id=$merchant["merchant_id"];	
				}else{
					$merchant_id=1;
				}
			}else{
				$merchant_id=1;
			}
			//获取到所有的团购商品
			$banner_id=15;
			if(isset($_POST["page"]) && @$_POST["page"]>0){
				$page=$_POST["page"];
			}else{
				$page=1;
			}
			$start=($page-1)*10;
			$sql="SELECT groupimage,group_id,g.product_id,groupname,groupnum,groupprice
				,p.price as price from hb_groupby as g,hb_product as p where p.product_id=g.product_id  and g.merchant_id='".$merchant_id."' and g.group_status=1 and UNIX_TIMESTAMP(start_time)<= '".time()."'  order by g.date_added desc limit ".$start.",10 ";
			$data=getData($sql);
			foreach ($data as $key => $value) {
				$data[$key]['groupprice']=sprintf("%.2f",$value['groupprice']);
				//获取到原商品的价格
				$data[$key]['price']=sprintf("%.2f",$value['price']);
			}
			include_once "xcontrol/home.php";
			$home=new home();
			$arr=array();
			$arr1=$home->getBanner($banner_id,$merchant_id);
			if(!empty($arr1)){
				$arr=$arr1;
			}
			$category=$arr;
			$this->res["retcode"]=0;
			$this->res["groupbuy"]=$data;
			$this->res["groupimage"]=$category;
			$this->res["content"]='<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"></head><body>1.参团人数必须在规定时间内达到规定人数才能成功开团，在规定时间内未达到开团人数则开团失败，支付金额会在1~3个工作日内返回到你的支付账户<br/>2.开团成功后，订单将会立即提交，我们会及时给你发货<br/>3.成功参团后不可退出参团<br/>4.只有会员才可主动发起开团，非会员只能参团<br/>5.拼团商品单件起售，一人只能购买一件</body></html>';
		}else{
			$this->res["retcode"]=1180;
		}
		return $this->res;
		die;
	}

	/*
	*获取到团购活动
	*/
	function groupBuyActivity($banner_id,$merchant_id){
	 //获取到商品banner
      $category=getData("SELECT `image` as imgurl,title,`type`,`subtype`,`item_id` as itemid,`link` FROM `hb_banner_image` WHERE  status=1 and banner_id=".$banner_id." and `merchant_id` = '". (int)$merchant_id ."' ORDER BY banner_id,`sort_order` DESC");   
      if(!empty($category)){
        foreach ($category as $key => $value) {      
          $category[$key]["imgurl"]= $this->get_img_thumb_url($value["imgurl"]);
          if($value["type"]==1){
            //外部链接  type=0分类，1商品，2活动，3.外部链接
            // $category[$key]["subtype"]=3; 
            // unset($category[$key]["type"]); 
          }
        }
      }
      return $category;
	}
	//替换图片的路径
	function get_img_thumb_url($content="")
	{
		if(preg_match("(catalog\/gd)",$content)){
			$pregRule = "/catalog/";
			if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
				$url = 'https://' .str_replace('www.', '', $_SERVER['HTTP_HOST']).rtrim(dirname($_SERVER['HTTP_HOST']), '/.\\').'/';
			} else {
				$url = 'http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['HTTP_HOST']), '/.\\').'/';
			}
			$content = $url."image/".$content;
		}
		
		return $content;
	}
	/**
	 * 检查 粘贴板的信息  cgl 2017-3-21
	 */

	function checkGroup(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$product_id=isset($_POST["product_id"])?$_POST["product_id"]:null;
			$join_id=isset($_POST["join_id"])?$_POST["join_id"]:null;
			if(isset($product_id) && isset($join_id)){
				//查询团
				$join=getRow("select * from hb_group_join as gj,hb_groupby as gb where gj.join_id='".$join_id."' and gj.product_id = '".$product_id."' and gj.join_status=1 and gj.group_id=gb.group_id and gb.group_status=1 ");
				//判断商品是否下架
				if(empty($join)){
					$this->res["retcode"]=3101;
					$this->res["msg"]="团购活动异常";
				}else if(!$this->is_down($product_id)){
					$this->res["retcode"]=3102;
					$this->res["msg"]="团购商品异常";
				}else{
					$group_id=$join["group_id"];
					$group=getRow("select *,count(gji.join_id) as total from hb_groupby as g left join hb_group_join as gj on g.group_id=gj.group_id 
								left join hb_group_join_info as gji on gj.join_id=gji.join_id
								where g.group_id ='".$group_id."' and g.product_id = '".$product_id."' and g.group_status=1 and gj.group_id ='".$group_id."' and gj.product_id = '".$product_id."' and gj.join_status=1 and gji.join_id = '".$join_id."' and gji.status=1 ");
					if($group["groupnum"]-$group["total"]>0){
						$this->res["retcode"]=0;
						$this->res["msg"]="success";
						//人数不够，还可以继续参团  查询商品信息
						$product=getRow("select * from hb_product as p left join hb_product_description as pd on p.product_id=pd.product_id where p.product_id ='".$product_id."' and status=1  ");
						$detail["group_id"]=$group_id;
						$detail["join_id"]=$join_id;
						$detail["product_id"]=$product["product_id"];
						$detail["productimg"]=$product["image"];
						$detail["productname"]=$product["name"];
						$this->res["data"]=$detail;
					}else{
						//人数已经够了
						$this->res["retcode"]=3104;
						$this->res["msg"]="这个团的人数已经够了";
					}
				}
			}else{
				$this->res["retcode"]=1000;
				$this->res["msg"]="请求参数错误";
			}
		}else{
			$this->res["retcode"]=1180;
			$this->res["msg"]="请求方式错误";
		}
		
		return $this->res;
		die;
	}
	/**
	 * 查询这个产品是否下架  cgl 2017-3-2  供上面验证  调用
	 */
	function is_down($product_id){
		$product=getRow("select * from hb_product where product_id = '".$product_id."' and status=1 ");
		if(!empty($product)){
			return 1;//产品没有下架
		}else{
			return 0;//产品已下架
		}
	}
	/**
	 * 新版团购   cgl  2017-4-24
	 */
	function groupNewList(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			if(isset($this->customer_id)){
				$merchant=getRow("SELECT merchant_id from hb_customer where customer_id = '".$this->customer_id."' ");
				if(!empty($merchant["merchant_id"])){
					$merchant_id=$merchant["merchant_id"];
					if($merchant_id==0){
						$merchant_id=1;
					}	
				}else{
					$merchant_id=1;
				}
			}else{
				$merchant_id=1;
			}
			//查询团购列表的分类
			$sql="SELECT pt.category_id FROM hb_groupby AS g JOIN hb_product_to_category AS pt ON g.`product_id`=pt.`product_id` JOIN hb_category as c on pt.category_id=c.category_id WHERE g.group_status=1 AND UNIX_TIMESTAMP(g.start_time)<= '".time()."' and pt.type=1  ";
			$group_list=getData($sql);
			$all_category=array();
			if(!empty($group_list)){
				foreach($group_list as $k=>$v){
					//查询是否是第一级
					$is_first=getRow("select * from hb_category where category_id = '".$v["category_id"]."' ");
					if($is_first["parent_id"]==0){
						$parent_id=$v["category_id"];
					}else{
						$parent_id=$is_first["parent_id"];
					}
					//查询一级分类的商品
					$is_first2=getRow("select * from hb_category where category_id = '".$parent_id."' ");
					if($is_first2["parent_id"]==0){
						$parent_id=$parent_id;
					}else{
						$parent_id=$is_first2["parent_id"];
					}

					$category_des=getRow("select * from hb_category_description where category_id = '".$parent_id."' ");

					$arr=array(
						"category_name"=>$category_des["name"],
						"category_id"=>$category_des["category_id"],
						"displaymode"=>0
						);
					$all_category[$k+1]=$arr;
				}
			}
			$a=array();
			if(!empty($all_category)){
				//去掉重复的
				$a=$this->array_unique_fb($all_category);
			}
			

			$first=array("category_id"=>0,"category_name"=>"精选","displaymode"=>1);
			array_unshift($a,$first);
			$this->res["data"]["group_category"]=$a; //团购商品分类
			//团购banner
			include_once "xcontrol/home.php";
			$home=new home();
			$arr=array();
			$arr1=$home->getBanner(15,$merchant_id);
			if(!empty($arr1)){
				$arr=$arr1;
			}
			if($_SERVER["SERVER_NAME"]=="test.haiqihuocang.com"){
                  $url_name="http://test.haiqihuocang.com";
            }else{
                  $url_name="http://haiqihuocang.cn";
            }

			$this->res["data"]["group_banner"]=$arr;
			$this->res["data"]["group_share"]=array("share_group_title"=>"嗨企拼团","share_group_content"=>"国际名牌最低价尽在嗨企拼团，喊小伙伴来拼团吧！","share_group_image"=>"http://haiqihuocang.oss-cn-hangzhou.aliyuncs.com/pro_detail_img/20170515149481767521748549.png","share_group_url"=>$url_name."/web/buy_group/index.html");
			//精选产品
			
			$this->res["data"]["group_product"]=$this->groupProductList(0,1);

		}else{
			$this->res["retcode"]=1180;
			$this->res["msg"]="请求方式错误";
		}
		return $this->res;
	}

	//二维数组去掉重复值
	function array_unique_fb($array2D){
	 foreach ($array2D as $k=>$v){
	  $v=join(',',$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
	  $temp[]=$v;
	 }
	 $temp=array_unique($temp); //去掉重复的字符串,也就是重复的一维数组
	 foreach ($temp as $k => $v){
	  $temp[$k]=explode(',',$v); //再将拆开的数组重新组装
	 }
	 $index=0;
	 $arr=array();
	 foreach($temp as $k=>$v){
	 	$arr[$index]=array("category_name"=>$v[0],"category_id"=>$v[1],"displaymode"=>$v[2]);
	 	$index++;
	 }
	 return $arr;
	}

	/**
	 * cgl 2017-4-27 团购分类下的商品
	 */

	function groupProductList($category_id=0,$page=0){
		//查询团购列表的分类
		$sql="SELECT pt.category_id FROM hb_groupby AS g JOIN hb_product_to_category AS pt ON g.`product_id`=pt.`product_id` JOIN hb_category as c on pt.category_id=c.category_id WHERE g.group_status=1 AND UNIX_TIMESTAMP(g.start_time)<= '".time()."' and pt.type=1  ";
		$group_list=getData($sql);
		if(isset($_POST["page"])){
			$page=$_POST["page"];
		}
		if(isset($_POST["categoryid"])){
			$category_id=$_POST["categoryid"];		
		}
		$arr="";//array();
		if(!empty($group_list)){
			foreach($group_list as $k=>$v){
				//查询是否是第一级
				$is_first=getRow("select * from hb_category where category_id = '".$v["category_id"]."' ");
				if($is_first["parent_id"]==0){
					$parent_id=$v["category_id"];
				}else{
					$parent_id=$is_first["parent_id"];
				}
				//查询一级分类的商品
				$is_first2=getRow("select * from hb_category where category_id = '".$parent_id."' ");
				if($is_first2["parent_id"]==0){
					$parent_id=$parent_id;
				}else{
					$parent_id=$is_first2["parent_id"];
				}

				if(@$parent_id==$category_id){
					$arr.=$is_first["category_id"].",";
				}
			}
			$arr=implode(",",array_unique(explode(",",substr($arr, 0,-1))));
		}

		if($page<=0){
			$page=1;
		}
		$limit=20;
		$start=($page-1)*$limit.",".$limit;
		//pt.category_id,
		$sql="SELECT 
			pt.product_id,
			g.groupname as product_name ,
			g.groupimage as product_image,
			g.groupnum as group_number,
			g.groupprice as group_price,
			g.group_id 
			FROM hb_groupby AS g JOIN hb_product_to_category AS pt ON g.`product_id`=pt.`product_id` JOIN hb_category as c on pt.category_id=c.category_id WHERE g.group_status=1 AND UNIX_TIMESTAMP(g.start_time)<= '".time()."' and pt.type=1 ";
		if($category_id!=0 && !empty($arr)){
			$sql.= " and pt.category_id in ( ".$arr.") ";
		}
		//g.sort_order,   
		$sql.=" order by g.date_added desc limit $start ";
		$product=array();
		$product=getData($sql);
		//查询用户的状态
		if(isset($this->customer_id)){
			$merchant=getRow("SELECT merchant_id from hb_customer where customer_id = '".$this->customer_id."' ");
			if(!empty($merchant["merchant_id"])){
				$merchant_id=$merchant["merchant_id"];	
			}else{
				$merchant_id=0;
			}
		}else{
			$merchant_id=0;
		}
		if(!empty($product)){
			foreach($product as $k=>$v){
				$product_description=getRow("select * from hb_product_description where product_id = '".$v["product_id"]."' ");
				$product[$k]["product_description"]=$product_description["basic_description"];
				//价格
				$price=getRow("select * from hb_product where product_id = '".$v["product_id"]."' ");
				if($merchant_id==0){
					//零售价
					$product[$k]["product_price"]=sprintf("%.2f",$price["price"]);
				}else if($merchant_id==1){
					$product[$k]["product_price"]=sprintf("%.2f",$price["proxyprice"]);
				}
				$order=getRow("select count(*) as total from hb_group_join_info where group_id='".$v["group_id"]."' and product_id = '".$v["product_id"]."' ");
				$product[$k]["has_group_number"]=$order["total"];
				
			}
		}
		// print_r($product);

		if(isset($_POST["categoryid"])){
			$this->res["data"]=$product;
			return $this->res;
		}else{
			return $product;
		}
	}
	/**
	 * 查询团的详情
	 */
	function groupJoinDetail(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$product_id=isset($_POST["product_id"])?$_POST["product_id"]:null;
			$group_id=isset($_POST["group_id"])?$_POST["group_id"]:null;
			$join_id=isset($_POST["join_id"])?$_POST["join_id"]:null;

			if($product_id && $group_id && $join_id){
				if($this->res["retcode"]==0){
					//查询产品的团购列表  gj.join_status,
					$sql="select gj.join_id,
						 gj.group_id,
						 gj.add_customer_image as open_image,
						 gj.add_customer_name as open_name,
						 gj.add_customer_id,
						 gj.product_id,
						 gb.start_time,
						 gb.end_time,
						 gj.join_status as group_status,
						 gb.groupnum,
						 gj.date_modified
						 from hb_group_join as gj left join hb_groupby as gb on gj.group_id=gb.group_id 
						 where gb.group_status=1 and gj.product_id='".$product_id."' and gj.group_id='".$group_id."' and (gj.join_status=1 or gj.join_status=3) and UNIX_TIMESTAMP(start_time)<= '".time()."' ";
					if(!empty($join_id)){
						$sql.=" and gj.join_id='".$join_id."'";
					}
					$sql.=" limit 5";
					$sql1="select * from hb_groupby as gb  where gb.group_status=1 and gb.product_id='".$product_id."' and gb.group_id='".$group_id."' and UNIX_TIMESTAMP(start_time)<= '".time()."' ";

					$one=getRow($sql1);
					if(empty($one) ){
						$this->res["retcode"]=3101;
						$this->res["msg"]="团购活动不存在";
					}else{
						$join_array=array();
						if(isset($this->customer_id)){
							$merchant=getRow("SELECT merchant_id from hb_customer where customer_id = '".$this->customer_id."' ");
							if(!empty($merchant["merchant_id"])){
								$merchant_id=$merchant["merchant_id"];	
							}else{
								$merchant_id=0;
							}
						}else{
							$merchant_id=0;
						}
						//查询产品
						$product=getRow("select * from hb_product where product_id = '".$product_id."' ");
						if($merchant_id==0){
							//零售价
							$jian_price=$product["price"];
						}else{
							//会员价
							$jian_price=$product["proxyprice"];
						}
						$fight_group_price=0;
						if($jian_price-$one["groupprice"]>0){
							$fight_group_price=$jian_price-$one["groupprice"];
						}
						$join_number=getRow("select count(*) as total from hb_group_join_info where product_id = '".$product_id."' and status=1 and join_id = '".$join_id."' ");
						
						if($one["groupnum"]-$join_number["total"]<0){
							$number=0;
						}else{
							$number=$one["groupnum"]-$join_number["total"];
						}
						$is_success=getRow("select * from hb_group_join where join_id = '".$join_id."' and join_status = 3 ");
						//看团是否成功
						if($number==0 || !empty($is_success)){
							$one["group_status"]=4;//已成功  团购
						}
						$join_grouper_s=getData("select * from hb_group_join_info where join_id = '".$join_id."' and status=1 order by type desc ");
						if(!empty($join_grouper_s)){
							foreach($join_grouper_s as $k=>$v){
								$customer=getRow("select * from hb_customer where customer_id = '".$v["customer_id"]."' ");
								$join_array[$k]["join_status"]=$v["type"];
								$join_array[$k]["joiner_image"]=$customer["headurl"];
							}
						}
						$order=getRow("select count(*) as total from hb_group_join_info where group_id='".$group_id."' and product_id = '".$product_id."' ");
						$product_item_id=0;
						if(!empty($one)){
							$product_item_id=$one["groupoptionid"];
						}

						$json["open_short_number"]=$number;//差几人
						$json["fight_group_price"]=sprintf("%.2f",$fight_group_price);
						$json["join_grouper"]=$join_array;//参团人数
						if($_SERVER["SERVER_NAME"]=="test.haiqihuocang.com"){
			                  $url_name="http://test.haiqihuocang.com";
			            }else{
			                  $url_name="http://haiqihuocang.cn";
			            }
			            ///web/buy_group/share_buy.html?product_id=4644&join_id=228&group_id=256
						$json["share_group_url"]=$url_name."/web/buy_group/share_buy.html?product_id=".$product_id."&join_id=".$join_id."&group_id=".$group_id;
						$json["has_group_number"]=$order["total"];//已团人数
						$json["product_name"]=$one["groupname"];
						$json["product_image"]=$one["groupimage"];
						$json["group_status"]=$one["group_status"];
						$json["group_price"]=empty($one["groupprice"])?"":$one["groupprice"];
						$json["group_end_time"]=empty($one["end_time"])?"":strtotime($one["end_time"]);
						$json["group_number"]=empty($one["groupnum"])?"0":$one["groupnum"];
						$json["current_time"]=time();
						$json["product_item_id"]=$product_item_id;
						$this->res["data"]=$json;
					}
				}
			}else{
				//参数错误
				$this->res["retcode"]=1001;
				$this->res["msg"]="参数错误";
			}
		}else{
			$this->res["retcode"]=1180;
			$this->res["msg"]="请求方式错误";
		}
		return $this->res;
	}
	/**
	 * 检查开团或者团购的状态 cgl 2017-5-2
	 */
	function checkGroupStatus(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$product_id=isset($_POST["product_id"])?$_POST["product_id"]:null;
			$group_id=isset($_POST["group_id"])?$_POST["group_id"]:null;
			$group_type=isset($_POST["group_type"])?$_POST["group_type"]:1;
			$join_id=isset($_POST["join_id"])?$_POST["join_id"]:null;
			//开团，或者参团   1参团，2开团
			if($this->res["retcode"]==0){
				$customer=getRow("select * from hb_customer where customer_id='".$this->customer_id."' ");

				$is_has=$this->is_customer_join_group2($group_id,$product_id);
				if(!empty($is_has)){
					if($is_has["type"]==2){
						//你已经在该商品开过团了
						$this->res["retcode"]=3201;
						$this->res["msg"]="亲,你已在该团购开过团了哦";	
					}else if($is_has["type"]==1){
						//你已经在该商品开过团了
						$this->res["retcode"]=3202;
						$this->res["msg"]="亲,你已在该团购参加过团了哦";
					}
					return $this->res;
					die;
				}

				if($group_type==1){
					//参团
					
					//开团
					$is_open=$this->is_open($group_id,$product_id);


					//查询是否这个产品已经下架     是否已经结束  查看该团购是否是正在进行或者已经成功
					if(!$this->is_ok($group_id,$product_id)){
						$this->res["retcode"]=3101;
						$this->res["msg"]="亲,该团购活动可能结束或者关闭了";
					}else if(!$this->is_down1($group_id,$product_id)){
						$this->res["retcode"]=3102;
						$this->res["msg"]="亲,团购商品已下架了哦";
					}else if(!$this->is_down2($group_id,$product_id)){
						$this->res["retcode"]=3102;
						$this->res["msg"]="亲,团购商品数量不足了哦";
					}else if($is_open==1){
						//开了  就不能开团了
						$this->res["retcode"]=3100;
						$this->res["msg"]="亲,你已经开过团了哦";
					}else{
						//是否已经参过这个团
						if($this->is_customer_join_group($group_id,$product_id,$join_id)){
							$this->res["retcode"]=3103;
							$this->res["msg"]="亲,你已经参加过这个团了哦";
						}else{
							//是否人数足够  还能够参团么？
							$group=getRow("select *,count(gji.join_id) as total from hb_groupby as g left join hb_group_join as gj on g.group_id=gj.group_id 
								left join hb_group_join_info as gji on gj.join_id=gji.join_id
								where g.group_id ='".$group_id."' and g.product_id = '".$product_id."' and g.group_status=1 and gj.group_id ='".$group_id."' and gj.product_id = '".$product_id."' and gj.join_status=1 and gji.join_id = '".$join_id."' and gji.status=1 ");
							if($group["groupnum"]-$group["total"]>0){
								//人数不够，还可以继续参团
								//cgl 2017-3-6  新增一个字段11
								$this->res["data"]["join_id"]="$join_id";
								$this->res["data"]["group_id"]="$group_id";
							}else{
								//人数已经够了
								$this->res["retcode"]=3104;
								$this->res["msg"]="亲,这个团的人数已经够了哦";
							}
						}
					}
				}else if($group_type==2){
					//开团
					$is_open=$this->is_open($group_id,$product_id);
					//是否已经开过团
					if($is_open==1){
						//开了  就不能开团了
						$this->res["retcode"]=3100;
						$this->res["msg"]="亲,你已经开过团了哦";
					}else{
						if($this->is_ok($group_id,$product_id) && $this->is_down1($group_id,$product_id) && $this->is_down2($group_id,$product_id) ){
							//没有开团可以开团
								//cgl 2017-3-6  新增一个字段
								$this->res["data"]["join_id"]="";
								$this->res["data"]["group_id"]="$group_id";
							// }
						}else{
							if(!$this->is_ok($group_id,$product_id)){
								$this->res["retcode"]=3101;
								$this->res["msg"]="亲,该团购活动可能结束或者关闭了";
							}else if(!$this->is_down1($group_id,$product_id)){
								$this->res["retcode"]=3102;
								$this->res["msg"]="亲,团购商品已下架了哦";
							}else if(!$this->is_down2($group_id,$product_id)){
								$this->res["retcode"]=3102;
								$this->res["msg"]="亲,团购商品数量不足了哦";

							}
						}
					}
				}
			}
		}else{
			$this->res["retcode"]=1180;
		}
		return $this->res;
	}
	/**
	 * 查询是否开过团 cgl 2017-3-1    供上面验证  调用
	 */
	function is_open($group_id,$product_id){
		//查询是否开过团
		$tuan=getRow("select * from hb_group_join as gj left join hb_group_join_info as gji on gj.group_id=gji.group_id  where gji.customer_id = '".$this->customer_id."' and gji.type=2 and gji.group_id = '".$group_id."' and gji.product_id = '".$product_id."' and gj.product_id = '".$product_id."' and gj.group_id = '".$group_id."' and gj.add_customer_id = '".$this->customer_id."' and gj.join_status=1 ");
		if($tuan){
			return 1;
		}else{
			return 0;
		}
	}
	/**
	 * 查询这个产品是否下架  cgl 2017-3-2  供上面验证  调用
	 */
	function is_down1($group_id,$product_id){
		$product=getRow("select * from hb_product where product_id = '".$product_id."' and status=1 ");
		if(!empty($product)){
			return 1;//产品没有下架
		}else{
			return 0;//产品已下架
		}
	}
	function is_down2($group_id,$product_id){
		$product=getRow("select * from hb_product where product_id = '".$product_id."' and status=1 and quantity>0 ");
		if(!empty($product)){
			return 1;//产品数量小于1
		}else{
			return 0;//产品数量小于1
		}
	}
	/**
	 * 团购活动是否到期   团购活动商品是否正常  cgl 2017-3-2  供上面验证  调用
	 */
	function is_ok($group_id,$product_id){
		$tuan=getRow("select * from hb_groupby where product_id='".$product_id."' and group_id = '".$group_id."' and group_status = 1 and UNIX_TIMESTAMP(end_time)>= '".time()."' and UNIX_TIMESTAMP(start_time)<= '".time()."' ");
		if(!empty($tuan)){
			return 1;//团购活动正常
		}else{
			return 0;//团购活动异常    失败，关闭，已结束
		}
	}
	/**
	 * 查询这个用户是否已经参加过这个团
	 */
	function is_customer_join_group($group_id,$product_id,$join_id){
		$is_join=getRow("select * from hb_group_join_info where customer_id = '".$this->customer_id."' and group_id = '".$group_id."' and product_id = '".$product_id."' and join_id = '".$join_id."' and status=1 ");
		if(!empty($is_join)){
			return 1;//参加过了的  不能参加了
		}else{
			return 0;//可以参团这个团购的团
		}
	}
	/**
	 * 查询这个用户是否已经参加过这个团 
	 * cgl 2017-5-11 修改  新增
	 */
	function is_customer_join_group2($group_id,$product_id){
		$is_join=getRow("select * from hb_group_join_info where customer_id = '".$this->customer_id."' and group_id = '".$group_id."' and product_id = '".$product_id."' and status=1 ");
		return $is_join;
		// if(!empty($is_join)){
		// 	return 1;//参加过了的  不能参加了
		// }else{
		// 	return 0;//可以参团这个团购的团
		// }
	}
	/**
	 * 查询团购的数量
	 */
	function groupNumber(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$this->res["data"]["unpay"]=$this->number(1);
			$this->res["data"]["ingroup"]=$this->number(2);
			$this->res["data"]["undelivery"]=$this->number(3);
			$this->res["data"]["unreceiving"]=$this->number(4);
		}else{
			$this->res["retcode"]=1180;
		}
		return $this->res;
	}
	/**
	 * 团购数量问题   上面调用
	 * 1：待付款
	 * 2：拼团中
	 * 3：待发货
	 * 4：待收货
	 */
	function number($status){
		$order_sql = '';
		if($status == 1){
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
		$sql="select COUNT(o.order_id) as count
 						   from `hb_order` as o
						   inner join `hb_order_product` as op on op.order_id = o.order_id
						   inner join `hb_product` as p on p.product_id = op.product_id
						   inner join `hb_warehouse` as w on w.warehouse_id = o.warehouse_id
						   left join `hb_return` as r on r.order_id = o.order_id
						   left join `hb_merchant_service` as ms on ms.merchant_id = o.merchant_id
						   where o.customer_id = '" .(int)$this->customer_id. "' $order_sql and is_member_status=0 order by o.date_added desc
						  ";
		$order = getRow($sql);
		return $order["count"];
	}
	/**
	 * 判断团   是否过期
	 * cgl 2017-5-17
	 */
	function groupIsEnd(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$group_id=isset($_POST["group_id"])?$_POST["group_id"]:null;
			$product_id=isset($_POST["product_id"])?$_POST["product_id"]:null;
			if(!$group_id || !$product_id){
				$this->res["retcode"]=1001;
				$this->res["msg"]="请求参数错误";
			}else{
				$tuan=getRow("select * from hb_groupby where product_id='".$product_id."' and group_id = '".$group_id."' and UNIX_TIMESTAMP(end_time)>= '".time()."' and UNIX_TIMESTAMP(start_time)<= '".time()."' ");
				if(!empty($tuan)){
					//没过期  判断是否关闭
					if($tuan["group_status"]==2){
						$this->res["retcode"]=3101;
						$this->res["msg"]="亲,该团购活动结束了";
					}else if($tuan["group_status"]==3){
						$this->res["retcode"]=3101;
						$this->res["msg"]="亲,该团购活动关闭了";
					}
				}else{
					$this->res["retcode"]=3101;
					$this->res["msg"]="亲,该团购活动结束了";
				}
			}
		}else{
			$this->res["retcode"]=1180;
			$this->res["msg"]="请求方式错误";
		}
		return $this->res;
	}

}
