<?php
	//面向对象的control 类
include "xcontrol/base.php";
class groupbuylist extends base
{
	function __construct() 
	{
       parent::__construct();
	   $this->passkey=@$_SESSION["default"]['passkey'];
	   $this->customer_id=@$_SESSION["default"]['customer_id'];
   	}
	
	/*
	*2017 02 28 zxx 获取团购商品
	*/
	function index(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$product_id=isset($_POST["product_id"])?$_POST["product_id"]:null;
			$group_id=isset($_POST["group_id"])?$_POST["group_id"]:null;
			$join_id=isset($_POST["join_id"])?$_POST["join_id"]:null;

			if($product_id && $group_id){
				if($this->res["retcode"]==0){
					if(isset($_POST["page"]) && @$_POST["page"]>0){
						$page=$_POST["page"];
					}else{
						$page=1;
					}
					$start=($page-1)*5;
					//查询该用户是否是会员
					$customer=getRow("select * from hb_customer where customer_id='".$this->customer_id."' ");
					// if(@$customer["merchant_id"]==0){
					// 	$is_open_group=0;//不能开团
					// }else{
						$is_open_group=1;//可以开团   只要是会员
					// }
					//判断是否开团
					$is_open=$this->is_open($group_id,$product_id);
					if($is_open==1){
						$is_open_group=2;//已经开过团了  改隐藏开团按钮
					}
					//查询产品的团购列表  gj.join_status,
					$sql="select gj.join_id,
						 gj.group_id,
						 gj.add_customer_image as open_image,
						 gj.add_customer_name as open_name,
						 gj.add_customer_id,
						 gj.product_id,
						 gb.start_time,
						 gb.end_time,
						 gj.join_status as group_status,
						 gb.groupnum,
						 gj.date_modified
						 from hb_group_join as gj left join hb_groupby as gb on gj.group_id=gb.group_id 
						 where gb.group_status=1 and gj.product_id='".$product_id."' and gj.group_id='".$group_id."' and (gj.join_status=1 or gj.join_status=3) and UNIX_TIMESTAMP(start_time)<= '".time()."' ";
					if(!empty($join_id)){
						$sql.=" and gj.join_id='".$join_id."'";
					}
					$sql.=" limit ".$start.",5";
					$sql1="select * from hb_groupby as gb  where gb.group_status=1 and gb.product_id='".$product_id."' and gb.group_id='".$group_id."' and UNIX_TIMESTAMP(start_time)<= '".time()."' ";

					$one=getRow($sql1);
					if(@$one["group_status"]!=1 ){
						$this->res["retcode"]=3101;
						$this->res["msg"]="团购活动异常";
					}else{
						$join_array=array();
						$data=getData($sql);
						$json=array();
						if(!empty($data)){
							foreach($data as $k=>$v){
								//查询下面的参团会员  显示头像
								$sql1="select c.firstname as join_name,c.headurl as join_image,c.customer_id,gi.type from hb_group_join_info as gi left join hb_customer as c on gi.customer_id=c.customer_id where gi.join_id= '".$v["join_id"]."' and gi.group_id = '".$v["group_id"]."' and gi.product_id= '".$v["product_id"]."' and gi.status=1  ";
								$join_customer=getData($sql1);
								$sql3="select c.firstname as join_name,c.headurl as join_image,c.customer_id,gi.type from hb_group_join_info as gi left join hb_customer as c on gi.customer_id=c.customer_id where gi.join_id= '".$v["join_id"]."' and gi.group_id = '".$v["group_id"]."' and gi.product_id= '".$v["product_id"]."' and gi.status=1 and gi.type=1 limit 5 ";
								$join_customer1=getData($sql3);
								if(!empty($join_customer1)){
									foreach($join_customer1 as $kkk=>$vvv){
										//查询参团人的昵称是否是电话号码
										$is_tel1=getRow("select * from hb_customer where customer_id='".$vvv["customer_id"]."' ");
										if($is_tel1["telephone"]==$vvv["join_name"]){
											$pattern = '/(\d{3})(\d{4})(\d{4})/i';
											$replacement = '$1****$3';  
											$resstr1 = preg_replace($pattern, $replacement,$vvv["join_name"]); 
											$join_customer1[$kkk]["join_name"]=$resstr1;//修改  cgl 2017-4-12
										}
									}
								}

								//查询还差多少人
								$sql2="select count(*) as total from hb_group_join_info as gi left join hb_customer as c on gi.customer_id=c.customer_id where gi.join_id= '".$v["join_id"]."' and gi.group_id = '".$v["group_id"]."' and gi.product_id= '".$v["product_id"]."' and gi.status=1 ";
								$total=getRow($sql2);
								$data[$k]["join_group"]=$join_customer1;
								if(!empty($join_customer)){
									foreach($join_customer as $kk=>$vv){
										if($vv["customer_id"]==$this->customer_id){
											$data[$k]["customer_sort"][]=1;
										}else{
											$data[$k]["customer_sort"][]=0;
										}
										$data[$k]["join_customer"][]=$vv["customer_id"];
									}
								}else{
									$data[$k]["join_group"]=$join_array;
									$data[$k]["customer_sort"]=0; //排序   自己没有参团的在前面
								}
								if($v["group_status"]==3){
									$data[$k]["group_time"]=$v["date_modified"];
								}else{
									$data[$k]["group_time"]="";
								}
								//查询开团人的昵称是否是电话号码
								$is_tel=getRow("select * from hb_customer where customer_id='".$v["add_customer_id"]."' ");
								if($is_tel["telephone"]==$v["open_name"]){
									$pattern = '/(\d{3})(\d{4})(\d{4})/i';
									$replacement = '$1****$3';  
									$resstr = preg_replace($pattern, $replacement,$v["open_name"]); 
									$data[$k]["open_name"]=$resstr;//修改  cgl 2017-4-12
								}

								$data[$k]["short_num"]=$v["groupnum"]-$total["total"];
								unset($data[$k]["groupnum"]);
								unset($data[$k]["add_customer_id"]);
							}
						}
						// 准备要排序的数组
						if(!empty($data)){
							foreach ($data as $k => $v) {
								if(in_array(1,$v["customer_sort"]) ){
									$edition[] = 1;
								}else{
									$edition[] = 0;	
								}
							}
							array_multisort($edition, SORT_DESC, $data);
						}
						$a="";
						if(!empty($data)){
							foreach($data as $k=>$v){
								if(in_array($this->customer_id,$v["join_customer"])){
									$data[$k]["join_group_status"]=1;
								}else{
									$data[$k]["join_group_status"]=0;
								}
								unset($data[$k]["customer_sort"]);
								unset($data[$k]["join_customer"]);
								$data[$k]["invite_url"]="http://haiqihuocang.cn/highup/index.php?route=share/share/shareCt&productid=".$v["product_id"]."&join_id=".$v["join_id"]."";
							}
						}
						$json["start_time"]=empty($one["start_time"])?"":strtotime($one["start_time"]);
						$json["end_time"]=empty($one["end_time"])?"":strtotime($one["end_time"]);
						$json["group_list"]=$data;
						$json["is_open_group"]=$is_open_group;
						$json["group_num"]=empty($one["groupnum"])?"0":$one["groupnum"];
						$this->res["group"]=$json;
					}
					// $json["group_status"]=empty($one["group_status"])?1:$one["group_status"];
				}
			}else{
				//参数错误
				$this->res["retcode"]=1001;
			}
		}else{
			$this->res["retcode"]=1180;
		}
		return $this->res;
		die;
	}
	/**
	 * 请求团购开团或者参团  cgl  2017-3-1
	 */
	function openGroup(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			$product_id=isset($_POST["product_id"])?$_POST["product_id"]:null;
			$group_id=isset($_POST["group_id"])?$_POST["group_id"]:null;
			$group_type=isset($_POST["group_type"])?$_POST["group_type"]:1;
			$join_id=isset($_POST["join_id"])?$_POST["join_id"]:null;
			//开团，或者参团   1参团，2开团
			if($this->res["retcode"]==0){
				$customer=getRow("select * from hb_customer where customer_id='".$this->customer_id."' ");
				if($group_type==1){
					//参团
					//查询是否这个产品已经下架     是否已经结束  查看该团购是否是正在进行或者已经成功
					if(!$this->is_ok($group_id,$product_id)){
						$this->res["retcode"]=3101;
						$this->res["msg"]="团购活动异常";
					}else if(!$this->is_down($group_id,$product_id)){
						$this->res["retcode"]=3102;
						$this->res["msg"]="团购商品异常";
					}else{
						//是否已经参过这个团
						if($this->is_customer_join_group($group_id,$product_id,$join_id)){
							$this->res["retcode"]=3103;
							$this->res["msg"]="已经参加过了这个团";
						}else{
							//是否人数足够  还能够参团么？
							$group=getRow("select *,count(gji.join_id) as total from hb_groupby as g left join hb_group_join as gj on g.group_id=gj.group_id 
								left join hb_group_join_info as gji on gj.join_id=gji.join_id
								where g.group_id ='".$group_id."' and g.product_id = '".$product_id."' and g.group_status=1 and gj.group_id ='".$group_id."' and gj.product_id = '".$product_id."' and gj.join_status=1 and gji.join_id = '".$join_id."' and gji.status=1 ");
							if($group["groupnum"]-$group["total"]>0){
								//人数不够，还可以继续参团
								//saveData("hb_group_join_info",array("group_id"=>$group_id,"product_id"=>$product_id,"customer_id"=>$this->customer_id,"type"=>1,"status"=>2,"date_added"=>date("Y-m-d H:i:s",time()),"date_modified"=>date("Y-m-d H:i:s",time()),"join_id"=>$join_id  ));
								//cgl 2017-3-6  新增一个字段
								$this->res["join_id"]="$join_id";
								$this->res["group_id"]="$group_id";
							}else{
								//人数已经够了
								$this->res["retcode"]=3104;
								$this->res["msg"]="这个团的人数已经够了";
							}
						}
					}
				}else if($group_type==2){
					//开团
					$is_open=$this->is_open($group_id,$product_id);
					//是否已经开过团
					if($is_open==1){
						//开了  就不能开团了
						$this->res["retcode"]=3100;
						$this->res["msg"]="已经开过团了";
					}else{
						if($this->is_ok($group_id,$product_id) && $this->is_down($group_id,$product_id)){
							//没有开团可以开团
							// saveData("hb_group_join",array("group_id"=>$group_id,"product_id"=>$product_id,"add_customer_id"=>$this->customer_id,"add_customer_name"=>@$customer["firstname"],"add_customer_image"=>@$customer["headurl"],"date_added"=>date("Y-m-d H:i:s",time()),"date_modified"=>date("Y-m-d H:i:s",time()),"join_status"=>2));
							// $id=getLastId();
							// if($id){
								// saveData("hb_group_join_info",array("group_id"=>$group_id,"product_id"=>$product_id,"customer_id"=>$this->customer_id,"type"=>2,"status"=>2,"date_added"=>date("Y-m-d H:i:s",time()),"date_modified"=>date("Y-m-d H:i:s",time()),"join_id"=>$id  ));
								//cgl 2017-3-6  新增一个字段
								$this->res["join_id"]="";
								$this->res["group_id"]="$group_id";
							// }
						}else{
							if(!$this->is_ok($group_id,$product_id)){
								$this->res["retcode"]=3101;
								$this->res["msg"]="团购活动异常";
							}else if(!$this->is_down($group_id,$product_id)){
								$this->res["retcode"]=3102;
								$this->res["msg"]="团购商品异常";
							}
						}
					}
				}
			}
		}else{
			$this->res["retcode"]=1180;
		}
		return $this->res;
		die;
	}

	/**
	 * 查询是否开过团 cgl 2017-3-1    供上面验证  调用
	 */
	function is_open($group_id,$product_id){
		//查询是否开过团
		$tuan=getRow("select * from hb_group_join as gj left join hb_group_join_info as gji on gj.group_id=gji.group_id  where gji.customer_id = '".$this->customer_id."' and gji.type=2 and gji.group_id = '".$group_id."' and gji.product_id = '".$product_id."' and gj.product_id = '".$product_id."' and gj.group_id = '".$group_id."' and gj.add_customer_id = '".$this->customer_id."' and gj.join_status=1 ");
		if($tuan){
			return 1;
		}else{
			return 0;
		}
	}
	/**
	 * 查询这个产品是否下架  cgl 2017-3-2  供上面验证  调用
	 */
	function is_down($group_id,$product_id){
		$product=getRow("select * from hb_product where product_id = '".$product_id."' and status=1 and quantity>1 ");
		if(!empty($product)){
			return 1;//产品没有下架
		}else{
			return 0;//产品已下架
		}
	}
	/**
	 * 团购活动是否到期   团购活动商品是否正常  cgl 2017-3-2  供上面验证  调用
	 */
	function is_ok($group_id,$product_id){
		$tuan=getRow("select * from hb_groupby where product_id='".$product_id."' and group_id = '".$group_id."' and group_status = 1 and UNIX_TIMESTAMP(end_time)>= '".time()."' and UNIX_TIMESTAMP(start_time)<= '".time()."' ");
		if(!empty($tuan)){
			return 1;//团购活动正常
		}else{
			return 0;//团购活动异常    失败，关闭，已结束
		}
	}
	/**
	 * 查询这个用户是否已经参加过这个团
	 */
	function is_customer_join_group($group_id,$product_id,$join_id){
		$is_join=getRow("select * from hb_group_join_info where customer_id = '".$this->customer_id."' and product_id = '".$product_id."' and product_id = '".$product_id."' and join_id = '".$join_id."' and status=1 ");
		if(!empty($is_join)){
			return 1;//参加过了的  不能参加了
		}else{
			return 0;//可以参团这个团购的团
		}
	}

}
?>