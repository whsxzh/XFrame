<?php
//刷单评价
use OSS\OssClient;
use OSS\Core\OssException;
//面向对象的control 类
include "xcontrol/base.php";
include "lib/pagination.php";


require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';
class clickfarm extends base
{
	
	function __construct() 
	{
       parent::__construct();
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}
   	//刷单商品列表
   	function productlist(){
         $this->getMenu();

         if(isset($_GET['page'])){
            $page=$_GET['page'];
            if($page<1)$page=1;
         }else{
            $page=1;
         }
         if(!empty($_GET['keyword'])){
            $keyword = trim($_GET['keyword']);
         }else{
            $keyword = '';
         }
       
         //每页显示条数
         $per_num = 15;  
         $start=($page-1)*$per_num;

         if(isset($_GET['keyword'])){
            $keyword = trim($_GET['keyword']);
            $this->res['keyword']  = $keyword;   
         }else{
            $keyword = '';
            $this->res['keyword']  = $keyword;
         }

         $sql = "select a.product_id,a.date_added,a.model,a.proxyprice,a.image,a.sales,a.status,c.name,e.name as pname
               from hb_product as a 
               left join hb_product_description as e on a.product_id = e.product_id
               inner join hb_product_to_category as b on a.product_id = b.product_id
               left join hb_category_description as c on b.category_id = c.category_id ";
         $sql .= " where a.status = 1 ";

         if(!empty($keyword)){
            $sql .= " and (a.product_id like '%".$keyword."%' or a.model like '%".$keyword."%' or e.name like '%".$keyword."%' or c.name like '%".$keyword."%')";
         }
         $sql .= " order by a.date_added desc limit ".$start.",".$per_num."";

         $productInfo = getData($sql);

       
         $sql1 = "select count(*) as total 
               from hb_product as a 
               left join hb_product_description as e on a.product_id = e.product_id
               inner join hb_product_to_category as b on a.product_id = b.product_id
               left join hb_category_description as c on b.category_id = c.category_id ";
         $sql1 .= " where a.status = 1 ";

         if(!empty($keyword)){
            $sql1 .= " and (a.product_id like '%".$keyword."%' or a.model like '%".$keyword."%' or e.name like '%".$keyword."%' or c.name like '%".$keyword."%')";
         }
           $total=getRow($sql1,600);
           $total=$total['total'];
           $total_page = ceil($total/20);
           $this->res['is_end_page'] = 1;
           if($page == $total_page){
             $this->res['is_end_page'] = 0;
           }
           $this->getPages($page,$total_page);

         // print_r($productInfo);die;

         // if(sizeof($productInfo)<$per_num){
         //    $this->getPages($page,$page);
         // }else{
         //    $this->getPages($page);
         // }

   		$sql2 = "select customer_id,firstname from hb_customer where is_shuadan = 1 and is_display = 1 and status = 1 order by customer_id desc";
   		$shuadanUser = getData($sql2);

   		$this->res['shuadan_user'] = $shuadanUser;
   		$this->res['data'] = $productInfo;

   		$this->res['goods_url']  = linkurl('clickfarm/productlist');
         $this->res['edit_sales']  = linkurl('clickfarm/editSales');
   		$this->res['shudan_user_url']  = linkurl('clickfarm/userlist');
         $this->res['remark_url']  = linkurl('clickfarm/remark');
         $this->res['home_url']  = linkurl('common/index');
         // $this->res['pagestr']  = $pagestr;
   		return $this->res;
   	}
      /*
       *zxx 刷商品销量 2017-4-13
       */
      function editSales(){
         if(!empty($_POST['id'])){
            //如果商品id存在
            $sales=(isset($_POST['psales']) && $_POST['psales']>0)?$_POST['psales']:0;
            $sql="update ".DB_PREFIX."product set sales=sales+".$sales." , shua_sales=shua_sales+".$sales." where product_id=".$_POST['id'];
            exeSql($sql);
            echo 2;exit;
         }else{
            echo 1; exit;
         }
         exit;
      }

      //获取商品总记录数
      function getTotal($keyword=''){
         $sql = "select count(a.product_id) as num
               from hb_product as a 
               left join hb_product_description as e on a.product_id = e.product_id
               left join hb_product_to_category as b on a.product_id = b.product_id
               left join hb_category_description as c on b.category_id = c.category_id ";
         if(!empty($keyword)){
            $sql .= " where a.product_id like '".$keyword."%' or a.model like '".$keyword."%' or e.name like '".$keyword."%' or c.name like '".$keyword."%'";
         }
         $num = getRow($sql);
         return $num['num'];
      }
   	
      //添加评价
      function remark(){
         $this->getMenu();
         $data['product_id']    = isset($_GET['pro_id'])?$_GET['pro_id']:null;
         $data['customer_id']   = isset($_GET['cid'])?$_GET['cid']:null;
         $data['isanon']        = isset($_GET['hide_name'])?$_GET['hide_name']:null;  //是否匿名 1:匿名 0:不匿名

         if(!empty($data['product_id']) && !empty($data['customer_id']) && !empty($_POST)){
            $data['text'] = $_POST['text'];
            $data['rating'] = $_POST['rating'];
            $data['status'] = 1;
            $data['author'] = $this->getFirstname($data['customer_id']);
            $data['isadditional']  = 0;  //是否追加 1:是 0:否
            if(empty($_POST['remark_time'])){
               $data['date_added'] = date('Y-m-d H:i:s');
            }else{
               $data['date_added'] = $_POST['remark_time'];
            }
            $table_name = DB_PREFIX.'review';
            saveData($table_name,$data);
            $insertID = getLastId();

            if(!empty($_POST['image'])){
               $imgArr = $_POST['image'];
               $table_name1 = DB_PREFIX.'review_image';
               foreach ($imgArr as $key => $value) {
                  if(!empty($value)){
                     $img['review_id'] = $insertID;
                     $img['image'] = $value;
                     saveData($table_name1,$img);
                  }
               }
            }
            //刷单销量增17
            $sales['product_id'] = $data['product_id'];
            $sales['shua_sales'] = $this->getSale($data['product_id'])+17;
             $sales['sales'] = $this->getSale($data['product_id'])+17;
            $table_name2 = DB_PREFIX.'product';
            saveData($table_name2,$sales);

            echo"<script>alert('评论保存成功！');history.go(-1);</script>"; 

         }elseif((empty($data['product_id']) || empty($data['customer_id']) && !empty($_POST) )){
            echo"<script>alert('评论保存失败！');history.go(-1);</script>";
         }
        
         $this->res['form_url']  = linkurl('clickfarm/remark');
         $this->res['home_url']  = linkurl('common/index');
         $this->res['goods_url']  = linkurl('clickfarm/productlist');
         $this->res['upimg_url']  = linkurl('clickfarm/upload_img');

         return $this->res;
      }
      //上传评论图片
      function upload_img(){
         if(isset($_FILES['Filedata'])) { 
            $file = $_FILES['Filedata'];
            $imgArr =  array();                              
            $length = count($file['name']);
            for($i=0;$i<$length;$i++){
               $name = $file['name'][$i];
               $type = strtolower(substr($name, strrpos($name, '.') + 1)); //得到文件类型，并且都转化成小写
               $allow_type = array('jpg', 'jpeg', 'gif', 'png');           //定义允许上传的类型
               
               if (!in_array($type, $allow_type)) {
                  echo 2;  exit;                                           //判断文件类型是否被允许上传   
               }
               if (!is_uploaded_file($file['tmp_name'][$i])) {
                  echo 3;exit;                                             //判断是否是通过HTTP POST上传的   
               }
               $filename=explode(".",$name);
               $filename[0]=rand(1,100000000);                          //设置随机数长度
               $name=implode(".",$filename);

               $uploadfile=date("Ymd").time().$name;
               if (!empty($name)) {
                     try{
                        $object = "remark_img/".$uploadfile;
                        $file_local_path = $file["tmp_name"][$i];
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
            print_r(json_encode($imgArr));exit;
            
         }
      }
      //根据customer_id查昵称
      function getFirstname($customer_id){
         $sql = "select firstname from hb_customer where customer_id = '".$customer_id."'";
         $info = getRow($sql);
         return $info['firstname'];
      }
      //根据product_id 查出该商品的销量
      function getSale($product_id){
         $sql = "select shua_sales from hb_product where product_id = '".$product_id."'";
         $info = getRow($sql);
         return $info['shua_sales'];
      }
      //刷单用户列表
      function userlist(){
         $this->getMenu();
         if(isset($_GET['page'])){
            $page=$_GET['page'];
            if($page<1)$page=1;
         }else{
            $page=1;
         }
         //每页显示条数
         $per_num = 14;  
         $start=($page-1)*$per_num;

         $total_num = $this->getShuadanerNum();

         $sql = "select customer_id,firstname,headurl,is_display,status
               from hb_customer where is_shuadan = 1 and is_display = 1 ";
         $sql .= " order by customer_id desc limit ".$start.",".$per_num."";
         $shuadanUser = getData($sql);

         //分页字符串
         $pagination = new Pagination();
         
         $userlist_url = linkurl('clickfarm/userlist');
         $page_url = $userlist_url;
         $pagestr = $pagination->page($total_num,$page,$per_num,$page_url);

         foreach ($shuadanUser as $key => $value) {
            $remark_num = $this->remark_num($value['customer_id']);
            $shuadanUser[$key]['remark_num'] = $remark_num;

            if($value['status']==0){
               $shuadanUser[$key]['status'] = '已禁用';
               $shuadanUser[$key]['status_other'] = '启用';
               $shuadanUser[$key]['dis_url'] = linkurl('clickfarm/disabled').'&status=0&cid='.$value['customer_id'];
            }else{
               $shuadanUser[$key]['status'] = '已启用';
               $shuadanUser[$key]['status_other'] = '禁用';
               $shuadanUser[$key]['dis_url'] = linkurl('clickfarm/disabled').'&status=1&cid='.$value['customer_id'];
            }
            $shuadanUser[$key]['yichu_url'] = linkurl('clickfarm/yichu').'&cid='.$value['customer_id'];
            
         }
         $this->res['data']  = $shuadanUser;
         $this->res['home_url']  = linkurl('common/index');
         $this->res['goods_url']  = linkurl('clickfarm/productlist');
         $this->res['shudan_user_url']  = linkurl('clickfarm/userlist');
         $this->res['add_user_url']  = linkurl('clickfarm/addUser');
         $this->res['upimg_url']  = linkurl('clickfarm/upload_img');
         $this->res['edit_url']  = linkurl('clickfarm/edit');
         $this->res['pagestr']  = $pagestr;
         return $this->res;

      }
      //获取总的刷单用户数(未移除的，即显示的)
      function getShuadanerNum(){
         $sql= "select count(customer_id) as shua_user_num from hb_customer where is_shuadan = 1 and is_display = 1 ";
         $info = getRow($sql);
         return $info['shua_user_num'];
      }
      //根据customer_id 统计评论总数
      function remark_num($customer_id){
         $sql= "select count(customer_id) as remark_num from hb_review where customer_id = '".$customer_id."'";
         $info = getRow($sql);
         return $info['remark_num'];
      }
      //添加刷单用户
      function addUser(){
         $maxid = $this->getMaxid()+1;
         if(preg_match("/\[/",$_POST['headurl'])){
            $imgArr = json_decode($_POST['headurl']);
            $headurl = $imgArr[0];
            $date_added = date('Y-m-d H:i:s');
            $table_name = DB_PREFIX.'customer';
            $sql = " insert into ".$table_name." (customer_id,headurl,firstname,date_added,is_shuadan,is_display,status) 
                     values ('".$maxid."','".$headurl."','".$_POST['firstname']."','".$date_added."','1','1','1')"; 
            exeSql($sql);
            echo"<script>alert('用户添加成功！');history.go(-1);</script>";
            die;
         }else{
            echo"<script>alert('用户添加失败！');history.go(-1);</script>";
            die;
         }
      }
      //查出数据库中customer_id 的最大值
      function getMaxid(){
         $sql = "select max(customer_id) as maxid from hb_customer where 1 ";
         $info = getRow($sql);
         return $info['maxid'];
      }
      //是否禁用用户
      function disabled(){
         $customer_id   = isset($_GET['cid'])?$_GET['cid']:null;
         $status       = isset($_GET['status'])?$_GET['status']:null;
         if(!empty($customer_id) && isset($status)){
            $table_name = DB_PREFIX.'customer';
            if($status==0){
               $sql = "update ".$table_name." set status = 1 where customer_id = '".$customer_id."'";
            }else{
               $sql = "update ".$table_name." set status = 0 where customer_id = '".$customer_id."'";
            }
            exeSql($sql);
            echo"<script>alert('修改成功！');history.go(-1);</script>";
            die;
         }else{
            echo"<script>alert('修改失败！');history.go(-1);</script>";
            die;
         }   
      }
      //是否移除用户
      function yichu(){
         $customer_id   = isset($_GET['cid'])?$_GET['cid']:null;
         if(!empty($customer_id)){
            $table_name = DB_PREFIX.'customer';
            $sql = "update ".$table_name." set is_display = 0 where customer_id = '".$customer_id."'";
            exeSql($sql);
            echo"<script>alert('移除成功！');history.go(-1);</script>";
            die;
         }else{
            echo"<script>alert('移除失败！');history.go(-1);</script>";
            die;
         }
      }
      //用户信息编辑
      function edit(){
         if(!empty($_POST['customer_id'])){
            if(preg_match("/\[/",$_POST['headurl'])){
               $imgArr = json_decode($_POST['headurl']);
               $headurl = $imgArr[0];
            }else{
               $headurl = $_POST['headurl'];
            }
            $sql = "update hb_customer set firstname = '".$_POST['firstname']."',headurl = '".$headurl."' 
                    where customer_id = '".$_POST['customer_id']."'";
            exeSql($sql);
            echo"<script>alert('编辑成功！');history.go(-1);</script>";
            die; 
         }else{
            echo"<script>alert('编辑失败！');history.go(-1);</script>";
            die; 
         }  
      }
	

	
}

