<?php
//推送管理
//面向对象的control 类
include "xcontrol/base.php";
// GeTui Push

$path = realpath(dirname(__FILE__).'/../../');
// echo $path.'/system/thirdlib/getuipush/' . 'push.php';die;
require_once($path.'/system/thirdlib/getuipush/' . 'push.php');
require_once($path. '/system/thirdlib/getuipush/' . 'IGt.Push.php');
require_once($path. '/system/thirdlib/getuipush/' . 'igetui/IGt.AppMessage.php');
require_once($path. '/system/thirdlib/getuipush/' . 'igetui/IGt.APNPayload.php');
require_once($path. '/system/thirdlib/getuipush/' . 'igetui/template/IGt.BaseTemplate.php');
require_once($path. '/system/thirdlib/getuipush/' . 'IGt.Batch.php');
require_once($path. '/system/thirdlib/getuipush/' . 'igetui/utils/AppConditions.php');

class pushes extends base
{
	

	function __construct() 
	{
       parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}
   	//推送列表
	function getList(){
		$this->getMenu();
        $merchant_id = $this->getMerchantId();
        if(isset($_GET['page'])){
            $page=$_GET['page'];
            if($page<1)$page=1;
        }else{
            $page=1;
        }
        //每页显示条数
        $per_num = 14;  
        $start=($page-1)*$per_num;
        $wherestr="";
        //推送标题
        if(isset($_GET['title'])&&$_GET['title']){
            $this->res['title'] = $_GET['title'];
            $wherestr.=" and title like '%".trim($_GET['title'])."%'";
        }
        //推送内容
        if(isset($_GET['content'])&&$_GET['content']){
            $this->res['content'] = $_GET['content'];
            $wherestr.="  and content like '%".trim($_GET['content'])."%'";
        }

		$sql = "select * from hb_push_system where merchant_id='" .(int)$merchant_id. "' $wherestr ";
        $sql .= " order by push_id desc limit ".$start.",".$per_num."";

		$pushInfo = getData($sql);
        if(sizeof($pushInfo)<$per_num){
            $this->getPages($page,$page);
        }else{
            $this->getPages($page);
        }
        foreach ($pushInfo as $key => $value) {
            if($value['object']==1){
                $pushInfo[$key]['object'] = "全部用户";
           }elseif($value['object']==2){
                $pushInfo[$key]['object'] = "会员用户";
           }else{
                $pushInfo[$key]['object'] = "普通用户";
           }
           if($value['send_status']==1){
                $pushInfo[$key]['send_status'] = "已推送";
           }else{
                $pushInfo[$key]['send_status'] = "未推送";
           }
           // if(mb_strlen($value['content'])>60){
           //      $pushInfo[$key]['content'] = mb_substr($value['content'], 0,40,'utf-8').'....';
           // }
           // if(mb_strlen($value['title'])>60){
           //      $pushInfo[$key]['title'] = mb_substr($value['title'], 0,40,'utf-8').'....';
           // }
        }
        $this->res["pushlist"]=$pushInfo;
        $this->res["home_url"]=linkurl("common/index");
		$this->res["add_url"]=linkurl("pushes/addPush");
		$this->res["list_url"]=linkurl("pushes/getList");
        $this->res["del_url"] = linkurl("pushes/delPush");
		return $this->res;
	}
	//添加推送
	function addPush(){
		$this->getMenu();
        //商品分类
        $cateList = $this->getActiveCategory();

		if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){ 
			$id = isset($_POST['id'])?$_POST['id']:'';
            $type = isset($_POST['type'])?$_POST['type']:'';
            $title = isset($_POST['title'])?$_POST['title']:'';
			$object = isset($_POST['object'])?$_POST['object']:'';
			
			$time = isset($_POST['time'])?$_POST['time']:'';
            $link = isset($_POST['link'])?$_POST['link']:'';
            $content = isset($_POST['content'])?$_POST['content']:'';
			$send_status = isset($_POST['send_status'])?$_POST['send_status']:'';
            $name = '';
			if($type&&$title&&$object&&$content&&$send_status){
				if($type == 1){
                    $name = $this->getActiveName($id);
                    $type = 1;//打开活动
                }else if($type == 2){
                    $name = $this->getProductName($id);
                    $type = 0;//打开商品详情
                }else if($type == 3){
                    $type = 2;//打开H5链接
                }else{
                    $type = 3;//打开首页
                }

                $data = array(
                    'title'=>$title,
                    'content'=>$content,
                    'type'=>$type,
                    'object'=>$object,
                    'id'=>$id,
                    'name'=>$name,
                    'link'=>$link
                );
                $data['merchant_id'] = $this->getMerchantId();
                //是否是立即推送
                if($send_status == 1){
                    $data['time'] = date('Y-m-d H:i:s',time());        //推送时间
                    $data['add_time'] = date('Y-m-d H:i:s',time());    //编辑时间

                    $push = $this->gotoPush($data);
                    if($push){
                        $data['status'] = 1; 						  //立即推送
                        $data['send_status'] = 1; 					  //已推送
                        
                        saveData('hb_push_system',$data);
                        echo 1;exit;								  //推送成功
                    }else{
                        echo 2;exit;								  //推送失败
                    }
                }else{
                    $data['status'] = 0;   //定时推送
                    $data['send_status'] = 0;
                    $data['time'] = $time;
                    $data['add_time'] = date('Y-m-d H:i:s',time());  //编辑时间

                    saveData('hb_push_system',$data);
                    echo 1;exit;									//保存成功
                }
			}else{
				echo 2;    exit;          								//参数错误
			}
		}

        $this->res['catelist'] = $cateList;
		$this->res["home_url"] = linkurl("common/index");
		$this->res["add_url"]  = linkurl("pushes/addPush");
		$this->res["list_url"] = linkurl("pushes/getList");
		return $this->res;

	}
	function gotoPush($data){
    // $clide_id = array(
    //                   'e1c7538ae8e01711d19b3307b6d95e76',
    //                   '0aba26221f0663ca894461a20d5277b3',
    //                   'a36c121f86c1356c5bfc437eecbb6598',
    //                   // '8f1161290d8c5acd4e0517a7b01051ab',
    //                   // '225e620252eae9784101221c7f56ebac',
    //                   'a942e2160dc57602d02481815e4a7203'
    //                   );
     
        $clide_id_array = array();
        if($data['object'] == 2){
            $clide_id_array = $this->getClientid(2);
        }else if($data['object'] == 3){
            $clide_id_array = $this->getClientid(3);
        }else{
            $clide_id_array = $this->getClientid(1);
        }

        $passthrough = '透传消息，神奇吧';
        if($data['type'] == 3){
            $payload = json_encode(array(
                'pushtitle'=>$data['title'],
                'pushbody'=>$data['content'],
                'type'=>3
            ));
        }else if($data['type'] == 2){
            $payload = json_encode(array(
                'type'=>2,
                'title' => $data['name'],
                'pushtitle'=>$data['title'],
                'pushbody'=>$data['content'],
                'link'=>$data['link']

            ));
        }else if($data['type'] == 1){
            $payload = json_encode(array(
                'type'=>1,
                'title' => $data['name'],
                'pushtitle'=>$data['title'],
                'pushbody'=>$data['content'],
                'id'=>$data['id']
            ));
        }else{
            $payload = json_encode(array(
                'type'=>0,
                'title' => $data['name'],
                'pushtitle'=>$data['title'],
                'pushbody'=>$data['content'],
                'id'=>$data['id']
            ));
        }
        $push = new Push();

        if(!empty($clide_id_array)){
            $clide_id = array();
            $index = 1;
            foreach($clide_id_array as $key=>$v){
                if($v['clientid'] != ''){
                    $clide_id[] = $v['clientid'];
                }
            }
            $clide_array = array_chunk($clide_id, 1000);

            foreach($clide_array as $val){
                $result = $push->pushMessageToList($data['title'], $payload, $passthrough, $data['content'], $val);
            }
        //$result = $this->push->pushMessageToList($data['title'], $payload, $passthrough, $data['content'], $clide_id);
        }else{
            $result = $push->pushMessageToApp($data['title'], $payload, $passthrough, $data['content']);
        }

        if($result['result'] == 'ok') {
            return true;
        } else {
            return false;
        }
    }
    //删除推送记录
    function delPush(){
        if(!empty($_POST['tui'])){
            $pushArr = $_POST['tui'];
            $push_id = implode(',',$pushArr);
            $sql = "delete from hb_push_system where push_id in (" .$push_id. ")";
            exeSql($sql);
            echo "<script>alert('删除成功');history.go(-1)</script>";  die;//删除成功
        }else{
            echo "<script>alert('删除失败');history.go(-1)</script>";  die;//删除成功
        }
        
    }
    //获取活动的名称及ID
    function getActiveCategory(){
        $sql = "select c.category_id,cd.name from `" .DB_PREFIX. "category` c left join `" .DB_PREFIX. "category_description` cd on cd.category_id=c.category_id where c.type=1";
        $result = getData($sql);
        return $result;
    }
	//获取分类名称
	function getActiveName($category_id){
        $sql = "select name from `" .DB_PREFIX. "category_description` where category_id='" .(int)$category_id. "'";
        $name = getRow($sql);
        if(!empty($name)){
            return $name['name'];
        }else{
            return '';
        }
        
    }
    //获取商品名称
    function getProductName($product_id){
        $sql = "select name from `" .DB_PREFIX. "product_description` where product_id='" .(int)$product_id. "'";
        $name = getRow($sql);
        if(!empty($name)){
            return $name['name'];
        }else{
            return '';
        }
    }
    //根据userid获取merchant_id
	function getMerchantId(){
		$user_id = $_SESSION['userid'];
		$sql = "select merchant_id from hb_user where user_id = '".$user_id."'";
		$info = getRow($sql);
		return $info['merchant_id'];
	}
	//获取用户的clientid
	function getClientid($type){
        if($type == 1){
            $sql = "select DISTINCT clientid from `" .DB_PREFIX. "customer` where (merchant_id = 0 or merchant_id = 1) and status =1";
        }else if($type == 2){
            $sql = "select DISTINCT clientid from `" .DB_PREFIX. "customer` where merchant_id = 1 and status =1";
        }else{
            $sql = "select DISTINCT clientid from `" .DB_PREFIX. "customer` where merchant_id = 0 and status =1";
        }
        $clientid = getData($sql);
        return $clientid;
    }
}