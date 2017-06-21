<?php
	//面向对象的control 类
include "xcontrol/base.php";
class sale extends base{
	function __construct() 
	{
       parent::__construct();
	   $this->passkey=@$_SESSION["default"]['passkey'];
	   $this->customer_id=@$_SESSION["default"]['customer_id'];
   	}
   	/*
	*ws 2017.1.9 销售管理
   	*/
	function index(){
      //菜单
    set_time_limit(0); 
      $this->getMenu();
$whereStr="";
$ncwhereStr="";
$twhere="";
$dt=array();
      if(isset($_POST['datepicker']))
      {
        $dt=explode("-",$_POST['datepicker']);
        

      }
      else
      {
        $dt=array(date("m/d/Y",strtotime("-7 days")),date("m/d/Y",time()));
      }
      //print_r($dt);
      $dtStart=strtotime($dt[0]);
        $dtEnd=strtotime($dt[1]);
        $whereStr=" and o.date_added>=".$dtStart." and o.date_added<".$dtEnd;
        $ncwhereStr=" and nc.date_added>='".date("Y-m-d H:i:s",$dtStart)."' and nc.date_added<'".date("Y-m-d H:i:s",$dtEnd)."'";
        $twhere=" and t.date_added>='".date("Y-m-d H:i:s",$dtStart)."' and t.date_added<'".date("Y-m-d H:i:s",$dtEnd)."'";
//得到 指定时间内对应邀请码的 的客户数，销售额，返利就完美了
     /* $sql="SELECT p.telephone,p.lastname,p.customer_id,i.invitecode, sum(o.`total`) as totle ,  count(o.order_id) as num1,count(distinct nc.customer_id) as cnum,sum(t.amount) as rttotle   
        FROM `".DB_PREFIX."invitecode`  as i left join `".DB_PREFIX."customer`  as c on i.invitecode_id=c.invitecode_id 
        left join `".DB_PREFIX."order` as o on c.customer_id=o.customer_id and o.status=0 and o.order_status_id  in(3,4,5,11) $whereStr 
        left join hb_customer_transaction as t on t.order_id=o.order_id and t.type=3 
        left join ".DB_PREFIX."customer as nc on i.invitecode_id=nc.invitecode_id $ncwhereStr, 
        ".DB_PREFIX."customer as p 
        where  
        i.customer_id=p.customer_id   
        group by i.invitecode_id,i.customer_id,p.telephone,p.lastname";

*/
      /* $sql1="SELECT i.invitecode, count(distinct nc.customer_id) as cnum 
        FROM `".DB_PREFIX."invitecode`  as i left join ".DB_PREFIX."customer as nc on i.invitecode_id=nc.invitecode_id $ncwhereStr 
        group by i.invitecode_id,i.customer_id";
*/
         $sql="select bb.*,aa.cnum from (SELECT i.invitecode_id, count(distinct nc.customer_id) as cnum 
        FROM `".DB_PREFIX."invitecode`  as i left join ".DB_PREFIX."customer as nc on i.invitecode_id=nc.invitecode_id $ncwhereStr 
        group by i.invitecode_id,i.customer_id) as aa,
        (SELECT i.invitecode_id,p.telephone,p.lastname,p.customer_id,i.invitecode, sum(o.`total`) as totle ,  count(distinct o.order_id) as num1,sum(t.amount) as rttotle 
        FROM `".DB_PREFIX."invitecode`  as i ,
        `".DB_PREFIX."customer`  as c 
        left join `".DB_PREFIX."order` as o on c.customer_id=o.customer_id and o.status=0 and o.order_status_id  in(2,3,4,5,11,10,9,8) $whereStr ,
        hb_customer_transaction as t 
        , 
        ".DB_PREFIX."customer as p 
        where  i.invitecode_id=c.invitecode_id and t.order_id=o.order_id and t.type=3 $twhere 
         and i.customer_id=p.customer_id  and i.customer_id=t.customer_id  
        group by i.invitecode_id,i.customer_id,p.telephone,p.lastname order by i.customer_id)
        as bb
        where aa.invitecode_id=bb.invitecode_id 
        ";
       // echo $sql;
    	$data=getData($sql,600);

      $sum1=array("inviteNum"=>0,"proxyNum"=>0,"orderNum"=>0,"moneyNum"=>0,"rebackNum"=>0,"ncNum"=>0);
      $prox="";
    	foreach ($data as $key => $value) {
    		$data[$key]['record_url']=linkurl("sale/record");//销售记录url
        $data[$key]['total_url']=linkurl("sale/total");//销售统计url
        $sum1["inviteNum"]+=1;
        if($prox!=$value['customer_id'])
        {
          $sum1["proxyNum"]+=1;
          $prox=$value['customer_id'];
        }
        
        $sum1["orderNum"]+=$value['num1'];
        $sum1["rebackNum"]+=$value['rttotle'];
        $sum1["moneyNum"]+=$value['totle'];
        $sum1["ncNum"]+=$value['cnum'];

    	}
      $this->res['dt']=$dt;
      $this->res['sum1']=$sum1;
    	$this->res['data']=$data;
    	return $this->res;
	}
	/*
	*ws 2017.1.9 销售记录
  *2017.1.10 更改sql
	*/
	function record(){
       //菜单
       $this->getMenu();
      
       if(isset($_GET['id'])){
             $sql="select c.customer_id,c.lastname,c.telephone,p.name,sum(o.`total`) as price,o.ship_date ,p.quantity  from ".DB_PREFIX."customer as c,".DB_PREFIX."order as o,".DB_PREFIX."order_product as p  where c.customer_id=o.customer_id and o.order_id=p.order_id and parent_id='".$_GET['id']."' and o.ship_order_no is not null and o.ship_id is not null and c.status=1 and o.status=0 and o.order_status_id in(3,4,5,11) group by date_format(o.ship_date,'%Y%m%d%H%i%s')";
             $data=getData($sql);
             foreach ($data as $key => $value) {
                  $data[$key]["member_url"]=linkurl("sale/member");//会员信息url
             }
  	   }
       $this->res["index_url"]=linkurl("sale/index");
       $this->res['data']=$data;
       return $this->res;
	}
  /*
  * ws 2017.1.10 销售统计
  */
  function total(){
        //菜单
       $this->getMenu();

       $date1=isset($_POST['date'])?$_POST['date']:date("Y-m");
       $str=str_replace('-','',$date1);
       if(isset($_GET['id']) && is_numeric($str) ){
        $customer_id=$_GET['id'];
        $this->res['customer_id']=$customer_id;
        $sql="select c.customer_id,c.lastname,c.telephone,p.name,sum(o.total) as price,date_format(o.ship_date,'%e') as ship_date ,sum(p.quantity) as quantity from ".DB_PREFIX."customer as c,".DB_PREFIX."order as o,".DB_PREFIX."order_product as p where c.customer_id=o.customer_id and o.order_id=p.order_id  and o.ship_order_no is not null and o.ship_id is not null and parent_id='".$_GET['id']."' and c.status=1 and o.status=0 and o.order_status_id in(3,4,5,11)  and date_format(o.ship_date,'%Y%m')=$str group by date_format(o.ship_date,'%Y%m%d')";
         $data=getData($sql);
         $days=array();
         for($i=1;$i<=31;$i++){
          $days[]=$i;
         }
         $price=array();
         $quantity=array();
         $date=array();
             if(!empty($data)){
               foreach ($data as $key => $value) {
                 $price[]=$value['price'];
                 $quantity[]=$value['quantity'];
                 $date[]=$value['ship_date'];
               }
             }
             $arr1=array_combine($date, $quantity);
             $arr2=array_combine($date, $price);
             $finalquantity=array();
             $finalprice=array();
             foreach($days as $k=>$v){
                if(in_array($v, $date)){
                    array_push($finalquantity, $arr1[$v]);
                    array_push($finalprice, $arr2[$v]);
                }else{
                    array_push($finalprice,0);
                    array_push($finalquantity,0);
                }
             }
         $price=implode(",", $finalprice);
         $quantity=implode(",", $finalquantity);
         $date=implode(",", $days).",";
         $this->res["price"]=$price;
         $this->res["quantity"]=$quantity;
         $this->res["date"]=$date;
         $this->res["date1"]=$date1; 
        
       }
       $this->res["index_url"]=linkurl("sale/index");
       $this->res["total_url"]=linkurl("sale/total");
       return $this->res;
  }
  /*
  *ws 2017.1.12 会员信息
  */
  function member(){
      //菜单
      $this->getMenu();

      if(isset($_GET["id"])){
        $sql="select c.customer_id,c.headurl,c.firstname,c.lastname,c.telephone,c.card,i.invitecode,c.qq_openid,c.wechat_openid,a.address_1,a.address_2,a.firstname as name,s.name as sheng ,z.name as zone,a.city,a.custom_field from ".DB_PREFIX."customer as c,".DB_PREFIX."invitecode as i,".DB_PREFIX."address as a,".DB_PREFIX."country as s,".DB_PREFIX."zone as z where  c.parent_id=i.customer_id and c.customer_id=a.customer_id and s.country_id=a.country_id and a.zone_id=z.zone_id and a.customer_id=".$_GET['id'];//
        $sql1="select invitecode from ".DB_PREFIX."invitecode where customer_id=".$_GET['id'];
        $invite=getRow($sql1);
        if($invite){
          $this->res["invite"]=$invite;
        }else{
          $this->res["invite"]=null;
        }
        $data=getData($sql);
        foreach ($data as $key => $value) {
          $data[$key]["custom_field"]=json_decode($data[$key]["custom_field"],true);
            if($data[$key]["qq_openid"]){
              $this->res["qq"]="已绑定";
            }else{
              $this->res["qq"]="未绑定";
            }
            if($data[$key]["wechat_openid"]){
              $this->res["wechat"]="已绑定";
            }else{
              $this->res["wechat"]="未绑定";
            }
        }
      }
      $this->res["data"]=$data;
      return $this->res;
  }
  /**
   * 订单管理   cgl 2017-2-10
   */
  function order(){
    //分页
    require 'lib/pagination.php';
    $pagination=new pagination();
    //菜单
    $this->getMenu();

    //查询全部订单的数量
    // $all_order_num=1000;
    $all_order_num=getRow("SELECT count(*) as count from ".DB_PREFIX."order where merchant_id='".$_SESSION["merchant_id"]."'");
    // $page=isset($_GET["page"])?$_GET["page"]:1;
    if(isset($_GET["page"]) && @$_GET["page"]>=1 ){
      $page=$_GET["page"];
    }else{
      $page=1;
    }
    $limit=20;
    $start=($page-1)*$limit.",".$limit;
    $sql="select o.order_id,op.name,op.quantity,o.payment_method,FORMAT(o.total,2) as total,o.order_status_id,o.customer_id,
                      (select firstname from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
                      where customer_id=o.customer_id ) ) as parent_name,
                      (select telephone from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
                      where customer_id=o.customer_id ) ) as telephone,
                      o.date_added ,o.order_pay_trade_code,o.firstname,o.ship_order_no,o.ship_id,o.order_type,o.order_type_status,
                      o.return_add_money as amount,op.product_id as product_id,
                      (select format(proxyprice,2) from hb_product where product_id=op.product_id ) as proxyprice,o.order_pay_money
                      from " .DB_PREFIX. "order as o 
                      , ".DB_PREFIX."order_product as op
                      where o.merchant_id='".$_SESSION["merchant_id"]."' and o.order_id=op.order_id and o.order_type!=4 ";
    $count_sql="SELECT count(*) as count from ".DB_PREFIX."order as o left join ".DB_PREFIX."order_product as op on o.order_id=op.order_id  ";
    $cout=1;
    if(!isset($_GET["status_id"])){
      $url=linkurl("sale/order");
      $this->res["status"]=0;
      
    }else{
      if($_GET["status_id"]==4){
        $statuss="4,5";
      }else{
        if($_GET["status_id"]==2){
          $statuss="2,11";
        }else{
          $statuss=$_GET["status_id"];
        }
      }
      if($_GET["status_id"]==5){
        //退款
        $sql="select o.order_id,r.product as name,r.quantity,o.payment_method,FORMAT(o.total,2) as total,o.order_status_id,o.customer_id,
                      (select firstname from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
                      where customer_id=o.customer_id ) ) as parent_name,
                      (select telephone from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
                      where customer_id=o.customer_id ) ) as telephone,
                      r.return_status_id,
                      o.date_added ,o.order_pay_trade_code,r.date_added as return_time,o.firstname,o.ship_order_no,o.ship_id,
                      o.order_type,o.order_type_status,o.return_add_money as amount,
                      (select proxyprice from hb_product where product_id = r.product_id ) as proxyprice,
                      r.product_id as product_id,o.order_pay_money
                      from ".DB_PREFIX."return as r 
                      , ".DB_PREFIX."order as o 
                      where o.merchant_id= '".$_SESSION["merchant_id"]."'  and o.order_id=r.order_id  ";
        //and r.product_id = p.product_id
        // , ".DB_PREFIX."product as p
        $count_sql="SELECT count(*) as count from ".DB_PREFIX."return as r left join ".DB_PREFIX."order as o on o.order_id=r.order_id ";
        $cout=0;
        //cgl 2017-5-31 增加未处理的订单
        if(isset($_GET["return_status"])){
          $sql.=" and r.return_status_id = 1 ";
        }

      }else if(in_array($_GET["status_id"],array("1","2","3","4"))){
        //查询各个类型的订单
        $sql.=" and o.order_status_id in (".$statuss.") ";
      }
      $count_sql.=" where o.merchant_id='".$_SESSION["merchant_id"]."' ";
      if($cout==1){
        $count_sql.=" and o.order_status_id in (".$statuss.") ";
      }
      $url=linkurl("sale/order",array("status_id"=>$_GET["status_id"]));
      $this->res["status"]=$_GET["status_id"];
      
    }
    //根据条件查询
    if(isset($_GET["order"]) && !empty($_GET["order"])){
      if($_GET["type"]==1){//订单编号
        $sql.=" and o.order_id='".htmlspecialchars_decode($_GET["order"])."' ";
        if(isset($_GET["status_id"])){
          $count_sql.=" and o.order_id='".htmlspecialchars_decode($_GET["order"])."' ";  
        }else{
          $count_sql.=" where o.order_id='".htmlspecialchars_decode($_GET["order"])."' ";  
        }
      }
      if($_GET["type"]==2){//商品名称
        if(isset($_GET["status_id"]) && $_GET["status_id"]==5){
          //退款
          $sql.=" and r.product like '%".htmlspecialchars_decode($_GET["order"])."%' ";
        }else{
          $sql.=" and op.name like '%".htmlspecialchars_decode($_GET["order"])."%' ";
        }
        
        if(isset($_GET["status_id"])){
          if(isset($_GET["status_id"]) && $_GET["status_id"]==5){
            $count_sql.=" and r.product like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
          }else{
            $count_sql.=" and op.name like '%".htmlspecialchars_decode($_GET["order"])."%' ";
          }
        }else{
          $count_sql.=" where op.name like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
        }  
      }
      if($_GET["type"]==3){//购买人
        $sql.=" and o.firstname like '%".htmlspecialchars_decode($_GET["order"])."%' ";
        if(isset($_GET["status_id"])){
          $count_sql.=" and o.firstname like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
        }else{
          $count_sql.=" where o.firstname like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
        }
      }
    }
    if(isset($_GET["start"]) && !empty($_GET["start"])){
      $sql.=" and o.date_added>=".strtotime($_GET["start"])." ";
      if(isset($_GET["status_id"])){
        $count_sql.=" and o.date_added>=".strtotime($_GET["start"])." ";  
      }else{
        $count_sql.=" where o.date_added>=".strtotime($_GET["start"])." ";  
      }
    }
    if(isset($_GET["end"]) && !empty($_GET["end"])){
      $sql.=" and o.date_added<".strtotime($_GET["end"])." ";
      if(isset($_GET["status_id"])){
        $count_sql.=" and o.date_added<".strtotime($_GET["end"])." ";  
      }else{
        $count_sql.=" and o.date_added<".strtotime($_GET["end"])." ";  
      }
    }
    //cgl   2017-2-27  增加团购订单的搜索  sql条件    

    if(isset($_GET["order_type_status"])){
      if(in_array($_GET["order_type_status"],array(1,2,3,4) )){
        $sql.=" and o.order_type_status= ".$_GET["order_type_status"]." ";
        $count_sql.=" and o.order_type_status= ".$_GET["order_type_status"]." ";  
      }
      else{
        $sql.=" and o.order_type_status in (1,2,3,4) ";
        $count_sql.=" and o.order_type_status in (1,2,3,4) ";  
      }

    }
    //cgl   2017-2-27  增加团购订单的搜索   路径
    if(isset($_GET["order_type_status"])){
      if(in_array($_GET["order_type_status"],array(1,2,3,4) )){
        $url.="&order_type_status=".$_GET["order_type_status"];
      }else{
        $url.="&order_type_status=0";
      }
    }

    //搜索路径
    if(isset($_GET["page"])){
      $this->res["href_url"]=$url."&page=".$page;
    }else{
      $this->res["href_url"]=$url;
    }
    $this->res["start"]=isset($_GET["start"])?$_GET["start"]:"";
    $this->res["end"]=isset($_GET["end"])?$_GET["end"]:"";
    $this->res["order_id"]=isset($_GET["order"])?$_GET["order"]:"";

    if(isset($_GET["start"])){
      $url.="&start=".$_GET["start"];
    }
    if(isset($_GET["end"])){
      $url.="&end=".$_GET["end"];
    }
    if(isset($_GET["order"])){
      $url.="&order=".$_GET["order"];
    }
    if(isset($_GET["type"])){
      $url.="&type=".$_GET["type"];
    }
    $this->res["order_type1"]=isset($_GET["type"])?$_GET["type"]:1;

    $sql.=" ORDER BY o.order_id desc ";
    $get_status=isset($_GET["status_id"])?$_GET["status_id"]:1;
    //获取总页数
    if(getCache("last_count".$get_status)){
      $last=getCache("last_count".$get_status);
    }else{
      //查询多少页
      $this_count=getData($sql);
      $last=ceil(count($this_count)/20);
      setCache("last_count".$get_status,$last,3600*24);//缓存时间为24小时
      $last=getCache("last_count".$get_status);
    }
    
    $is_end_page=0;
    if($page==$last){
      $is_end_page=1;//是最后一页
    }
    $this->res["is_end_page"]=$is_end_page;
    $this->res["total_page"]=$last;
    $sql.=" limit $start";
    // echo $sql;
    //查询全部订单
    $order=getData($sql);
    // $order_num=getRow($count_sql);
    if(!empty($order)){
        foreach ($order as $key => $value) {
          //实际支付金额 cgl 2017-6-5
          if($value["order_pay_money"]=="" || $value["order_pay_money"]==NULL || $value["order_pay_money"]==null){
            $order[$key]["order_pay_money"]="0.00";
          }
          if($value["payment_method"]==3){
            $order[$key]["order_pay_money"]=$value["total"];
          }

          //订单金额是否和实际支付一样
          if($order[$key]["order_pay_money"]==$value["total"]){
            $order[$key]["is_diff"]=0;//相同
          }else{
            $order[$key]["is_diff"]=1;//金额不相同  
          }

          if($value["payment_method"]==1){
            $order[$key]["pay"]="app支付(支付宝)";
          }else if($value["payment_method"]==2){
            $order[$key]["pay"]="app支付(微信)";
          }else if($value["payment_method"]==3){
            $order[$key]["pay"]="余额支付";
          }else if($value["payment_method"]==4){
            $order[$key]["pay"]="支付宝混合支付";
          }else if($value["payment_method"]==5){
            $order[$key]["pay"]="微信混合支付";
          }else if($value["payment_method"]==6){
            $order[$key]["pay"]="微信h5支付";
          }else if($value["payment_method"]==7){
            $order[$key]["pay"]="支付宝h5支付";
          }
          //查询邀请码
          $cus=getRow("select parent_id from ".DB_PREFIX."customer where customer_id= '".$value["customer_id"]."' ");
          $invite=getRow("select invitecode from hb_invitecode where customer_id = '".@$cus["parent_id"]."' ");

          $invitecode=@$invite["invitecode"];

          if(!empty($value["parent_name"])){
            $order[$key]["parent"]=$value["parent_name"]."(".$value["telephone"].",".$invitecode.")"; 
          }else{
            $order[$key]["parent"]='无所属'; 
          }
          $order[$key]["date_added"]=date("Y-m-d H:i:s",$value["date_added"]);
          if($order[$key]["order_status_id"]==1){
            $order[$key]["status"]='未付款';
            $order[$key]["ok_accept"]=1;//确认收到货款
          }else if($order[$key]["order_status_id"]==2){
            $order[$key]["status"]='待出库';
             $order[$key]["send_goods_now"]=1;//立即出库
          }else if($order[$key]["order_status_id"]==3){
            $order[$key]["status"]='待收货';
            $order[$key]["accept_goods"]=1;//收货
          }else if($order[$key]["order_status_id"]==4){
            $order[$key]["status"]='已收货'; 
          }else if($order[$key]["order_status_id"]==5){
            $order[$key]["status"]='已完成'; 
          }else if($order[$key]["order_status_id"]==6){
            $order[$key]["status"]='已取消'; 
          }else if($order[$key]["order_status_id"]==7){
            $order[$key]["status"]='已关闭(删除状态)'; 
          }else if($order[$key]["order_status_id"]==11){
            $order[$key]["status"]='已出库(待发货)'; 
            $order[$key]["send_now"]=1;//立即发货
          }
          //是否申请退款
          $is_return=getRow("select * from ".DB_PREFIX."return where order_id='".$value["order_id"]."' ");
          if($is_return){
            $order[$key]["is_return"]=1;
          }
          if(isset($value["return_status_id"])){
            if($value["return_status_id"]==1){
              $order[$key]["status"]='待处理';
              $order[$key]["deal_status"]=1;//去处理
            }else if($value["return_status_id"]==2){
              $order[$key]["status"]='等待商品被寄出'; 
            }else if($value["return_status_id"]==3){
              $order[$key]["status"]='同意退款'; 
            }else if($value["return_status_id"]==4){
              $order[$key]["status"]='卖家拒绝退款'; 
            }
            //有退款
            $order[$key]["return_status"]=1; 
          }else{
            if($cout==0){
              unset($order[$key]["status"]);
            }
            $order[$key]["return_status"]=0; 
          }
           //订单详情 
          $order[$key]["order_detail"]=linkurl("sale/order_detail",array("order"=>$value["order_id"]));
          if($value["order_type"]==1 || $value["order_type"]==null){
            $info="普通订单";
          }else if($value["order_type"]==2){
            $info="团购";
            if($value["order_type_status"]==1){
              $info.="<span style='color:red;'><br/>(未付款)</span>";
            }else if($value["order_type_status"]==2){
              $info.="<span style='color:red;'><br/>(未成团)</span>";
            }else if($value["order_type_status"]==3){
              $info.="<span style='color:red;'><br/>(开团成功)</span>";
            }else if($value["order_type_status"]==4){
              $info.="<span style='color:red;'><br/>(开团失败)</span>";
            }
          }else if($value["order_type"]==3){
            $info="秒杀订单";
          }else if($value["order_type"]==5){
            $info="抽奖订单";
          }
          $order[$key]["order_type_info"]=@$info;
          //截取商品名称
          $order[$key]["name"]=mb_substr($value["name"],0,22,"utf-8");
          $order[$key]["product_name"]=$value["name"];
          //计算固定利益
          $order[$key]["amount"]=sprintf("%.2f",$value["amount"]*$value["quantity"]);
          
        }
    }
    $order_type_status=0;
    if(isset($_GET["order_type_status"])){
      if(in_array($_GET["order_type_status"],array(1,2,3,4))){
        $order_type_status=$_GET["order_type_status"];
      }else{
        $order_type_status=0;
      }
    }else{
      $order_type_status=0;
    }
    $all_count=0;
    //设置缓存   17-3-30 cgl
    if(getCache("order_count")){
      $all_count=getCache("order_count");
    }else{
      setCache("order_count",$all_order_num["count"],3600);
      $all_count=getCache("order_count");
    }
    //查询全部订单的数量
    $this->res["all_order_num"]=$all_count;
    //团购订单状态
    $this->res["order_group_type_status"]=$order_type_status;
    // setCache("order",$order,3600);
    // $order1=getCache("order");

    // print_r($order1);
    $this->res["order"]=$order;


    if(sizeof($order )<20)
      $this->getPages($page,$page);
    else
      $this->getPages($page);

    $start=isset($_GET["start"])?$_GET["start"]:"";
    $end=isset($_GET["end"])?$_GET["end"]:"";


    //$pagination->page(,$page,$limit,$url);//$order_num["count"]
    //$this->res["pagination"]=
    $this->res["order_url"]=linkurl("sale/order");
    //确认收货
    $this->res["confirm_goods"]=linkurl("sale/confirm_goods");
    //立即出库  
    $this->res["now_send_goods"]=linkurl("sale/now_send_goods");
    //查询所有的物流公司
    $this->res["ship_company"]=getData("select * from ".DB_PREFIX."shipping ");
    //立即发货
    $this->res["now_send_goods_by_time"]=linkurl("sale/now_send_goods_by_time");
    //收货地址
    $this->res["accept_address"]=linkurl("sale/accept_address");
    //收到货款
    $this->res["accept_money"]=linkurl("sale/accept_money");
    //退款处理
    $this->res["deal_return"]=linkurl("sale/deal_return");
    //退款详情
    $this->res["return_detail"]=linkurl("sale/return_detail");
    //导出订单
    $this->res["orderExport"]=linkurl("sale/orderExport");
    //编辑物流单号
    $this->res["editWuliu"]=linkurl("sale/editWuliu");
    return $this->res;
  }
  /**
   * cgl 2017-6-8 取消出库
   */
  function cancelgoods(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
    $order1=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      if($order1["order_status_id"]!=11){
        $json["msg"]="该订单的状态错误,不能取消出库";
      }else{
        $json["msg"]="操作成功";
        exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '2',date_modified=NOW() WHERE order_id = '" . $order . "'");
        //增加订单记录
        saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"2","notify"=>"0","comment"=>"取消出库","date_added"=>date("Y-m-d H:i:s")));
      }
    }
    echo json_encode($json);
    die;
  }

  /**
   * cgl  2017-2-13  确认收货
   */
  function confirm_goods(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
    $order1=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      if($order1["order_status_id"]!=3){
        $json["msg"]="该订单的状态错误,不能收货";
      }else{
        $is_return=getRow("SELECT * FROM ".DB_PREFIX."return where order_id = '".$order."' AND return_status_id = 1 ");
        if($is_return){
          $json["msg"]="有申请退款的订单，不能操作";
        }else{
          if(date("Ymd",time())<date("Ymd",strtotime("+1week",strtotime($order1["ship_date"])))){
            $json["msg"]="发货时间一周后才可以操作";
          }else{
            $json["msg"]="操作成功";
            exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '4',date_modified=NOW() WHERE order_id = '" . $order . "'");
            //增加订单记录
            saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"4","notify"=>"0","comment"=>"已确认收货","date_added"=>date("Y-m-d H:i:s")));
          }
        }
      }
      // print_r($order1);
    }
    echo json_encode($json);
    die;
  }
  /**
   * cgl  2017-2-13 收货地址
   */
  function accept_address(){
    $order=$_POST["order"];
    $json=array();
    $order_list=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
    if(empty($order_list)){
      $json["msg"]="没有该订单";
    }else{
      $address=$order_list["shipping_country"].$order_list["shipping_zone"].$order_list["shipping_city"].$order_list["shipping_address_1"];
      $phone=json_decode($order_list["shipping_custom_field"],"json");
      $data["phone"]=empty($phone[1])?"未填写电话":$phone[1];//$order_list["telephone"];
      $data["address"]=$address;
      $data["buyer"]=$order_list["shipping_firstname"];
      $data["order_id"]=$order_list["order_id"];
      $json=$data;
    }
    echo json_encode($json);
    die;
  }
  /**
   * cgl  2017-2-13 立即出库
   */
  function now_send_goods(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
    $order1=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      if($order1["order_status_id"]!=2){
        $json["msg"]="该订单的状态错误,不能出库";
      }else{
         $is_return=getRow("SELECT * FROM ".DB_PREFIX."return where order_id = '".$order."' AND return_status_id = 1 ");
         if($is_return){
           $json["msg"]="有申请退款的订单，不能操作";
         }else{
          if($order1["order_type"]==2){
            if($order1["order_type_status"]!=3){
              //团购
              $json["msg"]="团购必须成功，才能出库";
            }else{
              $json["msg"]="出库成功";
              exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '11',date_modified=NOW() WHERE order_id = '" . $order . "'");
             //增加出库订单记录
              saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"11","notify"=>"0","comment"=>"已经出库，待发货","date_added"=>date("Y-m-d H:i:s")));
            }
          }else{
             $json["msg"]="出库成功";
             exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '11',date_modified=NOW() WHERE order_id = '" . $order . "'");
             //增加出库订单记录
              saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"11","notify"=>"0","comment"=>"已经出库，待发货","date_added"=>date("Y-m-d H:i:s")));
          }
         }
      }
    }
    echo json_encode($json);
    die;
  }
/**
   * cgl  2017-2-14 立即发货
   */
  function now_send_goods_by_time(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
    $order1=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      if($order1["order_status_id"]!=11){
        $json["msg"]="该订单的状态错误,不能发货";
      }else{
         $is_return=getRow("SELECT * FROM ".DB_PREFIX."return where order_id = '".$order."' AND return_status_id = 1 ");
         if($is_return){
           $json["msg"]="有申请退款的订单，不能操作";
         }else{
          if($order1["order_type"]==2){
            if($order1["order_type_status"]!=3){
              //团购
              $json["msg"]="团购必须成功，才能出库";
            }else{
               $json["msg"]="发货成功";
              //发送短信  通知用户
              $com=getRow("select * from ".DB_PREFIX."shipping where id='".@$_POST["shipid"]."'");
              include_once 'lib/sms.php';
              $sms=new Sms();
              $msg="【嗨企货仓】"."尊敬的".$order1['firstname'].",您的商品已由".@$com['com']."快递发出，运单号为".@$_POST["shipcode"]."，请前往客户端查看物流详情";
              $mobile=$order1["telephone"];
              $sms->sendSingleMt($mobile,$msg);
              exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '3',date_modified=NOW(),ship_date=NOW(),ship_id='".$_POST["shipid"]."',ship_order_no='".$_POST["shipcode"]."' WHERE order_id = '" . $order . "'");
               //增加出库订单记录
              saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"3","notify"=>"0","comment"=>"已经发货，待收货","date_added"=>date("Y-m-d H:i:s")));
            }
          }else{
              $json["msg"]="发货成功";
              //发送短信  通知用户
              $com=getRow("select * from ".DB_PREFIX."shipping where id='".@$_POST["shipid"]."'");
              include_once 'lib/sms.php';
              $sms=new Sms();
              $msg="【嗨企货仓】"."尊敬的".$order1['firstname'].",您购买的商品已搭乘".@$com['com']."快递列车，班次(运单号)".@$_POST["shipcode"]."出发，请前往“嗨企货仓”客户端查看物流详情";
              $mobile=$order1["telephone"];
              $sms->sendSingleMt($mobile,$msg);
              exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '3',date_modified=NOW(),ship_date=NOW(),ship_id='".$_POST["shipid"]."',ship_order_no='".$_POST["shipcode"]."' WHERE order_id = '" . $order . "'");
               //增加出库订单记录
              saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"3","notify"=>"0","comment"=>"已经发货，待收货","date_added"=>date("Y-m-d H:i:s")));
          }
         }
      }
    }
    echo json_encode($json);
    die;
  }
  /**
   * cgl 2017-2-14 收到货款   1为支付宝，2为微信
   */
  function accept_money(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
    $order1=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      if($order1["order_status_id"]!=1){
        $json["msg"]="该订单的状态错误,不能操作";
      }else{
        if($_POST["payway"]==1 && empty($_POST["pay_code"]) ){
          $json["msg"]="支付宝的支付单号必填";
        }else{
          if($_POST["payway"]==1){
            //支付宝
            require_once("lib/alipay.config.php");
            require_once("lib/lib/alipay_core.function.php");
            require_once("lib/aop/AopClient.php");
            require_once("lib/aop/request/AlipayTradeQueryRequest.php");

            $aop = new AopClient ();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = '2016082601805285';
            $aop->rsaPrivateKeyFilePath = $alipay_config["private_key_path"];//RSA私钥
            $aop->alipayPublicKey       = $alipay_config["ali_public_key_path"];//支付宝公钥
            $aop->apiVersion = '1.0';
            $aop->postCharset='utf-8';
            $aop->format='json';
            $request = new AlipayTradeQueryRequest ();
            $out_trade_no=$order1["order_num"];
            $trade_no=$_POST["pay_code"];

            //ssdf20160910e1g8    2016091021001004000247043378
            $request->setBizContent("{" .
                "    \"out_trade_no\":\"$out_trade_no\"," .
                "    \"trade_no\":\"$trade_no\"" .
                "  }");
            $result = $aop->execute ( $request,null);
            $res=$result->alipay_trade_query_response;
            //判断是否已经成功
            if($res->code=="10000" && $res->msg=="Success" && $res->trade_status=="TRADE_SUCCESS"){
              if($res->out_trade_no!=$order1["order_num"]){
                $json["msg"]="支付宝订单号和平台订单号不一致";
              }else{
                // 修改订单状态 成功
                exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '2',date_modified=NOW() WHERE order_id = '" . $order . "'");
               //增加订单记录
                saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"2","notify"=>"0","comment"=>"已经付款，待发货","date_added"=>date("Y-m-d H:i:s")));
                $json["msg"]="操作成功";
              }
            }else{
              $json["msg"]="该订单没有支付";
            }
          }else if($_POST["payway"]==2){
            //微信
            $order_out = getRow("SELECT * FROM `" .DB_PREFIX. "orderout` WHERE order_id = " .$order);
            if($order_out){
              $type = 2;
            }else{
              $type = 1;
            }
            //微信
            // $json["msg"]="这是微信";
            require_once("lib/lib/WxPay.Api.php");
            require_once("lib/lib/WxPayPubHelper.php");
            //$type=1时是app收到货款，=2时是分享或H5的收到货款
            if($type == 1){
              $input = new \WxPayOrderQuery();
              //商户支付订单id
              $transaction_out_id = $order1["order_num"];
              $input->SetOut_trade_no($transaction_out_id);
              $result = \WxPayApi::orderQuery($input);
            }else{
              $res = new \OrderQuery_pub();
              $transaction_out_id = $order1["order_num"];
              $res->setParameter('out_trade_no',$transaction_out_id);
              $result = $res->getResult();
            }
              if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS" && $result["trade_state"] == "SUCCESS")
              {
                if($result["out_trade_no"]!=$order1["order_num"]){
                  $json["msg"]="微信订单号和平台订单号不一致";
                }else{
                   // 修改订单状态 成功
                  exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '2',date_modified=NOW() WHERE order_id = '" . $order . "'");
                 //增加订单记录
                  saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"2","notify"=>"0","comment"=>"已经付款，待发货","date_added"=>date("Y-m-d H:i:s")));
                  $json["msg"]="操作成功";
                }
              }else{
                $json["msg"]="该订单没有支付";
              }
          }
        }
      }
    }
    echo json_encode($json);
    die;
  }
  /**
   * cgl  2017-2-14  退款处理
   */
  function deal_return(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
    $order1=getRow("select * from ".DB_PREFIX."order as o left join ".DB_PREFIX."return as r on o.order_id=r.order_id where o.order_id='".$order."' and o.merchant_id='".$_SESSION["merchant_id"]."' and r.return_status_id=1 ");
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      $history=getRow("select * from hb_order_history where order_id = '".$order."' and order_status_id in (4,5) ");
      $date_modified=@$history["date_added"];

      if($_POST["status"]=="1"){
        //同意退款
         if($order1["order_status_id"]==3){
            $json["msg"]="该订单待收货,不能操作";
          }else if($order1["order_status_id"]==11){
            $json["msg"]="该订单已出库,不能操作";
          }else if( ($order1["order_status_id"]==4 || $order1["order_status_id"]==5) && date("Ymd")>date("Ymd",strtotime("+1week",strtotime($date_modified) )) ){
            //cgl 2017-3-24 修改
            $json["msg"]="该订单已收货,已确认收货七天之后,不能操作";
          }else if( $order1["order_status_id"]==6){
            $json["msg"]="该订单已取消,不能操作";
          }else if( $order1["order_status_id"]==7){
            $json["msg"]="该订单已关闭,不能操作";
          }else{
            //修改订单为退款状态  cgl  2017-2-15   
            exeSql("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '7' WHERE order_id = '" . $order1["order_id"] . "'");
            //增加订单记录
            saveData(DB_PREFIX."order_history",array("order_id"=>$order,"order_status_id"=>"7","notify"=>"0","comment"=>"已关闭了订单","date_added"=>date("Y-m-d H:i:s")));
            //查询是否有第三方分享的订单
            $sed=getRow("SELECT * FROM `" .DB_PREFIX. "orderout` WHERE order_id = ".$order1["order_id"]);
            $merchant_id = $order1['merchant_id'];
            $return_money = $order1['total']; //- $order1['freight']-$order1['invoicefee'];
            

            $customer_id=$order1["customer_id"];
            $order_id=$order1["order_id"];
            if($sed){
              //增加第三方的价格
              //增加用户资金记录
              //将返利人的资金扣除
              //以及增加商户的返利记录
              $this->money_record22(array("differenceprice"=>$sed["differenceprice"],"customer_id"=>$sed["customer_id"],"order_id"=>$sed["order_id"],"merchant_id"=>$order1["merchant_id"]));
              //将商户余额中的该订单金额退还
              $money = $return_money - $sed["differenceprice"]-$order1['freight'];
            }else{
              //将商户余额中的该订单金额退还
              $money = $return_money-$order1['freight'];
            }
            //cgl 2017-6-5 未发货退邮费处理
            if($order1["order_status_id"]<=2 || $order1["order_status_id"]==11){
              $money=$money+$order1["freight"];   //退款增加运费
            }
            if($money<0){
              $money=0;
            }

            //将商户余额中的该订单金额退还
            exeSql("update `" .DB_PREFIX. "merchant` set money = money-".(float)$money." where merchant_id = " .(int)$merchant_id);
            //退还退款人的资金
            exeSql("UPDATE `" .DB_PREFIX. "balance` SET balance = balance+" .$money. ",availabe_balance = availabe_balance+" .$money. ",date_modified = NOW() WHERE customer_id = " .$customer_id);
            //增加退款的商户资金明细记录  cgl   2017-2-15  增加
            // exeSql("insert `" .DB_PREFIX. "merchant_money_record` set money = -".(float)$money." , merchant_id = " .(int)$merchant_id.",add_time=NOW(),remark='订单退款:".$order_id."',relate_id=$order_id,type=5,customer_id=$customer_id ");
            saveData(DB_PREFIX."merchant_money_record",
              array("money"=>"-".(float)$money,
                    "merchant_id"=>$merchant_id,
                    "add_time"=>date("Y-m-d H:i:s",time()),
                    "remark"=>"订单退款:".$order_id,
                    "relate_id"=>$order_id,
                    "type"=>5,
                    "customer_id"=>$customer_id
                )
            );
            //增加退款用户的资金记录
            $this->insertmoney($return_money,$customer_id,$order1["order_id"],$order1["merchant_id"]);
            //退款返利的价格  cgl 2017-2-15 
            $this->add_return_money(array("customer_id"=>$customer_id,"order_id"=>$order1["order_id"],"merchant_id"=>$order1["merchant_id"],"return_add_money"=>$order1["return_add_money"]));

            $sql = "UPDATE `" .DB_PREFIX. "return` SET return_status_id = '3',date_modified = NOW() WHERE return_id = ".$order1["return_id"];
            exeSql($sql);
            saveData(DB_PREFIX."return_history",array("return_id"=>$order1["return_id"],"return_status_id"=>3,"comment"=>empty($_POST["reply"])?"已拒绝退款":'卖家已同意了退款申请,'.$_POST["reply"],"date_added"=>date("Y-m-d H:i:s",time()),"notify"=>0));
            
            $json["msg"]="该订单已同意退款";
          }

      }else if($_POST["status"]=="2"){
        $sql = "UPDATE `" .DB_PREFIX. "return` SET return_status_id = '4',date_modified = NOW() WHERE return_id = ".$order1["return_id"];
        exeSql($sql);
        saveData(DB_PREFIX."return_history",array("return_id"=>$order1["return_id"],"return_status_id"=>4,"comment"=>empty($_POST["reply"])?"已拒绝退款":$_POST["reply"],"date_added"=>date("Y-m-d H:i:s",time()),"notify"=>0));
        $json["msg"]="该订单已拒绝退款";
      }
     
    }
    echo json_encode($json);
    die;
  }
  /**
   * 第三方分享的退款   cgl  2017-2-15
   */
  //用户退款之后，金额返回
  function money_record22($v){
    //增加数量  cgl  2017-1-19
    $order_product=getRow("select * from `".DB_PREFIX."order_product` where order_id='".$v["order_id"]."' ");

    //减少第三方的价格
    exeSql("UPDATE " . DB_PREFIX . "balance set balance=balance-'".$v["differenceprice"]*$order_product["quantity"]."' WHERE customer_id = '" . $v["customer_id"] . "'");
    $customer_id=$v["customer_id"];
    //查询该用户的资金
    $balance=getRow("select * from `".DB_PREFIX."balance` where customer_id='".$customer_id."' ");
    $customer=getRow("select * from `".DB_PREFIX."customer` where customer_id='".$customer_id."' ");

    $balance_money=empty($balance["balance"])?"0.00":$balance["balance"];
    //余额
    $availabe_balance=empty($balance["availabe_balance"])?"0.00":$balance["availabe_balance"];
    
    //增加用户资金记录
    // $this->db->query("INSERT INTO `" .DB_PREFIX. "customer_transaction` SET customer_id = '" .$v["customer_id"]. "',order_id='".$v["order_id"]."',description='分享的用户申请了退款:".$v["differenceprice"]*$order_product["quantity"]."',amount=-".$v["differenceprice"]*$order_product["quantity"].",date_added = NOW(),status=2,balance_change='-".$v["differenceprice"]*$order_product["quantity"]."',availabe_balance_change='0',date_modified=NOW(),pingzhen='0',pingzhen_type=0,relate_merchant_id='0',last_balance='".$balance_money."',last_availabe_balance='".$availabe_balance."',type=6");

    saveData(DB_PREFIX."customer_transaction",
            array("customer_id"=>$v["customer_id"],
                  "order_id"=>$v["order_id"],
                  "description"=>'分享的用户申请了退款:'.$v["differenceprice"]*$order_product["quantity"],
                  "amount"=>"-".$v["differenceprice"]*$order_product["quantity"],
                  "date_added"=>date("Y-m-d H:i:s",time()),"status"=>2,
                  "balance_change"=>"-".$v["differenceprice"]*$order_product["quantity"],
                  "availabe_balance_change"=>0,
                  "date_modified"=>date("Y-m-d H:i:s",time()),
                  "pingzhen"=>0,
                  "pingzhen_type"=>0,
                  "relate_merchant_id"=>0,
                  "last_balance"=>$balance_money,
                  "last_availabe_balance"=>$availabe_balance,
                  "type"=>6)
            );

    //查询是否有返利情况
    if(!empty($customer["parent_id"])){
      $first=getRow("select * from `".DB_PREFIX."customer` where customer_id='".$customer["parent_id"]."' ");
      //查询上一级的资金
      $first_money=getRow("select * from `".DB_PREFIX."balance` where customer_id='".$customer["parent_id"]."' ");
      //查询订单号
      $order=getRow("select * from `".DB_PREFIX."order` where order_id='".$v["order_id"]."' ");
      
      //返利资金
      $return_money=$order["return_add_money"]*$order_product["quantity"];
      //扣除资金
      exeSql("UPDATE `" . DB_PREFIX . "balance` set balance=balance-'".$return_money."' WHERE customer_id = '" . $customer["parent_id"] . "'");
      //增加返利人员的资金记录
      // $this->db->query("INSERT INTO `" .DB_PREFIX. "customer_transaction` SET customer_id = '" .$customer["parent_id"]. "',order_id='".$v["order_id"]."',description='子级分享出去的商品，购买的用户申请了退款:".$v["differenceprice"]*$order_product["quantity"]."',amount=-".$return_money.",date_added = NOW(),status=2,balance_change='-".$return_money."',availabe_balance_change='0',date_modified=NOW(),pingzhen='0',pingzhen_type=0,relate_merchant_id='".$v["merchant_id"]."',last_balance='".$first_money["balance"]."',last_availabe_balance='".$first_money["availabe_balance"]."',type=7");
      saveData(DB_PREFIX."customer_transaction",
            array("customer_id"=>$customer["parent_id"],
                  "order_id"=>$v["order_id"],
                  "description"=>'子级分享出去的商品，购买的用户申请了退款:'.$v["differenceprice"]*$order_product["quantity"],
                  "amount"=>"-".$return_money,
                  "date_added"=>date("Y-m-d H:i:s",time()),"status"=>2,
                  "balance_change"=>"-".$return_money,
                  "availabe_balance_change"=>0,
                  "date_modified"=>date("Y-m-d H:i:s",time()),
                  "pingzhen"=>0,
                  "pingzhen_type"=>0,
                  "relate_merchant_id"=>$v["merchant_id"],
                  "last_balance"=>$first_money["balance"],
                  "last_availabe_balance"=>$first_money["availabe_balance"],
                  "type"=>7)
            );
      //增加商户的资金记录  商户增加资金
      $sql = "update `" .DB_PREFIX. "merchant` set money = money+".(float)$return_money." where merchant_id = " .(int)$v['merchant_id'];
      exeSql($sql);
      // $sql1 = "INSERT into `" .DB_PREFIX. "merchant_money_record` set money = +".(float)$return_money." , merchant_id = " .(int)$v['merchant_id'].",add_time=NOW(),remark='返利资金退款增加:".$v["order_id"]."',relate_id='".$v["order_id"]."',type=6,customer_id='".$customer["parent_id"]."' ";
      saveData(DB_PREFIX."merchant_money_record",
              array("money"=>"+".(float)$return_money,
                    "merchant_id"=>$v["merchant_id"],
                    "add_time"=>date("Y-m-d H:i:s",time()),
                    "remark"=>"返利资金退款增加:".$v["order_id"],
                    "relate_id"=>$v["order_id"],
                    "type"=>6,
                    "customer_id"=>$customer["parent_id"]
                )
            );
    }
  }
  /**
   * 增加用户的退款记录  
   * cgl  2017-2-15 新增
   */
  function insertmoney($return_money,$customer_id,$order_id,$merchant_id){
    //查询该用户的资金
    $balance=getRow("select * from `".DB_PREFIX."balance` where customer_id='".$customer_id."' ");
    
    $balance_money=$balance["balance"];
    //余额
    $availabe_balance=$balance["availabe_balance"];

    //增加用户资金记录
    // $this->db->query("INSERT INTO `" .DB_PREFIX. "customer_transaction` SET customer_id = '" .$customer_id. "',order_id='".$order_id."',description='退款:".$return_money."',amount=+".$return_money.",date_added = NOW(),status=2,balance_change='+".$return_money."',availabe_balance_change='+".$return_money."',date_modified=NOW(),pingzhen='0',pingzhen_type=0,relate_merchant_id='".$merchant_id."',last_balance='".$balance_money."',last_availabe_balance='".$availabe_balance."',type=8");
    saveData(DB_PREFIX."customer_transaction",
            array("customer_id"=>$customer_id,
                  "order_id"=>$order_id,
                  "description"=>"退款:".$return_money,
                  "amount"=>"+".$return_money,
                  "date_added"=>date("Y-m-d H:i:s",time()),"status"=>2,
                  "balance_change"=>"+".$return_money,
                  "availabe_balance_change"=>"+".$return_money,
                  "date_modified"=>date("Y-m-d H:i:s",time()),
                  "pingzhen"=>0,
                  "pingzhen_type"=>0,
                  "relate_merchant_id"=>$merchant_id,
                  "last_balance"=>$balance_money,
                  "last_availabe_balance"=>$availabe_balance,
                  "type"=>8)
            );
  }
  /**
   * cgl  2017-2-15  增加  退款返利的价格
   */
  function add_return_money($val){
    $customer=getRow("SELECT * FROM `" . DB_PREFIX . "customer` WHERE customer_id = '".$val['customer_id']."' ");
        //是否有上级会员
        $is_has=$customer["parent_id"];
        if($is_has!=null && $is_has!="" && $customer["merchant_id"]==1 && $is_has!=0){
          //根据订单号查询
          $quantitys=getRow("SELECT * FROM `hb_order_product` WHERE order_id = " .$val["order_id"]);

            //只适合嗨企货仓的会员   返利的资金
            $money_return=$val["return_add_money"]*(float)$quantitys["quantity"];
            //增加上级会员的资金   和资金记录
            $sql3 = "UPDATE `" .DB_PREFIX. "balance` SET balance = balance-'".$money_return."', date_modified = NOW() WHERE customer_id = " .$is_has;
            exeSql($sql3);
            //查询上级的资金
            $on=getRow("SELECT * FROM `" . DB_PREFIX . "balance` WHERE customer_id = '".$is_has."' ");

            $description="购买人：".$val["customer_id"].",返利退款：".$money_return;
            // $insert_money1="INSERT INTO `" .DB_PREFIX. "customer_transaction` SET customer_id = '" .$is_has. "',order_id='".$val["order_id"]."',description='".$description."',amount=-".$money_return.",date_added = NOW(),status=2,balance_change='-".$money_return."',availabe_balance_change='0',date_modified=NOW(),pingzhen='',pingzhen_type='',relate_merchant_id='".$val["merchant_id"]."',last_balance='".$on["balance"]."',last_availabe_balance='".$on["availabe_balance"]."',type=7  ";
            saveData(DB_PREFIX."customer_transaction",
            array("customer_id"=>@$is_has,
                  "order_id"=>$val["order_id"],
                  "description"=>$description,
                  "amount"=>"-".$money_return,
                  "date_added"=>date("Y-m-d H:i:s",time()),"status"=>2,
                  "balance_change"=>"-".$money_return,
                  "availabe_balance_change"=>0,
                  "date_modified"=>date("Y-m-d H:i:s",time()),
                  "pingzhen"=>'',
                  "pingzhen_type"=>'',
                  "relate_merchant_id"=>$val["merchant_id"],
                  "last_balance"=>$on["balance"],
                  "last_availabe_balance"=>$on["availabe_balance"],
                  "type"=>7)
            );
            // $this->db->query($insert_money1);
            //扣除商户的资金   增加商户的资金记录
            exeSql("update `" .DB_PREFIX. "merchant` set money = money+" .(float)$money_return ." where merchant_id = " .(int)$val['merchant_id']);
            saveData(DB_PREFIX."merchant_money_record",
              array("money"=>"+".(float)$money_return,
                    "merchant_id"=>$val["merchant_id"],
                    "add_time"=>date("Y-m-d H:i:s",time()),
                    "remark"=>"订单：".$val['order_id'],
                    "relate_id"=>$val["order_id"],
                    "type"=>6,
                    "customer_id"=>@$is_has
                )
            );
            // $this->db->query("insert into `" .DB_PREFIX. "merchant_money_record` set remark='订单：".$val['order_id'].",返利退款：".$is_has."',merchant_id=" .(int)$val['merchant_id']. ",money = +".(float)$money_return.",add_time = NOW(),relate_id='".$val["order_id"]."',type=6,customer_id='".@$is_has."' ");

        }
  }
  /**
   * 退款详情  cgl  2017-2-14 
   */
  function return_detail(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
     $sql="select o.order_id,r.returnreason as reason,r.firstname as name from ".DB_PREFIX."return as r left join 
                      ".DB_PREFIX."order as o on o.order_id=r.order_id 
            where o.merchant_id= '".$_SESSION["merchant_id"]."' and o.order_id='".$order."' ";
    $order1=getRow($sql);
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      $json=$order1;
    }
    echo json_encode($json);
    die;
  }
  /**
   * 订单详情   cgl  2017-2-16
   */
  function order_detail(){
    //获取订单详情
    $order=getRow("select *,op.quantity as o_quantity,p.price as now_price,o.telephone as tel,o.total as total,
                  (select firstname from ".DB_PREFIX."customer where customer_id=c.parent_id) as parent_name,
                  (select telephone from ".DB_PREFIX."customer where customer_id=c.parent_id ) as parent_telephone,
                  c.parent_id as parent_id,
                  c.card as card,o.return_add_money as return_add_money,o.derate_money as derate_money,o.freight,o.payment_method,
                  o.order_num,o.order_type_status,o.is_open_free,o.is_share,o.relate_id,op.product_id,
                  (select merchant_id from ".DB_PREFIX."customer where customer_id=o.customer_id ) as merchant_id,o.customer_id,
                  o.order_pay_money
                  from ".DB_PREFIX."order as o 
                  left join ".DB_PREFIX."order_product as op on o.order_id=op.order_id
                  left join ".DB_PREFIX."product_description as pd on op.product_id=pd.product_id
                  left join ".DB_PREFIX."product as p on p.product_id=pd.product_id
                  left join ".DB_PREFIX."customer as c on o.customer_id=c.customer_id
                  where o.order_id='".@$_GET["order"]."' ");

    if(!empty($order)){
      //获取订单产品的规格
      $option=getRow("select * from ".DB_PREFIX."order_option where order_id='".$order["order_id"]."' ");
      //查询是否有退款的记录
      $return=getRow("select * from ".DB_PREFIX."return where order_id='".$order["order_id"]."' ");
      if(!empty($return)){
        $order["return_status"]=1;//有退款
        if($return["return_status_id"]==1){
          $order["return_value"]="待处理";
        }else if($return["return_status_id"]==2){
          $order["return_value"]="等待商品寄回";
        }else if($return["return_status_id"]==3){
          $order["return_value"]="同意退款";
        }else if($return["return_status_id"]==4){
          $order["return_value"]="卖家拒绝退款";
        }
        $seller=getRow("SELECT * FROM `" .DB_PREFIX. "merchant` WHERE merchant_id = '".$_SESSION["merchant_id"]."' " );
        //退款的记录
        $returnhistory=getData("SELECT * FROM `" .DB_PREFIX. "return_history` WHERE return_id = " . $return['return_id'] . " ORDER BY date_added DESC");
        if(!empty($returnhistory)){
            foreach($returnhistory as $key=>$val){
                if($val['return_status_id'] == 1){
                    $returnprocess[$key]['processdate'] = $val['date_added'];
                    $returnprocess[$key]['info'] = "买家（".$return['firstname']."）创建了退款申请，".$val['comment'];
                }else if($val['return_status_id'] == 3){
                    $returnprocess[$key]['processdate'] = $val['date_added'];
                    $returnprocess[$key]['info'] = "卖家（".$seller['merchant_name']."）已经同意了申请，交易款项已归还至".$return['firstname']."账户";
                }else if($val['return_status_id'] == 4){
                    $returnprocess[$key]['processdate'] = $val['date_added'];
                    $returnprocess[$key]['info'] = "卖家（".$seller['merchant_name']."）已经拒绝了退款申请，".$val['comment'];
                }
            }
        }
        $order["return_msg"]=$returnprocess;
      }else{
        $order["return_status"]=0;
      }

      if($order["order_status_id"]==1){
        $order["status"]='未付款';
        $order["takedownprice"]=1;//拍下改价
      }else if($order["order_status_id"]==2){
        $order["status"]='待出库';
      }else if($order["order_status_id"]==3){
        $order["status"]='待收货';
        $order["accept_goods"]=1;//收货
      }else if($order["order_status_id"]==4){
        $order["status"]='已收货'; 
      }else if($order["order_status_id"]==5){
        $order["status"]='已完成'; 
      }else if($order["order_status_id"]==6){
        $order["status"]='已取消'; 
      }else if($order["order_status_id"]==7){
        $order["status"]='已关闭(删除状态)'; 
      }else if($order["order_status_id"]==11){
        $order["status"]='已出库(待发货)'; 
        $order["send_now"]=1;//立即发货
      }

      $order["optionname"]=@$option["name"];
      $order["optionvalue"]=@$option["value"];

      $order["derate_money"]=sprintf("%.2f",$order["derate_money"]);
      $order["freight"]=sprintf("%.2f",$order["freight"]);
      $order["return_add_money"]=sprintf("%.2f",$order["return_add_money"]);
      $order["marketprice"]=sprintf("%.2f",$order["marketprice"]);
      $order["proxyprice"]=sprintf("%.2f",$order["proxyprice"]);
      $order["total"]=sprintf("%.2f",$order["total"]);
      $order["now_price"]=sprintf("%.2f",$order["now_price"]);
      $order["take_down_money"]=sprintf("%.2f",$order["take_down_money"]);//商品的拍下改价

      $order["is_balance"]=$order["is_blance"];//是否余额支付

      $address=$order["shipping_country"].$order["shipping_zone"].$order["shipping_city"].$order["shipping_address_1"];
      $phone=json_decode($order["shipping_custom_field"],"json");
      $order["my_phone"]=empty($phone[1])?"未填写电话":$phone[1];//$order["telephone"];
      $order["my_address"]=$address;
      $order["my_buyer"]=$order["shipping_firstname"];
      $order["return_url"]=linkurl("sale/order");//返回的路径
      //物流信息
      //require_once 'lib/wuliu.php';
      // $wuliu=new wuliu();
      // $wu=$wuliu->sel_admin_ship(@$order["order_id"]);//$order["order"]
      //parent::expressInfo();
      //var_dump($wu);exit();
      if(empty($wu)){
        $order["wuliu"]=array();
      }else{
        $order["wuliu"]=empty($wu["Traces"])?array():$wu["Traces"];
      }
      $shipcommo=getRow("select com from ".DB_PREFIX."shipping where id='".$order["ship_id"]."'");
      $order["ship_order_no"]=empty($order["ship_order_no"])?"暂无物流编号":$order["ship_order_no"];

      $expressinfo = $this->expressInfo('',$order["ship_order_no"]);
      $info = json_decode($expressinfo,true);
      if($info['showapi_res_code']==0 && !empty($info['showapi_res_body']['data'])){                      
        $order['wuliu'] = $info['showapi_res_body']['data'];              //查询成功
      }
      $order["shipcommo"]=empty($shipcommo['com'])?"暂无物流公司":$shipcommo['com'];
      //订单操作记录
      $order_history=getData("select * from ".DB_PREFIX."order_history where order_id='".$order["order_id"]."' order by date_added desc ");
      //超级管理员的权限
      // if($_SESSION["user_group_id"]==1){
        $order["order_history"]=$order_history;
      // }
      //拍下改价
      $order["takeDownPrice"]=linkurl("sale/takeDownPrice");
      // $order_histoer=getRow("select * from hb_customer_transaction where order_id ='".$order["order_id"]."' and customer_id='".$order["customer_id"]."' ");
      // $left_money=abs(sprintf("%.2f",$order_histoer["balance_change"]));
      //使用余额支付
      
      $left_money=sprintf("%.2f",$order["balance_money"]);
      //5.10不对 数据 cgl 2017-5-10

      // if($order["total"]>$order["balance_money"] && $order["payment_method"]==4 && date("Ymd",time())>20170510){
      //   $left_money=sprintf("%.2f",$order["total"]-$order["balance_money"]);
      // }else if($order["total"]<$order["balance_money"] && $order["payment_method"]!=4 && $order["payment_method"]!=5){
      //   $left_money=sprintf("%.2f",$order["balance_money"]);
      // }
      $order["balance_money"]=$left_money;
      //是否是团购订单
      $info="";
      $group_price="";
      $sale_price="";
      //判断该订单的身份
      $customer_info="";
      $group_numer=0;

      if($order["merchant_id"]==0){
        $customer_info="非会员";
      }else if($order["merchant_id"]==1){
        $customer_info="会员";
      }

      if($order["order_type"]==2){
        $order["order_info"]="团购订单";
        //以及团购状态
        
        if($order["order_type_status"]==1){
          $info.="<span style='color:red;'>(未付款)</span>";
        }else if($order["order_type_status"]==2){
          $info.="<span style='color:red;'>(未成团)</span>";
        }else if($order["order_type_status"]==3){
          $info.="<span style='color:red;'>(开团成功)</span>";
        }else if($order["order_type_status"]==4){
          $info.="<span style='color:red;'>(开团失败)</span>";
        }
        //团购价格
        $group_json=json_decode($order["relate_id"],"json");
        $group_id=$group_json["group_id"];
        $group_price=getRow("select * from hb_groupby where group_id = '".$group_id."' ");
        $group_numer=$group_price["groupnum"];

        $group_price=$group_price["groupprice"];
        

      }else if($order["order_type"]==3){
        $order["order_info"]="限时抢购订单";
        //查询限时抢购价格
        $sale=getRow("select * from hb_product_sale_price where product_id = '".$order["product_id"]."' and sale_id='".$order["relate_id"]."' ");
        $sale_price=$sale["sale_price"];

      }else if($order["order_type"]==5){
        $order["order_info"]="抽奖订单";
      }else if($order["order_type"]==4){
        $order["order_info"]="会员订单";
      }
      if($order["coupon_id"]==NULL || $order["coupon_id"]=="" ){
        $order["coupon_id"]=0;
      }
      //新增
      if($order["payment_method"]==1){
        $payment_method="app(支付宝)支付";
      }else if($order["payment_method"]==2){
        $payment_method="app(微信)支付";
      }else if($order["payment_method"]==3){
        $payment_method="余额支付";
        //实际支付金额 cgl 2017-6-5
        $order["order_pay_money"]=$order["total"];
      }else if($order["payment_method"]==4){
        $payment_method="app(支付宝混合)支付";
      }else if($order["payment_method"]==5){
        $payment_method="app(微信混合)支付";
      }else if($order["payment_method"]==6){
        $payment_method="H5(微信)支付";
      }else if($order["payment_method"]==7){
         $payment_method="H5(支付宝)支付";
      }
      //是否分享
      $share_content="";
      if($order["is_share"]==0 || $order["is_share"]=="" || $order["is_share"]==null){
        $share_content="不参加分享减免";
      }else if($order["is_share"]==1){
        $share_content="未分享";
      }else if($order["is_share"]==2){
        $share_content="已分享";
      }
      //查询购买人的余额
      $customer_balance=getRow("select format(balance,2) as balance,format(availabe_balance,2) as availabe_balance from hb_balance where customer_id = '".$order["customer_id"]."' ");
      $invite=getRow("select invitecode from ".DB_PREFIX."invitecode where customer_id='".@$order["parent_id"]."' ");
      $order["invitecode"]=@$invite["invitecode"];
      //用户余额和可提现余额
      $order["customer_balance"]=empty($customer_balance["balance"])?"0.00":$customer_balance["balance"];
      $order["customer_availabe_balance"]=empty($customer_balance["availabe_balance"])?"0.00":$customer_balance["availabe_balance"];

      $order["customer_info"]=$customer_info;
      $order["sale_price"]=$sale_price;
      $order["group_price"]=$group_price;
      $order["group_numer"]=$group_numer;
      
      $order["share_content"]=$share_content;
      $order["payment_method"]=$payment_method;
      $order["group_info"]=$info;

      //查询订单的记录
      $customer_transaction=getData("select ct.*,c.firstname from hb_customer_transaction as ct,hb_customer as c where ct.order_id = '".$order["order_id"]."' and ct.customer_id = c.customer_id ");


      if(!empty($customer_transaction )){
        foreach ($customer_transaction as $k => $v) {
          if($v["type"]==1){
            $customer_transaction[$k]["type_info"]="分享收入";
          }else if($v["type"]==2){
            $customer_transaction[$k]["type_info"]="购买支出";
          }else if($v["type"]==3){
            $customer_transaction[$k]["type_info"]="返利收入";
          }else if($v["type"]==4){
            $customer_transaction[$k]["type_info"]="提现支出";
          }else if($v["type"]==5){
            $customer_transaction[$k]["type_info"]="资金解锁";
          }else if($v["type"]==6){
            $customer_transaction[$k]["type_info"]="分享退款";
          }else if($v["type"]==7){
            $customer_transaction[$k]["type_info"]="返利退款";
          }else if($v["type"]==8){
            $customer_transaction[$k]["type_info"]="购买退款";
          }else if($v["type"]==9){
            $customer_transaction[$k]["type_info"]="购买会员";
          }
          $customer_transaction[$k]["balance_change"]=sprintf("%.2f",$v["balance_change"]);
          $customer_transaction[$k]["availabe_balance_change"]=sprintf("%.2f",$v["availabe_balance_change"]);
          $customer_transaction[$k]["last_balance"]=sprintf("%.2f",$v["last_balance"]);
          $customer_transaction[$k]["last_availabe_balance"]=sprintf("%.2f",$v["last_availabe_balance"]);
          if($v["pingzhen_type"]==1){
            $customer_transaction[$k]["pingzhen_type_info"]="支付宝";
          }else if($v["pingzhen_type"]==2){
            $customer_transaction[$k]["pingzhen_type_info"]="微信";
          }else{
            $customer_transaction[$k]["pingzhen_type_info"]="其他";
          }

        }
      }
      // print_r($customer_transaction);
      $order["customer_transaction"]=$customer_transaction;
      //查询是否为分享订单
      $orderout=getRow("select * from hb_orderout where order_id = '".$order["order_id"]."' ");
      if(!empty($orderout)){
        $target=getRow("select * from hb_customer where customer_id = '".$orderout["target_id"]."'");
        $orderout["share_name"]=$target["firstname"];
        $orderout["differenceprice"]=sprintf("%.2f",$orderout["differenceprice"]);
      }

      $order["orderout"]=$orderout;
      $order["customer_transaction"]=$customer_transaction;

      //查询是否有相同的支付订单
      $is_common=getData("select * from hb_order where order_num = '".$order["order_num"]."' and customer_id = '".$order["customer_id"]."' ");
      $common_arr=array();
      $order["common_arr_order"]="";
      if(!empty($is_common)){
        foreach ($is_common as $k => $v) {
          if($v["order_id"] != $order["order_id"] ){
            $common_arr[]=$v;
            $order["common_arr_order"].=$v["order_id"].",";
          }
        }
      }
      $order["common_arr_count"]=0;
      
      if(!empty($common_arr)){
        $order["common_arr_count"]=count($common_arr);
        $order["common_arr_order"]=substr($order["common_arr_order"],0,-1);
      }
      $order["common_arr"]=$common_arr;

    }else{
      $order["none"]=1;
      $order["return_url"]=linkurl("sale/order");//返回的路径
    }
    $data=$order;
    //var_dump($data);exit();

    $this->res=$data;
    //菜单
    $this->getMenu();
    return $this->res;
  }
  /**
   * cgl  2017-2-20  导出订单
   */
  function orderExport(){
    $order=@$_GET["order"];
    $status=@$_GET["status"];
    $json=array();
    $sql="select *,w.name as warehouse_name,opt.name as productname,o.date_added as order_date_added,
          (select name FROM " . DB_PREFIX . "order_option as op WHERE op.order_id = 'o.order_id' AND order_product_id = 'o.product_id') as optionname,
          (select value FROM " . DB_PREFIX . "order_option as op WHERE op.order_id = 'o.order_id' AND order_product_id = 'o.product_id') as optionvalue,o.total as total
      from ".DB_PREFIX."order as o left join ".DB_PREFIX."order_product as opt on o.order_id=opt.order_id 
      left join ".DB_PREFIX."warehouse as w on w.warehouse_id=o.warehouse_id
      where o.merchant_id='".$_SESSION["merchant_id"]."' ";
    $sta="";
    if($status==2){
      //待收货和收款
      $sql.=" and o.order_status_id in (2,11)";
    }else if($status==0){
      //导出全部订单
    }else if($status==4){
      //导出已完成或者已收货
      $sql.=" and o.order_status_id in (4,5)";
    }else if($status==5){
      //导出退款的订单
      $sql="select *,(select name FROM " . DB_PREFIX . "order_option as op WHERE op.order_id = 'o.order_id' AND order_product_id = 'o.product_id') as optionname,
          (select value FROM " . DB_PREFIX . "order_option as op WHERE op.order_id = 'o.order_id' AND order_product_id = 'o.product_id') as optionvalue,r.date_added as return_time,o.total as total
          from ".DB_PREFIX."return as r left join ".DB_PREFIX."order as o on o.order_id=r.order_id 
          left join ".DB_PREFIX."order_product as opt on opt.order_id=o.order_id 
          where o.merchant_id='".$_SESSION["merchant_id"]."' ";
    }else{
      $sql.=" and o.order_status_id in (".$status.")"; 
    }
    if(!empty($order)){
      $sql.=" and o.order_id in (".$order.")";
    }
    $sql.=" order by o.date_added desc";
    $order_list=getData($sql);
    if(empty($order_list)){
      echo "<script>alert('暂无订单数据');history.back();</script>";
    }else{
        $data=array();
        $option="";
        require_once("lib/PHPExcel/PHPExcel.php");
        $objPHPExcel = new PHPExcel();
        if($status==0){
          $order_name="全部";
        }else if($status==1){
          $order_name="未付款";
        }else if($status==2){
          $order_name="待发货";
        }else if($status==3){
          $order_name="待收货";
        }else if($status==4){
          $order_name="已完成";
        }else if($status==5){
          $order_name="退款";
        }
        @$objPHPExcel->getActiveSheet()->setCellValue('F1', '嗨企货仓'.$order_name.'订单');
        //设置H1字体大小
        $objPHPExcel->getActiveSheet()->getStyle('F1')->getFont()->setSize(20);
        //合并H和I
        $objPHPExcel->getActiveSheet()->mergeCells('F1:G1');
        /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        //填入主标题
        $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(35);
        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension('A')->setWidth(16);
        if($status==5){
          //导出退款订单
          foreach($order_list as $k=>$v){
            if($v["return_status_id"]==3){
              $order_status="同意退款";
            }else if($v["return_status_id"]==1){
              $order_status="待处理";
            }else if($v["return_status_id"]==4){
              $order_status="拒绝退款";
            }
            //增加  订单类型   cgl  2017-4-20
            if($v["order_type"]==1){
              $type="普通订单";
            }else if($v["order_type"]==2){
              $type="团购订单";
            }else if($v["order_type"]==3){
              $type="限时抢购订单";
            }else if($v["order_type"]==4){
              $type="会员订单";
            }else if($v["order_type"]==5){
              $type="抽奖订单";
            }
            // foreach($v["option"] as $k1=>$v1){
              $option=$v["optionname"]."".$v["optionvalue"];
            // }
            $data[]=array(
              "order_id"=>$v["order_id"],  //订单号
            //  "order_num"=>$v["order_num"], //支付单号
            //  "ship_order_no"=>$v["ship_order_no"], //物流单号
            //  "freight"=>$v["freight"],  //物流费用
              "nickname"=>$v["firstname"], //昵称
            //  "accept_name"=>$v["shipping_firstname"],  //收货人
            //  "address"=>$v["shipping_country"].$v["shipping_zone"].$v["shipping_city"].$v["shipping_address_1"], //地址
              "phone"=>$v["telephone"], //电话
              "quantity"=>$v["quantity"], //数量
              "total"=>sprintf("%.2f",$v["total"]),  //订单总价格
              "product_name"=>$v["name"], //产品名称
              "price"=>sprintf("%.2f",$v["price"]), //产品价格
              "option"=>$option, //规格
              "type"=>$type, //订单类型
              "return_status"=>$order_status, //退货状态
              "return_time"=>$v["return_time"],  //date("Y-m-d H:i:s",$v["date_added"]) //退款时间
              "comment"=>empty($v["comment"])?"暂无回复":$v["comment"],
              "returnreason"=>empty($v["returnreason"])?"暂无退款理由":$v["returnreason"],
              "coupon_discount"=>$v["coupon_discount"]
            );
          }
          //表格信息
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', "订单号")
                   ->setCellValue('B2', "昵称")
                   ->setCellValue('C2', "联系方式")
                   ->setCellValue('D2', "商品数量")
                   ->setCellValue('E2', "订单总价格")
                   ->setCellValue('F2', "商品名称")
                   ->setCellValue('G2', "商品价格")
                   ->setCellValue('H2', "商品规格")
                   ->setCellValue('I2', "退款理由")
                   ->setCellValue('J2', "回复理由")
                   ->setCellValue('K2', "退款状态")
                   ->setCellValue('L2', "退款时间")
                   ->setCellValue('M2', "订单类型")
                   ->setCellValue('N2', "优惠券")
                   ;
        }else{
          foreach ($order_list as $k => $v) {
            if($v["order_status_id"]==4){
              $order_status="已收货";
            }else if($v["order_status_id"]==5){
              $order_status="已完成";
            }else if($v["order_status_id"]==1){
              $order_status="待付款";
            }else if($v["order_status_id"]==2){
              $order_status="待发货";
            }else if($v["order_status_id"]==3){
              $order_status="待收货";
            }else if($v["order_status_id"]==6){
              $order_status="已取消";
            }else if($v["order_status_id"]==7){
              $order_status="已关闭";
            }else if($v["order_status_id"]==11){
              $order_status="已出库";
            }
            //增加  订单类型   cgl  2017-4-20
            if($v["order_type"]==1){
              $type="普通订单";
            }else if($v["order_type"]==2){
              $type="团购订单";
            }else if($v["order_type"]==3){
              $type="限时抢购订单";
            }else if($v["order_type"]==4){
              $type="会员订单";
            }else if($v["order_type"]==5){
              $type="抽奖订单";
            }

            //foreach($v["option"] as $k1=>$v1){
              $option=$v["optionname"]."".$v["optionvalue"];
            //}
              $phone=json_decode($v["shipping_custom_field"],"json");
            $data[]=array(
              "order_id"=>$v["order_id"],  //订单号
              "order_num"=>$v["order_num"], //支付单号
              "ship_order_no"=>$v["ship_order_no"], //物流单号
              "freight"=>$v["freight"],  //物流费用
              "nickname"=>$v["firstname"], //昵称
              "accept_name"=>$v["shipping_firstname"],  //收货人
              "address"=>$v["shipping_country"].$v["shipping_zone"].$v["shipping_city"].$v["shipping_address_1"], //地址
              "phone"=>$phone[1],//$v["telephone"], //电话
              "quantity"=>$v["quantity"], //数量
              "total"=>sprintf("%.2f",$v["total"]),  //订单总价格
              "product_name"=>$v["productname"], //产品名称
              "price"=>sprintf("%.2f",$v["price"]), //产品价格
              "option"=>$option, //规格
              "type"=>$type, //订单类型
              "order_status"=>$order_status, //支付状态
              "order_time"=>date("Y-m-d H:i:s",$v["order_date_added"]), //订单时间
              "manufacturer_name"=>empty($v["warehouse_name"])?"嗨企货仓":$v["warehouse_name"],    //仓库
              "coupon_discount"=>$v["coupon_discount"]
            );
          }

          //表格信息
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', "订单编号")
                   ->setCellValue('B2', "平台订单号")
                   ->setCellValue('C2', "物流单号")
                   ->setCellValue('D2', "物流费用")
                   ->setCellValue('E2', "昵称")
                   ->setCellValue('F2', "收货人")
                   ->setCellValue('G2', "收货地址")
                   ->setCellValue('H2', "收货人电话")
                   ->setCellValue('I2', "商品数量")
                   ->setCellValue('J2', "订单总价格")
                   ->setCellValue('K2', "商品名称")
                   ->setCellValue('L2', "商品价格")
                   ->setCellValue('M2', "商品规格")
                   ->setCellValue('N2', "订单状态")
                   ->setCellValue('O2', "订单时间")
                   ->setCellValue('P2', "仓库名称")
                   ->setCellValue('R2', "订单类型")
                   ->setCellValue('S2', "优惠券")
                   ;
        }
        $num1=3;
        foreach ($data as $k => $v) {
          $num=$num1++;
          if($status==5){
            //退款
             $objPHPExcel->setActiveSheetIndex(0)
                         //Excel的第A列，uid是你查出数组的键值，下面以此类推
                          ->setCellValue('A'.$num, $v['order_id'])  
                          ->setCellValue('B'.$num, $v['nickname'])
                          ->setCellValue('C'.$num, $v['phone'])
                          ->setCellValue('D'.$num, $v['quantity'])
                          ->setCellValue('E'.$num, $v['total'])
                          ->setCellValue('F'.$num, $v['product_name'])
                          ->setCellValue('G'.$num, $v['price'])
                          ->setCellValue('H'.$num, $v['option'])
                          ->setCellValue('I'.$num, $v['returnreason'])
                          ->setCellValue('K'.$num, $v['comment'])
                          ->setCellValue('K'.$num, $v['return_status'])
                          ->setCellValue('L'.$num, $v['return_time'])
                          ->setCellValue('M'.$num, $v['type'])
                          ->setCellValue('N'.$num, $v['coupon_discount'])
                          ;
          }else{
            $objPHPExcel->setActiveSheetIndex(0)
                         //Excel的第A列，uid是你查出数组的键值，下面以此类推
                          ->setCellValue('A'.$num, $v['order_id'])    
                          ->setCellValue('B'.$num, $v['order_num'])
                          ->setCellValue('C'.$num, $v['ship_order_no'])
                          ->setCellValue('D'.$num, $v['freight'])
                          ->setCellValue('E'.$num, $v['nickname'])
                          ->setCellValue('F'.$num, $v['accept_name'])
                          ->setCellValue('G'.$num, $v['address'])
                          ->setCellValue('H'.$num, $v['phone'])
                          ->setCellValue('I'.$num, $v['quantity'])
                          ->setCellValue('J'.$num, $v['total'])
                          ->setCellValue('K'.$num, $v['product_name'])
                          ->setCellValue('L'.$num, $v['price'])
                          ->setCellValue('M'.$num, $v['option'])
                          ->setCellValue('N'.$num, $v['order_status'])
                          ->setCellValue('O'.$num, $v['order_time'])
                          ->setCellValue('P'.$num, $v['manufacturer_name'])
                          ->setCellValue('R'.$num, $v['type'])
                          ->setCellValue('S'.$num, $v['coupon_discount'])
                          ;
          }
        }
        // print_r($data);
        // die;
        ob_end_clean();//清除缓冲区,避免乱码 
        @$objPHPExcel->getActiveSheet()->setTitle(date("Y-m-d",time()).$order_name."订单信息导出");
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date("Y-m-d",time()).$order_name.'订单信息导出.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');                
    }
    die;
  }

  /**
   * 财务数据导出  cgl 2017-6-1
   */
  function downloadmoney(){
    $start=$_GET["start"];
    $end=$_GET["end"];
    $order=getData("SELECT a.order_id,b.name,a.is_blance,format(a.balance_money,2) as balance_money ,
      a.payment_method,format(c.differenceprice,2) as differenceprice,a.return_add_money,a.customer_id,
      format(a.total,2) as total,
      d.return_id,d.return_status_id
     from hb_order as a left join hb_order_product as b on a.order_id = b.order_id left join hb_orderout as c on a.order_id = c.order_id left join hb_return as d on a.order_id = d.order_id where a.date_added >= '".strtotime($start)."' and a.date_added <= '".strtotime($end)."' and a.order_status_id>=2 order by a.date_added desc ");
    if(!$order){
      echo "<script>alert('没有你想要的数据哦');history.back();</script>";
    }else{
      $all_pay_total=0;  //所有订单价格
      $all_left_money=0; //所有余额支付价格
      $all_free_money=0; //所有财务费用
      $all_diff_money=0; //所有差价
      $all_return_money=0; //所有固定利益
      $all_accept_money=0; //所有实际收入

      //已同意退款的单数
      $return_order_count=0;

      foreach($order as $k=>$v){
        //判断支付方式
        if($v["payment_method"]==1){
          $order[$k]["payment_method"]="APP支付宝";
          $order[$k]["free_money"]=sprintf("%.2f",0.55/100);//财务费用

        }else if($v["payment_method"]==2){
          $order[$k]["payment_method"]="APP微信";
          $order[$k]["free_money"]=sprintf("%.2f",0.60/100);//财务费用
        }else if($v["payment_method"]==3){
          $order[$k]["payment_method"]="余额";
          $order[$k]["free_money"]=0.00;//财务费用
        }else if($v["payment_method"]==4){
          $order[$k]["payment_method"]="(余额)支付宝";
          $order[$k]["free_money"]=sprintf("%.2f",($v["balance_money"]/$v["total"])*0.55/100);//财务费用

        }else if($v["payment_method"]==5){
          $order[$k]["payment_method"]="(余额)微信";
          $order[$k]["free_money"]=sprintf("%.2f",($v["balance_money"]/$v["total"])*0.60/100);//财务费用

        }else if($v["payment_method"]==6){
          $order[$k]["payment_method"]="H5微信";
          $order[$k]["free_money"]=sprintf("%.2f",0.60/100);//财务费用
        }else if($v["payment_method"]==7){
          $order[$k]["payment_method"]="H5支付宝";
          $order[$k]["free_money"]=sprintf("%.2f",0.55/100);//财务费用
        }
        //判断是否有返利
        $parent=getRow("SELECT parent_id from hb_customer where customer_id = (select parent_id from hb_customer where customer_id = '".$v["customer_id"]."' ) ");
        if(!$parent || @$parent["parent_id"]==0){
          $order[$k]["return_add_money"]=0.00;
        }
        if(empty($v["differenceprice"])){
          $order[$k]["differenceprice"]=0.00; 
        }
        //计算实际收入
        $order[$k]["real_money"]=$v["total"]-$order[$k]["differenceprice"]-$order[$k]["return_add_money"]-$order[$k]["free_money"];

        $all_pay_total+=$v["total"]; //所有订单总价格
        $all_accept_money+=$order[$k]["real_money"]; //所有实际收入
        $all_left_money+=$v["balance_money"]; //所有余额支付价格
        $all_free_money+=$order[$k]["free_money"]; //所有财务费用
        $all_diff_money+=$order[$k]["differenceprice"]; //所有差价
        $all_return_money+=$order[$k]["return_add_money"]; //所有固定利益

        if($v["return_id"]>0){
          if($v["return_status_id"]==3){
            //同意退款
            $order[$k]["is_return"]=1;
            $return_order_count+=1;
          }else{
            //没有
            $order[$k]["is_return"]=0;
          }
        }else{
          $order[$k]["is_return"]=0;
        }

      }
    }
    $all_order=count($order)-$return_order_count;
    
    require_once("lib/PHPExcel/PHPExcel.php");
    $objPHPExcel = new PHPExcel();

    // @$objPHPExcel->getActiveSheet()->setCellValue('F1', '嗨企货仓'.$order_name.'订单');
    //设置H1字体大小
    // $objPHPExcel->getActiveSheet()->getStyle('F1')->getFont()->setSize(20);
    //合并H和I
    // $objPHPExcel->getActiveSheet()->mergeCells('F1:G1');
    /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
    //填入主标题
    $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(35);
    // $objPHPExcel->getActiveSheet()->getDefaultColumnDimension('B')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getDefaultColumnDimension(A)->setWidth(18);

    $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('K')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('L')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle('M')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    
    $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth('1');//自动填充到页面的宽度

    //表格信息
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', "订单号")
             ->setCellValue('B1', "产品名称")
             ->setCellValue('C1', "收入")
             ->setCellValue('D1', "支付方式")
             ->setCellValue('E1', "余额支付金额")
             ->setCellValue('F1', "财务费用")
             ->setCellValue('G1', "加价")
             ->setCellValue('H1', "固定利益")
             ->setCellValue('I1', "实际收入")
             ->setCellValue('J1', "成本")
             ->setCellValue('K1', "利润率")
             ->setCellValue('L1', "平均每单利润")
             ->setCellValue('M1', "备注")
             ;
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', $all_order)
             ->setCellValue('B2', "本月合计")
             ->setCellValue('C2', $all_pay_total)
             ->setCellValue('D2', "")
             ->setCellValue('E2', $all_left_money)
             ->setCellValue('F2', $all_free_money)
             ->setCellValue('G2', $all_diff_money)
             ->setCellValue('H2', $all_return_money)
             ->setCellValue('I2', $all_accept_money)
             ->setCellValue('J2', "")
             ->setCellValue('K2', "")
             ->setCellValue('L2', "")
             ->setCellValue('M2', "")
             ;
    $num=3;
    foreach($order as $k=>$v){
      //是否同意退款
      if($v["is_return"]==1){
        $remark="该订单已同意退款";
      }else{
        $remark="";
      }

      $objPHPExcel->setActiveSheetIndex(0)
       //Excel的第A列，uid是你查出数组的键值，下面以此类推
        ->setCellValue('A'.$num, $v['order_id'])  
        ->setCellValue('B'.$num, $v['name'])
        ->setCellValue('C'.$num, $v['total'])
        ->setCellValue('D'.$num, $v['payment_method'])
        ->setCellValue('E'.$num, $v['balance_money'])
        ->setCellValue('F'.$num, $v['free_money'])
        ->setCellValue('G'.$num, $v['differenceprice'])
        ->setCellValue('H'.$num, $v['return_add_money'])
        ->setCellValue('I'.$num, $v['real_money'])
        ->setCellValue('K'.$num, "")
        ->setCellValue('K'.$num, "")
        ->setCellValue('L'.$num, "")
        ->setCellValue('M'.$num, $remark)
        ;
        $num++;
    }
    $start=str_replace("/","-",$start);
    $end=str_replace("/","-",$end);

    ob_end_clean();//清除缓冲区,避免乱码 
    @$objPHPExcel->getActiveSheet()->setTitle($start."到".$end."的财务数据");
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.date("Y-m-d",time()).'财务报表导出.xls"');
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');  

    die;
  }


  /**
   * cgl   2017-2-20  拍下改价
   */
  function takeDownPrice(){
    $order=$_POST["order"];
    $json=array();
    //查询是否有该订单
    $order1=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
    if(empty($order1)){
      $json["msg"]="没有该订单";
    }else{
      if($order1["order_status_id"]!=1){
        $json["msg"]="订单状态错误";
      }else{
        //修改订单价格   增加订单历史记录
        $price=(float)abs($_POST["price"]);
        $userid=$_SESSION["userid"];
        $username=$_SESSION['username'];
        exeSql("update ".DB_PREFIX."order set total='".$price."',date_modified=NOW() where order_id='".$order1["order_id"]."' ");
        $description="管理员(".$username."[".$userid."])进行拍下改价操作,(原价:".(float)$order1["take_down_money"].")改价成".$price."元";
        saveData(DB_PREFIX."order_history",array("order_id"=>$order1["order_id"],"order_status_id"=>1,"comment"=>$description,"notify"=>0,"date_added"=>date("Y-m-d H:i:s",time())));
        $json["msg"]="改价成功";
      }
    }
    echo json_encode($json);
    die;
  }
   /**
     * cgl   2017-2-22  编辑物流单号
     */
    function editWuliu(){
      $order=$_POST["order"];
      $json=array();
      //查询是否有该订单
      $order1=getRow("select * from ".DB_PREFIX."order where order_id='".$order."' and merchant_id='".$_SESSION["merchant_id"]."' ");
      if(empty($order1)){
        $json["msg"]="没有该订单";
      }else{
        if($order1["order_status_id"]!=3){
          $json["msg"]="订单状态错误";
        }else{
          //编辑物流单号   增加订单历史记录
          $shippingcode=$_POST["shippingcode"];
          $shipcompany=$_POST["shipcompany"];
          $userid=$_SESSION["userid"];
          $username=$_SESSION['username'];
          exeSql("update ".DB_PREFIX."order set ship_order_no='".$shippingcode."',date_modified=NOW(),ship_id='".$shipcompany."' where order_id='".$order1["order_id"]."' ");
          $description="管理员(".$username."[".$userid."])进行编辑物流操作,(原物流公司：".$order1["ship_id"]."改成".$shipcompany.",原物流编码:".(float)$order1["ship_order_no"].")改成".$shippingcode."";
          saveData(DB_PREFIX."order_history",array("order_id"=>$order1["order_id"],"order_status_id"=>3,"comment"=>$description,"notify"=>0,"date_added"=>date("Y-m-d H:i:s",time())));
          $json["msg"]="编辑物流成功";
        }
      }
      echo json_encode($json);
      die;
    }

  /**
   *  @description 统计某企业下的订单情况
   *  @parmars     none
   *  @return      array $this->res
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-01
   */
  function customer_order(){
    //分页
    require 'lib/pagination.php';
    $pagination = new pagination();
    
    //菜单
    $this->getMenu();

    //查询全部订单的数量
    $all_order_num=getRow("SELECT count(*) as count from ".DB_PREFIX."order where merchant_id='".$_SESSION["merchant_id"]."' AND return_add_money != 0");
    $page=isset($_GET["page"])?$_GET["page"]:1;
    $limit=15;
    $start=($page-1)*$limit.",".$limit;
    $sql="select o.order_id,op.name,op.quantity,o.return_add_money,o.payment_method,FORMAT(o.total,2) as total,o.order_status_id,o.customer_id,
          (select firstname from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
          where customer_id=o.customer_id ) ) as parent_name,
          (select telephone from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
          where customer_id=o.customer_id ) ) as telephone,
          o.date_added,o.order_pay_trade_code,o.firstname,o.ship_order_no,o.ship_id,o.order_type,o.order_type_status,p.proxyprice
          from " .DB_PREFIX. "order as o
          left join ".DB_PREFIX."order_product as op on o.order_id=op.order_id 
          left join ".DB_PREFIX."product as p on p.product_id = op.product_id
          where o.merchant_id='".$_SESSION["merchant_id"]."' AND o.return_add_money != 0";

    $count_sql="SELECT count(*) as count from ".DB_PREFIX."order as o left join ".DB_PREFIX."order_product as op on o.order_id=op.order_id  ";
    $cout=1;
    if(!isset($_GET["status_id"])){
      $url=linkurl("sale/customer_order");
      $this->res["status"]=0;
      
    }else{
      if($_GET["status_id"]==4){
        $statuss="4,5";
      }else{
        if($_GET["status_id"]==2){
          $statuss="2,11";
        }else{
          $statuss=$_GET["status_id"];
        }
      }
      if($_GET["status_id"]==5){
        //退款
        $sql="select o.order_id,r.product as name,r.quantity,o.payment_method,o.return_add_money,FORMAT(o.total,2) as total,o.order_status_id,o.customer_id,
                      (select firstname from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
                      where customer_id=o.customer_id ) ) as parent_name,
                      (select telephone from ".DB_PREFIX."customer where customer_id=(select parent_id from ".DB_PREFIX."customer 
                      where customer_id=o.customer_id ) ) as telephone,
                      r.return_status_id,
                      o.date_added ,o.order_pay_trade_code,r.date_added as return_time,o.firstname,o.ship_order_no,o.ship_id,
                      o.order_type,o.order_type_status,p.proxyprice
                      from ".DB_PREFIX."return as r 
                      left join ".DB_PREFIX."product as p on r.product_id = p.product_id
                      left join ".DB_PREFIX."order as o on o.order_id=r.order_id 
                      where o.merchant_id= '".$_SESSION["merchant_id"]."' AND o.return_add_money != 0";
        $count_sql="SELECT count(*) as count from ".DB_PREFIX."return as r left join ".DB_PREFIX."order as o on o.order_id=r.order_id AND o.return_add_money != 0";
        $cout=0;
      }else if(in_array($_GET["status_id"],array("1","2","3","4"))){
        //查询各个类型的订单
        $sql.=" and o.order_status_id in (".$statuss.") ";
      }
      $count_sql.=" where o.merchant_id='".$_SESSION["merchant_id"]."' ";
      if($cout==1){
        $count_sql.=" and o.order_status_id in (".$statuss.") ";
      }
      $url=linkurl("sale/customer_order",array("status_id"=>$_GET["status_id"]));
      $this->res["status"]=$_GET["status_id"];
      
    }
    //根据条件查询
    if(isset($_GET["order"]) && !empty($_GET["order"])){
      if($_GET["type"]==1){//订单编号
        $sql.=" and o.order_id='".htmlspecialchars_decode($_GET["order"])."' ";
        if(isset($_GET["status_id"])){
          $count_sql.=" and o.order_id='".htmlspecialchars_decode($_GET["order"])."' ";  
        }else{
          $count_sql.=" where o.order_id='".htmlspecialchars_decode($_GET["order"])."' ";  
        }
      }
      if($_GET["type"]==2){//商品名称
        if(isset($_GET["status_id"]) && $_GET["status_id"]==5){
          //退款
          $sql.=" and r.product like '%".htmlspecialchars_decode($_GET["order"])."%' ";
        }else{
          $sql.=" and op.name like '%".htmlspecialchars_decode($_GET["order"])."%' ";
        }
        
        if(isset($_GET["status_id"])){
          if(isset($_GET["status_id"]) && $_GET["status_id"]==5){
            $count_sql.=" and r.product like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
          }else{
            $count_sql.=" and op.name like '%".htmlspecialchars_decode($_GET["order"])."%' ";
          }
        }else{
          $count_sql.=" where op.name like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
        }  
      }
      if($_GET["type"]==3){//购买人
        $sql.=" and o.firstname like '%".htmlspecialchars_decode($_GET["order"])."%' ";
        if(isset($_GET["status_id"])){
          $count_sql.=" and o.firstname like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
        }else{
          $count_sql.=" where o.firstname like '%".htmlspecialchars_decode($_GET["order"])."%' ";  
        }
      }
    }
    if(isset($_GET["start"]) && !empty($_GET["start"])){
      $sql.=" and o.date_added>=".strtotime($_GET["start"])." ";
      if(isset($_GET["status_id"])){
        $count_sql.=" and o.date_added>=".strtotime($_GET["start"])." ";  
      }else{
        $count_sql.=" where o.date_added>=".strtotime($_GET["start"])." ";  
      }
    }
    if(isset($_GET["end"]) && !empty($_GET["end"])){
      $sql.=" and o.date_added<".strtotime($_GET["end"])." ";
      if(isset($_GET["status_id"])){
        $count_sql.=" and o.date_added<".strtotime($_GET["end"])." ";  
      }else{
        $count_sql.=" where o.date_added<".strtotime($_GET["end"])." ";  
      }
    }
    //cgl   2017-2-27  增加团购订单的搜索  sql条件    

    if(isset($_GET["order_type_status"])){
      if(in_array($_GET["order_type_status"],array(1,2,3,4) )){
        $sql.=" and o.order_type_status= ".$_GET["order_type_status"]." ";
        $count_sql.=" and o.order_type_status= ".$_GET["order_type_status"]." ";  
      }
      else{
        $sql.=" and o.order_type_status in (1,2,3,4) ";
        $count_sql.=" and o.order_type_status in (1,2,3,4) ";  
      }

    }
    //cgl   2017-2-27  增加团购订单的搜索   路径
    if(isset($_GET["order_type_status"])){
      if(in_array($_GET["order_type_status"],array(1,2,3,4) )){
        $url.="&order_type_status=".$_GET["order_type_status"];
      }else{
        $url.="&order_type_status=0";
      }
    }

    //搜索路径
    if(isset($_GET["page"])){
      $this->res["href_url"]=$url."&page=".$page;
    }else{
      $this->res["href_url"]=$url;
    }
    $this->res["start"]=isset($_GET["start"])?$_GET["start"]:"";
    $this->res["end"]=isset($_GET["end"])?$_GET["end"]:"";
    $this->res["order_id"]=isset($_GET["order"])?$_GET["order"]:"";

    if(isset($_GET["start"])){
      $url.="&start=".$_GET["start"];
    }
    if(isset($_GET["end"])){
      $url.="&end=".$_GET["end"];
    }
    if(isset($_GET["order"])){
      $url.="&order=".$_GET["order"];
    }
    if(isset($_GET["type"])){
      $url.="&type=".$_GET["type"];
    }
    $this->res["order_type1"]=isset($_GET["type"])?$_GET["type"]:1;

    $sql.=" ORDER BY o.date_added desc limit $start";

    //查询全部订单
    $order=getData($sql);
    $order_num=getRow($count_sql);

    if(!empty($order)){
        foreach ($order as $key => $value) {
          if($value["payment_method"]==1){
            $order[$key]["pay"]="app支付(支付宝)";
          }else if($value["payment_method"]==2){
            $order[$key]["pay"]="app支付(微信)";
          }else if($value["payment_method"]==3){
            $order[$key]["pay"]="H5支付/余额支付";
          }
          if(!empty($value["parent_name"])){
            $order[$key]["parent"]=$value["parent_name"]."(".$value["telephone"].")"; 
          }else{
            $order[$key]["parent"]='无所属'; 
          }
          $order[$key]["date_added"]=date("Y-m-d H:i:s",$value["date_added"]);
          if($order[$key]["order_status_id"]==1){
            $order[$key]["status"]='未付款';
            $order[$key]["ok_accept"]=1;//确认收到货款
          }else if($order[$key]["order_status_id"]==2){
            $order[$key]["status"]='待出库';
             $order[$key]["send_goods_now"]=1;//立即出库
          }else if($order[$key]["order_status_id"]==3){
            $order[$key]["status"]='待收货';
            $order[$key]["accept_goods"]=1;//收货
          }else if($order[$key]["order_status_id"]==4){
            $order[$key]["status"]='已收货'; 
          }else if($order[$key]["order_status_id"]==5){
            $order[$key]["status"]='已完成'; 
          }else if($order[$key]["order_status_id"]==6){
            $order[$key]["status"]='已取消'; 
          }else if($order[$key]["order_status_id"]==7){
            $order[$key]["status"]='已关闭(删除状态)'; 
          }else if($order[$key]["order_status_id"]==11){
            $order[$key]["status"]='已出库(待发货)'; 
            $order[$key]["send_now"]=1;//立即发货
          }
          //是否申请退款
          $is_return=getRow("select * from ".DB_PREFIX."return where order_id='".$value["order_id"]."' ");
          if($is_return){
            $order[$key]["is_return"]=1;
          }
          if(isset($value["return_status_id"])){
            if($value["return_status_id"]==1){
              $order[$key]["status"]='待处理';
              $order[$key]["deal_status"]=1;//去处理
            }else if($value["return_status_id"]==2){
              $order[$key]["status"]='等待商品被寄出'; 
            }else if($value["return_status_id"]==3){
              $order[$key]["status"]='同意退款'; 
            }else if($value["return_status_id"]==4){
              $order[$key]["status"]='卖家拒绝退款'; 
            }
            //有退款
            $order[$key]["return_status"]=1; 
          }else{
            if($cout==0){
              unset($order[$key]["status"]);
            }
            $order[$key]["return_status"]=0; 
          }
           //订单详情 
          $info='';
          $order[$key]["order_detail"]=linkurl("sale/order_detail",array("order"=>$value["order_id"]));
          if($value["order_type"]==1 || $value["order_type"]==null){
            $info="普通订单";
          }else if($value["order_type"]==2){
            $info="团购";
            if($value["order_type_status"]==1){
              $info.="<span style='color:red;'>(未付款)</span>";
            }else if($value["order_type_status"]==2){
              $info.="<span style='color:red;'>(未成团)</span>";
            }else if($value["order_type_status"]==3){
              $info.="<span style='color:red;'>(开团成功)</span>";
            }else if($value["order_type_status"]==4){
              $info.="<span style='color:red;'>(开团失败)</span>";
            }
          }else if($value["order_type"]==3){
            $info="秒杀订单";
          }
          $order[$key]["order_type_info"]=$info;
        }
    }
    $order_type_status=0;
    if(isset($_GET["order_type_status"])){
      if(in_array($_GET["order_type_status"],array(1,2,3,4))){
        $order_type_status=$_GET["order_type_status"];
      }else{
        $order_type_status=0;
      }
    }else{
      $order_type_status=0;
    }
    //查询全部订单的数量
    $this->res["all_order_num"]=$all_order_num["count"];
    //团购订单状态
    $this->res["order_group_type_status"]=$order_type_status;
    // 处理价格的显示问题
    foreach($order as $key=>$value){
      $order[$key]['proxyprice'] = sprintf('%.2f',$order[$key]['proxyprice']);
      $order[$key]['total'] = sprintf('%.2f',$order[$key]['total']);
      $order[$key]['return_add_money'] = sprintf('%.2f',$order[$key]['return_add_money']);
    }
    $this->res["order"]=$order;
    if($all_order_num["count"]<$limit)
      $this->getPages($page,1);
    else
      $this->getPages($page,ceil($all_order_num["count"]/$limit));

    //$pagination->page(,$page,$limit,$url);//$order_num["count"]
    //$this->res["pagination"]=
    $this->res["order_url"]=linkurl("sale/customer_order");
    //确认收货
    $this->res["confirm_goods"]=linkurl("sale/confirm_goods");
    //立即出库  
    $this->res["now_send_goods"]=linkurl("sale/now_send_goods");
    //查询所有的物流公司
    $this->res["ship_company"]=getData("select * from ".DB_PREFIX."shipping ");
    //立即发货
    $this->res["now_send_goods_by_time"]=linkurl("sale/now_send_goods_by_time");
    //收货地址
    $this->res["accept_address"]=linkurl("sale/accept_address");
    //收到货款
    $this->res["accept_money"]=linkurl("sale/accept_money");
    //退款处理
    $this->res["deal_return"]=linkurl("sale/deal_return");
    //退款详情
    $this->res["return_detail"]=linkurl("sale/return_detail");
    //导出订单
    $this->res["orderExport"]=linkurl("sale/orderExport");
    //编辑物流单号
    $this->res["editWuliu"]=linkurl("sale/editWuliu");
    return $this->res;
  }
  /**
   * 会员服务费  becomeMember  cgl 2017-4-3
   */
  function becomeMember(){
    //分页
    require 'lib/pagination.php';
    $pagination = new pagination();
    
    //菜单
    $this->getMenu();
    if(isset($_GET["page"]) && @$_GET["page"]>=1 ){
      $page=$_GET["page"];
    }else{
      $page=1;
    }
    $limit=20;
    $start=($page-1)*$limit.",".$limit;
    $sql="select o.order_id,op.name,op.quantity,o.payment_method,FORMAT(o.total,2) as total,o.order_status_id,o.customer_id,
                      o.date_added ,o.order_pay_trade_code,o.firstname,o.ship_order_no,o.ship_id,o.order_type,o.order_type_status,
                      o.return_add_money as amount,op.product_id as product_id
                      from " .DB_PREFIX. "order as o 
                      , ".DB_PREFIX."order_product as op
                      where o.merchant_id='".$_SESSION["merchant_id"]."' and o.order_id=op.order_id and o.is_member_status=1 ";
    $sql.=" ORDER BY o.order_id desc limit $start";
    
    //查询全部订单
    $order=getData($sql);
     if(!empty($order)){
        foreach ($order as $key => $value) {
          if($value["payment_method"]==1){
            $order[$key]["pay"]="app支付(支付宝)";
          }else if($value["payment_method"]==2){
            $order[$key]["pay"]="app支付(微信)";
          }else if($value["payment_method"]==3){
            $order[$key]["pay"]="H5支付/余额支付";
          }
          $order[$key]["date_added"]=date("Y-m-d H:i:s",$value['date_added']);
          //截取商品名称
          $order[$key]["name"]=mb_substr($value["name"],0,22,"utf-8");
          $order[$key]["product_name"]=$value["name"];
          //计算固定利益
          $order[$key]["amount"]=sprintf("%.2f",$value["amount"]*$value["quantity"]);
        }
    }
    if(sizeof($order )<20)
      $this->getPages($page,$page);
    else
      $this->getPages($page);

    $this->res["order"]=$order;

    return $this->res;
  }


}
?>