<?php
	//面向对象的control 类
	include "xcontrol/base.php";
	class salelimit
	{
		var $res=array("retcode"=>0,'msg'=>'success');
		var $customer_id=0;
		var $passkey="";
		function __construct() 
		{
	       // parent::__construct();
          if(isset($_POST["customerid"])){
               $customer_id = $_POST["customerid"];
            }else{
               $customer_id=@$_SESSION["default"]["customer_id"];
            }
            if(isset($_POST["passkey"])){
               $req_key=@$_POST["passkey"];
            }else{
               $req_key=@$_SESSION["default"]["passkey"];
            } 
		   $this->passkey=$req_key;
		   $this->customer_id=$customer_id;
	   	}
	   	/**
	   	 * 查询秒杀的列表   cgl  2017-4-7
	   	 */
   		function index(){
   			if($_SERVER['REQUEST_METHOD']=="POST"){
   				$sql="select sale_id,date_start,date_end,sale_name from hb_salelimit ";
               $sql1="select sale_id,date_start,date_end,sale_name from hb_salelimit ";//查询商品数据
               $status=isset($_POST["status"])?$_POST["status"]:null;//  默认为1   第一个时间
               $merchant_id=1;
               if(isset($this->customer_id)){
                  $customer=getRow("select * from hb_customer where customer_id = '".$this->customer_id."' ");
                  if(!empty($customer)){
                     if($customer["merchant_id"]!=0 && $customer["merchant_id"]!=1){
                        $merchant_id=$customer["merchant_id"];
                     }
                  }
               }
               //要为当天的时间   and UNIX_TIMESTAMP(date_start)<='".time()."'
               $sql.=" where merchant_id = '".$merchant_id."' and  UNIX_TIMESTAMP(date_end)>'".time()."'  and status=1 and date_format(date_start, '%Y%m%d') = '".date("Ymd")."'  order by date_start asc ";
               $da=getData($sql);
               $time=array();
               $now_time=array();
               if(!empty($da)){
                  foreach($da as $k=>$v){
                     $time[]=$v["date_start"];
                  }
               }
               $time1=array();
               //去重
               if(!empty($time)){
                  foreach($time as $k=>$v){
                     // if(date("H:i",strtotime($time[$k]))!=@date("H:i",strtotime($time[$k+1])) && date("H:i",strtotime($time[$k]))!=@date("H:i",strtotime($time[$k+2])) ){
                        $time1[]=$v;
                     // }
                  }
               }
               $time1[count($time1)]=date("Y-m-d 23:59:59",strtotime("+1 day"));
               $arr_content=array();
               if(!empty($time1)){
                  
                  // print_r($time1);
                  foreach($time1 as $k=>$v){
                     $arr=array();
                     if($status==$k+1){  //选中状态  1,0   是否
                        $arr=array("time"=>date("H:i",strtotime($v)),"is_selected"=>1);
                     }else{
                        $arr=array("time"=>date("H:i",strtotime($v)),"is_selected"=>0);
                     }
                     if(date("Y-m-d H:i:s",time()) >=$v && date("Y-m-d H:i:s",time())<date("Y-m-d H:i:s",strtotime("$v+2 hour")) ){
                        $arr["status"]=1;//抢购中
                        if($status==null){
                           $status=$k+1;
                           $arr1[$k+1]=1;//已经选中
                           $arr["is_selected"]=1;
                        }
                        $arr_content[$k]="限时抢购  好价总在犹豫中错过~";//展示内容
                     }else if(date("Y-m-d H:i:s",time())>=date("Y-m-d H:i:s",strtotime("$v+2 hour")) ){
                        $arr["status"]=2;//已开抢
                        $arr_content[$k]="还有好物等你继续抢购~";//展示内容
                     }else if(date("Y-m-d H:i:s",time())<$v ){
                        $arr["status"]=0;//即将开抢
                        $arr_content[$k]="限时抢购即将开始";//展示内容
                     }
                     $arr["info"]=$v;
                     // print_r($arr);
                     $time1[$k]=$arr;
                  }
               }
               $arr2="";
               if(!empty($time1)){
                  foreach($time1 as $k=>$v){//如果没有选中的  默认第一个
                     $arr2.=$v["is_selected"].",";
                  }
               }
               $a=substr($arr2,0,-1);
               $b=strpos($a,"1");
               if(!$b){
                  if(!empty($time1)){
                     $time1[0]["is_selected"]=1;
                  }
                  
               }
               if(@$status-1>0){
                  $in=$status-1;
               }else{
                  $in=0;
               }
               $this->res["data"]["content"]=@$arr_content[$in];
               $newArr=array();
               if(!empty($time1)){
                  for($j=0;$j<count($time1);$j++){
                     $newArr[]=$time1[$j]['time'];
                  }
                  array_multisort($newArr,$time1);
               }
               // if(!empty($time1)){
                  $index=count($time1);
                  if(@$status==$index){
                     $selected=1;//选中
                  }else{
                     $selected=0;//没选中
                  }
                  if(!empty($time1)){
                     $time_torrow=count($time1)-1;
                  }else{
                     $time_torrow=count($time1);
                  }
                  @$time1[$time_torrow]=array("time"=>"明日","is_selected"=>$selected,"status"=>0,"info"=>date("Y-m-d 00:00:00",strtotime("+1 day")));
               // }
               $start="";
               if(!empty($status)){
                  if($status>count($time1)){
                     $start=@$time1[0]["info"];
                  }else{
                     $start=@$time1[$status-1]["info"];
                  }
               }else{
                  $start=@$time1[0]["info"];   //cgl 2017-4-19  修改
               }
               if(!empty($start)){
                  $start_time=date("H:i",strtotime($start));
               }
               //判断是否是明天的时间  是明天
               if(@$status-@count($time1)==0)
               {
                  $sql1.=" where merchant_id = '".$merchant_id."' and  UNIX_TIMESTAMP(date_end)>='".time()."' and UNIX_TIMESTAMP(date_start)>='".strtotime($start)."' and status=1 and date_format(date_start, '%Y%m%d') = '".date("Ymd",strtotime($start))."'  order by date_start asc ";
               }else{
                  //要为当天的时间
                  $sql1.=" where merchant_id = '".$merchant_id."' and  UNIX_TIMESTAMP(date_end)>='".time()."' and UNIX_TIMESTAMP(date_start)='".strtotime($start)."' and status=1 and date_format(date_start, '%Y%m%d') = '".date("Ymd")."'  order by date_start asc ";
               }
               // print_r($time1);
   				$sale=getRow($sql1);
               $sale1=getData($sql1);
               $sale_id="";
               if(!empty($sale1)){
                  for($j=0;$j<count($sale1);$j++){
                     $newArr1[]=$sale1[$j]['date_start'];
                  }
                  array_multisort($newArr1,$sale1);
                  foreach($sale1 as $k=>$v){
                     $sale_id.=$v["sale_id"].",";
                  }
                  $sale_id=substr($sale_id,0,-1);
               }
               require_once 'home.php';
               $home=new home();
               $image=array();
               $image=$home->getBanner(23);
               if(!empty($sale)){
                  // foreach ($sale as $k => $v) {
                     //查询产品
                     $product=getData("select a.product_id,
                        b.image,
                        b.marketprice,
                        c.sale_id,
                        c.sale_price,
                        b.quantity,
                        b.sales,
                        d.date_start,
                        (select name from hb_product_description where product_id=a.product_id) as name
                      from hb_product_sale as a,hb_product as b ,hb_product_sale_price as c,hb_salelimit as d
                      where 
                      c.product_id=a.product_id 
                      and c.sale_id=a.sale_id 
                      and a.product_sale_id=c.product_sale_id 
                      and a.product_id=b.product_id 
                      and a.status=1 
                      and b.status=1 
                      and d.sale_id=c.sale_id

                      and a.sale_id in (".$sale_id.") 
                      and a.merchant_id= '".$merchant_id."' order by d.date_start asc ");
                     //$sale["sale_id"]
                     if(!empty($product)){
                        foreach ($product as $key => $value) {
                           $product[$key]["sale_price"]=sprintf("%.2f",$value["sale_price"]);
                           $product[$key]["marketprice"]=sprintf("%.2f",$value["marketprice"]);
                           //是否抢购完
                           $is_sale_status=0;
                           if($value["quantity"]==0){
                              $is_sale_status=1;  //已抢购完
                           }
                           $product[$key]["is_sale_status"]=$is_sale_status;
                           if($value["quantity"]+$value["sales"]==0){
                              $all=0;
                           }else{
                              $all=$value["quantity"]/($value["quantity"]+$value["sales"]);
                           }
                           $product[$key]["percent"]=sprintf("%.2f",$all)*100;
                           unset($product[$key]["sales"]);
                           //查询是否提醒  
                           $is_remind=getRow("select * from hb_salelimit_remind where product_id = '".$value["product_id"]."' and sale_id = '".$sale["sale_id"]."' and status!=0  and customer_id ='".$this->customer_id."' ");
                           if(!empty($is_remind)){
                              if($is_remind["status"]==1){  //已提交提醒
                                 $product[$key]["is_remind"]=1;
                              }else if($is_remind["status"]==0){ //已取消
                                 $product[$key]["is_remind"]=2; //还可以再次提醒
                              }else if($is_remind["status"]==2){//已提醒
                                 $product[$key]["is_remind"]=2;
                              }
                              $product[$key]["remind_id"]=$is_remind["remind_id"];
                           }else{
                              $product[$key]["remind_id"]=0;
                              $product[$key]["is_remind"]=2;  //未提醒
                           }

                           $product[$key]["start_time"]=date("H:i",strtotime(@$value["date_start"]));//$start_time;
                              //开始时间
                        }
                     }
                     $sale["product_info"]=$product;
                     $sale["date_start"]=strtotime($sale["date_start"]);
                     $sale["date_end"]=strtotime($sale["date_end"]);
                  // }
               }else{
                  $sale["product_info"]=array();
               }
               if(!empty($time1)){
                  foreach($time1 as $k=>$v){
                     // $time1[$k]["info"]=strtotime($v["info"]);
                     unset($time1[$k]["info"]);
                  }
               }
               if($_SERVER["SERVER_NAME"]=="iwant-u.com"){
                  $url_name="http://haiqihuocang.cn";
               }else{
                  $url_name="https://".$_SERVER["SERVER_NAME"];
               }
               $this->res["data"]["current_time"]=time();
               $this->res["data"]["share_url"]=$url_name."/web/buy_limit/index.html";
               $this->res["data"]["time"]=$time1;
               $this->res["data"]["sale_image"]=$image;
               $this->res["data"]["sale_activity"]=$sale;

   			}else{
	    		$this->res['msg']="请求方式错误"; //请求方式错误
	    		$this->res['retcode']=1180; //请求方式错误
	    	}
   			return $this->res;
   		}
         /**
          * 添加秒杀提醒 cgl 2017-4-18
          */

         function addSaleRemind(){
            if($_SERVER['REQUEST_METHOD']=="POST"){
               $product_id=isset($_POST["product_id"])?$_POST["product_id"]:null;
               $sale_id=isset($_POST["sale_id"])?$_POST["sale_id"]:null;
               $telephone=isset($_POST["telephone"])?$_POST["telephone"]:null;
               $merchant_id=1;
               if(isset($this->customer_id)){
                  $customer=getRow("select * from hb_customer where customer_id = '".$this->customer_id."' ");
                  if(!empty($customer)){
                     if($customer["merchant_id"]!=0 && $customer["merchant_id"]!=1){
                        $merchant_id=$customer["merchant_id"];
                     }
                  }
               }
                if(!preg_match("/^13[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|14[0-9]{9}$/",$telephone)){
                   //你的手机号不正确
                   $this->res["retcode"]=1208;
                   $this->res["msg"]="手机格式不正确";
               }else{
                  //判断该活动是否结束
                  $sql1="select sale_id,date_start,date_end,sale_name from hb_salelimit ";//查询商品数据
                  //要为当天的时间   and UNIX_TIMESTAMP(date_start)<='".time()."'
                  $sql1.=" where merchant_id = '".$merchant_id."' and  UNIX_TIMESTAMP(date_end)>'".time()."'  and status=1 and UNIX_TIMESTAMP(date_start) > '".time()."' and sale_id='".$sale_id."'  order by date_start asc ";
                  $sale_is_end=getRow($sql1);
                  if(!$sale_is_end){
                     $this->res['msg']="该限时活动已结束";
                     $this->res['retcode']=4013;
                  }else{
                     $is_remind=getRow("select * from  hb_salelimit_remind where customer_id ='".$this->customer_id."' and status=1 and product_id = '".$product_id."' and sale_id = '".$sale_id."' and telephone = '".$telephone."' ");
                     //查询是否已经通知过
                     if($is_remind){
                        $this->res['msg']="你已经添加该限时活动的提醒";
                        $this->res['retcode']=4011;
                     }else{
                        $data=array(
                           "product_id"=>$product_id,
                           "sale_id"=>$sale_id,
                           "customer_id"=>$this->customer_id,
                           "status"=>1,
                           "merchat_id"=>$merchant_id,
                           "telephone"=>$telephone,
                           "date_modified"=>date('Y-m-d H:i:s'),
                           "date_added"=>date('Y-m-d H:i:s')
                           );
                        saveData("hb_salelimit_remind",$data);
                     }
                  }
               }
            }else{
               $this->res['msg']="请求方式错误"; //请求方式错误
               $this->res['retcode']=1180; //请求方式错误
            }
            return $this->res;
         }
         /**
          * 取消提醒 cgl 2017-4-18
          */
         function cancelRemind(){
             if($_SERVER['REQUEST_METHOD']=="POST"){
               $remind=isset($_POST["remind_id"])?$_POST["remind_id"]:NULL;
               //是否有该提醒
               $is_remind=getRow("select * from hb_salelimit_remind where remind_id = '".$remind."' and status=1 or status=2 ");
               if(!$is_remind){
                  $this->res['msg']="该限时活动提醒不存在";
                  $this->res['retcode']=4012;
               }else{
                  exeSql("update hb_salelimit_remind set status=0,date_modified=NOW() where remind_id='".$remind."' and status=1 ");
               }
             }else{
               $this->res['msg']="请求方式错误"; //请求方式错误
               $this->res['retcode']=1180; //请求方式错误
            }
            return $this->res;
         }


	}
?>