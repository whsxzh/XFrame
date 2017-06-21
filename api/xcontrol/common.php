<?php
	//面向对象的control 类
include "xcontrol/base.php";
class common extends base
{
	function __construct() 
	{
       parent::__construct();
	   $this->passkey=@$_SESSION["default"]['passkey'];
	   $this->customer_id=@$_SESSION["default"]['customer_id'];
   	}
	/**
	 * cgl  2017 -1-9
	 * 个人的账单
	 * 参数：page（页码）默认为1，date（日期），例如2017-01 2016-12，默认为当前月
	 */
	function index(){
		$limit=10;
		if(isset($_POST["page"]) && @$_POST["page"]>=1){
			$page=$_POST["page"];
		}else{
			$page=1;	
		}
		//查询日期   默认为当前月
		$date=isset($_POST["date"])?$_POST["date"]:date("Y/m");
		$start=($page-1)*$limit;
		//个人的账单
		$record=getData("select * from hb_customer_transaction where customer_id ='".$this->customer_id."' AND DATE_FORMAT(date_added,'%Y/%m') = '".$date."' order by date_added desc limit $start,$limit ");
		//查询全部的收入
		$arr=array();
		$total=0;
		$money_total=getRow("select SUM(amount) as total from hb_customer_transaction where customer_id ='".$this->customer_id."' AND amount>0 ");
		$total=empty($money_total["total"])?'0.00':sprintf("%.2f",$money_total["total"]);
		//提供一个消费时间  如果没有，返回当前月
		$record_month=getRow("select DATE_FORMAT(date_added,'%Y/%m') as date_added FROM hb_customer WHERE customer_id ='".$this->customer_id."' ");
		$this->res["cord_month"]=empty($record_month)?date("Y/m"):$record_month["date_added"];
		if(!empty($record)){
			foreach($record as $k=>$v){
				$arr[$k]["date"]=$v["date_added"];
				$arr[$k]["money"]=sprintf("%.2f",$v["amount"]>=0?"+".$v["amount"]:$v["amount"]);
				$arr[$k]["date"]=date("Y年m月d日",strtotime($v["date_added"]));
				$arr[$k]["record_id"]=$v["customer_transaction_id"];
				//1 销售收入(分享收入) 2 购买支出 3分成收入(返利) 5.资金解锁 4.提现 6.分享退款，7.返利退款8购买退款
				if($v["type"]==1){
					//销售收入
					$arr[$k]["description"]="差价";
				}else if($v["type"]==2){
					$arr[$k]["description"]="支出";
				}else if($v["type"]==3){
					$arr[$k]["description"]="返利";
				}else if($v["type"]==4){
					$arr[$k]["description"]="提现";
				}else if($v["type"]==5){
					$arr[$k]["description"]="资金解锁";
				}else if($v["type"]==6){
					$arr[$k]["description"]="差价退款";
				}else if($v["type"]==7){
					$arr[$k]["description"]="返利退款";
				}else if($v["type"]==8){
					$arr[$k]["description"]="退款";
				}else if($v["type"]==9){
					$arr[$k]["description"]="购买会员";//会员订单  2017-4-3 cgl
				}
			}
		}

		// lcb 6-12
        $blanceInfo = getRow('SELECT * FROM `hb_balance` WHERE customer_id = ' .intval($this->customer_id). ' LIMIT 1');
        $this->res['money'] = empty($blanceInfo["balance"]) ? "0.00" : sprintf('%.2f', $blanceInfo["balance"]);
        $cashTemp = empty($blanceInfo["availabe_balance"]) ? "0.00": $blanceInfo["availabe_balance"];
        // $this->res["notcash"] = sprintf("%.2f", ($this->res['money'] - $cashTemp)); // 冻结金额
        $this->res["cash"] = empty($blanceInfo["availabe_balance"]) ? "0.00": sprintf('%.2f', $blanceInfo["availabe_balance"]);

        $scheme = isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] : 'http';
        $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : 'haiqihuocang.cn';
        $path = isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : '';
        if(!$path){
            $path = isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : '';
        }
        $path = str_ireplace('/api/index.php', '', $path);
        $url = $scheme.'://'.$host.'/'.ltrim($path, '/');

        $this->res["questionUrl"]=$url.'/web/app/question.html';


		$this->res["total"]=$total;
		$this->res["moneyrecord"]=$arr;
		return $this->res;
	}
	/**
	 * 查询个人的月账单统计
	 * cgl 2017-1-9
	 */
	public function month(){
		//查询日期   默认为当前月
		$date=isset($_POST["date"])?$_POST["date"]:date("Y/m");
		//总的账单
		$all=getData("select * from hb_customer_transaction where customer_id ='".$this->customer_id."' AND DATE_FORMAT(date_added,'%Y/%m') = '".$date."' ");
		$come="0.00";
		$out="0.00";
		//1 销售收入(分享收入) 2 购买支出 3分成收入(返利) 5.资金解锁 4.提现 6.分享退款，7.返利退款8购买退款
		$share="0.00";
		$buy="0.00";
		$return="0.00";
		$unlock="0.00";
		$cash="0.00";
		$share_return="0.00";
		$return_money="0.00";
		$buy_return="0.00";
		//1 销售收入(分享收入)  3分成收入(返利) 5.资金解锁 8购买退款
		$share_per="0";
		$return_per="0";
		$unlock_per="0";
		$return_money_per="0";
		//2 购买支出 4.提现 6.分享退款，7.返利退款
		$buy_per="0";
		$cash_per="0";
		$share_return_per="0";
		$buy_return_per="0";

		if(!empty($all)){
			foreach($all as $k=>$v){
				//总收入
				if($v["amount"]>0){
					$come+=sprintf("%.2f",$v["amount"]);
				}else{
					$out+=sprintf("%.2f",$v["amount"]);
				}
				if($v["type"]==1){
					$share+=sprintf("%.2f",$v["amount"]);
				}else if($v["type"]==2 || $v["type"]==9){//会员订单  2017-4-3 cgl
					$buy+=sprintf("%.2f",$v["amount"]);
				}else if($v["type"]==3){
					$return+=sprintf("%.2f",$v["amount"]);
				}else if($v["type"]==4){
					$cash+=sprintf("%.2f",$v["amount"]);
				}else if($v["type"]==5){
					$unlock+=sprintf("%.2f",$v["amount"]);
				}else if($v["type"]==6){
					$share_return+=sprintf("%.2f",$v["amount"]);
				}else if($v["type"]==7){
					$return_money+=sprintf("%.2f",$v["amount"]);
				}else if($v["type"]==8){
					$buy_return+=sprintf("%.2f",$v["amount"]);
				}
			}
		}
		//1 销售收入(分享收入)  3分成收入(返利) 5.资金解锁 8购买退款
		if($come!=0){
			$share_per=sprintf("%.2f",($share/$come*100));
			$return_per=sprintf("%.2f",($return/$come*100));
			$unlock_per=sprintf("%.2f",($unlock/$come*100));
			$buy_return_per=sprintf("%.2f",($buy_return/$come*100));
		}
		//2 购买支出 4.提现 6.分享退款，7.返利退款
		if($out!=0){
			$buy_per=abs($buy/$out)*100;
			$cash_per=abs($cash/$out)*100;
			$share_return_per=abs($share_return/$out)*100;
			$return_money_per=abs($return_money/$out)*100;
		}
		// if(!empty($all)){
			
			$this->res["come"]="$come";
			$this->res['out']="$out";
			//收入
			$this->res["all_come"]=array(
				"share"=>"$share",
				"return"=>"$return",
				"unlock"=>"$unlock",
				"buy_return"=>"$buy_return",
				"share_per"=>"$share_per",
				"return_per"=>"$return_per",
				"unlock_per"=>"$unlock_per",
				"buy_return_per"=>"$buy_return_per",
			);
			//支出
			$this->res["all_out"]=array(
				"buy"=>"$buy",
				"cash"=>"$cash",
				"share_return"=>"$share_return",
				"return_money"=>"$return_money",
				"buy_per"=>"$buy_per",
				"cash_per"=>"$cash_per",
				"share_return_per"=>"$share_return_per",
				"return_money_per"=>"$return_money_per",
			);
		// }
		return $this->res;
	}
	/**
	 * 账单详情
	 * cgl 2017-1-9
	 */
	public function detail(){
		$record_id=isset($_POST["record_id"])?$_POST["record_id"]:null;
		if(!empty($record_id)){
			$record=getRow("select *,a.date_added as date,a.type as type,a.status as status from hb_customer_transaction as a  where a.customer_id ='".$this->customer_id."' AND a.customer_transaction_id='".$record_id."' ");
			$arr=array();
			if(!empty($record)){
				$order=getRow("select * from hb_order where order_id = ".$record["order_id"]);
				$record=array_merge($record,$order);
				
				if($record["type"]==1){
					//销售收入
					$arr["status"]="差价";
				}else if($record["type"]==2){
					$arr["status"]="支出";
				}else if($record["type"]==3){
					$arr["status"]="返利";
				}else if($record["type"]==4){
					$arr["status"]="提现";
				}else if($record["type"]==5){
					$arr["status"]="资金解锁";
				}else if($record["type"]==6){
					$arr["status"]="差价退款";
				}else if($record["type"]==7){
					$arr["status"]="返利退款";
				}else if($record["type"]==8){
					$arr["status"]="退款";
				}else if($record["type"]==9){
					$arr["status"]="购买会员";//会员订单  2017-4-3 cgl
				}
				//1 销售收入(分享收入) 2 购买支出 3分成收入(返利) 5.资金解锁 4.提现 6.分享退款，7.返利退款8购买退款
				//1.支付宝，微信，余额支付2.支付宝，微信，余额支付3.支付宝，微信，余额支付5.资金解锁4支付宝，微信
				//6分享退款7.返利退款8购买退款
				// print_r($record);
				$arr["order"]=empty($record["order_id"])?"暂无订单号":$record["order_id"];
				$arr["paynum"]=!empty($record["pingzhen"])?$record["pingzhen"]:"暂无支付单号";
				$arr["msg"]="嗨企货仓";
				$arr["date"]=$record["date"];
				if($record["type"]==1 || $record["type"]==2 ||  $record["type"]==3){
					//销售收入
					if($record["payment_method"]==1){
						$arr["paymsg"]="支付宝";
					}else if($record["payment_method"]==2){
						$arr["paymsg"]="微信支付";
					}else{
						$arr["paymsg"]="余额支付";
					}
				}else if($record["type"]==4){
					if($record["pingzhen_type"]==1){
						$arr["paymsg"]="支付宝";
					}else if($record["pingzhen_type"]==2){
						$arr["paymsg"]="微信支付";
					}
				}else if($record["type"]==5){
					$arr["paymsg"]="资金解锁";
				}else if($record["type"]==6){
					$arr["paymsg"]="分享退款";
				}else if($record["type"]==7){
					$arr["paymsg"]="返利退款";
				}else if($record["type"]==8){
					$arr["paymsg"]="购买退款";
				}
				if($record["status"]==1){
					$arr["deal_status"]="处理中";	
				}else if($record["status"]==2){
					$arr["deal_status"]="处理成功";	
				}else if($record["status"]==3){
					$arr["deal_status"]="处理失败";
				}
				$arr["money"]=sprintf("%.2f",$record["amount"]>=0?"+".$record["amount"]:$record["amount"]);
				$this->res["detail"]=$arr;
			}else{
				$this->res["retcode"]='1001';
			}
		}else{
			$this->res["retcode"]='1000';
		}
		return $this->res;	
	}


}
?>