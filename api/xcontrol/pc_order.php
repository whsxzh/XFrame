<?php
	//面向对象的control 类
include "xcontrol/base.php";
class pc_order
{
	/**
	 * cgl  2017 -1-12
	 * pc请求订单的状态
	 */
	function index(){
		if(!empty($_POST["out_trade_no"])){
			$order=isset($_POST["out_trade_no"])?$_POST["out_trade_no"]:null;//"1015342";//以逗号隔开
			$orders=getData("select * from hb_order as a join hb_order_product as b on a.order_id= b.order_id  where a.order_num = '".$order."' ");
			if(!empty($orders)){
				if($orders[0]["order_status_id"]==2){
					//支付成功
					$this->res["retcode"]=0;
				}else{
					//支付失败
					$this->res["retcode"]=1001;	
				}
			}else{
				$this->res["retcode"]=1130;
				// echo json_decode("retcode"=>"1000");	
			}
		}else{
			$this->res["retcode"]=1000;
			//echo json_decode("retcode"=>"1000");
		}
		return $this->res;
	}
	


}
?>