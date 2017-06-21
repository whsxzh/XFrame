<?php

//面向对象的control 类
include "xcontrol/base.php";
include "lib/pagination.php";
class merchant extends base
{
	
	function __construct() 
	{
        parent::__construct();
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}
   	//企业列表
   	function merchantList(){
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
         //排序条件
         if(isset($_GET['sale_total'])&&$_GET['sale_total'] !=0){
            $this->res['sale_total'] = $_GET['sale_total'];
            switch ($_GET['sale_total']) {
               case '1':
                  $sortstr = " order by c.sale_total asc ";
                  break;
               case '2':
                  $sortstr = " order by c.sale_total desc ";
                  break;   
            }
         }else{
            $this->res['sale_total'] = 0;
         }
         //企业名称
         if(isset($_GET['merchant_name'])&&$_GET['merchant_name']){
            $this->res['mer_name'] = $_GET['merchant_name'];
            $wherestr.=" and a.merchant_name like '%".trim($_GET['merchant_name'])."%'";
         }
         //账号
         if(isset($_GET['username'])&&$_GET['username']){
            $this->res['username'] = $_GET['username'];
            $wherestr.=" and b.username like '%".trim($_GET['username'])."%'";
         }
   		$sql = "select a.merchant_id,a.merchant_name,a.mer_remark,b.username,b.status,b.user_id,c.sale_total
   				from hb_merchant as a 
   				left join hb_user as b on a.user_id = b.user_id
   				left join (select sum(total) as sale_total,merchant_id from hb_order group by merchant_id ) as c 
   				on c.merchant_id = a.merchant_id where 1 ".$wherestr."";

         if(isset($_GET['sale_total'])&&$_GET['sale_total'] !=0){
            $sql .= $sortstr;
         }else{
            $sql .= " order by a.merchant_id desc ";
         }

         $sql .= " limit ".$start.','.$per_num." ";

   		$info = getData($sql);

         if(sizeof($info)<$per_num){
            $this->getPages($page,$page);
         }else{
            $this->getPages($page);
         }

   		foreach ($info as $key => $value) {
	   		if(!empty($value['sale_total'])){
				$info[$key]['sale_total'] = sprintf("%.2f",$value['sale_total']);
   			}else{
   				$info[$key]['sale_total'] =0;									//商户销售额
   			}
            if($value['status']==1){
               $info[$key]['status_type'] = "禁用";                     //已启用
            }else{
               $info[$key]['status_type'] = "启用";                     //已禁用
            }
   			$info[$key]['goodsList_url'] = linkurl('merchant/goodsList').'&mid='.$value['merchant_id'].'&re_page='.$page;
            $info[$key]['info_url'] = linkurl('merchant/userInfo').'&mid='.$value['merchant_id'].'&re_page='.$page;
            $info[$key]['invitecode_url'] = linkurl('merchant/codeList').'&mid='.$value['merchant_id'].'&re_page='.$page;
		   }
   		$this->res['data'] = $info;
         $this->res['addMer_url'] = linkurl('merchant/userInfo').'&re_page='.$page;
         $this->res['forbidden_url'] = linkurl('merchant/forbidden');
         $this->res['home_url'] = linkurl('common/index');
         $this->res['qiye_url'] = linkurl('merchant/merchantList');
   		return $this->res;
   	}
   	//企业的商品列表
   	function goodsList(){
   		$this->getMenu();
   		$merchant_id = isset($_GET['mid'])?$_GET['mid']:null;
         $this->res['mid'] = $merchant_id;

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
         //排序条件
         if(isset($_GET['sales'])&&$_GET['sales'] !=0){
            $this->res['sales'] = $_GET['sales'];
            switch ($_GET['sales']) {
               case '1':
                  $sortstr = " order by a.sales asc ";
                  break;
               case '2':
                  $sortstr = " order by a.sales desc ";
                  break;   
            }
         }else{
            $this->res['sales'] = 0;
         }
         //商品名称
         if(isset($_GET['name'])&&$_GET['name']){
            $this->res['name'] = $_GET['name'];
            $wherestr.=" and b.name like '%".trim($_GET['name'])."%'";
         }
         
   		$sql = "select a.price,a.sales,a.quantity,b.name 
   				from hb_product as a 
   				inner join hb_product_description as b on a.product_id = b.product_id
   				inner join hb_merchant as c on c.merchant_id = a.merchant_id 
   				where c.merchant_id = '".$merchant_id."' ".$wherestr." ";
         if(isset($_GET['sales'])&&$_GET['sales'] !=0){
            $sql .= $sortstr;
         }

         $sql .= "limit ".$start.','.$per_num."";
         $list = getData($sql);

         if(sizeof($list)<$per_num){
            $this->getPages($page,$page);
         }else{
            $this->getPages($page);
         }
         if(!empty($_GET['re_page'])){
            $re_page = $_GET['re_page'];
         }else{
            $re_page = '';
         }
         $this->res['re_page'] = $re_page;
   		$this->res['data'] = $list;
         $this->res['home_url'] = linkurl('common/index');
         $this->res['qiye_url'] = linkurl('merchant/merchantList');
         $this->res['back_url'] = linkurl('merchant/merchantList').'&page='.$re_page;
   		return $this->res;
   	}
      //企业信息
      function userInfo(){
         $this->getMenu();
         if(!empty($_GET['re_page'])){
            $re_page = $_GET['re_page'];
         }else{
            $re_page = '';
         }
         if(isset($_GET['mid'])&&!empty($_GET['mid'])){
            $mid = $_GET['mid'];
            $sql = "select a.merchant_id as mid,a.merchant_name as mer_name,a.user_id,a.mer_remark,a.mer_phone,
                     a.mer_address,b.username,b.user_group_id
                     from hb_merchant as a left join hb_user as b on a.user_id = b.user_id
                     where a.merchant_id = '".$mid."'";
            $this->res+=getRow($sql);
         }
         $groupList = $this->getAllRoles();
         $this->res['grouplist'] = $groupList;
         $this->res['re_page'] = $re_page;
         $this->res['form_url'] = linkurl('merchant/saveUserInfo');
         return $this->res;
      }
      //保存企业信息
      function saveUserInfo(){
         $url = linkurl('merchant/merchantList').'&page='.$_POST['re_page'];
         if(!empty($_POST['merchant_id'])){
            $user_id = $_POST['user_id'];
            if(!empty($user_id)){
               if(empty($_POST['password'])){
                  $sql = "update hb_user set username = '".$_POST['username']."',user_group_id = '".$_POST['user_group_id']."' where user_id = '".$user_id."'";
                  exeSql($sql);
               }else{
                  $salt = $this->getSalt($user_id);
                  $password = sha1($salt . sha1($salt . sha1($_POST['password'])));
                  $sql2 = "update hb_user set username = '".$_POST['username']."',password = '".$password."',user_group_id = '".$_POST['user_group_id']."' where user_id = '".$user_id."'";
                  exeSql($sql2);
               }
            }
            $table = 'hb_merchant';
            saveData($table,$_POST);
            echo "<script>alert('保存成功');window.location.href='".$url."';</script>";
            die;
         }else{
            $salt = token(9);
            $password = sha1($salt . sha1($salt . sha1($_POST['password'])));
            $date_created = time();
            $date_added = date('Y-m-d h:i:s');

            $_POST['merchant_name'] = isset($_POST['merchant_name'])?$_POST['merchant_name']:null;
            $_POST['mer_remark'] = isset($_POST['mer_remark'])?$_POST['mer_remark']:null;
            $_POST['user_group_id'] = isset($_POST['user_group_id'])?$_POST['user_group_id']:null;
            $_POST['mer_phone'] = isset($_POST['mer_phone'])?$_POST['mer_phone']:null;
            $_POST['mer_address'] = isset($_POST['mer_address'])?$_POST['mer_address']:null;
            $_POST['merchant_type'] = isset($_POST['merchant_type'])?$_POST['merchant_type']:null;

            $sql3 = "insert into hb_merchant (merchant_name,mer_phone,mer_remark,mer_address,date_created,merchant_type) 
                     values ('".$_POST['merchant_name']."','".$_POST['mer_phone']."','".$_POST['mer_remark']."','".$_POST['mer_address']."','".$date_created."','".$_POST['merchant_type']."')";
            exeSql($sql3);
            $mid = getLastId();

            $new_userid = $this->getMaxUserid();
            $new_userid = $new_userid+1;
            $sql4 = "insert into hb_user (user_id,user_group_id,username,salt,password,date_added) 
                     values ('".$new_userid."','".$_POST['user_group_id']."','".$_POST['username']."','".$salt."','".$password."','".$date_added."')";
            exeSql($sql4);
            
          
            $sql5 = "update hb_merchant set user_id = '".$new_userid."' where merchant_id = '".$mid."'";
            exeSql($sql5);

            $sql6 = "update hb_user set merchant_id = '".$mid."' where user_id = '".$new_userid."'";
            exeSql($sql6);

            echo "<script>alert('保存成功');window.location.href='".$url."';</script>";
            die;
         }

      }
      //是否禁用企业账号(user表status)
      function forbidden(){
         if(isset($_POST['type']) && !empty($_POST['userid'])){
            if($_POST['type'] == 1){
               $status = 0;
            }else{
               $status = 1;
            }
            $sql = "update hb_user set  status = '".$status."' where user_id = '".$_POST['userid']."'";
            exeSql($sql);
            echo 1;exit;      //修改成功
         }else{
            echo 2;exit;      //修改失败
         }
      }
      //企业邀请码管理
      function codeList(){
         $this->getMenu();
         $mid = isset($_GET['mid'])?$_GET['mid']:null;

         if(isset($_GET['page'])){
            $page=$_GET['page'];
            if($page<1)$page=1;
         }else{
            $page=1;
         }
         //每页显示条数
         $per_num = 15;  
         $start=($page-1)*$per_num;

         $wherestr = '';
         //邀请码
         if(isset($_GET['invitecode'])&&$_GET['invitecode']){
            $this->res['invitecode'] = $_GET['invitecode'];
            $wherestr.=" and invitecode like '%".trim($_GET['invitecode'])."%'";
         }
         if(!empty($mid)){
            $sql = "select invitecode,invitecode_id,customer_id,telephone,status,end_date,date_added,url,times from 
                     hb_invitecode where merchant_id = '".$mid."' ".$wherestr." order by invitecode_id desc ";
            $sql .= "limit ".$start.','.$per_num."";

            $info = getData($sql);
            
            if(sizeof($info)<$per_num){
               $this->getPages($page,$page);
            }else{
               $this->getPages($page);
            }
            foreach ($info as $key => $value) {
               if($value['status']==0){
                  $info[$key]['is_status'] = '有效';
               }else{
                  $info[$key]['is_status'] = '无效';
               }
               if($value['status']==0){
                  $info[$key]['status_type'] = "禁用";                     //已启用
               }else{
                  $info[$key]['status_type'] = "启用";                     //已禁用
               }
            }
            $this->res['codelist'] = $info;
         }
         if(!empty($_GET['re_page'])){
            $re_page = $_GET['re_page'];
         }else{
            $re_page = '';
         }
         $this->res['re_page'] = $re_page;
         $this->res['mid'] = $mid;
         $this->res['form_url'] = linkurl('merchant/saveInviteInfo');
         $this->res['stop_url'] = linkurl('merchant/forbiddenInvite');
         $this->res['edit_url'] = linkurl('merchant/getInviteInfo');
         $this->res['back_url'] = linkurl('merchant/merchantList').'&page='.$re_page;
         return $this->res;
      }
      //是否禁用邀请码(invitecode表status)
      function forbiddenInvite(){
         if(isset($_POST['type']) && !empty($_POST['invitecode_id'])){
            if($_POST['type'] == 1){
               $status = 0;
            }else{
               $status = 1;
            }
            $sql = "update hb_invitecode set  status = '".$status."' where invitecode_id = '".$_POST['invitecode_id']."'";
            exeSql($sql);
            echo 1;exit;      //修改成功
         }else{
            echo 2;exit;      //修改失败
         }
      }
      //保存邀请码信息
      function saveInviteInfo(){
         $table_name = "hb_invitecode";
         $invitecode = isset($_POST['invitecode'])?$_POST['invitecode']:null;
         $customer_no = isset($_POST['customer'])?$_POST['customer']:null;
         $merchant_id = isset($_POST['merchant_id'])?$_POST['merchant_id']:null;
        
         if(!empty($_POST['end_date'])){
            $_POST['end_date'] = date('Y-m-d H:i:s',strtotime($_POST['end_date']));
         }
         $customer_id = $this->judgeMerCustomer($merchant_id,$customer_no);
         
         if(!empty($customer_no)){
            if($customer_id>0){
               $_POST['customer_id'] = $customer_id;
               if(!empty($_POST['invitecode_id'])){
                  saveData($table_name,$_POST);
                  echo 1;exit;                  //编辑成功
               }else{
                  $invitecode_id = $this->judgeInvitecode($invitecode);
                  if($invitecode_id>0){
                     echo 4;exit;                           //该邀请码已存在
                  }
                  $_POST['date_added'] = date('Y-m-d H:i:s');
                  saveData($table_name,$_POST);
                  echo 1;exit;
               }
            }else{
               echo 2;exit;                  //该企业下无此会员
            }
         }else{
            echo 3;exit;                     //会员账号为空
         }
         
      }
      //根据邀请码id查询邀请码信息
      function getInviteInfo(){
         $invitecode_id = $_POST['invitecode_id'];
         $sql = "select a.invitecode,a.invitecode_id,a.customer_id,a.telephone,a.address,
                        a.status,a.end_date,a.date_added,a.url,a.remark,b.telephone as account
                from hb_invitecode as a left join hb_customer as b on a.customer_id = b.customer_id 
                where a.invitecode_id = '".$invitecode_id."' ";
         $info = getRow($sql);
         if(!empty($info)){
            print_r(json_encode($info));exit;
         }else{
            echo 1;exit;                  //无此邀请码
         }   
      }
      //判断邀请码是否已存在
      function judgeInvitecode($invitecode){
         $sql = "select invitecode_id from hb_invitecode where invitecode = '".$invitecode."'";
         $info = getRow($sql);
         if(!empty($info['invitecode_id'])){
            return $info['invitecode_id'];exit;             //该邀请码已存在
         }else{
            return 0;exit;             //无此邀请码
         }   
      }
      //判断会员账号是否属于该企业
      function judgeMerCustomer($merchant_id,$tel){
         $sql = "select customer_id from hb_customer where merchant_id = '".$merchant_id."' and telephone = '".$tel."'";
         $info = getRow($sql);
         if(!empty($info['customer_id'])){
            return $info['customer_id'];exit;
         }else{
            return 0;exit;             //该企业下无此会员
         }
      }
      //获取hb_user对应的salt
      function getSalt($user_id){
         $sql = "select salt from hb_user where user_id = '".$user_id."'";
         $info = getRow($sql);
         return $info['salt'];
      }
      //获取所有角色
      function getAllRoles(){
         $mid = $this->getMerchantId();
         $sql = "select user_group_id,name from hb_user_group where merchant_id = '".$mid."'";
         $roleInfo = getData($sql);
         return $roleInfo;
      }
      //根据userid获取merchant_id
      function getMerchantId(){
         $user_id = $_SESSION['userid'];
         $sql = "select merchant_id from hb_user where user_id = '".$user_id."'";
         $info = getRow($sql);
         return $info['merchant_id'];
      }
      //查询hb_user表中user_id的最大值
      function getMaxUserid(){
         $sql = "select max(user_id) as user_id from hb_user where 1";
         $maxid = getRow($sql);
         return $maxid['user_id'];
      }
      //
	  

	
}

