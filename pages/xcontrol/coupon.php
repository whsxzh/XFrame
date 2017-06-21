<?php
/**
 *  @description   优惠券管理(coupon)控制器
 *  @author        godloveevin@yeah.net
 *  @d/t           2017-03-30/10:30
 */

use OSS\OssClient;
use OSS\Core\OssException;
require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';

//面向对象的control 类
include "xcontrol/base.php";
include "lib/tree.php";
include "xcontrol/product.php";


class coupon extends base
{
   /**
    *   成员方法列表
    */
	
   /**
    *   构造方法
    */
	public function __construct() 
	{
      parent::__construct();
  		$this->userid = $_SESSION['userid'];
  		$this->username = $_SESSION['username'];
      $this->merchant_id = $_SESSION['merchant_id'];
   }

  public function coupon(){
    $this->__construct();
  }

  /**
   *  @description  发送优惠券给客户
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function releaseCoupon(){
    $this->getMenu();
    if(!empty($_GET['coupon_id'])){
        $coupon_id=$_GET['coupon_id'];
        $this->res['coupon_id']=$coupon_id;
    }else{
      echo "<script>alert('请选择一张优惠券');window.history.go(-1);</script>";
    }
    // 优惠券列表跳转url
    $this->res['indexUrl'] = linkurl('coupon/index');

    // 发送优惠券跳转url
    $this->res['releaseCouponUrl'] = linkurl('coupon/releaseCoupon');

    // 获取用户的跳转url
    $this->res['getMemberListUrl'] = linkurl('coupon/getMemberList');

    // 获取渠道用户的跳转url
    $this->res['getInviteListUrl'] = linkurl('coupon/getInviteList');

    // 处理用户id集，或者是邀请码id
    $this->res['ids_str'] = !empty($_GET['ids_str'])? htmlspecialchars($_GET['ids_str']): '';

    // 处理id数量
    $this->res['counts'] = !empty($_GET['counts'])? htmlspecialchars($_GET['counts']): '';

    // 处理用户范围
    $this->res['send_flag'] = !empty($_GET['send_flag']) ? htmlspecialchars($_GET['send_flag']) : '';
    if($this->res['send_flag'] == 'user_defined'){
      $this->res['user_defined'] = 1;
    }
    if($this->res['send_flag'] == 'invite'){
      $this->res['invite'] = 1;
    }

    //$this->res['coupon_id'] = !empty($_GET['coupon_id']) ? $_GET['coupon_id']: 0;

    // 获取所有没有被删除的优惠券
    $coupons = getData("select coupon_id,name from ".DB_PREFIX."coupon where is_delete=0 and status=1");    
    foreach($coupons as $key=>$value){
      if($value['coupon_id'] == $this->res['coupon_id'])
        $coupons[$key]['is_selected'] = 1;
    }
    $this->res['coupons'] = $coupons;

    if ($_POST) {
      // 获取优惠券的id
      $coupon_id = !empty($_POST['coupon_id']) ? $_POST['coupon_id']: 0;

      // 发送优惠券
      if (!empty($_POST['send_flag']) || !empty($_GET['send_flag'])) {
        if(!empty($_POST['send_flag'])){
          $sendflag = htmlspecialchars($_POST['send_flag']);
        }else{
          $sendflag = htmlspecialchars($_GET['send_flag']);
        }

        // 区分不同类型的用户
        $sql = '';
        $ids_arr = array();
        if ('all' == $sendflag) {
          // 全部用户
          $ids_arr = getData("select customer_id from ".DB_PREFIX."customer");
        }else if ('customer' == $sendflag) {
          // 会员用户
          $where = ' where merchant_id !=0 ';
          $ids_arr = getData("select customer_id from ".DB_PREFIX."customer".$where);
        }else if ('un_customer' == $sendflag) {
          // 非会员用户
          $where = ' where merchant_id=0';
          $ids_arr = getData("select customer_id from ".DB_PREFIX."customer".$where);
        }else if ('invite' == $sendflag) {
          // 渠道用户
          // 根据邀请码的id获取用户的id
          $ids_arr = getData("select customer_id from hb_customer where invitecode_id in(".$this->res['ids_str'].")");
        }else if ('user_defined' == $sendflag) {
          // 自选用户
          $ids_str = $this->res['ids_str'];
          $ids_str = explode(',',$ids_str);
          foreach($ids_str as $key=>$value){
            $ids_arr[$key]['customer_id'] = $value;
          }
        }
        // 判断发行的数量是否足够发送
        $coupon_info = getRow("select release_total,date_start,date_end,get_total from ".DB_PREFIX."coupon where coupon_id=".$coupon_id);
        $shengyu=$coupon_info['release_total']-$coupon_info['get_total'];
        if ($shengyu < count($ids_arr)) {
          echo '<script>alert("优惠券发行数量不够，发送失败");window.history.go(-1)</script>';
          exit;
        }
        // 组装待插入的优惠券和用户的数组
        $now = time();
        foreach($ids_arr as $key=>$value){
          $ids_arr[$key]['coupon_id'] = $coupon_id;
          $ids_arr[$key]['customer_id'] = $value['customer_id'];
          $ids_arr[$key]['date_added'] = $now;
          $ids_arr[$key]['status'] = 0;
          $ids_arr[$key]['date_start'] = $coupon_info['date_start'];
          $ids_arr[$key]['date_end'] = $coupon_info['date_end'];
        }

        // 处理重复发送的问题
        if ('user_defined' == $sendflag) {
          $send_nums = 0;
          foreach($ids_arr as $key=>$value){
            if(getRow("select * from ".DB_PREFIX."coupon_customer where coupon_id=".$value['coupon_id']." 
                       and customer_id=".$value['customer_id'])){
              // 已发送
              echo "该用户已经发送过了";
            }else{
              $send_nums++;
              if(saveData("hb_coupon_customer",array('coupon_id'=>$value['coupon_id'],
                                                     'customer_id'=>$value['customer_id'],
                                                     'date_added'=>$value['date_added'],
                                                     'date_start'=>$coupon_info['date_start'],
                                                     'date_end'=>$coupon_info['date_end']))){
                // 优惠券发送成功，提示相应的用户，获得优惠券的短信通知
                
                echo 'success';
              }else{
                echo 'fail';
              }
            }
          }
          // 同步更新被发送的优惠券的领取数量
          exeSql("UPDATE hb_coupon SET get_total=get_total+".$send_nums." WHERE coupon_id=".$coupon_id);
          redirect(linkurl('coupon/index'));
        }else{
          if(saveDataMuti("hb_coupon_customer",$ids_arr)){
            // 同步更新被发送的优惠券的领取数量
            exeSql("UPDATE hb_coupon SET get_total=get_total+".count($ids_arr)." WHERE coupon_id=".$coupon_id);
            redirect(linkurl('coupon/index'));
          }else{
            echo 'fail';
            redirect(linkurl('coupon/index'));
          }
        }
      }
    }

    return $this->res;
  }

    /*
    *上传优惠券图片
    *zxx 2017-4-26
     */
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

  /**
   *  @description  获取自选用户
   *  @param
   *  @return
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function getMemberList(){
    $this->getMenu();

    //判断是add还是edit
    if(isset($_GET['coupon_id'])){
      if(isset($_GET['ss'])){
        //edit
        $this->res['types']='edit';
        
      }else{
        //发券
        $this->res['types']='faquan';
      }
      //获取到coupon_id
      $coupon_id=isset($_GET['coupon_id'])?$_GET['coupon_id']:"";
      $this->res["coupon_id"]="$coupon_id";
    }else{
      //add
      $this->res['types']='add';
    }
    //echo $this->res['types'];
    // 优惠券信息form表单
    // 优惠券名称
    if(!empty($_GET['coupon_name'])){
        $_SESSION['coupon_name']=$_GET['coupon_name'];
    }
    // 优惠券开始时间
    if( empty($_SESSION['coupon_date_start']) ){
      $_SESSION['coupon_date_start'] = !empty($_GET['coupon_date_start']) ? htmlspecialchars($_GET['coupon_date_start']) : '';
    }

    // 优惠券结束时间
    if( empty($_SESSION['coupon_date_end']) ){
      $_SESSION['coupon_date_end'] = !empty($_GET['coupon_date_end']) ? htmlspecialchars($_GET['coupon_date_end']) : '';
    }

    // 优惠券金额
    if(empty($_SESSION['coupon_discount']) ){
      $_SESSION['coupon_discount'] = !empty($_GET['coupon_discount']) ? htmlspecialchars($_GET['coupon_discount']) : '';
    }

    // 优惠券金额描述
    if( empty($_SESSION['coupon_discount_desn']) ){
      $_SESSION['coupon_discount_desn'] = !empty($_GET['coupon_discount_desn']) ? htmlspecialchars($_GET['coupon_discount_desn']) : '';
    }

    // 优惠券最少限额才可以使用，例如：500
    if( empty($_SESSION['coupon_min_limit_amount']) ){
      $_SESSION['coupon_min_limit_amount'] = !empty($_GET['coupon_min_limit_amount']) ? htmlspecialchars($_GET['coupon_min_limit_amount']) : '';
    }

    // 优惠券发行数量
    if( empty($_SESSION['coupon_release_total']) ){
      $_SESSION['coupon_release_total'] = !empty($_GET['coupon_release_total']) ? htmlspecialchars($_GET['coupon_release_total']) : '';
    }

    // 优惠券类型
    if( empty($_SESSION['type']) ){
      $_SESSION['type'] = !empty($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
    }

    // 商品类型对应的id
    if( empty($_SESSION['ids_str']) ){
      $_SESSION['ids_str'] = !empty($_GET['ids_str']) ? htmlspecialchars($_GET['ids_str']) : '';
    }

    // 商品数量
    if( empty($_SESSION['counts']) ){
      $_SESSION['counts'] = !empty($_GET['counts']) ? htmlspecialchars($_GET['counts']) : '';
    }



    // 发送优惠券跳转url
    $this->res['releaseCouponUrl'] = linkurl('coupon/releaseCoupon');
    $this->res['getMemberListUrl'] = linkurl('coupon/getMemberList');

    // 刷选用户范围的条件
    $sendflag = !empty($_GET['sendflag']) ? htmlspecialchars($_GET['sendflag']): 0;

    // 处理搜索条件
    $where = ' where 1=1 ';
    if (!empty($_GET['send_flag'])) {
      $sendflag = htmlspecialchars($_GET['send_flag']);
      if ('user_defined' == $sendflag) {
        // 自选用户
        if( !empty($_GET['user_type']) ){
          if(1 == $_GET['user_type']){
            // 会员用户
            $where .= " AND merchant_id != 0";
          }else if(2 == $_GET['user_type']){
            // 非会员用户
            $where .= " AND merchant_id = 0";
          }

          $this->res['user_type'] = $_GET['user_type'];
        }
      }
    }

    // 根据用户电话以及用户名称搜索用户信息
    if (! empty($_GET['customer_telephone'])) {
      $where .= " AND telephone=".$_GET['customer_telephone'];
      $this->res['customer_telephone'] = $_GET['customer_telephone'];
    }
    if (! empty($_GET['customer_name'])) {
      $where .= " AND firstname like '%".$_GET['customer_name']."%'";
      $this->res['customer_name'] = $_GET['customer_name'];
    }

    $this->res['coupon_id'] = !empty($_GET['coupon_id']) ? $_GET['coupon_id'] : 0;

    // 处理分页
    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $limit = 15;
    $all_arr = getRow("select count(customer_id) as all_nums from ".DB_PREFIX."customer".$where);
    $count = $all_arr['all_nums'];
    if($count < $limit){
      $this->getPages($page,1);
    }else{
      $this->getPages($page,ceil($count/$limit));
    }

    // 获取用户信息
    $sql = "select customer_id,firstname,lastname,parent_id,invitecode_id,merchant_id,telephone
            from ".DB_PREFIX."customer ".$where." limit ".($page-1)*$limit.",".$limit;
    // echo $sql;exit;
    $customers = getData($sql);
    //跳转链接
    $this->res['couponAddUrl'] = linkurl('coupon/add');
    $this->res['couponEditUrl'] = linkurl('coupon/edit');

    foreach($customers as $key=>$value){
      // 会员用户
      if ( !empty($value['invitecode_id']) || !empty($value['merchant_id']) ) {
        $customers[$key]['customer_type'] ='会员用户';
      }
      // 普通用户
      if ( empty($value['invitecode_id']) && (empty($value['merchant_id'])) ) {
        $customers[$key]['customer_type'] ='普通用户';
      }
    }
    $this->res['customers'] = $customers;
    return $this->res;
  }

  /**
   *  @description 获取渠道用户
   *  @param
   *  @return
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function getInviteList(){
    $this->getMenu();

    //判断是add还是edit
    if(isset($_GET['coupon_id'])){
      if(isset($_GET['coupon_name'])){
        //edit
        $this->res['types']='edit';
      }else{
        //发券
        $this->res['types']='faquan';
      }
      //获取到coupon_id
      $coupon_id=isset($_GET['coupon_id'])?$_GET['coupon_id']:"";
      $this->res["coupon_id"]="$coupon_id";
    }else{
      //add
      $this->res['types']='add';
    }
    // 优惠券信息form表单
    // 优惠券名称
    if( empty($_SESSION['coupon_name']) || ($_SESSION['coupon_name'] != $_GET['coupon_name']) ){
      $_SESSION['coupon_name'] = !empty($_GET['coupon_name']) ? htmlspecialchars($_GET['coupon_name']) : '';
    }

    // 优惠券开始时间
    if( empty($_SESSION['coupon_date_start']) ){
      $_SESSION['coupon_date_start'] = !empty($_GET['coupon_date_start']) ? htmlspecialchars($_GET['coupon_date_start']) : '';
    }

    // 优惠券结束时间
    if( empty($_SESSION['coupon_date_end']) ){
      $_SESSION['coupon_date_end'] = !empty($_GET['coupon_date_end']) ? htmlspecialchars($_GET['coupon_date_end']) : '';
    }

    // 优惠券金额
    if(empty($_SESSION['coupon_discount']) ){
      $_SESSION['coupon_discount'] = !empty($_GET['coupon_discount']) ? htmlspecialchars($_GET['coupon_discount']) : '';
    }

    // 优惠券金额描述
    if( empty($_SESSION['coupon_discount_desn']) ){
      $_SESSION['coupon_discount_desn'] = !empty($_GET['coupon_discount_desn']) ? htmlspecialchars($_GET['coupon_discount_desn']) : '';
    }

    // 优惠券最少限额才可以使用，例如：500
    if( empty($_SESSION['coupon_min_limit_amount']) ){
      $_SESSION['coupon_min_limit_amount'] = !empty($_GET['coupon_min_limit_amount']) ? htmlspecialchars($_GET['coupon_min_limit_amount']) : '';
    }

    // 优惠券发行数量
    if( empty($_SESSION['coupon_release_total']) ){
      $_SESSION['coupon_release_total'] = !empty($_GET['coupon_release_total']) ? htmlspecialchars($_GET['coupon_release_total']) : '';
    }

    // 优惠券类型
    if( empty($_SESSION['type']) ){
      $_SESSION['type'] = !empty($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
    }

    // 商品类型对应的id
    if( empty($_SESSION['ids_str']) ){
      $_SESSION['ids_str'] = !empty($_GET['ids_str']) ? htmlspecialchars($_GET['ids_str']) : '';
    }

    // 商品数量
    if( empty($_SESSION['counts']) ){
      $_SESSION['counts'] = !empty($_GET['counts']) ? htmlspecialchars($_GET['counts']) : '';
    }

    $data=getData("select c.lastname,c.firstname,i.* from hb_invitecode as i,hb_customer as c
                   where i.customer_id=c.customer_id  order by invitecode_id desc");
    $this->res['data']=$data;

    $this->res['coupon_id'] = !empty($_GET['coupon_id']) ? $_GET['coupon_id'] : 0;

    // 发送优惠券跳转url
    $this->res['releaseCouponUrl'] = linkurl('coupon/releaseCoupon');
    $this->res['couponAddUrl'] = linkurl('coupon/add');
    $this->res['couponEditUrl'] = linkurl('coupon/edit');
    return $this->res;
  }

  /**
   *  @description  优惠券列表
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function index(){
    $this->getMenu();

    // 优惠券列表跳转url
    $this->res['indexUrl'] = linkurl('coupon/index');

    // 新增优惠券
    $this->res['addUrl'] = linkurl('coupon/add');

    // 编辑优惠券
    $this->res['editUrl'] = linkurl('coupon/edit');

    // 删除优惠券
    $this->res['delUrl'] = linkurl('coupon/del');

    // 启用优惠券
    $this->res['unDelUrl'] = linkurl('coupon/undel');

    // 作废优惠券
    $this->res['cancelUrl'] = linkurl('coupon/cancel');

    // 发送优惠券跳转url
    $this->res['releaseCouponUrl'] = linkurl('coupon/releaseCoupon');

    // 处理优惠券的搜索条件
    if (!empty($_GET['type'])) {
      $where = ' where status!=3 and type='.(int)$_GET['type'];
      $this->res['type'] = $_GET['type'];
    }else{
      $where = ' where status!=3';
    }

    if(!empty($_GET['search_name'])){
      $where.=" and name like '%".$_GET['search_name']."%'";
      $this->res['search_name']=$_GET['search_name'];
    }
    if(!empty($_GET['num'])){
      if($_GET['select_bijiao']==1){
         $where.=" and discount>".$_GET['num'];
      }elseif($_GET['select_bijiao']==2){
         $where.=" and discount<".$_GET['num'];
      }elseif($_GET['select_bijiao']==3){
         $where.=" and discount=".$_GET['num'];
      }
      $this->res['num']=$_GET['num'];
      $this->res['select_bijiao']=$_GET['select_bijiao'];
    }

    // 处理分页
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $limit = 15;
    $all_arr = getRow("select count(coupon_id) as all_nums from ".DB_PREFIX."coupon".$where);
    //echo "select count(coupon_id) as all_nums from ".DB_PREFIX."coupon".$where;exit;
    $count = $all_arr['all_nums'];
    if($count < $limit){
      $this->getPages($page,1);
    }else{
      $this->getPages($page,ceil($count/$limit));
    }

    $coupon_sql = "select * from ".DB_PREFIX."coupon ".$where." order by coupon_id desc limit ".($page-1)*$limit.",".$limit;
    //echo $coupon_sql;exit;
    $now = time();
    $coupons = getData($coupon_sql);
    foreach($coupons as $key=>$value){
      if ($value['date_end'] < $now) {
        $coupons[$key]['is_common_done'] = 1;
      }else{
        $coupons[$key]['is_common_done'] = 0;
      }
      $coupons[$key]['date_start'] = date("Y-m-d H:i:s", $value['date_start']);
      $coupons[$key]['date_end'] = date("Y-m-d H:i:s", $value['date_end']);
      $coupons[$key]['date_added'] = date("Y-m-d H:i:s", $value['date_added']);

      if ($value['code']) {
        $coupons[$key]['has_code'] = 1;
      }else{
        $coupons[$key]['has_code'] = 0;
      }
    }

    $this->res['coupons'] = $coupons;

    return $this->res;
  }

  /**
   *  @description  发行优惠券
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function add(){

    $this->getMenu();
    // 优惠券信息form表单
    // 优惠券名称
    if( empty($_SESSION['coupon_name']) ){
      $this->res['name'] = !empty($_GET['coupon_name']) ? htmlspecialchars($_GET['coupon_name']) : '';
    }else{
      $this->res['name'] = $_SESSION['coupon_name'];
    }
    // 优惠券开始时间
    if( empty($_SESSION['coupon_date_start']) ){
      $this->res['date_start'] = !empty($_GET['coupon_date_start']) ? htmlspecialchars($_GET['coupon_date_start']) : '';
    }else{
      $this->res['date_start'] = $_SESSION['coupon_date_start'];
    }

    // 优惠券结束时间
    if( empty($_SESSION['coupon_date_end']) ){
      $this->res['date_end'] = !empty($_GET['coupon_date_end']) ? htmlspecialchars($_GET['coupon_date_end']) : '';
    }else{
      $this->res['date_end'] = $_SESSION['coupon_date_end'];
    }

    // 优惠券金额
    if(empty($_SESSION['coupon_discount']) ){
      $this->res['discount'] = !empty($_GET['coupon_discount']) ? htmlspecialchars($_GET['coupon_discount']) : '';
    }else{
      $this->res['discount'] = $_SESSION['coupon_discount'];
    }

    // 优惠券金额描述
    if( empty($_SESSION['coupon_discount_desn']) ){
      $this->res['discount_desn'] = !empty($_GET['coupon_discount_desn']) ? htmlspecialchars($_GET['coupon_discount_desn']) : '';
    }else{
      $this->res['discount_desn'] = $_SESSION['coupon_discount_desn'];
    }

    // 优惠券最少限额才可以使用，例如：500
    if( empty($_SESSION['coupon_min_limit_amount']) ){
      $this->res['min_limit_amount'] = !empty($_GET['coupon_min_limit_amount']) ? htmlspecialchars($_GET['coupon_min_limit_amount']) : '';
    }else{
      $this->res['min_limit_amount'] = $_SESSION['coupon_min_limit_amount'];
    }

    // 优惠券发行数量
    if( empty($_SESSION['coupon_release_total']) ){
      $this->res['release_total'] = !empty($_GET['coupon_release_total']) ? htmlspecialchars($_GET['coupon_release_total']) : '';
    }else{
      $this->res['release_total'] = $_SESSION['coupon_release_total'];
    }

     // 优惠券类型
    if( empty($_SESSION['type']) ){
      // 优惠券类别type 1:全部商品；2：部分商品；3：部分分类；4：部分品牌
       $this->res['type'] = !empty($_GET['type']) ? $_GET['type'] : 0;
    }else{
       $this->res['type'] = $_SESSION['type'];
    }

    // 商品类型对应的id
    if( empty($_SESSION['ids_str']) ){
       $this->res['ids_str'] = !empty($_GET['ids_str']) ? htmlspecialchars($_GET['ids_str']) : 0;
    }else{
      // 选择的商品，分类，品牌，id字符串
      $this->res['ids_str'] = $_SESSION['ids_str'];
    }

    // 商品数量
    if( empty($_SESSION['counts']) ){
         $this->res['select_counts'] = !empty($_GET['counts']) ? htmlspecialchars($_GET['counts']) : 0;
    }else{
        // 选择的数量
        $this->res['select_counts'] = $_SESSION['counts'];
    }

      // 优惠券类型
    if( empty($_SESSION['send_flag']) ){
      // 优惠券类别type 1:全部商品；2：部分商品；3：部分分类；4：部分品牌
       $this->res['send_flag'] = !empty($_GET['send_flag']) ? $_GET['send_flag'] : 0;
    }else{
       $this->res['send_flag'] = $_SESSION['send_flag'];
    }

    // 商品类型对应的id
    if( empty($_SESSION['customer_ids_str']) ){
       $this->res['customer_ids_str'] = !empty($_GET['customer_ids_str']) ? htmlspecialchars($_GET['customer_ids_str']) : 0;
    }else{
      // 选择的商品，分类，品牌，id字符串
      $this->res['customer_ids_str'] = $_SESSION['customer_ids_str'];
    }

    // 商品数量
    if( empty($_SESSION['customer_counts']) ){
         $this->res['customer_counts'] = !empty($_GET['customer_counts']) ? htmlspecialchars($_GET['customer_counts']) : 0;
    }else{
        // 选择的数量
        $this->res['customer_counts'] = $_SESSION['customer_counts'];
    }

    // 获取用户的跳转url
    $this->res['getMemberListUrl'] = linkurl('coupon/getMemberList');

    // 获取渠道用户的跳转url
    $this->res['getInviteListUrl'] = linkurl('coupon/getInviteList');

  


    // 新增优惠券，优惠券分类，优惠券商品，优惠券品牌等
    $insert_data = array();
    if(!empty($_POST)){
      
       if(!empty($_FILES['headurl']['name'])){
          $file = $_FILES;
          // var_dump($file);exit();
          $headurl = $this->upload_img($file);
          $headurl = $headurl[0];
        }else{
          $headurl = '';
        }

      // 优惠券名称
      $insert_data['name'] = !empty($_POST['name']) ? htmlspecialchars($_POST['name']) : '';

      // 优惠券类别（1全部商品，2部分商品，3部分商品类别，4部分品牌）
      if ('all_products' == $_POST['type']) {
        $insert_data['type'] = 1;
      }else if ('some_products' == $_POST['type']) {
        $insert_data['type'] = 2;
      }else if ('product_category' == $_POST['type']) {
        $insert_data['type'] = 3;
      }else if ('product_manufacturer' == $_POST['type']) {
        $insert_data['type'] = 4;
      }
      
      //优惠券图片
      $insert_data['image'] = $headurl;
      // 优惠券开始时间
      $insert_data['date_start'] = !empty($_POST['date_start']) ? strtotime(htmlspecialchars($_POST['date_start'])) : '';

      // 优惠券结束时间
      $insert_data['date_end'] = !empty($_POST['date_end']) ? strtotime(htmlspecialchars($_POST['date_end'])) : '';

      // 折扣金额
      $insert_data['discount'] = !empty($_POST['discount']) ? htmlspecialchars($_POST['discount']) : '';

      // 折扣描述
      $insert_data['discount_desn'] = !empty($_POST['discount_desn']) ? htmlspecialchars($_POST['discount_desn']) : '';

      // 最少限额才可以使用
      $insert_data['min_limit_amount'] = !empty($_POST['min_limit_amount']) ? htmlspecialchars($_POST['min_limit_amount']) : '';

      // 发行数量
      $insert_data['release_total'] = !empty($_POST['release_total']) ? htmlspecialchars($_POST['release_total']) : '';
      $insert_data['get_total'] = 0;
      $insert_data['use_total'] = 0;

      // 优惠券新增时间
      $insert_data['date_added'] = time();

      if ($insert_data['date_end'] < $insert_data['date_added']) {
        $insert_data['status'] = 3;
      }

      // 用户范围
      $insert_data['send_type'] = !empty($_POST['send_flag']) ? htmlspecialchars($_POST['send_flag']) : 0;
      $insert_data['send_flag'] = !empty($_GET['customer_ids_str']) ? htmlspecialchars($_GET['customer_ids_str']) : '';

      //短信内容
      $insert_data['msg'] = !empty($_POST['msg']) ? htmlspecialchars($_POST['msg']) : '';

      //注意事项
      $insert_data['content'] = !empty($_POST['content']) ? htmlspecialchars($_POST['content']) : '';
      // 处理防止重复的优惠券
      if (false) {
        echo "优惠券名称不可重复";
        redirect(linkurl('coupon/add'));exit;
      }else{
        if(saveData('hb_coupon',$insert_data)){
          // 获取优惠券最新的id
          $coupon_id = getLastId();
          // 更新领取优惠券的url地址
          
          $url = 'http://'.$_SERVER['HTTP_HOST'].'/web/active/coupon/index.html?coupon_id='.$coupon_id;          
          exeSql("UPDATE hb_coupon SET url='".$url."' WHERE coupon_id=".$coupon_id);
          //处理优惠券的相关联的表
          if ('some_products' == $_POST['type']) {
            // 优惠券商品关联表
            $product_ids = !empty($_POST['ids_str']) ? htmlspecialchars($_POST['ids_str']) : '';
            if(! $this->saveCouponRelevance($coupon_id, $insert_data['type'], $product_ids)) {
              redirect(linkurl('coupon/add'));exit;
            }
          }else if ('product_category' == $_POST['type']) {
            // 优惠券商品分类关联表
            $category_ids = !empty($_POST['ids_str']) ? htmlspecialchars($_POST['ids_str']) : '';
            if(! $this->saveCouponRelevance($coupon_id, $insert_data['type'], $category_ids)) {
              redirect(linkurl('coupon/add'));exit;
            }
          }else if ('product_manufacturer' == $_POST['type']) {
            // 优惠券品牌表
            $manufacturer_ids = !empty($_POST['ids_str']) ? htmlspecialchars($_POST['ids_str']) : '';
            if(! $this->saveCouponRelevance($coupon_id, $insert_data['type'], $manufacturer_ids)) {
              redirect(linkurl('coupon/add'));exit;
            }
          }else{
            // 全部商品
            // do nothing
          }

          // 成功新增了优惠券之后，销毁回话里的优惠券字段
          unset($_SESSION['coupon_name']);
          unset($_SESSION['coupon_date_start']);
          unset($_SESSION['coupon_date_end']);
          unset($_SESSION['coupon_discount']);
          unset($_SESSION['coupon_discount_desn']);
          unset($_SESSION['coupon_min_limit_amount']);
          unset($_SESSION['coupon_release_total']);
          unset($_SESSION['type']);
          unset($_SESSION['ids_str']);
          unset($_SESSION['counts']);
          unset($_SESSION['customer_type']);
          redirect(linkurl('coupon/index'));exit;
        }else{
          echo '<script type="text/javascript">alert("优惠券名称不可重复");sleep(100000);</script>';
          redirect(linkurl('coupon/add'));exit;
        }
      }
    }

    // 新增优惠券url
    $this->res['couponAddUrl'] = linkurl('coupon/add');

    // 获取分类列表
    $this->res['getCategoryListUrl'] = linkurl('coupon/getCategoryList');

    // 获取商品列表
    $this->res['getProductsListUrl'] = linkurl('coupon/getProductsList');

    // 优惠券列表跳转url
    $this->res['indexUrl'] = linkurl('coupon/index');
    
    // 获取品牌列表
    $this->res['getManufacturerListUrl'] = linkurl('coupon/getManufacturerList');

    return $this->res;
  }


  /**
   *  @description  存储优惠券的关联表数据
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
   function saveCouponRelevance($coupon_id,$type,$ids){
    $return = true;
    $data = array();
    if (2 == $type) {
      // 商品
      // 先删除掉之前记录的商品信息
      $sql=exeSql("delete from hb_coupon_product where coupon_id=".$coupon_id);
      $product_id_array = explode(',',$ids);
      foreach ($product_id_array as $key => $value) {
        $data[$key]['coupon_id'] = $coupon_id;
        $data[$key]['product_id'] = $value;
        $sql=getRow("select * from hb_coupon_product where coupon_id=". $coupon_id." and product_id=".$value);
        if(!empty($sql)){
          unset($data[$key]);
        }
      }
     shuffle($data);
       if(!empty($data)){
       if (! saveDataMuti('hb_coupon_product',$data)) {
        $return = false;
      }
     }
    }else if (3 == $type) {
      // 分类
    // 先删除掉之前记录的分类信息
      $sql=exeSql("delete from hb_coupon_category where coupon_id=".$coupon_id);
      $category_id_array = explode(',',$ids);
      foreach ($category_id_array as $key => $value) {
        $data[$key]['coupon_id'] = $coupon_id;
        $data[$key]['category_id'] = $value;
        $sql=getRow("select * from hb_coupon_category where coupon_id=". $coupon_id." and category_id=".$value);
        if(!empty($sql)){
          unset($data[$key]);
        }
      }
     shuffle($data);
      if(!empty($data)){
       if (! saveDataMuti('hb_coupon_category',$data)) {
        $return = false;
      }
     }
    }else if (4 == $type) {
      // 品牌
      // 先删除掉之前记录的分类信息
      $sql=exeSql("delete from hb_coupon_manufacturer where coupon_id=".$coupon_id);
      $manufacturer_id_array = explode(',',$ids);
      foreach ($manufacturer_id_array as $key => $value) {
        $data[$key]['coupon_id'] = $coupon_id;
        $data[$key]['manufacturer_id'] = $value;
        $sql=getRow("select * from hb_coupon_manufacturer where coupon_id=". $coupon_id." and manufacturer_id=".$value);
        //var_dump($sql);exit;
        if(!empty($sql)){
          unset($data[$key]);
        }
      }
     shuffle($data);
     if(!empty($data)){
       if (! saveDataMuti('hb_coupon_manufacturer',$data)) {
        $return = false;
      }
     }
    }
   //var_dump($data);exit;
    return $return;
  }

  /**
   *  @description  编辑优惠券
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function edit(){

    $this->getMenu();
    if ($_GET['coupon_id']) {
      $coupon_info = getRow('select * from hb_coupon where coupon_id='.$_GET['coupon_id']);
    }
    //优惠券名称
    if(!empty($_SESSION['coupon_name']) ){
      $coupon_info['name'] = $_SESSION['coupon_name'];
    }
    // 优惠券开始时间
    if(!empty($_SESSION['coupon_date_start']) ){
       $coupon_info['date_start'] = $_SESSION['coupon_date_start'];
    }else{
      $coupon_info['date_start'] = date("Y-m-d H:i:s",$coupon_info['date_start']);
    }

    // 优惠券结束时间
    if(!empty($_SESSION['coupon_date_end']) ){
      $coupon_info['date_end'] = $_SESSION['coupon_date_end'];
    }else{
      $coupon_info['date_end'] = date("Y-m-d H:i:s",$coupon_info['date_end']);
    }

    // 优惠券金额
    if(!empty($_SESSION['coupon_discount']) ){
      $coupon_info['discount'] = $_SESSION['coupon_discount'];
    }

    // 优惠券金额描述
    if(!empty($_SESSION['coupon_discount_desn']) ){
      $coupon_info['discount_desn'] =$_SESSION['coupon_discount_desn'];
    }

    // 优惠券最少限额才可以使用，例如：500
    if(!empty($_SESSION['coupon_min_limit_amount']) ){
      $coupon_info['min_limit_amount'] = $_SESSION['coupon_min_limit_amount'];
    }

    // 优惠券发行数量
    if(!empty($_SESSION['coupon_release_total']) ){
     $coupon_info['release_total'] = $_SESSION['coupon_release_total'];
    }
 // 优惠券类型
    if( empty($_SESSION['type']) ){
      // 优惠券类别type 1:全部商品；2：部分商品；3：部分分类；4：部分品牌
       $this->res['type'] = !empty($_GET['type']) ? $_GET['type'] : 0;
    }else{
       $this->res['type'] = $_SESSION['type'];
    }
    //优惠券定制短信
    if(empty($coupon_info['msg'])){
      $coupon_info['msg']="定制短信将以 【嗨企货仓】+ 内容 + http://t.cn/RXGwfzP 回TD退订";
    }
    // 商品类型对应的id
    if( empty($_SESSION['ids_str']) ){
       $this->res['ids_str'] = !empty($_GET['ids_str']) ? htmlspecialchars($_GET['ids_str']) : 0;
    }else{
      // 选择的商品，分类，品牌，id字符串
      $this->res['ids_str'] = $_SESSION['ids_str'];
    }
    // 商品数量
    if( empty($_SESSION['counts']) ){
         $this->res['select_counts'] = !empty($_GET['counts']) ? htmlspecialchars($_GET['counts']) : 0;
    }else{
        // 选择的数量
        $this->res['select_counts'] = $_SESSION['counts'];
    }
      // 优惠券类型
    if( empty($_GET['send_flag']) ){
      // 优惠券类别type 1:全部商品；2：部分商品；3：部分分类；4：部分品牌
       $this->res['send_flag'] = $coupon_info['send_type'];
    }else{
       $this->res['send_flag'] = $_GET['send_flag'];
    }

    // 商品类型对应的id
    if( empty($_GET['customer_ids_str']) ){
       $customer_ids_str = $coupon_info['send_flag'];
    }else{
      // 选择的商品，分类，品牌，id字符串
      $customer_ids_str = $_GET['customer_ids_str'];
    }

    // 商品数量
    if( empty($_GET['customer_counts']) ){
         $pcount=$coupon_info['send_flag'];
         $pcount=explode(',', $pcount);
         $pcount=count($pcount);
         $this->res['customer_counts'] =$pcount;
    }else{
        // 选择的数量
        $this->res['customer_counts'] = $_GET['customer_counts'];
    }

    // 获取用户的跳转url
    $this->res['getMemberListUrl'] = linkurl('coupon/getMemberList');

    // 获取渠道用户的跳转url
    $this->res['getInviteListUrl'] = linkurl('coupon/getInviteList');

    // 修改优惠券url
    $this->res['couponEditUrl'] = linkurl('coupon/edit');

    // 获取分类列表
    $this->res['getCategoryListUrl'] = linkurl('coupon/getCategoryList');

    // 获取商品列表
    $this->res['getProductsListUrl'] = linkurl('coupon/getProductsList');

    // 优惠券列表跳转url
    $this->res['indexUrl'] = linkurl('coupon/index');
    
    // 获取品牌列表
    $this->res['getManufacturerListUrl'] = linkurl('coupon/getManufacturerList');

    // 生成兑换码
    $this->res['generationCouponCodeUrl'] = linkurl('coupon/randomGenerationCouponCode');
    // 优惠券类别type 1:全部商品；2：部分商品；3：部分分类；4：部分品牌
    if(empty($this->res['type'])){
      $this->res['type'] = $coupon_info['type'];
    }
    $type=getRow("select type from hb_coupon where coupon_id=".$_GET['coupon_id']);
    $type=$type['type'];
    //echo $type['type'];exit;
    // 选择的数量
    if(!empty($this->res['select_counts'])){
     
    }else{
       if($type == 4){
          $count=getRow("select count(coupon_id) as count from hb_coupon_manufacturer where coupon_id=".$_GET['coupon_id']);
       }elseif($type == 2){
         $count=getRow("select count(coupon_id) as count from hb_coupon_product where coupon_id=".$_GET['coupon_id']);
       }elseif($type == 3) {
          $count=getRow("select count(coupon_id) as count from hb_coupon_category where coupon_id=".$_GET['coupon_id']);
       }else{
        $count['count']=0;
       }
      $this->res['select_counts']=$count['count'];
    }

    // 编辑优惠券，优惠券分类，优惠券商品，优惠券品牌等
     if(!empty($_POST)){

      //判断是否有图片传入
        if(!empty($_FILES['headurl']['name'])){
          $file = $_FILES;
          // var_dump($file);exit();
          $headurl = $this->upload_img($file);
          $headurl = $headurl[0];
        }else{
          $headurl = $coupon_info['image'];
        }
       if($_POST['type']=='all_products'){
          $type=1;
          $count=getRow("select count(*) as count from hb_coupon_product where coupon_id=".$_GET['coupon_id']);
         // echo $count['count'];exit;
       }elseif($_POST['type']=='some_products'){
          $type=2;
       }elseif($_POST['type']=='product_category'){
          $type=3;
       }elseif($_POST['type']=='product_manufacturer'){
          $type=4;
       }

      //处理优惠券的相关联的表
      if ('some_products' == $_POST['type']) {
        // 优惠券商品关联表
        $product_ids = !empty($_POST['ids_str']) ? htmlspecialchars($_POST['ids_str']) : '';

        if(!empty($product_ids)){
          $this->saveCouponRelevance($_GET['coupon_id'], $type, $product_ids);
        }

      }else if ('product_category' == $_POST['type']) {
        // 优惠券商品分类关联表
        $category_ids = !empty($_POST['ids_str']) ? htmlspecialchars($_POST['ids_str']) : '';
        if(!empty($category_ids)){
          $this->saveCouponRelevance($_GET['coupon_id'],$type, $category_ids);
        }
      }else if ('product_manufacturer' == $_POST['type']) {
        // 优惠券品牌表
        $manufacturer_ids = !empty($_POST['ids_str']) ? htmlspecialchars($_POST['ids_str']) : '';
         if(!empty($manufacturer_ids)){
             $this->saveCouponRelevance($_GET['coupon_id'], $type, $manufacturer_ids);
         }
      }else{

        // 全部商品
        // 更改状态
        exeSql("update hb_coupon set type=1 where coupon_id=".$_GET['coupon_id']);
      }
     // echo $_POST['code'];exit;
      $update_sql = "UPDATE ".DB_PREFIX."coupon SET name='".htmlspecialchars($_POST['name'])."', 
                     type=".$type.",
                     image='".$headurl."',
                     date_start='".strtotime($_POST['date_start'])."',
                     url='".htmlspecialchars($_POST['url'])."',
                     date_end='".strtotime($_POST['date_end'])."',
                     discount_desn='".htmlspecialchars($_POST['discount_desn'])."',
                     code='".htmlspecialchars($_POST['code'])."',
                     send_type='".$_POST['send_flag']."',
                     send_flag='".$customer_ids_str."',
                     discount='".htmlspecialchars((float)$_POST['discount'])."',
                     msg='".htmlspecialchars($_POST['msg'])."',
                     content='".htmlspecialchars($_POST['content'])."',
                     min_limit_amount='".htmlspecialchars((float)$_POST['min_limit_amount'])."',
                     release_total='".htmlspecialchars($_POST['release_total'])."'";
      $update_sql .= " WHERE coupon_id=".$coupon_info['coupon_id'];
       // 成功新增了优惠券之后，销毁回话里的优惠券字段
      unset($_SESSION['coupon_name']);
      unset($_SESSION['coupon_date_start']);
      unset($_SESSION['coupon_date_end']);
      unset($_SESSION['coupon_discount']);
      unset($_SESSION['coupon_discount_desn']);
      unset($_SESSION['coupon_min_limit_amount']);
      unset($_SESSION['coupon_release_total']);
      unset($_SESSION['type']);
      unset($_SESSION['ids_str']);
      unset($_SESSION['counts']);
      //echo $update_sql;exit;
      if(exeSql($update_sql))
        redirect(linkurl('coupon/index'));exit;
    }

    if ($coupon_info['code']) {
      $coupon_info['has_code'] = 1;
      $coupon_info['nums'] = count(explode(',',$coupon_info['code']));
    }else{
      $coupon_info['has_code'] = 0;
      $coupon_info['nums'] = 0;
    }

    $this->res['coupon_info'] = $coupon_info;
    $this->res['coupon_id'] =@$_GET['coupon_id'];
    return $this->res;
  }

  /**
   *  @description  作废优惠券，那么所有领取过次优惠券的用户不可用了
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function cancel(){
    exeSql("update hb_coupon set status=3 where coupon_id=".$_GET['coupon_id']);
    // 让有这张优惠券并且没有使用的用户失效
    exeSql("update hb_coupon_customer set status=2 where coupon_id=".$_GET['coupon_id']." and status=0");
    redirect(linkurl('coupon/index',array('page'=>$_GET['page'])));exit;
  }

  /**
   *  @description  删除优惠券
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function del(){
    exeSql("update hb_coupon set is_delete=1 where coupon_id=".$_GET['coupon_id']);
    redirect(linkurl('coupon/index',array('page'=>$_GET['page'])));exit;
  }

  /**
   *  @description  启用优惠券
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function undel(){
    exeSql("update hb_coupon set status=1 where coupon_id=".$_GET['coupon_id']);
    // 让有这张优惠券并且没有使用的用户生效
    exeSql("update hb_coupon_customer set status=0 where coupon_id=".$_GET['coupon_id']." and status=2 and date_end>".time());
    redirect(linkurl('coupon/index',array('page'=>$_GET['page'])));exit;
  }

  /**
   *  @description  获取部分商品
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function getProductsList(){

    //判断是add还是edit
    if(isset($_GET['types'])){
      //edit
      $this->res['types']='edit';
      //获取到coupon_id
      $coupon_id=isset($_GET['coupon_id'])?$_GET['coupon_id']:"";
      $this->res["coupon_id"]="$coupon_id";
    }else{
      //add
      $this->res['types']='add';
    }
    // 优惠券信息form表单
    // 优惠券名称
    if( empty($_SESSION['coupon_name']) || ($_SESSION['coupon_name'] != $_GET['coupon_name']) ){
      $_SESSION['coupon_name'] = !empty($_GET['coupon_name']) ? htmlspecialchars($_GET['coupon_name']) : '';
    }

    // 优惠券开始时间
    if( empty($_SESSION['coupon_date_start']) ){
      $_SESSION['coupon_date_start'] = !empty($_GET['coupon_date_start']) ? htmlspecialchars($_GET['coupon_date_start']) : '';
    }

    // 优惠券结束时间
    if( empty($_SESSION['coupon_date_end']) ){
      $_SESSION['coupon_date_end'] = !empty($_GET['coupon_date_end']) ? htmlspecialchars($_GET['coupon_date_end']) : '';
    }

    // 优惠券金额
    if(empty($_SESSION['coupon_discount']) ){
      $_SESSION['coupon_discount'] = !empty($_GET['coupon_discount']) ? htmlspecialchars($_GET['coupon_discount']) : '';
    }

    // 优惠券金额描述
    if( empty($_SESSION['coupon_discount_desn']) ){
      $_SESSION['coupon_discount_desn'] = !empty($_GET['coupon_discount_desn']) ? htmlspecialchars($_GET['coupon_discount_desn']) : '';
    }

    // 优惠券最少限额才可以使用，例如：500
    if( empty($_SESSION['coupon_min_limit_amount']) ){
      $_SESSION['coupon_min_limit_amount'] = !empty($_GET['coupon_min_limit_amount']) ? htmlspecialchars($_GET['coupon_min_limit_amount']) : '';
    }

    // 优惠券发行数量
    if( empty($_SESSION['coupon_release_total']) ){
      $_SESSION['coupon_release_total'] = !empty($_GET['coupon_release_total']) ? htmlspecialchars($_GET['coupon_release_total']) : '';
    }

    // 用户范围
    //$this->res['sendflag'] = !empty($_GET['sendflag']) ? htmlspecialchars($_GET['sendflag']): 0;

    // 返回编辑优惠券的页面
    $this->res['couponEditUrl'] = linkurl('coupon/edit');

    // 获取分类
    $cat=$this->getCategoryList();
    $this->res['cat'] = $cat['cate'];

    // 获取品牌
    $this->res['brand'] = getData("select manufacturer_id,name from hb_manufacturer where status=0");

    // 处理商品分类搜索条件
    $where = '';
    if(!empty($_GET['product_category_id'])){
      // 获取分类id的下级分类id集
      $category_ids = $this->getCategoryIds($_GET['product_category_id']);
      if ($category_ids) {        
        $where .= " AND ptc.category_id in (".$category_ids.")";
      }else{
        $where .= " AND ptc.category_id=".htmlspecialchars($_GET['product_category_id']);
      }
    }

    // 处理商品品牌搜索条件    
    if(!empty($_GET['product_brand_id'])){
      foreach($this->res['brand'] as $k=>$v){
        if($v['manufacturer_id'] == $_GET['product_brand_id'])
          $this->res['brand'][$k]['is_selected'] = 1;
      }
      $where .= " AND p.brand_id=".htmlspecialchars($_GET['product_brand_id']);
    }

    // 处理商品名称的搜索条件
    if(!empty($_GET['product_name'])){
      $where .= " AND pd.name like '%".htmlspecialchars($_GET['product_name']."%'");
      $this->res['product_name'] = htmlspecialchars($_GET['product_name']);
    }

    // 处理商品id的搜索条件
    if(!empty($_GET['product_id'])){
      $where .= " AND p.product_id=".htmlspecialchars($_GET['product_id']);
      $this->res['product_id'] = htmlspecialchars($_GET['product_id']);
    }
    $this->getMenu();

    // 处理分页
    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $limit = 20;
    $count = '';
    $all_arr = getRow("select count(p.product_id) as all_nums from hb_product as p 
                             left join hb_product_description as pd on p.product_id=pd.product_id 
                             left join hb_product_to_category as ptc on p.product_id=ptc.product_id
                             where p.status=1 ".$where);
    if($all_arr){
      $count = $all_arr['all_nums'];
    }
    
    if($count < $limit){
      $this->getPages($page,1);
    }else{
      $this->getPages($page,ceil($count/$limit));
    }

    if (!empty($_GET['product_name'])) {
      $product_info = getData("select p.product_id,pd.name,p.brand_id,p.image,ptc.category_id from hb_product as p 
                             left join hb_product_description as pd on p.product_id=pd.product_id 
                             left join hb_product_to_category as ptc on p.product_id=ptc.product_id
                             where p.status=1 ".$where);
    }else{
      $product_info = getData("select p.product_id,pd.name,p.brand_id,p.image,ptc.category_id from hb_product as p 
                             left join hb_product_description as pd on p.product_id=pd.product_id 
                             left join hb_product_to_category as ptc on p.product_id=ptc.product_id
                             where p.status=1 ".$where." limit ".($page-1)*$limit.",".$limit);
    }
    // 处理分类名和品牌名
    foreach($product_info as $key=>$value){
      $category_info = getRow("select name from hb_category_description where category_id=".$value['category_id']);
      $product_info[$key]['category_name'] = !empty($category_info['name']) ? $category_info['name']: '';

      $manufacturer_info = getRow("select name from hb_manufacturer where manufacturer_id=".$value['brand_id']);
      $product_info[$key]['manufacturer_name'] = $manufacturer_info['name'];
    }

    $this->res['product_info'] = $product_info;
    // 返回新增优惠券的页面
    $this->res['couponAddUrl'] = linkurl('coupon/add');
    
    return $this->res;
  }

  /**
   *  @description  获取部分品牌
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-31
   */
  public function getManufacturerList(){
    // 优惠券信息form表单
    // 优惠券名称
    
    //判断是add还是edit
    if(isset($_GET['types'])){
      //edit
      $this->res['types']='edit';
      //获取到coupon_id
      $coupon_id=isset($_GET['coupon_id'])?$_GET['coupon_id']:"";
      $this->res["coupon_id"]="$coupon_id";
    }else{
      //add
      $this->res['types']='add';
    }

    //判断是否在session中存在
    if( empty($_SESSION['coupon_name']) || ($_SESSION['coupon_name'] != $_GET['coupon_name']) ){
      $_SESSION['coupon_name'] = !empty($_GET['coupon_name']) ? htmlspecialchars($_GET['coupon_name']) : '';
    }

    // 优惠券开始时间
    if( empty($_SESSION['coupon_date_start']) ){
      $_SESSION['coupon_date_start'] = !empty($_GET['coupon_date_start']) ? htmlspecialchars($_GET['coupon_date_start']) : '';
    }

    // 优惠券结束时间
    if( empty($_SESSION['coupon_date_end']) ){
      $_SESSION['coupon_date_end'] = !empty($_GET['coupon_date_end']) ? htmlspecialchars($_GET['coupon_date_end']) : '';
    }

    // 优惠券金额
    if(empty($_SESSION['coupon_discount']) ){
      $_SESSION['coupon_discount'] = !empty($_GET['coupon_discount']) ? htmlspecialchars($_GET['coupon_discount']) : '';
    }

    // 优惠券金额描述
    if( empty($_SESSION['coupon_discount_desn']) ){
      $_SESSION['coupon_discount_desn'] = !empty($_GET['coupon_discount_desn']) ? htmlspecialchars($_GET['coupon_discount_desn']) : '';
    }

    // 优惠券最少限额才可以使用，例如：500
    if( empty($_SESSION['coupon_min_limit_amount']) ){
      $_SESSION['coupon_min_limit_amount'] = !empty($_GET['coupon_min_limit_amount']) ? htmlspecialchars($_GET['coupon_min_limit_amount']) : '';
    }

    // 优惠券发行数量
    if( empty($_SESSION['coupon_release_total']) ){
      $_SESSION['coupon_release_total'] = !empty($_GET['coupon_release_total']) ? htmlspecialchars($_GET['coupon_release_total']) : '';
    }

    // 用户范围
    // $this->res['sendflag'] = !empty($_GET['sendflag']) ? htmlspecialchars($_GET['sendflag']): 0;

  

    $this->getMenu();

    // 分页处理
    $page = isset($_GET['page']) ? $_GET['page'] : 1 ;
    $limit = 20;
    $all_arr = getRow("select count(manufacturer_id) as all_nums from ".DB_PREFIX."manufacturer where status=0");
    //品牌
    $man = getData("select manufacturer_id as id,name,status from ".DB_PREFIX."manufacturer where status=0 limit ".($page-1)*$limit.",".$limit."");
    if(count($man) < $limit)
      $this->getPages($page,$page);
    else
      $this->getPages($page,ceil($all_arr['all_nums']/$limit));

    $this->res['man']=$man;

    // 返回新增优惠券的页面
    $this->res['couponAddUrl'] = linkurl('coupon/add');
    $this->res['couponEditUrl'] = linkurl('coupon/edit');
    
    return $this->res;
  }

  /**
   *  @description  获取所有商品分类
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function getCategoryList(){
    //判断是add还是edit
    if(isset($_GET['types'])){
      //edit
      $this->res['types']='edit';
      //获取到coupon_id
      $coupon_id=isset($_GET['coupon_id'])?$_GET['coupon_id']:"";
      $this->res["coupon_id"]="$coupon_id";
    }else{
      //add
      $this->res['types']='add';
    }
    // 优惠券信息form表单
    // 优惠券名称
    if( empty($_SESSION['coupon_name']) || ($_SESSION['coupon_name'] != $_GET['coupon_name']) ){
      $_SESSION['coupon_name'] = !empty($_GET['coupon_name']) ? htmlspecialchars($_GET['coupon_name']) : '';
    }

    // 优惠券开始时间
    if( empty($_SESSION['coupon_date_start']) ){
      $_SESSION['coupon_date_start'] = !empty($_GET['coupon_date_start']) ? htmlspecialchars($_GET['coupon_date_start']) : '';
    }

    // 优惠券结束时间
    if( empty($_SESSION['coupon_date_end']) ){
      $_SESSION['coupon_date_end'] = !empty($_GET['coupon_date_end']) ? htmlspecialchars($_GET['coupon_date_end']) : '';
    }

    // 优惠券金额
    if(empty($_SESSION['coupon_discount']) ){
      $_SESSION['coupon_discount'] = !empty($_GET['coupon_discount']) ? htmlspecialchars($_GET['coupon_discount']) : '';
    }

    // 优惠券金额描述
    if( empty($_SESSION['coupon_discount_desn']) ){
      $_SESSION['coupon_discount_desn'] = !empty($_GET['coupon_discount_desn']) ? htmlspecialchars($_GET['coupon_discount_desn']) : '';
    }

    // 优惠券最少限额才可以使用，例如：500
    if( empty($_SESSION['coupon_min_limit_amount']) ){
      $_SESSION['coupon_min_limit_amount'] = !empty($_GET['coupon_min_limit_amount']) ? htmlspecialchars($_GET['coupon_min_limit_amount']) : '';
    }

    // 优惠券发行数量
    if( empty($_SESSION['coupon_release_total']) ){
      $_SESSION['coupon_release_total'] = !empty($_GET['coupon_release_total']) ? htmlspecialchars($_GET['coupon_release_total']) : '';
    }

    // 用户范围
    // $this->res['sendflag'] = !empty($_GET['sendflag']) ? htmlspecialchars($_GET['sendflag']): 0;

    $this->getMenu();

    $tree=new product();
    $getTree=$tree->getCat();
    if(!empty($getTree)){
      foreach ($getTree as $key => $value) {
        $getTree[$key]["image"]=admindefault($value["image"]);
        if(isset($getTree[$key]['son'])){
          foreach ($getTree[$key]['son'] as $key1 => $value1) {
          $getTree[$key]['son'][$key1]['image']=admindefault($value1["image"]);            
            if(isset($getTree[$key]['son'][$key1]['son'])){
              foreach ($getTree[$key]['son'][$key1]['son'] as $key2 => $value2) {
                $getTree[$key]['son'][$key1]['son'][$key2]['image']=admindefault($value2["image"]);
              }
            }
          }
        }        
      }
     }
     $this->res['cate'] = $getTree;

    // 返回新增优惠券的页面
    $this->res['couponAddUrl'] = linkurl('coupon/add');
    $this->res['couponEditUrl'] = linkurl('coupon/edit');
    
    return $this->res;
  }

  /**
   *  @description  获取商品所有品牌
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-30/14:00
   */
  public function getManufacturerInfoByAjax(){
    $manufacturer = getData("select manufacturer_id,name from `" .DB_PREFIX. "manufacturer` where status = '0' ");
    echo json_encode($manufacturer);exit;
  }

  /**
   *  @description  根据分类id获取该分类下的所有下级分类id以及包括本身id集合
   *  @param        string category_id  商品分类id
   *  @return       string category_ids 商品分类id字符串，逗号分隔开的分类id
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-04-17
   */
  private function getCategoryIds($category_id){
    $category_ids = array();

    return $category_ids;
  }

  /**
   * @description 随机生成优惠券兑换码
   * @param       int $nums 兑换码个数，默认值100
   * @return      string  $coupon_codes  兑换码字符串集合，例如：2jw5,hd21,14uy
   * @author      godloveevin@yeah.net
   * @d/t         2017-04-18
   */
  public function randomGenerationCouponCode($nums=100){
    $coupon_codes = '';
    $nums = $nums;
    if ($_POST['nums']) {
      $nums = htmlspecialchars($_POST['nums']);
    }
    for($i=0;$i<$nums;$i++){
      $coupon_codes .= mt_rand(0,9).$this->getAchar().$this->getAchar().mt_rand(0,9).',';
    }
    $coupon_codes = substr($coupon_codes,0,strlen($coupon_codes)-1);
    echo json_encode($coupon_codes);exit;
  }

  /**
   * @description 随机生成一个英文字母，包括大写字母和小写字母
   * @param       none
   * @return      string  $char 一个字母
   * @author      godloveevin@yeah.net
   * @d/t         2017-04-18
   */
  private function getAchar(){
    $char = '';
    $type = mt_rand(0,1);
    if(0 == $type){
      // type=0 小写字母
      $char = chr(mt_rand(97, 122));
    }else{
      // type=1 大写字母
      $char = chr(mt_rand(65, 90));
    }
    return $char;
  }

  /**
   * @description 
   * @param
   * @param
   * @return
   * @author
   * @d/t
   */
  public function test(){
    // 处理优惠券过期问题
    /*$coupons = getData("select * from hb_coupon");
    $now = time();
    foreach($coupons as $key=>$value){
      if ($value['date_end'] < $now) {
        exeSql("UPDATE hb_coupon SET status=3");
        echo '1';
      }
    }*/
  }

}