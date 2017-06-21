<?php
	//面向对象的control 类
include "xcontrol/base.php";
//extends base
class user {
   	/**
   	 * ws  
   	 * 登录
   	 */
   	function login(){
   		if(isset($_SESSION["userid"]) && isset($_SESSION["username"]) ){
			//跳转
	   		$url=linkurl("common/index");
	   		redirect($url);
		}
		if($_POST){
			if(isset($_POST['username'])&&isset($_POST['password'])){
				$username = htmlspecialchars($_POST['username']); // 用户名

				$userdb=getRow("select salt,user_id,username,password,user_group_id,merchant_id from ".DB_PREFIX."user where username='$username' and status=1");
        if(!$userdb){
          $this->res['error']="该账号不存在或者已禁用！";
        }else{
          $salt=$userdb['salt'];//getRow("select salt from ".DB_PREFIX."user where username='$username'");
          $password =htmlspecialchars($_POST['password']);
          $password = escape(sha1($salt . sha1($salt . sha1($password))));
          # 拿接收过来的用户名和密码去数据库查找，看是否存在此用户名以及其密码
          //$data =getRow("select * from ".DB_PREFIX."user where username='$username' and password='$password'");
          if($password==$userdb['password']){
            $_SESSION['userid']=$userdb['user_id'];
                    $_SESSION['username']=$userdb['username'];
                    $_SESSION['user_group_id']=$userdb['user_group_id'];
                     $_SESSION['merchant_id']=$userdb['merchant_id'];
                   

              redirect(linkurl("common/index"));//跳转
          }else{
            $this->res['error']="用户名或密码错误";
          }
        }
				
			}else{
				$this->res['error']="用户名或密码不能为空";
			}
		}else{
			$this->res['loginurl']=linkurl("user/login");
		}
		return $this->res;
   	}
   	/**
   	 * cgl  12.29   注销
   	 */
   	function loginout(){
   		unset($_SESSION["userid"]);
   		unset($_SESSION["username"]);
   		//跳转
   		$url=linkurl("user/login");
   		redirect($url);
   	}

   	/*//xzh
   	function getList()
   	{
   		$this->getMenu();
   		$sql="SELECT `customer_id`,
    `customer_group_id`, 
    `firstname`,
    `lastname`,
    `email`,
    `telephone`,
    `cart`, 
    `ip`,
    `status`,
    `approved`, 
    `date_added`,
    `headurl`,
    `card`, 
    `isdisturb`,
    `sharetimes`,
    `remark`,
    `parent_id` 
	FROM `hb_customer` limit 0,20";
	$customer = getData($sql);
 	$this->res['customer']=$customer;
 	return $this->res;
   	}*/

}
?>