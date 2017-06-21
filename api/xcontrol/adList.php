<?php
	//面向对象的control 类
include "xcontrol/base.php";
class adList extends base{

	function __construct() 
	{
       parent::__construct();
	   $this->passkey=@$_SESSION["default"]['passkey'];
	   $this->customer_id=@$_SESSION["default"]['customer_id'];
   	}


	/**
	 *   zxx  新增 2017/3/9  活动列表
	 */
	 function index(){
      $merchant_id=1; 
      //获取到商品banner
      $category=getData("SELECT banner_id,`image`,`type`,`subtype`,`item_id`,`link` FROM `hb_banner_image` WHERE  `merchant_id` = '". (int)$merchant_id ."' ORDER BY banner_id,`sort_order` DESC");
      //如果广告不空，则使用处理照片路径的函数
      if(!empty($category)){
        foreach ($category as $key => $value) {
          $category[$key]['image']=$this->get_img_thumb_url($value['image']);
          //当他的位置是活动列表时，查询出前七个产品
          if($value['banner_id']==12){
          		$sql="SELECT p.image,
          		p.price,
          		p.marketprice,
          		p.proxyprice,
          		pd.name,
          		p.product_id,
          		pa.category_id 
          		from hb_product as p,hb_product_description as pd,hb_product_to_activity as pa  where p.status=1 and p.product_id=pd.product_id and p.product_id=pa.product_id and pa.category_id='".$value['item_id']."' and pa.status=1 order by p.sort_order desc,p.product_id asc limit 7";
          		$res=getData($sql);
          		$category[$key]["product_list"]=$res;
          }
          if($category[$key]["type"]==1){
          	//外部url subtype为3
          	$category[$key]["subtype"]=3;
          }
        }
      }
      $this->res['dt']=$category;
      return $this->res;
      die;  
	 }

	 /**
	 *   zxx  新增 2017/3/10  活动商品
	 */
	 function getActivityGoods(){

	 	if($_SERVER['REQUEST_METHOD']=="POST"){
	        //默认merchant_id=1
	        $merchant_id=1;
	        //要获取到活动的ID
	        if(empty($_POST['category_id'])){
	        	//参数错误
	        	$this->res["retcode"]=1001;
	        	$this->res["msg"]="未传入活动ID";
	        	return $this->res;
	        }
	       
			$sortRule=isset($_POST['type'])?$_POST['type']:0;
			if($sortRule==1){
				$rule='p.quantity!=0 DESC,p.sales DESC,p.quantity DESC';
			}elseif($sortRule==2){
				$rule='p.quantity!=0 DESC,p.proxyprice,p.quantity DESC';
			}elseif($sortRule==3){
				$rule='p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC';
			}elseif($sortRule==4){
				$rule='p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC';
			}elseif($sortRule==5){
				$rule='p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC';
			}else{
				$rule='p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC';
			}
			//echo $rule;exit();
	        //获取到该活动下的活动商品
	    	 $sql="SELECT p.image as productimg,p.date_added,
	      		FORMAT(p.price,2) as price,
	      		FORMAT(p.marketprice,2) as marketprice,
	      		FORMAT(p.proxyprice,2) as proxyprice,
	      		pd.name as productname,
	      		p.product_id as productid,
	      		p.quantity
	      		from hb_product as p,hb_product_description as pd,hb_product_to_activity as pa  where p.status=1 and p.product_id=pd.product_id and p.product_id=pa.product_id and pa.category_id='".$_POST['category_id']."' and pa.status=1 order by ".$rule." ";    		
			$res=getData($sql);
	        //设置信息

	        $customer=getRow("select merchant_id from hb_customer where customer_id='".$this->customer_id."' ");
			//返回两个价格 当是会员时返回会员价和市场价，当不是会员时返回当前价和市场价
			//不是会员
			if(!empty($res)){
				foreach ($res as $key => $value) {
					if(@$customer["merchant_id"]==0){
						$res[$key]['finalprice']=$value['price'];//最终价格
						$res[$key]['originalprice']=$value['marketprice'];//原价
					}else{
						$res[$key]['finalprice']=$value['proxyprice'];//最终价格
						$res[$key]['originalprice']=$value['marketprice'];//原价
					}

					if($value["quantity"]==0){
						//卖光了
						$res[$key]["issaled"]=1;
						$res[$key]["isenough"]=2;
					}elseif($value["quantity"]<10){
						//不够
						$res[$key]["issaled"]=0;
						$res[$key]["isenough"]=0;
					}else{
						//充足
						$res[$key]["issaled"]=0;
						$res[$key]["isenough"]=1;
					}

					$currenttime = time();
					$prdaddedtime = $value['date_added'];
					//转换成时间戳
					$prdaddedtimestamp = strtotime($prdaddedtime);
					if (($currenttime - $prdaddedtimestamp) > 3600 * 24 * 3) {
						$res[$key]['isnew'] = '0';
					} else {
						$res[$key]['isnew'] = '1';
					}

					unset($res[$key]["is_saled"]);
					unset($res[$key]["quantity"]);
					unset($res[$key]['price']);
					unset($res[$key]['marketprice']);
					unset($res[$key]['proxyprice']);
				}

					
			}

	        $this->res['data']=$res;
	        $this->res["retcode"]=0;
	        $this->res["msg"]="success";
	    }else{
	    	//请求方式错误
	    	$this->res["msg"]="请求方式错误";
			$this->res["retcode"]=1180;
		}
		return $this->res;
		die;    
	 }

	//替换图片的路径
	function get_img_thumb_url($content="")
	{
		if(preg_match("(catalog\/gd)",$content)){
			$pregRule = "/catalog/";
			$content = $_SERVER["HTTP_HOST"]."/image/".$content;
		}
		
		return $content;
	}

}
