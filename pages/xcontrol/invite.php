<?php

//面向对象的control 类
include "xcontrol/base.php";
include "lib/pagination.php";
include "phpqrcode.php";
class invite extends base
{
	



	function __construct() 
	{
       parent::__construct();
       //print "In SubClass constructor\n";
		$this->userid=$_SESSION['userid'];
		$this->username=$_SESSION['username'];
   	}

	function edit()
	{
		$this->getMenu();
		if(isset($_GET["id"]))
		{
			$this->res['data']=getRow("select c.lastname,c.firstname,i.* from hb_invitecode as i,hb_customer as c where i.customer_id=c.customer_id and  i.invitecode_id=".$_GET['id']);

		}
		return $this->res;
		
		
	}

	function getList()
	{	
		$this->getMenu();
		if(isset($_POST['telephone']))//保存
		{
			//获得用户信息
			$cust=getRow("select lastname,customer_id,telephone from hb_customer where telephone='".$_POST['telephone']."'");
			if(isset($cust['customer_id']))
			{
				$_POST['customer_id']=$cust['customer_id'];
				
				// 确保邀请码的唯一性处理
				// 如果找到您输入的邀请码，则说明已经存在
				$invitecode_id = getRow("select invitecode_id from hb_invitecode where invitecode like '".htmlspecialchars($_POST['invitecode'])."'");
				if(@$invitecode_id['invitecode_id']){
					//var_dump($invitecode_id['invitecode_id']);exit();
					if($invitecode_id['invitecode_id'] != $_POST['invitecode_id']){
						echo "此邀请码已经存在，请您重新设定，谢谢";
						//var_dump($invitecode_id['invitecode_id']);var_dump($_POST['invitecode_id']);
					}else{
						saveData("hb_invitecode",$_POST);
					}
				}else{
					
					saveData("hb_invitecode",$_POST);
				}				
			}
			else
			{
				echo "这个手机没有注册";
			}
		}

		$data=getData("select c.lastname,c.firstname,i.*,(select count(customer_id) from hb_customer where invitecode_id=i.invitecode_id) as times from hb_invitecode as i,hb_customer as c where i.customer_id=c.customer_id  order by invitecode_id desc ");

		//$data=getData("select * from hb_customer as c left join hb_invitecode as i on c.invitecode_id=i.invitecode_id ");
		//var_dump($data);exit;
		//limit $start,20
		foreach ($data as $key => $value) {
			$data[$key]['invite_url']="haiqihuocang.cn/becomeInvite.php?id=".$value['invitecode_id'];
			//$data[$key]['invite_image']=$this->generateQRfromGoogle($data[$key]['invite_url']);
		}
		$this->res['editurl']=linkurl('invite/edit');
		$this->res['data']=$data;
		return $this->res;
	}


		public function generateQRfromGoogle($chl,$widhtHeight ='70',$EC_level='L',$margin='0'){
			 $url = urlencode($chl);
			 return '<img src="http://chart.apis.google.com/chart?chs=70x70&cht=qr&chld=L|0&chl='.$chl.'" alt="QR code" widhtHeight=70widhtHeight=70/>';//Google API接口，若失效可到Google网址查询最新接口
		}

	function disable()
	{
		if(isset($_GET['id']))
		{
			if($_GET["status"]=='1')
				$status='0';
			else
				$status='1';

			saveData("hb_invitecode" , array('invitecode_id' =>$_GET['id'] ,"status"=>$status ));
		}
		echo "<script>alert('状态已经更改')</script>";
		gourl("xindex.php?m=invite&act=getList");
	}

	function getDetail()
	{
		//判断是不是购买了本条信息
		if($this->userid>0)
		{
			$data=getData("select re.* from requment as re,order1 as o where o.requid=re.id and o.userid='".$this-userid."' and re.id=".$_GET['id']);
			$this->res['data']=$data;
		}
		else
		{
			$this->res=array("code"=>0,"msg"=>"您还没有登录");
		}

		echo json_encode($this->res);
	}

	function login()
	{
		if(isset($_POST['name'])&&$_POST['name']&&$_POST['passwd'])
		{
			$_POST['pws']=md5("egg".$_POST['passwd']);
			if($row=getRow("select * from user where name=:name and pws=:pws",$_POST))
			{
				$_SESSION['username']=$row['name'];
				$_SESSION['userid']=$row['id'];
			}
		}
	}

	function regist()
	{
		if(isset($_POST['name'])&&$_POST['name']&&$_POST['passwd']==$_POST['passwd1'])
		{
			$_POST['pws']=md5("egg".$_POST['passwd']);
			saveData("user",$_POST);
		}
	}




	
	function code(){
		/*$data=getData("SELECT COUNT(c.`customer_id`  ) as total ,c.`invitecode_id` ,c.`parent_id`,p.lastname,p.firstname   from `hb_customer` as c,`hb_customer` as p  where c.`parent_id`=p.customer_id   GROUP BY c.`invitecode_id`,c. `parent_id` ");*/
		//print_r($data);
		$data=getData("SELECT COUNT(c.`customer_id`  ) as total ,c.`invitecode_id` ,c.`parent_id`,p.lastname,p.firstname,p.telephone,i.`invitecode`   from `hb_customer` as c,`hb_customer` as p ,`hb_invitecode` as i where c.`parent_id`=p.customer_id and c.invitecode_id=i.`invitecode_id`   GROUP BY c.`invitecode_id`,c. `parent_id` ");
		$this->res['data']=$data;
		return $this->res;
	}


    /**
     *  @description 邀请码活动的中奖名单用户详细信息的统计
     *  @param       none
     *  @return      array $this->res
     *  @author 	 godloveevin@yeah.net
     *  @d/t         2017-03-03/17:00
     */
	function activity(){
	    // 后台菜单
		$this->getMenu();

		$total_sql = "select count(*) as total 
					 from `" .DB_PREFIX. "active_order` ao 
					 left join `" .DB_PREFIX. "invitecode` i on i.invitecode_id = ao.invitecode_id 
					 where i.customer_id in (
						 select customer_id from ".DB_PREFIX."customer 
						 where merchant_id = '".$_SESSION['merchant_id']."')";
		$info = getRow($total_sql);

		// 某商户下的中奖者全部数量
		$total = $info['total'];

		$sql = "select ao.*,i.invitecode,ap.product_name 
				from `" .DB_PREFIX. "active_order` ao 
				left join `" .DB_PREFIX. "invitecode` i on i.invitecode_id = ao.invitecode_id 
				left join `" .DB_PREFIX. "active_product` ap on ap.product_id = ao.product_id 
				where i.customer_id in (
					select customer_id from ".DB_PREFIX."customer
					where merchant_id = '".$_SESSION['merchant_id']."')";

		$list = getData($sql);
		foreach($list as $key=>$val){
            if($val['status'] == 1){
                $list[$key]['status_name'] = '未发货';
            }else{
                $list[$key]['status_name'] = '已发货';
            }
        }
		$this->res['data'] = $list;
		return $this->res;
	}

}