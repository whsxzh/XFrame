<?php
	//面向对象的control 类
include "xcontrol/base.php";
class permission extends base{

		function userList()//账号管理列表
		{
			
			//保存
			if(isset($_POST['username']))
			{
				if(isset($_POST['password'])&&$_POST['password']==$_POST['passworda'] )
				{
					$salt=token(9);
					$_POST['salt']=$salt;

					$password =htmlspecialchars($_POST['password']);
					$_POST['password'] = escape(sha1($salt . sha1($salt . sha1($password))));

				}
				$_POST['date_added']=date("Y-m-d H:i:s");
				$_POST['date_update']=date("Y-m-d H:i:s");
				$_POST['merchant_id']=$_SESSION['merchant_id'];
			//print_r( $_POST);
				saveData("hb_user",$_POST);
			}


			//列表
			$this->getMenu();

			$this->res['dt']=getData("select u.user_id,username,firstname,u.status,ip,name as groupname from hb_user as u,hb_user_group as g where u.user_group_id=g.user_group_id and  u.merchant_id=".$_SESSION['merchant_id']);

			//
			return $this->res;
		}

		function userEdit()//账号管理列表
		{
			$this->getMenu();
			$this->res['role']=getData("select * from hb_user_group");
			if(isset($_GET['id']))
			{
				$this->res['data']=getRow("select user_id,username,firstname,user_group_id from hb_user where user_id=".$_GET['id']);

				

				foreach ($this->res['role'] as $key => $v) {
					# code...
					if($v['user_group_id']==$this->res['data']['user_group_id'])
						$this->res['role'][$key]['selected']=true;
				}

			}
			


			return $this->res;
			/*if(isset($_POST['user_uuid']))
			saveData("hb_user",$_POST);*/
		}

		function disableUser()
		{
			if(isset($_GET['id'])&&$_GET['status']=='1')
			{
				$sql="update  hb_user set status=0 where user_id=".$_GET['id'];
				exeSql($sql);
			}
			elseif (isset($_GET['id'])) 
			{
				$sql="update  hb_user set status=1 where user_id=".$_GET['id'];
				exeSql($sql);
			}

			header("Location:xindex.php?m=permission&act=userList");
		}

		function roleList()//	角色管理
		{
			$this->getMenu();
			$this->res['dt']=getData("select * from hb_user_group where merchant_id=".$_SESSION['merchant_id']);
			return $this->res;
		}

		function roleEdit()//角色编辑
		{
			if(isset($_POST['name']))
			{
				//得到permission
				$arr =  array();;
				foreach ($_POST['sale-tab_0'] as $key => $v) {
					$arr[$v]=$_POST['sale-tab_'.$v];
				}

				$_POST['permission']=json_encode($arr);
				$_POST['merchant_id']=$_SESSION['merchant_id'];
				$_POST['user_id']=$_SESSION['userid'];
				//print_r($_POST);
				saveData("hb_user_group",$_POST);
			}
			
			//
			$parr=array();
			if(isset($_GET['id']))
			{

				$permission=getRow("select * from hb_user_group where user_group_id=".$_GET['id']);
				$rep=array("["=>'',"]"=>"","{"=>"","}"=>"","\""=>"",":"=>",");
				$ids=strtr($permission['permission'],$rep);
				$parr=explode(",", $ids);
			}

			
			//得到权限列表
			$dt=getData("select * from hb_permissionx order by permission_id asc");
			$right=array();
			foreach ($dt as $k => $v) {
				# code...
				if(in_array($v['permission_id'], $parr))
					$v['checked']=true;
				else
					$v['checked']=false;

				if($v['parent_id']==0)
					$right[$v['permission_id']]=$v;
				else
					$right[$v['parent_id']]['son'][$v['permission_id']]=$v;
			}

			$this->getMenu();
			$this->res['dt']=$right;
			
			if(isset($permission))
				$this->res+=$permission;
			
			return $this->res;
		}


}
?>