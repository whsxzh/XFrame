<?php
use OSS\OssClient;
use OSS\Core\OssException;

include "xcontrol/base.php";
include "lib/tree.php";
include "xcontrol/product.php";

require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';

class catalog extends base{
    function __construct() 
    {
       parent::__construct();
       $this->passkey=@$_SESSION["default"]['passkey'];
       $this->customer_id=@$_SESSION["default"]['customer_id'];
    }
    //ws 货仓
    function warehouse(){
    	//菜单
    	$this->getMenu();
       $page=isset($_GET['page'])?$_GET['page']:1;
       if($page<1){
         $page=1;
       }
         $size=10;
      $start=($page-1)*$size;

       $userid=isset($_SESSION['userid'])?$_SESSION['userid']:null;
       $merchant_id=getRow("select merchant_id from ".DB_PREFIX."user where user_id=$userid");
       $id=implode('', $merchant_id);
       //仓库
       $house=getData("select warehouse_id as id,name,needauth from ".DB_PREFIX."warehouse where status=0 and merchant_id=$id ");
       $count=count($house);
       $house=getData("select warehouse_id as id,name,needauth from ".DB_PREFIX."warehouse where status=0 and merchant_id=$id limit ".$start.",".$size."");
       //编辑 删除 url
       $hediturl=linkurl("catalog/hedit");
       $this->res["hediturl"]=$hediturl;
       $hdelurl=linkurl("catalog/hdel");
       foreach ($house as $key => $value) {
           $house[$key]["hediturl"]=$hediturl;
           $house[$key]["hdelurl"]=$hdelurl;
       }

       if($count<$size)
        $this->getPages($page,1);
       else
        $this->getPages($page,ceil($count/$size));
       $this->res["warehouse"]=linkurl("catalog/warehouse");
       //$this->res["updateAd"]=linkurl("common/updateAdimage");
       $this->res['curpage']=$page;
       $this->res['house']=$house;
       return $this->res;
    }
    /*
    *货仓
    * ws 12.30
    */
    /*修改*/
    function hedit(){
        //菜单
    $this->getMenu();
      $userid=isset($_SESSION['userid'])?$_SESSION['userid']:null;
       $merchant_id=getRow("select merchant_id from ".DB_PREFIX."user where user_id=$userid");
       $id=implode('', $merchant_id);
      if($_POST){
          $_POST['date_added']=time();
          $_POST['merchant_id']=$id;
          $_POST['status']=0;//0表示存在*/
          if(!empty($_POST['name'])){
            $edt=saveData(DB_PREFIX."warehouse",$_POST);
             if($edt){
               $this->res["msg"]="编辑成功";
               redirect(linkurl("catalog/warehouse"));//跳转
             }else{
                $this->res["error"]="编辑失败";
             }
         }else{
          echo "<script>alert('名称不能为空');history.go(-1)</script>"; 
         }
      }else if(isset($_GET["id"])){
         $house=getRow("select warehouse_id,name,needauth from ".DB_PREFIX."warehouse where warehouse_id=".$_GET['id']);
         $this->res['id']=$house['warehouse_id'];
         $this->res['name']=$house['name'];
         $this->res['needauth']=$house['needauth'];
      }
      $this->res["editurl"]=linkurl("catalog/hedit");
      return $this->res;
    } 
     /*
    * 删除货仓 ws 12.31
    * 假删除，1表示删除，0表示没有删除
    */
    function hdel(){
        if(isset($_GET["id"])){
            $status='1';
          $del=saveData(DB_PREFIX."warehouse",array('warehouse_id'=>$_GET['id'],"status"=>$status));
          if($del){
            echo "<script>alert('删除成功');history.go(-1)</script>"; 
           //redirect(linkurl("manufacturer/getList"));//跳转
          }else{
            return false;
          }
        }else{
          return false;
        }
        die;
    }
     /***************************************/
    //ws 品牌
    function manufacturer(){
        //菜单
        $this->getMenu();
      $page=isset($_GET['page'])?$_GET['page']:1;
      if($page<1){
        $page=1;
      }
      $size=20;
      $start=($page-1)*$size;
       $userid=isset($_SESSION['userid'])?$_SESSION['userid']:null;
       $merchant_id=getRow("select merchant_id from ".DB_PREFIX."user where user_id=$userid");
       $id=implode('', $merchant_id);
       //品牌
       $man=getData("select manufacturer_id as id,name,status from ".DB_PREFIX."manufacturer where status=0 and merchant_id=$id limit ".$start.",".$size."");
       $total=getRow("select count(*) as count from ".DB_PREFIX."manufacturer where status=0 and merchant_id=$id",600);

       //编辑 删除 url
       $editurl=linkurl("catalog/edit");
       $this->res["editurl"]=$editurl;
       $delurl=linkurl("catalog/del");
       foreach ($man as $key => $value) {
           $man[$key]["editurl"]=$editurl;
           $man[$key]["delurl"]=$delurl;
       }

        $total=$total['count'];
        $total_page = ceil($total/20);
        $this->res['is_end_page'] = 1;
        if($page == $total_page){
          $this->res['is_end_page'] = 0;
        }

        $this->getPages($page,$total_page);

       $this->res['man']=$man;
       return $this->res;
    }
    /*
    * 商品品牌
    *编辑 ws 12.30
    */
    function edit(){
  		//菜单
	    $this->getMenu();

     $userid=isset($_SESSION['userid'])?$_SESSION['userid']:null;
     $merchant_id=getRow("select merchant_id from ".DB_PREFIX."user where user_id=$userid");
     $id=implode('', $merchant_id);
      if($_POST){
        $_POST['sort_order']=0;
        $_POST['merchant_id']=$id;
        $_POST['status']=0;//0表示存在
        if(!empty($_POST['name'])){
             $edt=saveData(DB_PREFIX."manufacturer",$_POST);
             if($edt){
               $this->res["msg"]="编辑成功";
               redirect(linkurl("catalog/manufacturer"));//跳转
             }else{
                //echo "<script>alert('名称不能为空');history.go(-1)</script>"; 
                $this->res["error"]="编辑失败";
             }
         }else{
            echo "<script>alert('名称不能为空');history.go(-1)</script>"; 
         }
      }else if(isset($_GET["id"])){
         $man=getRow("select manufacturer_id,name from ".DB_PREFIX."manufacturer where manufacturer_id=".$_GET['id']);
         $this->res['id']=$man['manufacturer_id'];
         $this->res['name']=$man['name'];
      }
      
       $this->res["editurl"]=linkurl("catalog/edit");
       return $this->res;
    }
    /*
    * 删除品牌 ws 12.30
    * 假删除，1表示删除，0表示没有删除
    */
    function del(){
        if(isset($_GET["id"])){
            $status='1';
          $del=saveData(DB_PREFIX."manufacturer",array('manufacturer_id'=>$_GET['id'],"status"=>$status));
          if($del){
            echo "<script>alert('删除成功');history.go(-1)</script>"; 
           //redirect(linkurl("manufacturer/getList"));//跳转
          }else{
            return false;
          }
        }else{
          return false;
        }
        die;
    }
    /***************************************/
    /*
    * ws 分类 
    */
    function category(){
      //var_dump($_SESSION['image_category_id']);exit();
      //菜单
     $this->getMenu();

     $tree=new product();
     $getTree=$tree->getCat();
     if(!empty($getTree)){
      foreach ($getTree as $key => $value) {
        $getTree[$key]["image"]=admindefault($value["image"]);
        if(isset($getTree[$key]['son'])){
            foreach ($getTree[$key]['son'] as $key1 => $value1) {

            $getTree[$key]['son'][$key1]['image']=admindefault($value1["image"]);
            
                if(isset($getTree[$key]['son'][$key1]['son'])){
                    foreach ($getTree[$key]['son'][$key1]['son'] as $key2 => $value2) {
                    $getTree[$key]['son'][$key1]['son'][$key2]['image']=admindefault($value2["image"]);
                }
            }
          }
        }
        
      }
     }
     //var_dump($getTree);exit();
     $this->res['cate']=$getTree;
     //添加分类
     $this->res["addcategory"]=linkurl("catalog/addcategory");
     //删除分类  修改为 0
     $this->res["deletecategory"]=linkurl("catalog/cdel");
     return $this->res;
    }
    /**
     * 添加分类 cgl 2017-2-8  编辑 2017-2-9
     */
    function addcategory(){
      if($_POST["type"]=="add"){
        //增加
          $id=$_SESSION['userid'];
          $user=getRow("select * from ".DB_PREFIX."user where user_id='$id'");
          $merchant_id=$user['merchant_id'];
          if($_POST["parent"]==0){
            $parent_id=0;
          }else{
            $parent_id=$_POST["parent"];
          }
          if(empty($_POST["sort"])){
            $_POST["sort"]=1;
          }
          $data=array(
            "sort_order"=>$_POST["sort"],
            "parent_id"=>$parent_id,
            "merchant_id"=>$merchant_id,
            "type"=>0,
            "top"=>0,
            "column"=>0,
            "date_added"=>date("Y-m-d H:i:s",time()),
            "date_modified"=>date("Y-m-d H:i:s",time()),
            "status"=>1,
            "points"=>0,
            "display_mode"=>0,
            "store_id"=>0,
            "image"=>"/"
          );
        saveData(DB_PREFIX."category",$data);
        $id=getLastId();
        $_SESSION['image_category_id']=$id;
        //var_dump($id);exit();
        $data1=array(
          "category_id"=>$id,
          "language_id"=>0,
          "name"=>$_POST["cat"],
          "description"=>"",
          "meta_title"=>"",
          "meta_description"=>"",
          "meta_keyword"=>"",
        );
        //$sql=saveData(DB_PREFIX."category_description",$data1);
        $sql="insert into ".DB_PREFIX."category_description (`category_id`, `language_id`, `name`) values('".$id."',2,'".$_POST['cat']."')";
        exeSql($sql);
      }else if($_POST["type"]="update"){
        //修改
        saveData(DB_PREFIX."category",array("category_id"=>$_POST["id"],"sort_order"=>$_POST["sort"],"image"=>$_POST["photo"]));
        exeSql("update ".DB_PREFIX."category_description set name='".$_POST["cat"]."' where category_id= ".$_POST["id"]);
      }
      die;
    }

    /*
    *图片上传
    */
    function saveImage(){
       if(!empty($_FILES['headurl']['name'])){
          $file = $_FILES;
          // var_dump($file);exit();
          $headurl = $this->upload_img($file);
          $headurl = $headurl[0];
        }else{
          $headurl = '';
        }
        if(isset($_POST['category_id'])){
            if($headurl!=null){
                //编辑
              $_POST['category_id']=(int)$_POST['category_id'];
              $sql="update ".DB_PREFIX."category set image='".$headurl."' where category_id='".$_POST['category_id']."'";
              exeSql($sql);
            }
        }else{
          if($headurl!=null){
                //编辑
              $category_id=(int)$_SESSION['image_category_id'];
              $sql="update ".DB_PREFIX."category set image='".$headurl."' where category_id='".$category_id."'";
              exeSql($sql);
            }
        }

       echo "<script> location.href='xindex.php?m=catalog&act=category' </script>";
       die();
    }

    //上传分类图片
    function upload_img($_FILE){
        // if(isset($_FILES['Filedata'])) { 
            $file = $_FILE['headurl'];
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

    /*
    * 删除
    */
    function cdel(){
      if(isset($_POST["id"])){
        $status='0';
        $del=saveData(DB_PREFIX."category",array('category_id'=>$_POST['id'],"status"=>$status));
        die;
      }
    }
    /**
     * 添加新商品 cgl 2017-1-1  
     */
    function addnewgoods(){
      //菜单
      $this->getMenu();
      //注销的链接
      $url=linkurl("user/loginout");
      $this->res['loginouturl']=$url;
      //首页的链接
      $this->res['index']=$url=linkurl("common/index");
            


      return $this->res;
    }
    /**
     * cgl 规格管理  2017-2-8
     */
    function option(){
       //菜单
      $this->getMenu();

      return $this->res;
    }
    

}
