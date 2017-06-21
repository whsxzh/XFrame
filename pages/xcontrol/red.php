<?php
use OSS\OssClient;
use OSS\Core\OssException;

  //面向对象的control 类
include "xcontrol/base.php";
include "xcontrol/product.php";

require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';

class red extends base
{
  
    /**
     * 红包管理界面
     * zxx 2017-5-26 
     */
    function packetList(){
       $this->getMenu();
       if($_POST){
         //获取图片
          if(!empty($_FILES['headurl']['name'])){
            $file = $_FILES;
             //var_dump($file);exit();
            $headurl = $this->upload_img($file);
            $_POST['image'] = $headurl[0];
          }else{
            $_POST['image']="";
          }
          //保存信息
          if($_POST['redpacket_id']){
            //var_dump($_POST);exit;
            saveData("hb_redpacket",$_POST);
          }else{
            if(getRow("select * from hb_redpacket where name='".$_POST['name']."'")){

            }else{
                saveData("hb_redpacket",$_POST);
                $id=getLastId();
                if($_SERVER['HTTP_HOST']=='test.haiqihucoang.com'){
                   $url = 'http://test.haiqihuocang.com/web/red_packet/new.html';
                }else{
                   $url = 'http://www.haiqihuocang.cn/web/red_packet/new.html';
                }
                
                $_POST['redpacket_id']=$id;
                $_POST['url']=$url;
                saveData("hb_redpacket",$_POST);
            } 
          }
       }
       //找出所有的红包列表
       $red_info=getData("select * from hb_redpacket ");
       $this->res['data']=$red_info;
       $this->res['edit']=linkurl("red/add");
       $this->res['updateStatus']=linkurl("red/updateStatus");
       $this->res['packetList']=linkurl("red/packetList");
       return $this->res;
    }

    /**
     * 红包新增界面
     * zxx 2017-5-26 
     */
    function add(){
       $this->getMenu();
       if(isset($_GET['redpacket_id'])){
          //编辑
          $redpacket_id=isset($_GET['redpacket_id'])?$_GET['redpacket_id']:0;
          $red_info=getRow("select * from hb_redpacket where redpacket_id='".$redpacket_id."'");
          $this->res['id']=$redpacket_id;
          $this->res['data']=$red_info;
          $this->res['title']="编辑现金红包";
       }else{
          //新增
          $this->res['title']="新增现金红包";
          $this->res['data']['date_start']="2017-05-26 00:00:00";
          $this->res['data']['date_end']="2017-05-26 00:00:00";
       }
       $this->res['packetList']=linkurl("red/packetList");
       return $this->res;
    }

    /**
     * 更改红包状态
     * zxx 2017-5-26
     */
    function updateStatus(){
      $res=getRow("select status from ".DB_PREFIX."redpacket where redpacket_id ='".$_POST['redpacket_id']."'");
      $status=$res['status'];
      //状态是0时，修改为1，为1时，修改为0
      if($status == 1){
        $data=array('redpacket_id'=>$_POST['redpacket_id'],'status'=>0);      
        $status=saveData(DB_PREFIX."redpacket",$data);
        if($status ){
            echo "disable";
        }
      }elseif($status == 0){
        $data=array('redpacket_id'=>$_POST['redpacket_id'],'status'=>1);
        $status=saveData(DB_PREFIX."redpacket",$data);
        if($status ){
            echo "enable";
        }
      }
      exit();
    }



    /**
     * 上传图片
     * zxx 2017-5-26
     */
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
    

}