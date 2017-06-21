<?php

//面向对象的control 类
include "xcontrol/base.php";
include "lib/pagination.php";
class bigscreen extends base
{
	
	// function __construct() {
 //        parent::__construct();
	// 	$this->userid=$_SESSION['userid'];
	// 	$this->username=$_SESSION['username'];
 //   }
   function index(){
      $invitecode_id = 3;
      $customer_id = 36;
      $inviteinfo = $this->getInviteinfo2($customer_id);

      $datetime = strtotime('2017-02-09 00:00:00');

      $cityArr       = array();
      $movelineArr   = array();
      $cityArr2      = array();
      $movelineArr2  = array();
      $fanli_total_money = 0;       //返利总收入
      $fanli_total_num = 0;         //返利总单量
      $total_order_money =0;        //返利相关订单金额

      $_SESSION['refresh_time'] = time();       //刷新页面时间，即进入页面的时间

      foreach ($inviteinfo as $key => $value) {
         if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){ 
            $last_ctid = isset($_SESSION['max_ctid'])?$_SESSION['max_ctid']:0;

            $new_str = " and ct.customer_transaction_id > ".$last_ctid." ";
         }else{
            $new_str = " and UNIX_TIMESTAMP(ct.date_added) < '".$_SESSION['refresh_time']."' ";
         }
         //返利收入
         $amount_sql = "select sum(ct.amount) as total from `" .DB_PREFIX. "customer_transaction` ct
            left join `" .DB_PREFIX. "orderout` ot on ot.order_id = ct.order_id
            left join `" .DB_PREFIX. "order` o on o.order_id = ct.order_id
            left join `" .DB_PREFIX. "customer` as c1 on c1.customer_id = ot.customer_id
            left join `" .DB_PREFIX. "customer` as c2 on  c2.customer_id = o.customer_id
            where ct.customer_id = '" .(int)$value['customer_id']. "' and ct.type in (3,7)
            and  ((c1.parent_id = '" .(int)$value['customer_id']. "' and ifnull(0,c2.parent_id) <> '" .(int)$value['customer_id']. "' and c1.invitecode_id = '" .(int)$value['invitecode_id']. "')
            or (ifnull(0,c1.parent_id) <> '" .(int)$value['customer_id']. "' and c2.parent_id = '" .(int)$value['customer_id']. "' and c2.invitecode_id = '" .(int)$value['invitecode_id']. "')
            or (c1.parent_id = '" .(int)$value['customer_id']. "' and c2.parent_id = '" .(int)$value['customer_id']. "' and (c1.invitecode_id = '" .(int)$value['invitecode_id']. "' or c1.invitecode_id = '" .(int)$value['invitecode_id']. "')))
            and UNIX_TIMESTAMP(ct.date_added) > '".$datetime."'";
            // echo $amount_sql;
         $first_date  = strtotime(date('Y-m-d'));
         $end_date = time();
         $today_amount_sql = $amount_sql." and UNIX_TIMESTAMP(ct.date_added) > '".$first_date."' and UNIX_TIMESTAMP(ct.date_added) < '".$end_date."' ";
         // echo $amount_sql;die;
         

         //返利单量
         $total_sql = "select count(*) as total from `" .DB_PREFIX. "customer_transaction` ct
            left join `" .DB_PREFIX. "order` o on o.order_id = ct.order_id
            left join `" .DB_PREFIX. "orderout` ot on ot.order_id = ct.order_id
            left join `" .DB_PREFIX. "customer` as c1 on c1.customer_id = ot.customer_id left join `" .DB_PREFIX. "customer` as c2 on  c2.customer_id = o.customer_id
            where ct.customer_id = '" .(int)$value['customer_id']. "' and ct.type in (3)
            and ((c1.parent_id = '" .(int)$value['customer_id']. "' and ifnull(0,c2.parent_id) <> '" .(int)$value['customer_id']. "' and c1.invitecode_id = '" .(int)$value['invitecode_id']. "' )
            or (ifnull(0,c1.parent_id) <> '" .(int)$value['customer_id']. "' and c2.parent_id = '" .(int)$value['customer_id']. "' and c2.invitecode_id = '" .(int)$value['invitecode_id']. "')
            or (c1.parent_id = '" .(int)$value['customer_id']. "' and c2.parent_id = '" .(int)$value['customer_id']. "' and (c1.invitecode_id = '" .(int)$value['invitecode_id']. "' or c2.invitecode_id = '" .(int)$value['invitecode_id']. "')))
            and o.date_added > '".$datetime."'";
            // echo $total_sql;die;
         $today_sql = $total_sql." and UNIX_TIMESTAMP(ct.date_added) > '".$first_date."' and UNIX_TIMESTAMP(ct.date_added) < '".$end_date."'";

         //返利相关的订单金额
         $sql5 = "select sum(o.total) as order_money from `" .DB_PREFIX. "customer_transaction` ct
            left join `" .DB_PREFIX. "order` o on o.order_id = ct.order_id
            left join `" .DB_PREFIX. "orderout` ot on ot.order_id = ct.order_id
            left join `" .DB_PREFIX. "customer` as c1 on c1.customer_id = ot.customer_id left join `" .DB_PREFIX. "customer` as c2 on  c2.customer_id = o.customer_id
            where ct.customer_id = '" .(int)$value['customer_id']. "' and ct.type in (3)
            and ((c1.parent_id = '" .(int)$value['customer_id']. "' and ifnull(0,c2.parent_id) <> '" .(int)$value['customer_id']. "' and c1.invitecode_id = '" .(int)$value['invitecode_id']. "' )
            or (ifnull(0,c1.parent_id) <> '" .(int)$value['customer_id']. "' and c2.parent_id = '" .(int)$value['customer_id']. "' and c2.invitecode_id = '" .(int)$value['invitecode_id']. "')
            or (c1.parent_id = '" .(int)$value['customer_id']. "' and c2.parent_id = '" .(int)$value['customer_id']. "' and (c1.invitecode_id = '" .(int)$value['invitecode_id']. "' or c2.invitecode_id = '" .(int)$value['invitecode_id']. "')))
            and o.date_added > '".$datetime."'";
         // echo $sql5;die;
         $money = getRow($sql5);

  

         $sql_order = "select ct.customer_transaction_id as ct_id,ct.amount,ct.customer_id as tid,ct.type,ct.date_added,
                        a.order_id,c.address,c.invitecode_id,a.customer_id,
                        (select ip from hb_customer where customer_id = a.customer_id) as buy_ip
                        from hb_order as a 
                        inner JOIN hb_customer_transaction as ct on a.order_id = ct.order_id
                        inner join hb_customer as b on b.customer_id = a.customer_id 
                        inner join hb_invitecode as c on b.invitecode_id = c.invitecode_id 
                        where c.invitecode_id = ".$value['invitecode_id']." and ct.type = 3 
                        and UNIX_TIMESTAMP(ct.date_added) > '".$datetime."' $new_str order by ct.customer_transaction_id desc limit 3 ";

         $sql_orderout = "select ct.customer_transaction_id as ct_id,ct.amount,ct.customer_id as fid,ct.type,ct.date_added,a.order_id,c.customer_id as out_cid,
                           c.target_id,d.address,d.invitecode_id,d.customer_id as invite_cid, 
                           (select ip from hb_customer where customer_id=c.customer_id) as share_ip,
                           (select ip from hb_customer where customer_id=c.target_id) as buy_ip
                           from hb_order as a 
                           inner join hb_orderout as c on a.order_id = c.order_id 
                           inner JOIN hb_customer_transaction as ct on a.order_id = ct.order_id
                           inner join hb_customer as cu on cu.customer_id = c.customer_id 
                           inner join hb_invitecode as d on cu.invitecode_id = d.invitecode_id 
                           where d.invitecode_id = ".$value['invitecode_id']." and ct.type =3 and ct.customer_id = d.customer_id  
                           and UNIX_TIMESTAMP(ct.date_added) > '".$datetime."' $new_str order by ct.customer_transaction_id desc limit 3 ";
         // echo $sql_order;
                           // die;
         $option_invitecode     = array();               //邀请码参数数组
         $option_order          = array();               //购买订单参数数组
         $option_orderout       = array();               //分享购买订单参数数组
         $moveline_order        = array();               //购买->邀请码连线数组
         $moveline_orderout     = array();               //分享->购买连线数组

         /*********************邀请码信息转化**********************/

         
         if(empty($value['address'])){
            $address = "浙江省杭州市";
         }else{
            $address = $value['address'];
         }
         $address_0 = $this->getLatitudeByAddress($address);
         $option_invitecode[$key]['name'] = $address;
         $option_invitecode[$key]['value'] = array($address_0['result']['location']['lng'],$address_0['result']['location']['lat']);
         $option_invitecode[$key]['symbolSize'] = 16;
         $option_invitecode[$key]['itemStyle'] = array('normal'=>array('color'=>'#9ed900'));
        
         /*********************邀请码信息转化**********************/

         /*********************购买订单转化**********************/
         $orderInfo = getData($sql_order);

         if(!empty($orderInfo)){
            foreach ($orderInfo as $k => $v) {
               $buy_ip_1 = $v['buy_ip'];
               $address_1 = $this->getLatitudeByIp($buy_ip_1);
               $option_order[$k]['name'] = $address_1['content']['address'];
               $option_order[$k]['value'] = array($address_1['content']['point']['x'],$address_1['content']['point']['y']);
               $option_order[$k]['symbolSize'] = 8;
               $option_order[$k]['itemStyle'] = array('normal'=>array('color'=>'#3db1fa'));
               
               if(empty($v['address'])){
                  $invite_address = "浙江省杭州市";
               }else{
                  $invite_address = $v['address'];
               }
               $in_address = $this->getLatitudeByAddress($invite_address);
               $moveline_order[$k]['fromName'] = $invite_address;
               $moveline_order[$k]['toName'] = $address_1['content']['address'];
               $moveline_order[$k]['coords'] = array(array($in_address['result']['location']['lng'],$in_address['result']['location']['lat']),
                                                   array($address_1['content']['point']['x'],$address_1['content']['point']['y']));
               $moveline_order[$k]['fanli_amount'] = sprintf('%.2f',$v['amount']);
            }
         }
         /*********************购买订单转化**********************/

         /*********************分享->购买订单转化**********************/
         $orderoutInfo = getData($sql_orderout);
         if(!empty($orderoutInfo)){
            foreach ($orderoutInfo as $k => $v) {
               $buy_ip_2 = $v['buy_ip'];
               $address_2 = $this->getLatitudeByIp($buy_ip_2);
               $option_orderout[$k]['name'] = $address_2['content']['address'];
               $option_orderout[$k]['value'] = array($address_2['content']['point']['x'],$address_2['content']['point']['y']);
               $option_orderout[$k]['symbolSize'] = 3;
               $option_orderout[$k]['itemStyle'] = array('normal'=>array('color'=>'#F58158'));

               $share_ip_2 = $v['share_ip'];
               $s_address_2 = $this->getLatitudeByIp($share_ip_2);
              
               $moveline_orderout[$k]['fromName'] = $s_address_2['content']['address'];
               $moveline_orderout[$k]['toName'] = $address_2['content']['address'];
               $moveline_orderout[$k]['coords'] = array(array($s_address_2['content']['point']['x'],$s_address_2['content']['point']['y']),
                                                   array($address_2['content']['point']['x'],$address_2['content']['point']['y']));
               $moveline_orderout[$k]['fanli_amount'] = sprintf('%.2f',$v['amount']);
            }
         }
         /*********************分享->购买订单转化**********************/

         //比较order、orderout 两种情况下的最大（新）交易记录id,即ct.customer_transaction_id
         if(!empty($orderInfo)&&!empty($orderoutInfo)){
            $o_max_ctid = $orderInfo[0]['ct_id'];
            $out_max_ctid = $orderoutInfo[0]['ct_id'];
            if($o_max_ctid>$out_max_ctid){
               $_SESSION['max_ctid']   =  $o_max_ctid;
            }else{
               $_SESSION['max_ctid']   =  $out_max_ctid;
            }
         }elseif(!empty($orderInfo)&&empty($orderoutInfo)){
               $_SESSION['max_ctid']   =  $orderInfo[0]['ct_id'];
         }elseif(empty($orderInfo)&&!empty($orderoutInfo)){
               $_SESSION['max_ctid']   =  $orderoutInfo[0]['ct_id'];
         }else{
               // $_SESSION['max_ctid']   =  666;
              
         }
         $inviteinfo[$key]['max_ctid'] = $_SESSION['max_ctid'];

         if(!isset($_SERVER["HTTP_X_REQUESTED_WITH"])){
            $city = array_merge($option_invitecode,$option_order,$option_orderout);

         }elseif(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){
            $city = array_merge($option_order,$option_orderout);
         }
         $moveline = array_merge($moveline_order,$moveline_orderout);
         array_push($cityArr,$city);
         array_push($movelineArr,$moveline);


         $total = getRow($total_sql);                    
         // $today_total = getRow($today_sql); 

         $total_amount = getRow($amount_sql);            
         // $today_amount = getRow($today_amount_sql);

         $total_amount = $total_amount['total'];         
         // $today_amount = $today_amount['total'];      //今日返利收入

         $total = $total['total'];                       
         

         // $today_total = $today_total['total'];        //今日返利订单

         $inviteinfo[$key]['fanli_ordernum'] = $total;            //单个邀请码返利订单量
         $inviteinfo[$key]['fanli_money']    = $total_amount;     //单个邀请码返利收入

         $fanli_total_money += $total_amount;                     //返利总收入
         $fanli_total_num   += $total;                            //返利总单量
         $total_order_money += $money['order_money'];
         

         $max_ctid = $inviteinfo[0]['max_ctid'];
         if($inviteinfo[$key]['max_ctid']>$max_ctid){
            $max_ctid = $inviteinfo[$key]['max_ctid'];
         }
      }

      $_SESSION['max_ctid'] = $max_ctid;

      foreach ($cityArr as $key => $value) {
         if(is_array($value)){
            $cityArr2 = array_merge($cityArr2,$value);
         }
      }
      foreach ($movelineArr as $key => $value) {
         if(is_array($value)){
            $movelineArr2 = array_merge($movelineArr2,$value);
         }
      }
      if($fanli_total_money>10000){
         $fanli_total_money = $fanli_total_money/10000;
         $total_money = sprintf('%.2f',$fanli_total_money);
         $data['unit1'] = '万元';
      }else{
         $total_money = sprintf('%.2f',$fanli_total_money);
         $data['unit1'] = '元';
      }
      /**************************返利相关的订单金额*******************************/
         if($total_order_money>10000){
            $dingdan_money = $total_order_money/10000;
            $this->res['total_order_money']  = sprintf('%.2f',$dingdan_money);
            $this->res['unit']  = "(万元)";
         }else{
            $this->res['total_order_money']  = sprintf('%.2f',$total_order_money);
            $this->res['unit']  = "(元)";
         }
         /**************************返利相关的订单金额*******************************/
      //判断是否为ajax请求，给出相应的输出
      if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){ 
         $data['city'] = $cityArr2;
         $data['moveline'] = $movelineArr2;
         $data['total_money'] = $total_money;
         $data['total_order_num'] = $fanli_total_num;
         $data['max_ctid'] = $_SESSION['max_ctid'];

         if(!empty($movelineArr2)){
            print_r(json_encode($data));die;
         }else{
            // echo $sql_orderout;
            // die;
            echo 1;exit;  //无
         }
         
      }else{ 
         if($fanli_total_money>10000){
            $this->res['unit1']  = "(万元)";
         }else{
            $this->res['unit1']  = "(元)";
         }
         // print_r(json_encode($cityArr2));die;
         $this->res['city']  = json_encode($cityArr2);
         $this->res['moveline']  = json_encode($movelineArr2);
         $this->res['total_money']  = $total_money;
         $this->res['total_order_num'] = $fanli_total_num;
         $this->res['ajax_url'] = linkurl("bigscreen/index");
         return $this->res;
      };
      
   }
   //获取最新订单
   function getNewOrder(){
      $last_cktime = isset($_SESSION['last_cktime'])?$_SESSION['last_cktime']:0;
      $_SESSION['last_cktime']=time();
      $sql = "select order_id,amount from hb_customer_transaction where type = 3 and customer_id = ".$customer_id."";

   }
   //根据order_id获取收货地址
   function getAddress(){
      $order_id = 1014870;
      $sql = "select shipping_address_1,shipping_country,shipping_city,shipping_zone from hb_order where order_id = $order_id ";
      $info = getRow($sql);
      $address = $info['shipping_country'].$info['shipping_zone'].$info['shipping_city'];
      $location = $this->getLatitudeByAddress($address);
      print_r($location);die;
   }
   //根据地址获取经纬度
   function getLatitudeByAddress($address){
      // $address = "浙江省杭州市滨江区";
      $url  = "http://api.map.baidu.com/geocoder/v2/?address=$address&output=json&ak=2ueWQSHhgLBhAtCI2C2wokMN1cVURLi2";

      $info = file_get_contents($url);
      $info =json_decode($info,true);

      return $info;die;
   }
   //根据ip获取经纬度
   function getLatitudeByIp($ip){
      // $ip = "123.157.222.138";
      $url = "http://api.map.baidu.com/location/ip?ak=2ueWQSHhgLBhAtCI2C2wokMN1cVURLi2&ip=$ip&coor=bd09ll";

      $info = file_get_contents($url);
      $info =json_decode($info,true);

      return $info;die;
   }
   //根据pid查询子级customer_id
   function getCidByPid($pid){
      $sql = "select customer_id from hb_customer where parent_id = '".$pid."'";
      $info = getData($sql);
      return $info;
   }
   //根据invitecode_id查询邀请码信息
   function getInviteinfo($invitecode_id){
      $sql = "select * from ".DB_PREFIX."invitecode where invitecode_id = '".$invitecode_id."'";
      $inviteinfo = getRow($sql);
      return $inviteinfo;exit;
   }
   //根据customer_id查询邀请码信息
   function getInviteinfo2($customer_id){
      $sql = "select * from ".DB_PREFIX."invitecode where customer_id = '".$customer_id."'";
      $inviteinfo = getData($sql);
      return $inviteinfo;exit;
   }
   //查询最大order_id
   function getMaxOrderid(){
      $sql = "select max(order_id) as max_oid from hb_order where 1 ";
      $order_id = getRow($sql);
      $max_oid  = $order_id['max_oid'];
      $_SESSION['max_oid'] = $max_oid;
      return $max_oid;die;
   }
   function cs(){
      $str = '{"unit1":"\u5143",
               "city":[{"name":"\u897f\u85cf\u81ea\u6cbb\u533a\u62c9\u8428\u5e02",
                  "value":["91.11189090","29.66255706"],
                  "symbolSize":8,
                  "itemStyle":{"normal":{"color":"#3db1fa"}}}],
               "moveline":[{"fromName":"\u56db\u5ddd\u7701\u6210\u90fd\u5e02\u6210\u534e\u533a",
                              "toName":"\u897f\u85cf\u81ea\u6cbb\u533a\u62c9\u8428\u5e02",
                              "coords":[[104.15003204704,30.695040111899],[116.4551, 40.2539]]
                           },{
                "fromName": "襄阳",
                "toName": "武汉",
                "coords": [
                    [110.8200413, 31.929278],
                    [106.8607211, 36.4901067]
                ]
            }],
               "total_money":"5.07",
               "total_order_num":14,
               "time":"2017-02-25 11:44:56"
            }';
      $arr = json_decode($str,true);
      print_r($str);die;
   }
   	
	

	
}

