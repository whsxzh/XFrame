<?php
use OSS\OssClient;
use OSS\Core\OssException;
//面向对象的control 类
include "xcontrol/base.php";
include "lib/pagination.php";

require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';
class customer extends base
{
	



	function __construct() 
	{
       parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}

	

		//xzh
   	function getList()
   	{
   		$this->getMenu();

   		$page=1;

   		if(isset($_GET['page'])){
   			$page=$_GET['page'];
   			if($page<1){
   				$page=1;
   			}
   		}

   		$start=($page-1)*20;


   		if(isset($_POST['firstname']))
   			saveData("hb_customer",$_POST);

   		$wherestr="";
   		if(isset($_GET['merchant_id'])){ 
   			@$this->res['merchant_id']=$_GET['merchant_id'];
   			//var_dump($this->res['merchant_id']);
   			if($_GET['merchant_id']==1){
   				$wherestr.=" and c.merchant_id=0";//普通用户
   			}elseif($_GET['merchant_id']==2){
   				$wherestr.=" and c.merchant_id>0 and c.proxy_status=0";//普通会员
   			}elseif($_GET['merchant_id']==3){
   				$wherestr.=" and c.merchant_id>0 and c.proxy_status=1";//企业会员
   			}
   			
   		}

   		if(isset($_GET['firstname'])&&$_GET['firstname']){
   			$wherestr.=" and c.firstname like '%".trim($_GET['firstname'])."%'";
   			@$this->res['firstname']=$_GET['firstname'];
   		}

   		if(isset($_GET['lastname'])&&$_GET['lastname']){
   			$wherestr.=" and c.lastname like '%".trim($_GET['lastname'])."%'";
   			@$this->res['lastname']=$_GET['lastname'];
   		}

   		if(isset($_GET['telephone'])&&$_GET['telephone']){
   			$wherestr.=" and c.telephone like'%".trim($_GET['telephone'])."%'";
   			@$this->res['telephone']=$_GET['telephone'];
   		}

   		$sql="SELECT c.`customer_id`,
   		i.invitecode,
   		c.proxy_status,
   		c.merchant_id,
	    c.`firstname`,
	    c.`lastname`,
	    c.`email`,
	    c.`telephone`,
	    c.`cart`, 
	    c.`ip`,
	    c.`status`,
	    c.`approved`, 
	    c.`date_added`,
	    c.`headurl`,
	    c.`card`, 
	    c.`isdisturb`,
	    c.`sharetimes`,
	    c.`remark`,
	    p.firstname as parent_name,
	    c.parent_id 
		FROM (`hb_customer` as c,hb_customer_group_description as g) left join `hb_customer` as p on c.parent_id=p.customer_id left join hb_invitecode as i on i.invitecode_id=c.invitecode_id where c.customer_group_id=g.customer_group_id $wherestr order by c.customer_id desc limit $start,20";
		$customer = getData($sql);

		/*计算数量*/

		$sql1="SELECT count(*) as count
				FROM (`hb_customer` as c,hb_customer_group_description as g) left join `hb_customer` as p on c.parent_id=p.customer_id where c.customer_group_id=g.customer_group_id $wherestr order by c.customer_id desc ";
		$total = getRow($sql1,60);
		//var_dump($customer1);
  		$total=$total['count'];
        $total_page = ceil($total/20);
        $this->res['is_end_page'] = 1;
        if($page == $total_page){
          $this->res['is_end_page'] = 0;
        }

        $this->getPages($page,$total_page);

		/*计算数量*/
		foreach ($customer as $key => $value) {
			if($value['status']==1){
				$customer[$key]['status_type'] = "禁用";							//已启用
			}else{
				$customer[$key]['status_type'] = "启用";  							//已禁用
			}

			if($value['proxy_status']==1){
				$customer[$key]['huiyuan_type'] = "企业会员";
				$customer[$key]['change_type'] = "转为普通会员";
			}else{
				$customer[$key]['huiyuan_type'] = "普通会员";
				$customer[$key]['change_type'] = "转为企业会员";
			}	

			//$customer[$key]['page'] = $page;
			$customer[$key]['money_url'] = linkurl('customer/moneydetails').'&cid='.$value['customer_id'].'&merchant_id='.@$_GET['merchant_id']."&pages=".@$_GET['page'];
		}
		
	 	$this->res['customer']=$customer;

	 	// if(sizeof($customer )<20)
	 	// 	$this->getPages($page,$page);
	 	// else
	 	// 	$this->getPages($page);
	 	$this->res['forbidden_url'] = linkurl('customer/forbidden');
	 	$this->res['home_url'] = linkurl('common/index');
	 	$this->res['change_type_url'] = linkurl('customer/changeType');
	 	//var_dump(@$this->res['telephone']);//exit;
	 	return $this->res;
   	}
   	
   	function Business(){
   		$this->getMenu();
   		return $this->res;
   	}
   	
   	//商户信息编辑
   	function Business_edit(){
   		$this->getMenu();
   		return $this->res;
   	}
   	
   	//刷单
   	function farming(){
   		$this->getMenu();
   		return $this->res;
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
				$sql="SELECT c.`customer_id`,
			    g.name as customer_group_name, 
			    c.customer_group_id,
			    c.invitecode_id,
			    i.invitecode,
			    c.`firstname`,
			    c.`lastname`,
			    c.`email`,
			    c.`telephone`,
			    c.`cart`, 
			    c.`ip`,
			    c.`status`,
			    c.`approved`, 
			    c.`date_added`,
			    c.`headurl`,
			    c.`card`, 
			    c.`isdisturb`,
			    c.`sharetimes`,
			    c.`remark`,
			    c.proxy_status,
			    c.merchant_id as merchant_ids,
			    p.firstname as parent_name,
			    c.parent_id 
				FROM (`hb_customer` as c,hb_customer_group_description as g) left join `hb_customer` as p on c.parent_id=p.customer_id left join hb_invitecode as i on i.invitecode_id=c.invitecode_id where c.customer_group_id=g.customer_group_id and c.customer_id=:id";
			$this->res+=getRow($sql,$_GET);
			//var_dump($this->res);//exit;
			// if(isset($_GET['page'])){
	  //           $page=$_GET['page'];
	  //           if($page<1)$page=1;
	  //       }else{
	  //           $page=1;
	  //       }
	  		if(isset($_GET['page'])){
	  			$this->res['page'] =$_GET['page'];
	  		}
			
		}
		$this->res['check_url'] = linkurl('customer/checkInvite');
		$this->res['home_url'] = linkurl('common/index');
        $this->res['getList_url'] = linkurl('customer/getList');
		return $this->res;
	}
	/*
	 *2017-4-6 判断验证码是否正确
	 */
	function checkInvite(){

		$invitecode=isset($_POST['invitecode'])?$_POST['invitecode']:"";
		//进行比对
			$sql="select invitecode_id,customer_id from hb_invitecode where invitecode='".$invitecode."'";
			$msg=getRow($sql);
			if(!empty($msg)){
				//邀请码正确
				echo 1;
			}else{
				//邀请码错误
				echo 2;
			}
		exit;	
	}
	//普通会员->新建用户或修改用户信息
	function saveCustomerInfo(){
		
		if(!empty($_FILES['headurl']['name'])){
			$file = $_FILES;
			$headurl = $this->upload_img($file);
			$headurl = $headurl[0];
		}else{
			$headurl = '';
		}
		if($_POST['customer_group_id']==2 || $_POST['customer_group_id']==3 ){
			//进行比对
			$sql="select invitecode_id,customer_id from hb_invitecode where invitecode='".$_POST['invitecode']."'";
			if($_POST['invitecode']==""){
				$url=linkurl('customer/detail');
				$url=$url."&id=".@$_POST['customer_id']."&page=".@$_POST['page'];
				echo "<script> alert('邀请码不能为空'); location.href='".$url."'; </script>";
				exit();
			}
			//var_dump($_POST['invitecode']);exit();
			$msg=getRow($sql);
			if(!empty($msg)){
				//邀请码正确
				$merchant_id=$_SESSION['merchant_id'];
				$parent_id=$msg['customer_id'];
				$invitecode_id=$msg['invitecode_id'];
				$proxy_status=0;
			}else{
				//邀请码错误
				$url=linkurl('customer/detail');
				$url=$url."&id=".@$_POST['customer_id']."&page=".@$_POST['page'];
				echo "<script> alert('邀请码错误'); location.href='".$url."'; </script>";exit;
			}
			
			if($_POST['customer_group_id']==3){
				$proxy_status=1;
			}
		}else{
			$merchant_id=0;
			$parent_id=0;
			$invitecode_id=0;
			$proxy_status=0;
		}
		//var_dump($merchant_id);
		if(!empty($_POST['customer_id'])){
			$customer_id = $_POST['customer_id'];
			$sql = "update hb_customer set merchant_id='".$merchant_id."' , parent_id='".$parent_id."' ,invitecode_id='".$invitecode_id."' , proxy_status='".$proxy_status."' , customer_group_id = '".$_POST['customer_group_id']."',
					   firstname = '".$_POST['firstname']."',card = '".$_POST['card']."',telephone = '".$_POST['telephone']."',
					   lastname = '".$_POST['lastname']."',headurl = '".$headurl."',
					   remark = '".$_POST['remark']."' where customer_id = ".$customer_id."";
			exeSql($sql);
			
	        $up_url=linkurl("customer/getList").'&page='.$_POST['page'];
	        redirect($up_url);
			exit;
		}else{
			$maxid = $this->getMaxid()+1;
			$date_added = date('Y-m-d H:i:s');
			$sql = "insert into hb_customer (customer_id,customer_group_id,firstname,card,lastname,headurl,remark,date_added,telephone,merchant_id,parent_id,invitecode_id,proxy_status) 
					values ('".$maxid."','".$_POST['customer_group_id']."','".$_POST['firstname']."','".$_POST['card']."',
							'".$_POST['lastname']."','".$headurl."','".$_POST['remark']."','".$date_added."','".$_POST['telephone']."','".$merchant_id."','".$parent_id."','".$invitecode_id."','".$proxy_status."')";
			exeSql($sql);
			$url=linkurl("customer/detail");
			echo"<script>alert('保存成功');self.location=document.referrer;</script>";  
			// $url=linkurl("customer/detail");
   			// 	redirect($url);
   			exit;
		}
		exit;

	}
	//会员（代理）管理
	function memberList(){
		$this->getMenu();
		$userid = $_SESSION['userid'];
		$merchant_id = $this->getMerchantId($userid);
		$datetime = strtotime('2017-02-09 00:00:00');

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
        //会员类型
   		if(isset($_GET['proxy_status'])&&$_GET['proxy_status'] !=2){
   			$wherestr.=" and a.proxy_status = ".$_GET['proxy_status'];
   		}
   		//排序条件
   		if(isset($_GET['sort_type'])&&$_GET['sort_type'] !=0){
   			switch ($_GET['sort_type']) {
   				case '1':
   					$sortstr = " order by s.sale_num asc ";
   					break;
   				case '2':
   					$sortstr = " order by s.sale_num desc ";
   					break;
   				case '3':
   					$sortstr = " order by m.sale_money asc ";
   					break;
   				case '4':
   					$sortstr = " order by m.sale_money desc ";
   					break;
   				case '5':
   					$sortstr = " order by a.proxy_time asc ";
   					break;
   				case '6':
   					$sortstr = " order by a.proxy_time desc ";
   					break;
   			}
   		}
   		//昵称
   		if(isset($_GET['firstname'])&&$_GET['firstname']){
   			$wherestr.=" and a.firstname like '".trim($_GET['firstname'])."%'";
   		}
   		//账号
   		if(isset($_GET['telephone'])&&$_GET['telephone']){
   			$wherestr.=" and a.telephone='".trim($_GET['telephone'])."'";
   		}
   		//所属代理邀请码
   		if(isset($_GET['invitecode'])&&$_GET['invitecode']){
   			$wherestr.=" and c.invitecode like '".trim($_GET['invitecode'])."%'";
   		}
   		// $wherestr="";
		$sql = "select distinct a.customer_id,a.firstname,a.telephone,a.proxy_time,a.proxy_status,a.status,s.sale_num,
					   m.sale_money,b.balance,c.invitecode_id,c.invitecode,c.customer_id as pid,s.date_added
				from hb_customer as a
				inner join hb_balance as b on a.customer_id = b.customer_id
				left join hb_invitecode as c on a.invitecode_id = c.invitecode_id
				left join (select count(amount) as sale_num,customer_id,date_added
						from hb_customer_transaction as ct
						where ct.type in (1,3) and UNIX_TIMESTAMP(date_added) > '".$datetime."' group by ct.customer_id ) as s on s.customer_id = a.customer_id
								
				left join (select sum(amount) as sale_money,customer_id
						from hb_customer_transaction as cd
						where cd.type in (1,3,6,7) and UNIX_TIMESTAMP(date_added) > '".$datetime."' group by cd.customer_id) as m on m.customer_id = a.customer_id
				where a.merchant_id = '".$merchant_id."' ".$wherestr." ";
		if(isset($_GET['sort_type'])&&$_GET['sort_type'] !=0){
			$sql .= $sortstr;
		}else{
			$sql .= "order by a.customer_id desc ";
		}
//1 销售收入(分享收入) 2 购买支出 3分成收入(返利) 5.资金解锁 4.提现 6.分享退款，7.返利退款8购买退款9会员订单
		$sql .= "limit ".$start.','.$per_num."";
		$customerInfo = getData($sql);
		//var_dump($sql);exit;
		if(sizeof($customerInfo)<$per_num){
            $this->getPages($page,$page);
        }else{
            $this->getPages($page);
        }

		foreach ($customerInfo as $key => $value) {
			if(!empty($value['pid'])){
				$firstname = $this->getAgentName($value['pid']);
			}else{
				$firstname = '';
			}
			$customerInfo[$key]['agentname'] = $firstname;								//所属代理人

			if(!empty($value['sale_money'])){
				$customerInfo[$key]['sale_money'] = sprintf("%.2f",$value['sale_money']);
			}else{
				$customerInfo[$key]['sale_money'] =0;									//个人总收入
			}
			
			if(empty($value['sale_num'])){
				$customerInfo[$key]['sale_num'] = 0;									//个人总销量
			}
					
			if(!empty($value['invitecode'])){
				$customerInfo[$key]['huiyuan_code'] = $value['invitecode'];				//会员所属代理的邀请码
			}else{
				$customerInfo[$key]['huiyuan_code'] = '';		
			}
			
			$customerInfo[$key]['balance'] = sprintf("%.2f",$value['balance']);			//个人余额	
			if($value['proxy_status']==1){
				$customerInfo[$key]['huiyuan_type'] = "企业会员";
				$customerInfo[$key]['change_type'] = "转为普通会员";
			}else{
				$customerInfo[$key]['huiyuan_type'] = "普通会员";
				$customerInfo[$key]['change_type'] = "转为企业会员";
			}	
			if($value['status']==1){
				$customerInfo[$key]['status_type'] = "禁用";							//已启用
			}else{
				$customerInfo[$key]['status_type'] = "启用";  							//已禁用
			}
			$customerInfo[$key]['money_url'] = linkurl('customer/moneydetails').'&cid='.$value['customer_id'].'&re_page='.$page;
		}

		$this->res['data'] = $customerInfo;
		$this->res['change_type_url'] = linkurl('customer/changeType');
		$this->res['forbidden_url'] = linkurl('customer/forbidden');
		$this->res['agent_info_url'] = linkurl('customer/getAgentInfo');
		$this->res['home_url'] = linkurl('common/index');
        $this->res['memberList_url'] = linkurl('customer/memberList');
		return $this->res;

	}
	//根据pid获取代理商信息
	function getAgentInfo(){
		if(!empty($_POST['pid'])){
			$sql = "select firstname,lastname,telephone from hb_customer where customer_id = '".$_POST['pid']."'";
			$info = getRow($sql);
			if(!empty($info)){
				print_r(json_encode($info));		exit;
			}else{
				echo 1;				exit;  //1:无信息
			}
		}
	}
	//改变会员类型
	function changeType(){
		if(isset($_POST['type']) && !empty($_POST['cid'])){
			if($_POST['type'] == 1){
				$proxy_status = 0;
			}else{
				$proxy_status = 1;
			}
			$sql = "update hb_customer set  proxy_status = '".$proxy_status."' where customer_id = '".$_POST['cid']."'";
			exeSql($sql);
			echo 1;exit;		//修改成功
		}else{
			echo 2;exit;		//修改失败
		}
	}
	//是否禁用
	function forbidden(){
		if(isset($_POST['type']) && !empty($_POST['cid'])){
			if($_POST['type'] == 1){
				$status = 0;
			}else{
				$status = 1;
			}
			$sql = "update hb_customer set  status = '".$status."' where customer_id = '".$_POST['cid']."'";
			exeSql($sql);
			echo 1;exit;		//修改成功
		}else{
			echo 2;exit;		//修改失败
		}
	}
	//根据pid查询所属代理人信息
	function getAgentName($pid){
		$sql = "select firstname from hb_customer where customer_id = '".$pid."'";
		$name = getRow($sql);
		if(!empty($name)){
			$firstname = $name['firstname'];
		}else{
			$firstname = '';
		}
		return $firstname;
	}
	//根据登录用户获取merchant_id
	function getMerchantId($userid){
		$sql = "select merchant_id from hb_user where user_id = '".$userid."'";
		$merchant_id = getRow($sql);
		return $merchant_id['merchant_id'];
	}
	//根据invitecode_id获取邀请码 
	function getInvitecode($invitecode_id){
		$sql = "select invitecode from hb_invitecode where invitecode_id = '".$invitecode_id."'";
		$invite = getRow($sql);
		if(!empty($invitecode)){
			$invitecode = $invite['invitecode'];
		}else{
			$invitecode = '';
		}
		return $invitecode;
	}
	//根据customer_id统计个人总销量
	function countSaleNum($customer_id){
		$sql = "select count(amount) as sale_num 
				from hb_customer_transaction 
				where type in (1,3) and customer_id = '".$customer_id."'";
		$saleNum = getRow($sql);
		return $saleNum;
	}
	//根据customer_id统计个人总收入
	function countIncome($customer_id){
		$sql = "select sum(amount) as single_amount 
				from hb_customer_transaction 
				where type in (1,3,6,7) and customer_id = '".$customer_id."'";
		$incomeInfo = getRow($sql);
		return $incomeInfo;
	}
	//资金明细列表
	function moneyDetails(){
		$this->getMenu();
		$customer_id = isset($_GET['cid'])?$_GET['cid']:null;

        $datetime = strtotime('2017-02-09 00:00:00');

        //订单号
        $wherestr = '';
   		if(isset($_GET['oid'])&&$_GET['oid']){
   			$wherestr.=" and ct.order_id = '".trim($_GET['oid'])."'";
   			$this->res['oid'] = $_GET['oid'];
   		}
   		if(isset($_GET['cid'])&&$_GET['cid']){
   			$this->res['cid'] = $_GET['cid'];
   		}
        if(isset($_GET['page'])){
            $page=$_GET['page'];
            if($page<1)$page=1;
        }else{
            $page=1;
        }
        //每页显示条数
        $per_num = 15;  
        $start=($page-1)*$per_num;

        $sql = "select ct.*,op.name,o.total,p.model,p.proxyprice from `" .DB_PREFIX. "customer_transaction` as ct
                left join `" .DB_PREFIX. "order_product` op on op.order_id = ct.order_id
                left join `" .DB_PREFIX. "product` p on p.product_id = op.product_id
                left join `" .DB_PREFIX. "orderout` ot on ot.order_id = ct.order_id
                left join `" .DB_PREFIX. "order` o on o.order_id = ct.order_id";

        $sql .= " where ct.customer_id = " .(int)$customer_id. " and ct.type in (1,3,6,7)  
        		and UNIX_TIMESTAMP(ct.date_added) > '".$datetime."' $wherestr order by ct.date_added  desc ";

        $sql .= "limit ".$start.','.$per_num."";

        //明细列表
        $capital_list = getData($sql);
         /* zxx 2017-4-6   分页*/
        $sql1 = "select count(*) as count from `" .DB_PREFIX. "customer_transaction` as ct
                left join `" .DB_PREFIX. "order_product` op on op.order_id = ct.order_id
                left join `" .DB_PREFIX. "product` p on p.product_id = op.product_id
                left join `" .DB_PREFIX. "orderout` ot on ot.order_id = ct.order_id
                left join `" .DB_PREFIX. "order` o on o.order_id = ct.order_id";

        $sql1 .= " where ct.customer_id = " .(int)$customer_id. " and ct.type in (1,3,6,7)  
        		and UNIX_TIMESTAMP(ct.date_added) > '".$datetime."' $wherestr order by ct.date_added  desc ";
        $total=getRow($sql1,60);
        $total=$total['count'];
        $total_page = ceil($total/15);
       // var_dump($total);exit();
        $this->res['is_end_page'] = 1;
        if($page == $total_page){
          $this->res['is_end_page'] = 0;
        }

        $this->getPages($page,$total_page);
        /* zxx 2017-4-6*/



        // if(sizeof($capital_list)<$per_num){
        //     $this->getPages($page,$page);
        // }else{
        //     $this->getPages($page);
        // }
        
        if($capital_list){
            foreach($capital_list as $key=>$val){
                if($val['type'] == 1){
                        $capital_list[$key]['status_description'] = '收入(分享)';
                }else if($val['type'] == 6){
                        $capital_list[$key]['status_description'] = '支出(分享退款)';
                }else if($val['type'] == 3){
                    $capital_list[$key]['status_description'] = '收入(返利)';
                }else if($val['type'] == 7){
                    $capital_list[$key]['status_description'] = '支出(返利退款)';
                }

                $product = getRow("select ifnull(pov.proxyprice,0.00) as option_proxyprice from `" .DB_PREFIX. "order_option` oo  left join `" .DB_PREFIX. "product_option_value` pov on pov.product_option_value_id = oo.product_option_value_id where oo.order_id = '" .(int)$val['order_id']. "'");
                $capital_list[$key]['option_proxyprice'] = sprintf("%.2f",@$product['option_proxyprice']);

                $capital_list[$key]['price'] = sprintf("%.2f",$val['total']);
                $capital_list[$key]['balance_change'] = sprintf("%.2f",$val['last_balance']+$val['amount']);
                $capital_list[$key]['amount'] = sprintf("%.2f",$val['amount']);
                if(@$product['option_proxyprice'] > 0){
                    $capital_list[$key]['proxyprice'] = sprintf("%.2f",$product['option_proxyprice']);
                }else{
                    $capital_list[$key]['proxyprice'] = sprintf("%.2f",$val['proxyprice']);
                }

            }
        }
		if(!empty($_GET['re_page'])){
			$re_page = $_GET['re_page'];
		}else{
			$re_page = '';
		}
        $this->res['re_page'] = $re_page;
        $this->res['capital_list'] = $capital_list;
        $this->res['home_url'] = linkurl('common/index');
        $this->res['memberList_url'] = linkurl('customer/memberList');
        $this->res['back_url'] = linkurl('customer/getList').'&merchant_id='.@$_GET['merchant_id'].'&page='.@$_GET['pages'];
        return $this->res;
	}
	//上传评论图片
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
    //查出数据库中customer_id 的最大值
	function getMaxid(){
		$sql = "select max(customer_id) as maxid from hb_customer where 1 ";
		$info = getRow($sql);
		return $info['maxid'];
	}
}

