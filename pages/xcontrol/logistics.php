<?php
use OSS\OssClient;
use OSS\Core\OssException;
require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';
//面向对象的control 类
include "xcontrol/base.php";

class logistics extends base
{
	
	function __construct() {
        parent::__construct();
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   }
   
   //物流列表
	function index(){
		$this->getMenu();

		if(isset($_GET['page'])){
            $page=$_GET['page'];
            if($page<1)$page=1;
        }else{
            $page=1;
        }
        //每页显示条数
        $per_num = 15;  
        $start=($page-1)*$per_num;
		$wherestr="";
		//订单号
   		if(isset($_GET['order_id'])&&$_GET['order_id']){
   			$this->res['order_id'] = $_GET['order_id'];
   			$wherestr.=" and a.order_id = '".trim($_GET['order_id'])."'";
   		}
   		//商品名称
   		if(isset($_GET['name'])&&$_GET['name']){
   			$this->res['name'] = $_GET['name'];
   			$wherestr.=" and b.name like '%".trim($_GET['name'])."%'";
   		}
   		//快递单号
   		if(isset($_GET['ship_order_no'])&&$_GET['ship_order_no']){
   			$this->res['ship_order_no'] = $_GET['ship_order_no'];
   			$wherestr.=" and a.ship_order_no like '".trim($_GET['ship_order_no'])."%'";
   		}
   		//发货时间
   		if(isset($_GET['ship_date'])&&$_GET['ship_date']){
   			$this->res['ship_date'] = $_GET['ship_date'];
   			$_GET['ship_date'] = date('Y-m-d',strtotime($_GET['ship_date']));
   			$wherestr.=" and a.ship_date like '".trim($_GET['ship_date'])."%'";
   		}
		$sql = "select a.order_id,a.ship_id,a.order_status_id,a.ship_order_no,a.ship_date,b.product_id,b.name,c.ali_code,c.com
				from hb_order as a
				inner join hb_order_product as b on a.order_id = b.order_id 
				left join hb_shipping as c on a.ship_id = c.id
				where a.order_status_id = 3 ".$wherestr." order by a.order_id desc ";
		$sql .= "limit ".$start.','.$per_num."";
		$orderInfo = getData($sql);
		if(sizeof($orderInfo)<$per_num){
            $this->getPages($page,$page);
        }else{
            $this->getPages($page);
        }
		$expressinfo = $this->getExpressInfo();
		$this->res['orderinfo'] = $orderInfo;
		$this->res['companys'] = $expressinfo;
		$this->res['form_url'] = linkurl('logistics/saveShipno');
		
		return $this->res;
	}
	//物流详情
	function expressDetails(){
		$this->getMenu();
		$com = isset($_GET['com'])?$_GET['com']:null;
		$nu = isset($_GET['nu'])?$_GET['nu']:null;
		$order_id = isset($_GET['oid'])?$_GET['oid']:null;
		$product_id = isset($_GET['pro_id'])?$_GET['pro_id']:null;

		$sql = "select a.firstname,a.shipping_custom_field,a.shipping_firstname,a.shipping_address_1,a.shipping_city,
					a.shipping_country,a.shipping_zone,a.freight,b.image,b.price,b.marketprice,d.name 
					from hb_order as a 
					inner join hb_order_product as c on a.order_id = c.order_id
					inner join hb_product as b on c.product_id = b.product_id 
					inner join hb_product_description as d on c.product_id = d.product_id
					where a.order_id = '".$order_id."' and b.product_id = '".$product_id."'";
		$orderinfo = getRow($sql);
		
		$orderinfo['price'] = sprintf("%.2f",$orderinfo['price']);
		$orderinfo['marketprice'] = sprintf("%.2f",$orderinfo['marketprice']);
		
		$this->res+=$orderinfo;

		if(!empty($orderinfo)){
			$s_tel = json_decode($orderinfo['shipping_custom_field'],true);
			$s_telphone = $s_tel[1];													//收货电话
			$s_address = $orderinfo['shipping_country'].$orderinfo['shipping_zone'].$orderinfo['shipping_city'].$orderinfo['shipping_address_1'];
			$this->res['s_address'] = $s_address;
    		$this->res['s_telphone'] = $s_telphone;
		}

		$expressinfo = $this->expressInfo($com,$nu);
		$info = json_decode($expressinfo,true);
		if($info['showapi_res_code']==0 && !empty($info['showapi_res_body']['data'])){											
			$data = $info['showapi_res_body']['data'];							//查询成功
		}else{
			$data = '';
		}
    	
    	$this->res['data'] = $data;
    	$this->res['expressinfo'] = $expressinfo;
    	return $this->res;


	}
	//编辑快递单号
	function saveShipno(){
		$data['order_id'] = $_POST['order_id'];
		$data['date_added'] = date('Y-m-d h:i:s');
		$data['order_status_id'] = $_POST['order_status_id'];
		$data['notify'] = 0;
		$data['comment'] = "操作者userid:".$_SESSION['userid'].";物流公司:".$_POST['ship_id1']."->".$_POST['ship_id'].";物流单号:".$_POST['ship_order_no1']."->".$_POST['ship_order_no'];

		$table_name = 'hb_order';
		$table_name1 = 'hb_order_history';

		saveData($table_name,$_POST);
		saveData($table_name1,$data);
		echo"<script>alert('编辑成功');history.go(-1);</script>";  
		exit;
	}
	
	//物流公司管理列表
	function shipcomList(){
		$this->getMenu();
		if(isset($_GET['page'])){
            $page=$_GET['page'];
            if($page<1)$page=1;
        }else{
            $page=1;
        }
        $wherestr="";
        //快递公司名称
   		if(isset($_GET['com'])&&$_GET['com']){
   			$this->res['com'] = $_GET['com'];
   			$wherestr.=" and com like '%".trim($_GET['com'])."%'";
   		}
   		//快递公司编码
   		if(isset($_GET['ali_code'])&&$_GET['ali_code']){
   			$this->res['ali_code'] = $_GET['ali_code'];
   			$wherestr.=" and ali_code like '%".trim($_GET['ali_code'])."%'";
   		}
        //每页显示条数
        $per_num = 9;  
        $start=($page-1)*$per_num;

		$sql = "select id,com,addtimes,ship_phone,ship_img,ali_code from hb_shipping where 1 $wherestr order by id desc ";
		$sql .= "limit ".$start.','.$per_num."";
		$shipInfo = getData($sql);

		if(sizeof($shipInfo)<$per_num){
            $this->getPages($page,$page);
        }else{
            $this->getPages($page);
        }

		$this->res['shipInfo'] = $shipInfo;
		$this->res['edit_url'] = linkurl('logistics/getInfoById');
		$this->res['form_shipinfo_url'] = linkurl('logistics/saveShipInfo');
		return $this->res;
	}
	//获取物流公司信息
	function getExpressInfo(){
		$sql = "select id,com,cod from hb_shipping where 1 ";
		$info = getData($sql);
		return $info;
	}
	//根据id查物流公司信息
	function getInfoById(){
		$id = $_POST['id'];
		$sql = "select id,com,addtimes,ship_phone,ship_img,ali_code from hb_shipping where id = '".$id."'";
		$info = getRow($sql);
		print_r(json_encode($info));die;
	}
	//保存物流公司信息
	function saveShipInfo(){
		if(!empty($_FILES['img']['name'])){
			$file = $_FILES;
			$img = $this->upload_img($file);
			$_POST['ship_img'] = $img[0];
		}
		$_POST['addtimes'] = date('Y-m-d h:i:s');
		if(!empty($_POST['id'])){
			$table_name = 'hb_shipping';
			saveData($table_name,$_POST);
			echo"<script>alert('保存成功');history.go(-1);</script>";  
			die;
		}else{
			$table_name = 'hb_shipping';
			saveData($table_name,$_POST);
			echo"<script>alert('保存成功');history.go(-1);</script>";  
			die;
		}	
	}
	//上传评论图片
    function upload_img($_FILE){
        // if(isset($_FILES['Filedata'])) { 
            $file = $_FILE['img'];
            $imgArr =  array();                              
            $length = count($file['name']);
            for($i=0;$i<$length;$i++){
               $name = $file['name'];
               $type = strtolower(substr($name, strrpos($name, '.') + 1)); //得到文件类型，并且都转化成小写
               $allow_type = array('jpg', 'jpeg', 'gif', 'png');           //定义允许上传的类型
               
               if (!in_array($type, $allow_type)) {
                  echo 2;  exit;                                           //判断文件类型是否被允许上传   
               }
               if (!is_uploaded_file($file['tmp_name'])) {
                  echo 3;exit;                                             //判断是否是通过HTTP POST上传的   
               }
               $filename=explode(".",$name);
               $filename[0]=rand(1,100000000);                          //设置随机数长度
               $name=implode(".",$filename);

               $uploadfile=date("Ymd").time().$name;
               if (!empty($name)) {
                     try{
                        $object = "remark_img/".$uploadfile;
                        $file_local_path = $file["tmp_name"];
                        $accessKeyId = OSS_ACCESS_KEY_ID;
                        $accessKeySecret = OSS_ACCESS_KEY_SECRET;
                        $endpoint = OSS_ENDPOINT;
                        $bucket = OSS_BUCKET;

                        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                        $ossClient->multiuploadFile($bucket,$object,$file_local_path);  //上传至阿里云OSS
                        $img_url = OSS_IMG_ENDPOINT."/".$object;

                        if(!empty($img_url)){
                           $imgArr[] = $img_url;                              //上传成功                                      
                        }else{
                           echo 5;     exit;                                  //上传失败
                        }    
                     }catch(OssException $e){
                        echo 5;     exit;                                     //上传失败
                     }                 
               }else{
                        echo 5;     exit;                                     //上传失败   
               } 
            } 
            return $imgArr;exit;    
        // }
    }

     
     
	
}

