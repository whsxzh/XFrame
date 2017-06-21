<?php
use OSS\OssClient;
use OSS\Core\OssException;

  //面向对象的control 类
include "xcontrol/base.php";
include "xcontrol/product.php";

require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';

class common extends base
{
  
  /**
   * cgl  2016-12-30
   * 后台首页
   */
  function index(){
    if($common_index = getCache($_SERVER['REQUEST_URI'].'-'.$this->userid))
    {
    	
      $this->res = $common_index;
      return $this->res;
    }

    $this->res['datepicker_yqm']=date("m/d/Y",strtotime("-7 days"))." - ".date("m/d/Y");
    $this->getMenu();
    //注销的链接
    $url=linkurl("user/loginout");
    $this->res['loginouturl']=$url;
    //首页的链接
    $this->res['index']=$url=linkurl("common/index");

    if($_SESSION['merchant_id']==1)
      $syswhere="1=1";
    else
      $syswhere="merchant_id = ".$_SESSION['merchant_id'];

    // 处理商户基本信息
    $merchant_info = getRow("select * from ".DB_PREFIX."merchant where $syswhere");
    $merchant_info['money'] = sprintf("%.2f",$merchant_info['money']);
    $merchant_info['availabe_money'] = sprintf("%.2f",$merchant_info['availabe_money']);
    $this->res['merchant_info'] = $merchant_info;

    // 可用余额为零，则不可申请提现
    $availabe_money = (float)$merchant_info['availabe_money'];
    if( empty($availabe_money) ){
      $this->res['withdraw'] = 1;
    }

    // 处理商户的总收入（今日），总订单数（今日），总会员数（今日）
    $start_date = strtotime(date("y-m-d"));
    $end_date = time();

    // 总会员数
    //$all_customers = getRow("select count(customer_id) as all_customers_num from ".DB_PREFIX."customer ");
   

    // 今日会员数
    // $today_customers = getRow("select count(customer_id) as today_customers from ".DB_PREFIX."customer where merchant_id =".$_SESSION['merchant_id']."
    //                            and UNIX_TIMESTAMP(date_added) > '".$start_date."' and UNIX_TIMESTAMP(date_added) < '".$end_date."'");
   

    // 总订单数
    $all_orders = getRow("select count(order_id) as all_orders from ".DB_PREFIX."order where order_status_id in (2,3,4,5,11) and $syswhere ");
    $this->res['all_orders'] = $all_orders['all_orders'];

    // 今日订单数where merchant_id =".$_SESSION['merchant_id']."
    $today_orders = getRow("select count(order_id) as today_orders ,
                          (select count(customer_id)  from ".DB_PREFIX."customer 
                               where UNIX_TIMESTAMP(date_added) > '".$start_date."' and UNIX_TIMESTAMP(date_added) < '".$end_date."') as today_customers , 
                          (select count(customer_id)  from ".DB_PREFIX."customer ) as all_customers_num , 
                          (select sum(total) from ".DB_PREFIX."order where order_status_id in (2,3,4,5,11)
                            and $syswhere
                            and date_added > '".$start_date."' and date_added < '".$end_date."')  as today_income 
                          from ".DB_PREFIX."order where order_status_id in (2,3,4,5,11) and $syswhere
                          and date_added > '".$start_date."' and date_added < '".$end_date."'");
//var_dump($today_orders);exit;

    // 总收入 status = 0 and
    $all_income = getRow("select sum(total) as all_income from ".DB_PREFIX."order where  order_status_id in (2,3,4,5,11) and $syswhere");// )

    $this->res['all_income'] = $all_income['all_income'];

    // 今日收入
    // $today_income = getRow("select sum(total) as today_income from ".DB_PREFIX."order where status = 0 and order_status_id in (2,3,4,5,11,10,8)
    //                         and merchant_id =".$_SESSION['merchant_id']."
    //                         and date_added > '".$start_date."' and date_added < '".$end_date."'");  

    $this->res['today_income'] = $today_orders['today_income'];
    $this->res['today_orders'] = $today_orders['today_orders'];
    $this->res['today_customers'] = $today_orders['today_customers'];
    $this->res['all_customers'] =  $today_orders['all_customers_num'];
    // 处理某个商户下的所拥有的邀请码数据情况
    $page = ! empty($_GET['page']) ? $_GET['page'] : 1;
    $limit = 10;
    $count_arr = getRow("select count(invitecode_id) all_nums from ".DB_PREFIX."invitecode 
              where customer_id in(select customer_id from ".DB_PREFIX."customer where $syswhere) and times != 0");
    $count = $count_arr['all_nums'];
    //cgl 2017-6-8 修改为登录者的资金  $_SESSION["merchant_id"]
    /*
 select a.*,b.onum,b.ototlal,c.rttotle from 
     (select i.invitecode_id,i.invitecode,p.lastname,p.firstname,count(c.customer_id) as cnum from hb_customer as c  
      left join  hb_invitecode as i on c.invitecode_id=i.invitecode_id ,hb_customer as p 
      where       i.customer_id=p.customer_id  
      group by c.invitecode_id) as a,  
      (select c.invitecode_id,count(o.order_id) as onum,truncate(sum(o.total),2) as ototlal from hb_customer as c  
      , hb_order as o    
      where c.customer_id=o.customer_id and o.order_status_id in(2,3,4,5,11)
             group by c.invitecode_id) as b,
       (select i.invitecode_id,truncate(sum(t.amount),2) as rttotle 
         from   hb_invitecode as i       , hb_customer_transaction as t 
      where t.customer_id=i.customer_id and t.type=3 
             group by i.invitecode_id) as c
    */
    $invitecode_info = getData("select a.*,b.onum,b.ototlal from 
     (select  IFNULL(i.invitecode_id,0) as invitecode_id,i.invitecode,p.lastname,p.firstname,count(c.customer_id) as cnum 
     from hb_customer as c  
      left join  hb_invitecode as i on c.invitecode_id=i.invitecode_id left join hb_customer as p on  i.customer_id=p.customer_id       
      group by c.invitecode_id) as a ,
      (select ifnull(c.invitecode_id,0) as invitecode_id,count(o.order_id) as onum,truncate(sum(o.total),2) as ototlal from hb_customer as c  
      , hb_order as o    
      where c.customer_id=o.customer_id and o.order_status_id in(2,3,4,5,11)
             group by c.invitecode_id) as b 
             where a.`invitecode_id`=b.`invitecode_id` ",600);//
    /*(select i.invitecode_id,truncate(sum(t.amount),2) as rttotle 
         from   hb_invitecode as i       , hb_customer_transaction as t 
      where t.customer_id=i.customer_id and t.type=3 
             group by i.invitecode_id) as c on b.`invitecode_id`=c.`invitecode_id` 
             */
    //where 
    // $invitecode_info=getData("SELECT  DISTINCT i.`invitecode_id`,
    //                           i.`invitecode`,
    //                           (SELECT COUNT(customer_id) FROM hb_customer WHERE invitecode_id=i.`invitecode_id` ) AS cnum,
    //                           (SELECT lastname FROM hb_customer WHERE customer_id=i.`customer_id` ) AS lastname,
    //                           COUNT(o.order_id)  AS onum,
    //                           SUM(t.amount) AS rttotle,
    //                           SUM(o.total) AS ototlal 
    //                           FROM hb_invitecode AS i 
    //                           LEFT JOIN hb_customer AS c ON i.`invitecode_id`=c.`invitecode_id`  
    //                           LEFT JOIN hb_order AS o ON o.customer_id=c.customer_id 
    //                           AND order_status_id IN (2,3,4,5,11) 
    //                           LEFT JOIN hb_customer_transaction AS t ON t.order_id=o.order_id AND t.type IN (3,7) WHERE i.status=0 GROUP BY i.`invitecode_id`",600);

    $total_inv=array("invitecode_id"=>0,"cnum"=>0,"onum"=>0,"ototlal"=>0,"rttotle"=>0);
    foreach ($invitecode_info as $key => $value) {
      # code...
      if($value['invitecode_id'])
       $total_inv["invitecode_id"]+=1;
     else
        $invitecode_info[$key]['invitecode'] ='无邀请码用户';

      $total_inv["cnum"]+=$value['cnum'];
      $total_inv["onum"]+=$value['onum'];
      $total_inv["ototlal"]+=$value['ototlal'];
      //$total_inv["rttotle"]+=$value['rttotle'];

    }
    $this->res['invitecode_info'] = $invitecode_info;
    $this->res['total_inv']=$total_inv;

    if($count < $limit){
      $this->getPages($page,1);
    }else{
      $this->getPages($page,ceil($count/$limit));
    }

    // 处理表格数据的情况，默认显示最近一周的数据
    $chart_data = $this->getDefaultChartData();
    
    $this->res['chart_data'] = $chart_data;

    // ajax请求今天的统计数据，收入，订单，会员
    $this->res['getTodayDataUrl'] = linkurl('common/getTodayDataByAjax');

    // ajax请求昨天的统计数据，收入，订单，会员
    $this->res['getYesterdayDataUrl'] = linkurl('common/getYesterdayDataByAjax');

    // ajax请求某天的统计数据，收入，订单，会员
    $this->res['getSomedayDataUrl'] = linkurl('common/getSomeDataByAjax');

    // ajax请求获取折线图数据的地址
    $this->res['getChartDataUrl'] = linkurl('common/getChartDataByAjax');

    // 企业账号余额明细跳转url
    $this->res['moneyDetailUrl'] = linkurl('common/moneyDetails',array('merchant_id'=>$merchant_info['merchant_id']));

    // 企业提现跳转url
    $this->res['merchantWithdrawUrl'] = linkurl('common/merchantWithdraw');

    $this->res['getInvitecodeRemarkUrl'] = linkurl('common/getInvitecodeRemarkByAjax');
    $this->res['saveInvitecodeRemarkUrl'] = linkurl('common/saveInvitecodeRemarkByAjax');

    $this->res['merchantWithdrawUrl'] = linkurl('common/merchantWithdraw');

    setCache( $_SERVER['REQUEST_URI'].'-'.$this->userid, $this->res, 600);
    return $this->res;
  }

  /**
   *  @description 
   *  @param       none
   *  @return      
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-14/17:20:00
   */
  function merchantWithdraw(){
    $this->getMenu();
    if( !empty($_POST) ){
      
    }


    return $this->res;
  }

  /**
   */
  // public function 

  /**
   *  @description 处理今天的统计数据，收入，订单，会员数量
   *  @param       none
   *  @return      array today_data 备注：包括，今日收入，今日订单，今日新增会员数量
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-13/17:20:00
   */
  function getTodayDataByAjax(){
    // 返回的数据

    if($_SESSION['merchant_id']==1)
      $syswhere="1=1";
    else
      $syswhere="merchant_id = ".$_SESSION['merchant_id'];

    $today_data = array('code'=>1, 'msg'=>'success', 'data'=>array());

    // 处理商户的今日收入，今日订单数，今日会员数
    $start_date = strtotime(date("y-m-d"));

    // 今日会员数
    $today_customers = getRow("select count(customer_id) as today_customers from ".DB_PREFIX."customer 
                               where $syswhere 
                               and UNIX_TIMESTAMP(date_added) > '".$start_date."'");
    $today_data['data']['today_customers']  = $today_customers['today_customers'];

    // 今日订单数
    $today_orders = getRow("select count(order_id) as today_orders from ".DB_PREFIX."order 
                            where $syswhere 
                            and date_added > '".$start_date."'");
    $today_data['data']['today_orders'] = $today_orders['today_orders'];

    // 今日收入
    $today_income = getRow("select sum(total) as today_income from ".DB_PREFIX."order 
                            where status = 0 and order_status_id in (2,3,4,5,11)
                            and $syswhere 
                            and date_added > '".$start_date."'");
    $today_refound = getRow("select sum(total) as today_refound from ".DB_PREFIX."order 
                             where status = 0 and order_status_id = 9
                             and $syswhere 
                             and date_added > '".$start_date."'");
    $today_data['data']['today_income'] = $today_income['today_income'] - $today_refound['today_refound'];

    echo json_encode($today_data);exit;
  }

  /**
   *  @description 处理昨天的统计数据，收入，订单，会员数量
   *  @param       none
   *  @return      array yesterday_data 备注：包括，昨日收入，昨日订单，昨日新增会员数量
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-13/17:20:00
   */
  function getYesterdayDataByAjax(){
    // 返回的数据

    if($_SESSION['merchant_id']==1)
      $syswhere="1=1";
    else
      $syswhere="merchant_id = ".$_SESSION['merchant_id'];

    $yesterday_data = array('code'=>1, 'msg'=>'success', 'data'=>array());

    // 处理商户的昨日收入，昨日订单数，昨日会员数
    $start_date = strtotime(date("y-m-d"));

    // 昨日会员数
    $yesterday_customers = getRow("select count(customer_id) as yesterday_customers from ".DB_PREFIX."customer 
                               where $syswhere 
                               and UNIX_TIMESTAMP(date_added) > '".($start_date-24*3600)."' 
                               and UNIX_TIMESTAMP(date_added) < '".$start_date."'");
    $yesterday_data['data']['yesterday_customers']  = $yesterday_customers['yesterday_customers'];

    // 昨日订单数
    $yesterday_orders = getRow("select count(order_id) as yesterday_orders from ".DB_PREFIX."order 
                            where $syswhere 
                            and date_added > '".($start_date-24*3600)."'
                            and date_added < '".$start_date."'");
    $yesterday_data['data']['yesterday_orders'] = $yesterday_orders['yesterday_orders'];

    // 昨日收入
    $yesterday_income = getRow("select sum(total) as yesterday_income from ".DB_PREFIX."order 
                            where status = 0 and order_status_id in (2,3,4,5,11)
                            and $syswhere 
                            and date_added > '".($start_date-24*3600)."'
                            and date_added < '".$start_date."'");
    $yesterday_refound = getRow("select sum(total) as yesterday_refound from ".DB_PREFIX."order 
                             where status = 0 and order_status_id = 9
                             and $syswhere 
                             and date_added > '".($start_date-24*3600)."'
                             and date_added < '".$start_date."'");
    $yesterday_data['data']['yesterday_income'] = $yesterday_income['yesterday_income'] - $yesterday_refound['yesterday_refound'];

    echo json_encode($yesterday_data);exit;
  }

  /**
   *  @description 处理某天的统计数据，收入，订单，会员数量
   *  @param       none
   *  @return      array yesterday_data 备注：包括，某日收入，某日订单，某日新增会员数量
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-13/17:20:00
   */
  function getSomeDataByAjax(){
    // 返回的数据

    if($_SESSION['merchant_id']==1)
      $syswhere="1=1";
    else
      $syswhere="merchant_id = ".$_SESSION['merchant_id'];

    $somedate_data = array('code'=>1, 'msg'=>'success', 'data'=>array());
    if(empty($_POST['somedate'])){
      $chart_data = array('code'=>-2, 'msg'=>'fail,[缺少必要参数：somedate]', 'data'=>'');
    }else{
      $somedate = strtotime($_POST['somedate']);

      // 某日会员数
      $somedate_customers = getRow("select count(customer_id) as somedate_customers from ".DB_PREFIX."customer 
                                 where $syswhere 
                                 and UNIX_TIMESTAMP(date_added) < '".($somedate+24*3600)."' 
                                 and UNIX_TIMESTAMP(date_added) > '".$somedate."'");
      $somedate_data['data']['somedate_customers']  = $somedate_customers['somedate_customers'];

      // 某日订单数
      $somedate_orders = getRow("select count(order_id) as somedate_orders from ".DB_PREFIX."order 
                              where $syswhere 
                              and date_added < '".($somedate+24*3600)."'
                              and date_added > '".$somedate."'");
      $somedate_data['data']['somedate_orders'] = $somedate_orders['somedate_orders'];

      // 某日收入
      $somedate_income = getRow("select sum(total) as somedate_income from ".DB_PREFIX."order 
                              where status = 0 and order_status_id in (2,3,4,5,11)
                              and $syswhere 
                              and date_added < '".($somedate+24*3600)."'
                              and date_added > '".$somedate."'");
      $somedate_refound = getRow("select sum(total) as somedate_refound from ".DB_PREFIX."order 
                               where status = 0 and order_status_id = 9
                               and $syswhere 
                               and date_added < '".($somedate+24*3600)."'
                               and date_added > '".$somedate."'");
      $somedate_data['data']['somedate_income'] = $somedate_income['somedate_income'] - $somedate_refound['somedate_refound'];
    }
    echo json_encode($somedate_data);exit;
  }


  /**
   *  @description 处理表格数据，根据用户选择的不同时间段和数据类型（收入，订单，会员等），拉取不同的数据
   *  @param       string start_date
   *  @param       string end_date
   *  @param       int    data_type
   *  @return      array chart_data，包含，元素以json字符串的形式存储
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-13/17:20:00
   */
  function getChartDataByAjax(){
    // 返回的数据
    $chart_data = array('code'=>1, 'msg'=>'success', 'data'=>'');
    if(empty($_POST['start_date'])){
      $chart_data = array('code'=>0, 'msg'=>'fail,[缺少必要参数：start_date]', 'data'=>'');
    }else if(empty($_POST['end_date'])){
      $chart_data = array('code'=>-1, 'msg'=>'fail,[缺少必要参数：end_date]', 'data'=>'');
    }else if(empty($_POST['data_type'])){
      $chart_data = array('code'=>-2, 'msg'=>'fail,[缺少必要参数：data_type]', 'data'=>'');
    }else{
      // 计算两个日期之间的相隔天数
      $diffTowDays = $this->diffDaysBetweenTwoDate($_POST['start_date'],$_POST['end_date']);

      // 如果相隔的天数超过60天
      if($diffTowDays > 60){
        // 计算两个日期之间的相隔的月数
        $diffMonths = $this->diffMonthsBetweenTwoDate($_POST['start_date'],$_POST['end_date']);
        // 横轴按月份展示折线图数据
        // ...

      }else{
        // 横轴按日期展示折线图数据
        $data = $this->getChartDataViaDays($_POST['start_date'], $_POST['end_date'], $_POST['data_type'], $diffTowDays);
        if(is_array($data) && !empty($data)){
          $chart_data = array('code'=>1, 'msg'=>'success', 'data'=>$data);
        }else{
          $chart_data = array('code'=>-3, 'msg'=>'未知错误', 'data'=>'');
        }
      }
    }
    echo json_encode($chart_data);exit;
  }

  /**
   *  @description 处理表格数据的情况，获取（收入，订单，会员）不同时间范围的数据
   *  @param       string   $start_date  开始时间
   *  @param       string   $end_date    结束时间
   *  @param       string   $data_type   数据类型（income：收入；order：订单；customer：会员）
   *  @param       num      $number      天数
   *  @return      array    $data  结果集
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-10/10:20:00
   */
  function getChartDataViaDays($start_date, $end_date, $data_type, $days){
    // 返回的数据结果集
    if($_SESSION['merchant_id']==1)
      $syswhere="1=1";
    else
      $syswhere="merchant_id = ".$_SESSION['merchant_id'];


    $data = array();
    $total_income = 0;
    $total_orders = 0;
    $total_customers = 0;
    $total_products = 0;
    if('income' == $data_type){
      // 处理收入
      for($i=0; $i<$days+1; $i++){
        $income_sql = "select sum(total) as income from ".DB_PREFIX."order where status = 0 and order_status_id in (2,3,4,5,11) 
                       and $syswhere 
                       and date_added > '".(string)(strtotime($start_date) + $i*24*3600)."' 
                       and date_added < '".(string)(strtotime($start_date) + ($i+1)*24*3600)."'";
        $income = getRow($income_sql);

        $refound_sql = "select sum(total) as refound from ".DB_PREFIX."order where status = 0 and order_status_id = 9 
                        and $syswhere  
                        and date_added > '".(string)(strtotime($start_date) + $i*24*3600)."' 
                        and date_added < '".(string)(strtotime($start_date) + ($i+1)*24*3600)."'";
        $refound = getRow($refound_sql);
        $income_data_tmp['d'] = date('Y-m-d',(string)(strtotime($start_date) + $i*3600*24));
        $income_data_tmp['item'] = $income['income'] - $refound['refound'];
        $income_data[] = $income_data_tmp;
        $total_income += $income_data_tmp['item'];
      }
      if(is_array($income_data) && $income_data){
        $data['income'] = $income_data;
        $data['total_income'] = $total_income;
      }else{
        $data = array();
      }
    }else if('order' == $data_type){
      // 处理订单
      for($j=0;$j<$days+1;$j++){
        $orders_sql = "select count(order_id) as orders from ".DB_PREFIX."order where $syswhere 
                       and date_added > '".(string)(strtotime($start_date) + $j*3600*24)."' 
                       and date_added < '".(string)(strtotime($start_date) + ($j+1)*3600*24)."'";
        $orders = getRow($orders_sql);
        $orders_data_tmp['d'] = date('Y-m-d',(string)(strtotime($start_date) +$j*3600*24));
        $orders_data_tmp['item'] = $orders['orders'];
        $orders_data[] = $orders_data_tmp;
        $total_orders += $orders_data_tmp['item'];
      }
      if(is_array($orders_data) && $orders_data){
        $data['orders'] = $orders_data;
        $data['total_orders'] = $total_orders;
      }else{
        $data = array();
      }

    }else if('customer' == $data_type){
      // 处理会员
      for($n=0;$n<$days+1;$n++){
        $customers_sql = "select count(customer_id) as customers from ".DB_PREFIX."customer where $syswhere 
                          and UNIX_TIMESTAMP(date_added) > '".(string)(strtotime($start_date) + $n*3600*24)."' 
                          and UNIX_TIMESTAMP(date_added) < '".(string)(strtotime($start_date) + ($n+1)*3600*24)."'";
        $customers = getRow($customers_sql);
        $customers_data_tmp['d'] = date('Y-m-d',(string)(strtotime($start_date)+$n*3600*24));
        $customers_data_tmp['item'] = $customers['customers'];
        $customers_data[] = $customers_data_tmp;
        $total_customers += $customers_data_tmp['item'];
      }
      if(is_array($customers_data) && $customers_data){
        $data['customers'] = $customers_data;
        $data['total_customers'] = $total_customers;
      }else{
        $data = array();
      }
    }else if('product' == $data_type){
      // 处理商品
      for($n=0;$n<$days+1;$n++){
        $products_sql = "select count(product_id) as products from ".DB_PREFIX."product where $syswhere 
                          and UNIX_TIMESTAMP(date_added) > '".(string)(strtotime($start_date) + $n*3600*24)."' 
                          and UNIX_TIMESTAMP(date_added) < '".(string)(strtotime($start_date) + ($n+1)*3600*24)."'";
        $products = getRow($products_sql);
        $products_data_tmp['d'] = date('Y-m-d',(string)(strtotime($start_date)+$n*3600*24));
        $products_data_tmp['item'] = $products['products'];
        $products_data[] = $products_data_tmp;
        $total_products += $products_data_tmp['item'];
      }
      if(is_array($products_data) && $products_data){
        $data['products'] = $products_data;
        $data['total_products'] = $total_products;
      }else{
        $data = array();
      }
    }else{
      // 处理其他待更新的数据
      // todo...
    }

    return $data;
  }

  /**
   *  @description 处理表格数据的情况，默认显示最近一周的数据，近一周的收入数据，近一周的会员数据，近一周的订单数据
   *  @param       none
   *  @return      array default_chart_data，包含，元素以json字符串的形式存储
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-10/10:20:00
   */
  function getDefaultChartData(){
    // 返回的数据

     if($_SESSION['merchant_id']==1)
      $syswhere="1=1";
    else
      $syswhere="merchant_id = ".$_SESSION['merchant_id'];

    $default_chart_data = array();
    $total_income = 0;
    $total_orders = 0;
    $total_customers = 0;
    $total_products = 0;

    // 当日时间开始时间戳
    $start_date = strtotime(date("y-m-d"));

    // 处理某企业近一周内每天的收入情况数据
    for($i=6; $i>=0; $i--){
      if(empty($i)){
        $income_sql = "select sum(total) as income from ".DB_PREFIX."order where status = 0 and order_status_id in (2,3,4,5,11) and $syswhere 
                       and date_added > '".(string)($start_date)."'";
      }else{
        $income_sql = "select sum(total) as income from ".DB_PREFIX."order where status = 0 and order_status_id in (2,3,4,5,11) and $syswhere 
                       and date_added > '".(string)($start_date-$i*3600*24)."' and date_added < '".(string)($start_date-($i-1)*3600*24)."'";
      }
      $income = getRow($income_sql);

      if(empty($i)){
        $refound_sql = "select sum(total) as refound from ".DB_PREFIX."order where status = 0 and order_status_id = 9
                        and date_added > '".(string)($start_date)."'";
      }else{
        $refound_sql = "select sum(total) as refound from ".DB_PREFIX."order where status = 0 and order_status_id = 9
                        and date_added > '".(string)($start_date-$i*3600*24)."' and date_added < '".(string)($start_date-($i-1)*3600*24)."'";
      }
      $refound = getRow($refound_sql);
      $income_data_tmp['d'] = date('Y-m-d',(string)($start_date-$i*3600*24));
      $income_data_tmp['item'] = $income['income'] - $refound['refound'];
      $income_data[] = $income_data_tmp;
      $total_income += $income_data_tmp['item'];
    }

    if(is_array($income_data) && $income_data){
      $default_chart_data['income'] = json_encode($income_data);
      $default_chart_data['total_income'] = $total_income;
    }else{
      $default_chart_data['income'] = '';
    }

    // 处理某企业近一周内每天的订单数量情况数据
    for($j=6;$j>=0;$j--){
      if(empty($j)){
        $orders_sql = "select count(order_id) as orders from ".DB_PREFIX."order where $syswhere 
                       and date_added > '".(string)($start_date)."'";
      }else{
        $orders_sql = "select count(order_id) as orders from ".DB_PREFIX."order where $syswhere 
                       and date_added > '".(string)($start_date-$j*3600*24)."' and date_added < '".(string)($start_date-($j-1)*3600*24)."' order by date_added desc";
      }
      $orders = getRow($orders_sql);
     
      $orders_data_tmp['d'] = date('Y-m-d',(string)($start_date-$j*3600*24));
      $orders_data_tmp['item'] = $orders['orders'];
      $orders_data[] = $orders_data_tmp;
      $total_orders += $orders_data_tmp['item'];
    }
    if(is_array($orders_data) && $orders_data){
      $default_chart_data['orders'] = json_encode($orders_data);
      $default_chart_data['total_orders'] = $total_orders;
    }else{
      $default_chart_data['orders'] = '';
    }
    // 处理某企业近一周内每天的新增会员数量情况数据
    for($n=6;$n>=0;$n--){
      if(empty($n)){
        $customers_sql = "select count(customer_id) as customers from ".DB_PREFIX."customer where $syswhere 
                          and UNIX_TIMESTAMP(date_added) > '".(string)($start_date-$n*3600*24)."'";
      }else{
        $customers_sql = "select count(customer_id) as customers from ".DB_PREFIX."customer where $syswhere 
                          and UNIX_TIMESTAMP(date_added) > '".(string)($start_date-$n*3600*24)."' and UNIX_TIMESTAMP(date_added) < '".(string)($start_date-($n-1)*3600*24)."'";
      }
      $customers = getRow($customers_sql);
      $customers_data_tmp['d'] = date('Y-m-d',(string)($start_date-$n*3600*24));
      $customers_data_tmp['item'] = $customers['customers'];
      $customers_data[] = $customers_data_tmp;
      $total_customers += $customers_data_tmp['item'];
    }
    if(is_array($customers_data) && $customers_data){
      $default_chart_data['customers'] = json_encode($customers_data);
      $default_chart_data['total_customers'] = $total_customers;
    }else{
      $default_chart_data['customers'] = '';
    }

    // 处理某企业近一周内每天的新增商品数量情况数据
    for($n=6;$n>=0;$n--){
      if(empty($n)){
        $products_sql = "select count(product_id) as products from ".DB_PREFIX."product where $syswhere 
                          and UNIX_TIMESTAMP(date_added) > '".(string)($start_date-$n*3600*24)."'";
      }else{
        $products_sql = "select count(product_id) as products from ".DB_PREFIX."product where $syswhere 
                          and UNIX_TIMESTAMP(date_added) > '".(string)($start_date-$n*3600*24)."' and UNIX_TIMESTAMP(date_added) < '".(string)($start_date-($n-1)*3600*24)."'";
      }
      $products = getRow($products_sql);
      $products_data_tmp['d'] = date('Y-m-d',(string)($start_date-$n*3600*24));
      $products_data_tmp['item'] = $products['products'];
      $products_data[] = $products_data_tmp;
      $total_products += $products_data_tmp['item'];
    }
    if(is_array($products_data) && $products_data){
      $default_chart_data['products'] = json_encode($products_data);
      $default_chart_data['total_products'] = $total_products;
    }else{
      $default_chart_data['products'] = '';
    }

    return $default_chart_data;
  }

  /**
   *  @description 根据企业下的所有邀请码获取由邀请码所产生的总订单数，当日订单数，总收入，当日收入情况
   *  @param       array invitecode
   *  @return      array invitecode_info
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-10/10:20:00
   */
  function getInvitecodeInfoById($invitecode = array()){
    // 处理当日数据所需要的时间戳设定值

      if($_SESSION['merchant_id']==1)
      $syswhere="1=1";
    else
      $syswhere="merchant_id = ".$_SESSION['merchant_id'];

    $start_date = strtotime(date("y-m-d"));
    $end_date = time();
    foreach($invitecode as $key => $value){
      // 某个邀请码的总订单数
      $all_orders = getRow("select count(*) as all_orders from ".DB_PREFIX."order where customer_id in
                            (select customer_id from ".DB_PREFIX."customer where invitecode_id=".$value['invitecode_id'].")");
      $invitecode[$key]['all_orders'] = $all_orders['all_orders'];

      // 某个邀请码当日订单数
      $today_orders = getRow("select count(*) as today_orders from ".DB_PREFIX."order where customer_id in
                            (select customer_id from ".DB_PREFIX."customer where invitecode_id=".$value['invitecode_id'].")
                            and date_added > '".$start_date."' and date_added < '".$end_date."'");
      $invitecode[$key]['today_orders'] = $today_orders['today_orders'];

      // 某个邀请码的总收入
      $all_income = getRow("select sum(total) as all_income from ".DB_PREFIX."order where customer_id in
                            (select customer_id from ".DB_PREFIX."customer where invitecode_id=".$value['invitecode_id'].")");
      $invitecode[$key]['all_income'] = $all_income['all_income'] ? sprintf("%.2f",$all_income['all_income']) : sprintf("%.2f",0.00);


      // 某个邀请码的当日收入
      $today_income = getRow("select sum(total) as today_income from ".DB_PREFIX."order where customer_id in
                            (select customer_id from ".DB_PREFIX."customer where invitecode_id=".$value['invitecode_id'].")
                            and date_added > '".$start_date."' and date_added < '".$end_date."'");
      $invitecode[$key]['today_income'] = $today_income['today_income'] ? sprintf("%.2f",$today_income['today_income']) : sprintf("%.2f",0.00);
    }
    return $invitecode;
  }

  /**
   *  @description  求两个日期之间相差的天数,(针对1970年1月1日之后，求之前可以采用泰勒公式)
   *  @param        string $start_date 开始日期
   *  @param        string $end_date   结束日期
   *  @return       number             天数
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-13/10:30
   */
  function diffDaysBetweenTwoDate($start_date, $end_date)
  {
    $start_second = strtotime($start_date);
    $end_second = strtotime($end_date);
      
    if($start_second < $end_second){
      $tmp = $end_second;
      $end_second = $start_second;
      $start_second = $tmp;
    }
    return ($start_second - $end_second) / 86400;
  }

  /**
   *  @description  求两个日期之间相差的月数,(针对1970年1月1日之后，求之前可以采用泰勒公式)
   *  @param        string $start_date 开始日期
   *  @param        string $end_date   结束日期
   *  @return       number             月数
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-13/10:30
   */
  function diffMonthsBetweenTwoDate($start_date, $end_date)
  {
    $end_date_stamp = strtotime($end_date);
    $start_date_stamp = strtotime($start_date);
    list($end_date_tmp['y'],$end_date_tmp['m']) = explode("-",date('Y-m',$end_date_stamp));
    list($start_date_tmp['y'],$start_date_tmp['m']) = explode("-",date('Y-m',$start_date_stamp));
    return ( $end_date_tmp['y'] - $start_date_tmp['y'] ) * 12 + ( $end_date_tmp['m']-$start_date_tmp['m'] );
  }
  
  /**
   * 封装后台的图片地址 cgl 2017-2-8
   */
  function admindefault($imgbefore) {
    if((strpos($imgbefore, 'http://') === false) || (strpos($imgbefore, 'http://') > 0)) {
      $imgafter = TEST_IP . '/image/' . $imgbefore;
    } else {
      $imgafter = $imgbefore;
    }
    return $imgafter;
  }
    /**
     * zxx 新增广告 2017-2-8
     */
    function addAd(){
       //菜单
      $this->getMenu();
      if(isset($_GET['banner_id'])){
          $banner_info=getRow("select banner_image from hb_banner where banner_id=".$_GET['banner_id']);
      }
      $this->res['banner_image']= $banner_info['banner_image'];
      $this->res['shangpin_name']="点击选择商品";
      $this->res['sort_order']=0;
      if(count($_POST)>0){
            $_SESSION['photo']=$this->admindefault("catalog/gd/product/".$_POST['photo']);
            $_SESSION['name']=$_POST['name'];
            $_SESSION['banner_select']=$_POST['banner_select'];
            $_SESSION['sort_order']=$_POST['sort_order'];
            $_SESSION['site']=$_POST['site'];

      }
      if(isset($_GET['shangpin'])){
            $this->res['name']=$_SESSION['name'];
            $this->res['image']=$_SESSION['photo'];
            $this->res['banner_select']=$_SESSION['banner_select'];
            $this->res['sort_order']=$_SESSION['sort_order'];
            $this->res['subtype']=2;
            $this->res['site']=$_SESSION['site'];
            $shangpin=trim($_GET['shangpin'],',');
            $item_id=$shangpin;

            $sql="select name from ".DB_PREFIX."product_description where product_id='".$item_id."'";
            $shangpin_name=getRow($sql);
            $this->res['shangpin_name']=$shangpin_name['name'];
            $this->res['item_id']=$item_id;
           
      }else{
        $this->res['item_id']=0;
      }

      //echo $shangpin_name['name'];
      $this->res['type']=0;
      $this->res['subtype']=1;
      $merchant_id=$_SESSION['merchant_id'];
      //找出所有的一级菜单
      $level1=getData("select cd.name,cd.category_id,c.parent_id from ".DB_PREFIX."category_description cd left join ".DB_PREFIX."category c on (c.category_id=cd.category_id) where c.parent_id=0 and c.type=0 and c.merchant_id=$merchant_id"  );
      //获取到所有的一级菜单的category_id
      $parent_ids=array();
      foreach ($level1 as $key => $value) {
          $parent_ids[]=$value['category_id'];
      }

      $parent_ids=implode(',', $parent_ids);
     
      //找出所有的二级菜单
       $level2=getData("select cd.name name2,cd.category_id category_id2,c.parent_id parent_id2 from ".DB_PREFIX."category_description cd left join ".DB_PREFIX."category c on (c.category_id=cd.category_id) where  c.type=0 and c.merchant_id=$merchant_id"  );
     foreach ($level1 as $key => $value) {
        foreach ($level2 as $key2 => $value2) {
         // echo $level1[$key]['category_id']."<br/>";
          if($level1[$key]['category_id']==$level2[$key2]['parent_id2']){
               $level1[$key]['son'][]=$level2[$key2];
          }
        }
        
     }

     //找出所有的第三级菜单
     foreach ($level1 as $key => $value) {

      if(count($level1[$key])==4){
          foreach ($level1[$key]['son'] as $key3 => $value3) {
              foreach ($level2 as $key2 => $value2) {
             // echo $level1[$key]['category_id']."<br/>";
              if($level1[$key]['son'][$key3]['category_id2']==$level2[$key2]['parent_id2']){
                   $level1[$key]['son'][$key3]['son'][]=$level2[$key2];
              }
            }
          }
      }
        
     }

     //获取到所有的活动
     $activity=getData("select c.category_id,c.sort_order,c.points,c.merchant_id,c.type,cd.name ,c.image from ".DB_PREFIX."category c left join ".DB_PREFIX."category_description cd on(c.category_id=cd.category_id) where c.status=1 and c.type=1 and c.parent_id=0 and merchant_id=".$merchant_id." order by c.sort_order");

      //获取到所有的符合要求的位置
      $sites=getData("select name,banner_id from ".DB_PREFIX."banner where banner_id>11");
      $this->res['activity']=$activity;
      $this->res['sites']=$sites;
      $this->res['cat']=$level1;
      $this->res['level1']=$level1;
      $this->res['level2']=$level2;
      $this->res['banner_id']=@$_GET['banner_id'];
      $this->res["addAd"]=linkurl("common/addAd");
      $this->res["getGoodsList"]=linkurl("common/getGoodsList");
      $this->res["getGoodsActivityList"]=linkurl("common/getGoodsActivityList");
      $this->res["getProductName"]=linkurl("common/getProductName");
      $this->res["SaveAd"]=linkurl("common/SaveAd");
      $this->res["homepage"]=linkurl("common/homepage");      
      return $this->res;
    }

     /**
     * zxx 显示广告修改页 2017-2-8
     */
    function updateAd(){
       //菜单
      $this->getMenu();
      if(isset($_GET['banner_id'])){
          $banner_info=getRow("select banner_image from hb_banner where banner_id=".$_GET['banner_id']);
      }
      $this->res['banner_image']= $banner_info['banner_image'];
      //获取商户id
      $merchant_id=$_SESSION['merchant_id'];
      $banner_image_id=isset($_GET['banner_image_id'])?$_GET['banner_image_id']:"";   
      //通过image_id获取到item_id和subtype
      $find=getRow("select subtype,item_id,type from ".DB_PREFIX."banner_image where banner_image_id=$banner_image_id");

      $item_id=$find['item_id'];//echo $item_id;exit;
      if(isset($_GET['shangpin'])){
        $shangpin=trim($_GET['shangpin'],',');
        $item_id=$shangpin;
        $find['subtype']=1;
        $find['type']=0;
      }
      //var_dump($find);exit();
       $this->res['shangpin_name']="点击选择商品";
      if(@$find['type']==0){
          if(@$find['subtype']==1){
          //产品
            if($item_id==0){
               @$this->res['shangpin_name']="全部商品";
            }else{
              $sql="select name from ".DB_PREFIX."product_description where product_id='".$item_id."'";
              $shangpin_name=getRow($sql);
              @$this->res['shangpin_name']=$shangpin_name['name'];
            }
           
          }elseif(@$find['subtype']==0){
              //分类
             if($find['item_id']!=0){
                  $fenlei_name=getRow("select name from ".DB_PREFIX."category_description where category_id='".$item_id."'");
                    $this->res['fenlei_name']=$fenlei_name['name'];
                    $this->res['fenlei_id']=$item_id;
             }else{
                $this->res['fenlei_name']="全部商品";
                
             }

              
              
          }elseif($find['subtype']==5){
              //活动
              $huodong_name=getRow("select category_id from ".DB_PREFIX."category_description where category_id='".$item_id."'");
              $this->res['category_ids']=$huodong_name['category_id'];


          }
      }
      
      //获取到广告信息
      $category=getRow("SELECT `sort_order`,`image`,`title`,`type`,`subtype`,`item_id`,`link`,height FROM `hb_banner_image`  WHERE  `merchant_id` = '". (int)$merchant_id ."' and banner_image_id=".$banner_image_id." ORDER BY `sort_order` ASC");
      
      //如果商品不空，则使用处理照片路径的函数
      if(!empty($category)){
        if(strpos($category["image"],"haiqihuocang.oss-cn-hangzhou")){
        }else{
            $category["image"]=$this->admindefault($category['image']);
        }
      }
      //找出所有的一级菜单
      $level1=getData("select cd.name,cd.category_id,c.parent_id from ".DB_PREFIX."category_description cd left join ".DB_PREFIX."category c on (c.category_id=cd.category_id) where c.parent_id=0 and c.type=0 and c.merchant_id=$merchant_id"  );
      //获取到所有的一级菜单的category_id
      $parent_ids=array();
      foreach ($level1 as $key => $value) {
          $parent_ids[]=$value['category_id'];
      }

      $parent_ids=implode(',', $parent_ids);
     
      //找出所有的二级菜单
       $level2=getData("select cd.name name2,cd.category_id category_id2,c.parent_id parent_id2 from ".DB_PREFIX."category_description cd left join ".DB_PREFIX."category c on (c.category_id=cd.category_id) where  c.type=0 and c.merchant_id=$merchant_id"  );
     foreach ($level1 as $key => $value) {
        foreach ($level2 as $key2 => $value2) {
          if($level1[$key]['category_id']==$level2[$key2]['parent_id2']){
               $level1[$key]['son'][]=$level2[$key2];
          }
        }
        
     }

     //找出所有的第三级菜单
     foreach ($level1 as $key => $value) {
      if(count($level1[$key])==4){
          foreach ($level1[$key]['son'] as $key3 => $value3) {
              foreach ($level2 as $key2 => $value2) {
             // echo $level1[$key]['category_id']."<br/>";
              if($level1[$key]['son'][$key3]['category_id2']==$level2[$key2]['parent_id2']){
                   $level1[$key]['son'][$key3]['son'][]=$level2[$key2];
              }
            }
          }
      }
        
     }
     //获取到所有的活动
     $activity=getData("select c.category_id,c.sort_order,c.points,c.merchant_id,c.type,cd.name ,c.image from ".DB_PREFIX."category c left join ".DB_PREFIX."category_description cd on(c.category_id=cd.category_id) where c.status=1 and c.type=1 and c.parent_id=0 and merchant_id=".$merchant_id." order by c.sort_order");
     //var_dump($activity);exit();

     //通过获取到的banner_id来获取广告的位置
      $site=getRow("select banner_id from ".DB_PREFIX."banner where banner_id='".$_GET['banner_id']."' and status=1");

      //获取到所有的符合要求的位置
      $sites=getData("select name,banner_id from ".DB_PREFIX."banner where banner_id>11");
      //var_dump($sites);exit();
      $this->res['activity']=$activity;
      //var_dump($this->res["fenlei_name"]);exit();
      $this->res['item_id']=$item_id;
      $this->res['sites']=$sites;
      $this->res['site']=$site['banner_id'];
      $this->res['cat']=$level1;
      $this->res['subtype']=$find['subtype'];
      $this->res['image']=$category['image'];
      $this->res['link']=$category['link'];
      $this->res['name']=$category['title'];
      $this->res['height']=$category['height'];
      $this->res['type']=$find['type'];
      $this->res['banner_image_id']=$banner_image_id;
      $this->res['banner_id']=$_GET['banner_id'];
      $this->res['sort_order']=$category['sort_order'];
      $this->res['category']=$category;
      //如果能获取到商品的值说明是通过选择商品的入口进入的
      if(count($_POST)>0){
            $_SESSION['photo_u']=$this->admindefault($_POST['photo']);
            $_SESSION['name_u']=$_POST['name'];
            $_SESSION['banner_select_u']=$_POST['banner_select'];
            $_SESSION['sort_order_u']=$_POST['sort_order'];
            $_SESSION['site_u']=$_POST['site'];

      }
      if(isset($_GET['shangpin'])){
            $this->res['name']=$_SESSION['name_u'];
            $this->res['image']=$_SESSION['photo_u'];
            $this->res['banner_select']=$_SESSION['banner_select_u'];
            $this->res['sort_order']=$_SESSION['sort_order_u'];
            $this->res['subtype']=1;
            $this->res['site']=$_SESSION['site_u'];
            $shangpin=trim($_GET['shangpin'],',');
            $item_id=$shangpin;

            $sql="select name from ".DB_PREFIX."product_description where product_id='".$item_id."'";
            $shangpin_name=getRow($sql);
            $this->res['shangpin_name']=$shangpin_name['name'];
            $this->res['item_id']=$item_id;
           
      }else{
        $this->res['item_id']=0;
      }
      // var_dump($this->res);exit();
      $this->res["addAd"]=linkurl("common/addAd");
      $this->res["getGoodsList"]=linkurl("common/getGoodsList");
      $this->res["getGoodsActivityList"]=linkurl("common/getGoodsActivityList");
      $this->res["getProductName"]=linkurl("common/getProductName");
      $this->res["SaveAd"]=linkurl("common/SaveAd");
      $this->res["homepage"]=linkurl("common/homepage");  
      return $this->res;

    }
    /*
    *保存广告信息
    *
    */
    function saveAd(){
       $merchant_id=$_SESSION['merchant_id'];
      //获取到ajax传递的数据
      if(empty($_POST['name']) && empty($_POST['photo'])){
        echo "图片和标题不能为空";exit();
      }
      //var_dump($_POST);exit();
      //先判断数据库中是否存在banner_image_id
        if (isset($_POST['banner_image_id'])) {
          $id=getRow("select * from ".DB_PREFIX."banner_image where banner_image_id='".$_POST['banner_image_id']."'");
        }
        
      //var_dump($banner_image_id);exit();
       
        if($_POST['banner_select']==0){
          //外部链接
          $item_id=0;
          $subtype=0;
          if(@$_POST['link']==""){
             $_POST['link']="http://"; 
          }
          $type=1;
        }elseif($_POST['banner_select']==1){
          //分类
           $_POST['link']='http://';
           $category_id=getRow("select category_id from ".DB_PREFIX."category_description where name='".$_POST['fenlei_input']."'");
           //var_dump($category_id);exit();
           if(empty($category_id['category_id'])){
            echo "清先选择分类";exit();
           }
           $item_id=$category_id['category_id'];
           $subtype=0;
           $type=0;
        }elseif($_POST['banner_select']==2){
          //商品
           $_POST['link']='http://';
           $item_id=$_POST['inputString'];
           $subtype=1;
           $type=0;
        }elseif($_POST['banner_select']==3){
          //活动
           $_POST['link']='http://';
           $item_id=$_POST['activity'];
           if($item_id==0){
              echo "清先选择活动";exit();
           }
           $subtype=5;
           $type=0;
        }elseif($_POST['banner_select']==4){
          //团购
           $_POST['link']='http://';
           $item_id=0;
           $subtype=3;
           $type=0;
        }elseif($_POST['banner_select']==5){
          //秒杀
           $_POST['link']='http://';
           $item_id=0;
           $subtype=4;
           $type=0;
        }

        if(empty($id)){
          
          //是添加
          $data=array("image"=>$_POST['photo'],"link"=>$_POST['link'],"sort_order"=>$_POST['sort_order'],"type"=>1,'title'=>$_POST['name'],'type'=>$type,'subtype'=>$subtype,'item_id'=>$item_id,'banner_id'=>$_POST['site'],'site'=>$_POST['site'],'store_id'=>1,'merchant_id'=>$merchant_id,'height'=>$_POST['height']);
           $edt=saveData(DB_PREFIX."banner_image",$data);
           $id=getLastId();
           $_SESSION['upload_banner_image_id']=$id;
           $sql="insert into ".DB_PREFIX."banner_image_description values('".$id."',2,'".$_POST['site']."','".$_POST['name']."')";
           exeSql($sql);
           //var_dump($sql);exit();
             if($edt){
               $this->res["msg"]="新增成功";
               echo "success";exit();
              // redirect(linkurl("common/banner"));//跳转
             }else{
                $this->res["error"]="新增失败";
                 echo "fail";exit();
             }
          //echo "要修改的banner不存在";exit;
        }

        //拼装保存语句
        $data=array("banner_image_id"=>$_POST['banner_image_id'],"image"=>$_POST['photo'],"link"=>$_POST['link'],"sort_order"=>$_POST['sort_order'],"type"=>1,'title'=>$_POST['name'],'type'=>$type,'subtype'=>$subtype,'item_id'=>$item_id,'banner_id'=>$_POST['site'],'height'=>$_POST['height']);
        $sql="update ".DB_PREFIX."banner_image_description set title='".$_POST['name']."' where banner_image_id='".$_POST['banner_image_id']."'";
        exeSql($sql);
        $edt=saveData(DB_PREFIX."banner_image",$data);
             if($edt){
               $this->res["msg"]="编辑成功";
               echo "修改成功";
              // redirect(linkurl("common/banner"));//跳转
             }else{
                $this->res["error"]="编辑失败";
                 echo "修改失败";
             }
       unset($_SESSION['photo']);
       unset($_SESSION['name']); 
       unset($_SESSION['banner_select']); 
       unset($_SESSION['sort_order']);
       unset($_SESSION['product_id']); 
      exit();

    }


 /*
    *图片上传
    */
    function saveImage(){
      //var_dump($_FILES);exit();
       if(!empty($_FILES['headurl']['name'])){
          $file = $_FILES;
          // var_dump($file);exit();
          $headurl = $this->upload_img($file);
          $headurl = $headurl[0];
        }else{
          $headurl = '';
        }
        //var_dump($_POST);exit();
        $banner_id=isset($_POST['banner_id'])?$_POST['banner_id']:"";
        if(isset($_POST['banner_image_id'])){
            if($headurl!=null){
                //编辑
              $_POST['banner_image_id']=(int)$_POST['banner_image_id'];
              $sql="update ".DB_PREFIX."banner_image set image='".$headurl."' where banner_image_id='".$_POST['banner_image_id']."'";
              exeSql($sql);
            }
        }else{
          if($headurl!=null){
                //新增
              $banner_image_id=(int)$_SESSION['upload_banner_image_id'];
              $sql="update ".DB_PREFIX."banner_image set image='".$headurl."' where banner_image_id='".$banner_image_id."'";
              exeSql($sql);
            }
        }
       echo "<script> location.href='xindex.php?m=common&act=homepage&banner_id=".$banner_id."' </script>";
       die();
    }

    function saveImage1(){
      //var_dump($_FILES);exit();
       if(!empty($_FILES['headurl']['name'])){
          $file = $_FILES;
          // var_dump($file);exit();
          $headurl = $this->upload_img($file);
          $headurl = $headurl[0];
        }else{
          $headurl = '';
        }
        if(isset($_POST['banner_id'])){
            if($headurl!=null){
                //编辑
              $_POST['banner_id']=(int)$_POST['banner_id'];
              $sql="update ".DB_PREFIX."banner set banner_image='".$headurl."' where banner_id='".$_POST['banner_id']."'";
              exeSql($sql);
            }
        }else{
          if($headurl!=null){
                //新增
              $banner_id=(int)$_SESSION['banner_image_id'];
              $sql="update ".DB_PREFIX."banner set banner_image='".$headurl."' where banner_id='".$banner_id."'";
              exeSql($sql);
            }
        }
       echo "<script> location.href='xindex.php?m=common&act=getListSite' </script>";
       die();
    }


    //上传分类图片
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
    
    /*
    * 通过获取到的banner_id 删除banner
    */
    function banner_delete(){
      $id=isset($_GET['banner_image_id'])?$_GET['banner_image_id']:"";
      if($id){
        $res="DELETE FROM `hb_banner_image_description` WHERE banner_image_id=$id";
        $res1="DELETE FROM `hb_banner_image` WHERE banner_image_id=$id";
        if($res && $res1){
            echo "<script language='javascript'>";
            echo "location='xindex.php?m=common&act=banner'";
            echo "<script>";
        }
      }
    }


    /*
    * 通过获取到的商品id 得到商品名字
    */
    function getProductName(){
      $str=isset($_POST['str'])?$_POST['str']:"";
      if($str){
        $name=getRow("select name from ".DB_PREFIX."category_description where category_id=$str");
        echo $name['name'];exit();
      }
    }

    /*
    * 新    获取广告列表
    */

    function homepage(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      $page=isset($_GET['page'])?$_GET['page']:1;
      $banner_id=isset($_GET['banner_id'])?$_GET['banner_id']:"";
      if(isset($banner_id)){
        $where="banner_id='".$banner_id."' and ";
        $name=getRow("SELECT name,banner_image from hb_banner where banner_id='".$banner_id."'");
        $banner_image=@$name['banner_image'];
        $name=@$name['name'];
        //var_dump($banner_image);exit();
        $this->res['name']=$name;
        $this->res['banner_image']=$banner_image;
        $this->res['banner_id']=$banner_id;
      }else{
        $where="";
      }
      if($page<1){
        $page=1;
      }
      $size=10;
      $start=($page-1)*$size;
      //echo $start;exit(); 
      //获取到商品banner
    // echo ("SELECT banner_id,`banner_image_id`,`sort_order`,`image`,`title`,`type`,`subtype`,`item_id`,`link`,status FROM `hb_banner_image` WHERE  '".$where."' `merchant_id` = '". (int)$merchant_id ."' ORDER BY banner_id,`sort_order` DESC ");exit()
      $category=getData("SELECT banner_id,`banner_image_id`,`sort_order`,`image`,`title`,`type`,`subtype`,`item_id`,`link`,status FROM `hb_banner_image` WHERE  ".$where." `merchant_id` = '". (int)$merchant_id ."' ORDER BY banner_id,`sort_order` DESC ");
     //计算获取到的总数
      // $sum=count($category[0]);
      $count=count($category);

      $category=getData("SELECT banner_id,`banner_image_id`,`sort_order`,`image`,`title`,`type`,`subtype`,`item_id`,`link`,status FROM `hb_banner_image` WHERE  ".$where." `merchant_id` = '". (int)$merchant_id ."' ORDER BY banner_id,`sort_order` DESC limit ".$start.",".$size."");
      //var_dump($count);exit();
      //如果商品不空，则使用处理照片路径的函数
      if(!empty($category)){
        foreach ($category as $key => $value) {
          //修改图片链接
          $item_id=$value['item_id'];
          $category[$key]['image']=$this->admindefault($value['image']);
          //将获取到的banner_id和banner表进行比对
          $banner_name=getRow("select name from ".DB_PREFIX."banner where banner_id=".$value['banner_id']);
          $banner_name=$banner_name['name'];
          $category[$key]["banner_name"]= $banner_name;
          if($value["type"]==1){
            //外部链接
            $category[$key]["lianjie"]="H5链接";
            $category[$key]["xiangqing"]=$category[$key]["link"];   
          }elseif($value["type"]==0){
            $cate_name['name']="";
            //内部链接
              if($value["subtype"]==0){
                 //分类
                $cate_name=getRow("select name from ".DB_PREFIX."category_description where category_id='".$item_id."'");
                $lianjie="分类链接";
              }elseif($value["subtype"]==1){
                //产品
                $sql="select name from ".DB_PREFIX."product_description where product_id='".$item_id."'";
                $cate_name=getRow($sql);
                $lianjie="商品链接";
              }elseif($value["subtype"]==5){
                //活动
                $cate_name=getRow("select name from ".DB_PREFIX."category_description where category_id='".$item_id."'");
                $lianjie="活动链接";
              }elseif($value["subtype"]==2){
                //会员链接
               $lianjie="会员链接";
              }elseif($value["subtype"]==3){
                //团购
               $lianjie="团购链接";
              }elseif($value["subtype"]==4){
                //秒杀
               $lianjie="秒杀链接";
              }
              $category[$key]["lianjie"]=$lianjie;
              //如果存在name
              if(isset($cate_name['name'])){
                  $category[$key]["xiangqing"]=$cate_name['name'];
              }else{
                  $category[$key]["xiangqing"]="全部商品";
              }      
          }
        }
      }
      //  //获取到所有的活动
      // $activity=getData("select c.category_id,c.sort_order,c.points,c.merchant_id,c.type,cd.name ,c.image from ".DB_PREFIX."category c left join ".DB_PREFIX."category_description cd on(c.category_id=cd.category_id) where c.status=1 and c.type=1 and c.parent_id=0 and merchant_id=".$merchant_id." order by c.sort_order");
     
      // //如果商品不空，则使用处理照片路径的函数
      // if(!empty($activity)){
      //   foreach ($activity as $key => $value) {
      //     $activity[$key]["image"]=admindefault($value['image']);
          
      //   }
      // }
      // echo "<pre/>";
      // print_r($activity);
      // print_r($category);exit();
      if($count<$size)
          $this->getPages($page,1);
        else
          $this->getPages($page,ceil($count/$size));
      $this->res['category']=$category;
      $this->res["addSite"]=linkurl("common/addSite");
      $this->res["updateAdStatus"]=linkurl("common/updateAdStatus");
      $this->res["updateAdSort"]=linkurl("common/updateAdSort");
      $this->res["updateSiteStatus"]=linkurl("common/updateSiteStatus");
      $this->res["getListSite"]=linkurl("common/getListSite");
      $this->res["homepage"]=linkurl("common/homepage");
      $this->res["updateAd"]=linkurl("common/updateAdimage");
      $url=linkurl("common/addAd")."&banner_id=".@$_GET['banner_id'];
      $this->res["addAd"]=$url; 
      return $this->res;
    }


    /*
    * 新    位置管理
    */

    function getListSite(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];

      $page=isset($_GET['page'])?$_GET['page']:1;
      if($page<1){
        $page=1;
      }
      $size=10;
      $start=($page-1)*$size;
      
      //获取满足条件的位置
      //获取到所有的符合要求的位置
      $site=getData("select name,banner_id,status,banner_image from ".DB_PREFIX."banner where banner_id>11 ");
      //var_dump($sites);exit();
      $count=count($site);
      $sites=getData("select name,banner_id,status,banner_image from ".DB_PREFIX."banner where banner_id>11 limit ".$start.",".$size."");
      if( $count<$size)
      $this->getPages($page,1);
      else
      $this->getPages($page,ceil($count/$size));

      $this->res['sites']=$sites;
      $this->res['curpage']=$page;
      $this->res["addSite"]=linkurl("common/addSite");
      $this->res["getGoodsList"]=linkurl("common/getGoodsList");
      $this->res["updateSiteName"]=linkurl("common/updateSiteName");
      $this->res["updateSiteStatus"]=linkurl("common/updateSiteStatus");
      $this->res["getListSite"]=linkurl("common/getListSite");
      $this->res["homepage"]=linkurl("common/homepage"); 
      //var_dump($this->res["updateSiteName"]);exit();
      return $this->res;
    }

    /*
    * 新   商品列表
    */

    function getGoodsList(){
        //菜单
      $this->getMenu();    

      if(isset($_GET['name'])){
        $where=$_GET['name'];
        //var_dump($where);exit();
      }else{
        $where='';
      }
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
            if(isset($_GET['customer_group_id'])&&$_GET['customer_group_id'])
              $wherestr.=" and c.customer_group_id=".trim($_GET['customer_group_id']);

            if(isset($_GET['firstname'])&&$_GET['firstname'])
              $wherestr.=" and c.firstname like '".trim($_GET['firstname'])."'%";

            if(isset($_GET['lastname'])&&$_GET['lastname'])
              $wherestr.=" and c.lastname like '".trim($_GET['lastname'])."%'";

            if(isset($_GET['telephone'])&&$_GET['telephone'])
              $wherestr.=" and c.telephone='".trim($_GET['telephone'])."'";
            $sql="SELECT distinct p.`product_id`,
            p.`model`,
            p.`quantity`,
            p.`image`,
            p.`manufacturer_id`,
            p.`price`, 
            p.`points`,
            p.sales,
            p.proxyprice,
            p.sort_order,
            p.`status`,
            p.brand_id,
            p.`date_modified`,
            b.name as bname,
            pd.name
          FROM `hb_product` as p,hb_product_description as pd,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id  and p.brand_id=b.manufacturer_id limit $start,20";

        $dt = getData($sql);
        $this->res['dt']=$dt;
        $product=new product;
        $this->res['cat']=$product->getCat();

       $total=getRow("SELECT count(*) as count 
        FROM `hb_product` as p,hb_product_description as pd,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id  and p.brand_id=b.manufacturer_id ",60);
        $total=$total['count'];
        $total_page = ceil($total/20);
        $this->res['is_end_page'] = 1;
        if($page == $total_page){
          $this->res['is_end_page'] = 0;
        }

        $this->getPages($page,$total_page);
        //print_r($this->res);
        if(isset($_GET['banner_image_id'])){
            $this->res['banner_image_id']=$_GET['banner_image_id'];
        }
         if(isset($_GET['banner_id'])){
            $this->res['banner_id']=$_GET['banner_id'];
        }
       if(isset($_GET['photo'])){
            $this->res['photo']=$_GET['photo'];
        }
        if(isset($_GET['site'])){
            $this->res['site']=$_GET['site'];
        }
        if(isset($_GET['title'])){
            $this->res['title']=$_GET['title'];
        }
        if(isset($_GET['banner_select'])){
            $this->res['banner_select']=$_GET['banner_select'];
        }
         if(isset($_GET['sort_order'])){
            $this->res['sort_order']=$_GET['sort_order'];
        }
        $this->res["addAd"]=linkurl("common/addAd");
        $this->res["updateAd"]=linkurl("common/updateAd");
        $this->res["getGoodsList"]=linkurl("common/getGoodsList");
        return $this->res;
    }


    /*
    * 新   修改位置状态
    */

    function updateSiteStatus(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      $res=getRow("select status from ".DB_PREFIX."banner where banner_id='".$_POST['banner_id']."'");
      $status=$res['status'];
      //状态是0时，修改为1，为1时，修改为0
      if($status == 1){
        $data=array('banner_id'=>$_POST['banner_id'],'status'=>0);
        $data1="UPDATE `hb_banner_image` SET `status`=0 WHERE banner_id='".$_POST['banner_id']."'";
          $status=saveData(DB_PREFIX."banner",$data);
          $status1=exeSql($data1);
          if($status && $status1){
              echo "disable";exit();
          }
        }elseif($status == 0){
        $data=array('banner_id'=>$_POST['banner_id'],'status'=>1);
        $data1="UPDATE `hb_banner_image` SET `status`=1 WHERE banner_id='".$_POST['banner_id']."'";
        $status1=exeSql($data1);
        $status=saveData(DB_PREFIX."banner",$data);
        if($status && $status1){
              echo "enable";exit();
          }
      }
      return $this->res;
    }

     /*
    * 新   修改位置名字
    */

    function updateSiteName(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      if(empty($_POST['name'])){
          echo "error";exit;
      }
      $res=saveData(DB_PREFIX."banner",$_POST);
      if($res){
        echo "success";exit;
      }else{
         echo "error";exit;
      }
      return $this->res;
    }

    /*
    * 新   添加位置
    */

    function addSite(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      if(empty($_POST['name'])){
          echo "error";exit;
      }
      $res=saveData(DB_PREFIX."banner",$_POST);
      $id=getLastId();
      $_SESSION["banner_image_id"]=$id;
      if($res){
        echo "success";exit;
      }else{
         echo "error";exit;
      }
      return $this->res;
    }


    /*
    * 新   修改广告状态
    */

    function updateAdStatus(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      $res=getRow("select status from ".DB_PREFIX."banner_image where banner_image_id='".$_POST['banner_image_id']."'");
      $status=$res['status'];
      //状态是0时，修改为1，为1时，修改为0
      if($status == 1){
        $data=array('banner_image_id'=>$_POST['banner_image_id'],'status'=>0);      
          $status=saveData(DB_PREFIX."banner_image",$data);
          if($status ){
              echo "disable";exit();
          }
        }elseif($status == 0){
          $data=array('banner_image_id'=>$_POST['banner_image_id'],'status'=>1);
          $status=saveData(DB_PREFIX."banner_image",$data);
          if($status ){
                echo "enable";exit();
            }
      }
      return $this->res;
    }

     /*
    * 新   修改广告排序
    */

    function updateAdSort(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      $res=getRow("select sort_order from ".DB_PREFIX."banner_image where banner_image_id='".$_POST['banner_image_id']."'");
      if(isset($res['sort_order'])){
          $sort_order=$res['sort_order'];
      }else{
          exit();
      }
      
      //type为add时候，使sort_order值变小
      if($_POST['type']=="add"){
          $sort_order=$sort_order+1;
          $data=array('banner_image_id'=>$_POST['banner_image_id'],'sort_order'=>$sort_order);      
          $res=saveData(DB_PREFIX."banner_image",$data);
          if($res){
              echo "yes";exit();
          }
      }elseif($_POST['type']=="reduce"){
          $sort_order=$sort_order-1;
          $data=array('banner_image_id'=>$_POST['banner_image_id'],'sort_order'=>$sort_order);      
          $res=saveData(DB_PREFIX."banner_image",$data);
          if($res){
              echo "yes";exit();
          }
      }
      return $this->res;
    }

    /**
   *  @description 拉取邀请码备注信息
   *  @param       none
   *  @return      
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-14/17:20:00
   */
  function getInvitecodeRemarkByAjax(){
    $data = array('code'=>0,'msg'=>"success",'data'=>true);
    if(empty($_POST['invitecode_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：invitecode_id",'data'=>false);
    }else{
      $invitecode_id = $_POST['invitecode_id'];
      $invitecode_info = getRow("SELECT invitecode_id,remark FROM ".DB_PREFIX."invitecode 
                                 WHERE invitecode_id=".$invitecode_id);
      if($invitecode_info){
        if(empty($invitecode_info['remark']))
          $invitecode_info['remark'] = '暂无邀请码备注';
        $data = array('code'=>0,'msg'=>"success",'data'=>$invitecode_info);
      }
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description 编辑保存邀请码备注信息
   *  @param       none
   *  @return      
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-14/17:20:00
   */
  function saveInvitecodeRemarkByAjax(){
    $data = array('code'=>0,'msg'=>"success",'data'=>true);
    if(empty($_POST['invitecode_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：invitecode_id",'data'=>false);
    }else if(empty($_POST['invitecode_remark'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：invitecode_remark",'data'=>false);
    }else{
      $invitecode_id = $_POST['invitecode_id'];
      $invitecode_remark = htmlspecialchars($_POST['invitecode_remark']);
      $save_sql = "UPDATE ".DB_PREFIX."invitecode SET remark='".$invitecode_remark."' 
                   WHERE invitecode_id=".$invitecode_id;
      if(!exeSql($save_sql)){
        $data = array('code'=>2,'msg'=>"数据库错误",'data'=>false);
      }
    }
    echo json_encode($data);exit;
  }

  /**
   * @description   前端测试网页的方法
   * @parmar        none
   * @return        none
   * @author        godloveevin@yeah.net
   * @d/t           2017-03-06/16:30 
   */
  function test(){
    $this->getMenu();
    if(! $_GET['filename']){
        echo "Please enter a url with parmar:'filename';".'<br>';
        echo "eg:http://127.0.0.1/xindex.php?m=common&act=test&filename=test".'<br>';
    }
    $filename = $_GET['filename'];

    show("xview/".$filename.".html",$this->res);
    exit;
  }

  /**
   * 客服电话
   * zxx 2017-6-8
   */
  function merchant_service(){
     $this->getMenu();
     $info=getRow("select telephone from hb_merchant_service where merchant_id='".$_SESSION['merchant_id']."'");
     $this->res['url']=linkurl("common/changePhone");
     $this->res['telephone']=@$info['telephone'];
     return $this->res;
  }

  /**
   * 修改电话
   * zxx 2017-6-9
   */
  
  function changePhone(){
    $telephone=isset($_POST['telephone'])?$_POST['telephone']:"";
    if(!empty($telephone)){
      if(exeSql("update hb_merchant_service set telephone='".$telephone."' ")){
        echo "success";
      }else{
        echo "fali";
      }
    }
    exit;
  }
}