<?php
/**
 *  @description   审核管理(review)控制器，主要负责审核商户板块，商户上传商品，认证等功能
 *  @author        godloveevin@yeah.net
 *  @d/t           2017-03-27/09:30
 */

use OSS\OssClient;
use OSS\Core\OssException;
require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';

//面向对象的control 类
include "xcontrol/base.php";
include "lib/pagination.php";


class review extends base
{
   /**
    *   成员属性列表
    */
   public $merchant_id = '';                                                                  // 登录用户所属的商户id
   public $user_id = '';                                                                      // 登录用户id
   public $username = '';                                                                     // 登录用户名


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

  public function review(){
    $this->__construct();
  }

  /**
   *  @description  审核商户板块
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function checkPlateList(){
    $this->getMenu();
    // 板块审核状态，1：通过；0：待审核；-1：审核失败
    $is_checked = isset($_GET['is_checked']) ? $_GET['is_checked'] : -2;

    $where = '';
    if(isset($is_checked) && (-2 != $is_checked)){
      $where = " WHERE is_checked=".$is_checked;
    }

    // 处理分页
    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $limit = 10;
    $all_arr = getRow("SELECT count(plate_id) as all_nums FROM ".DB_PREFIX."merchant_plate".$where);
    $count = $all_arr['all_nums'];
    if($count < $limit){
      $this->getPages($page,1);
    }else{
      $this->getPages($page,ceil($count/$limit));
    }

    // 默认获取全部板块数据集
    $plates = getData("SELECT * FROM ".DB_PREFIX."merchant_plate".$where." LIMIT ".($page-1)*$limit.",".$limit);

    foreach($plates as $key=>$value){
      // 处理商户名称，认证类型
      $merchant_info = getRow("SELECT merchant_name,merchant_type FROM ".DB_PREFIX."merchant WHERE merchant_id=".$value['merchant_id']);
      $plates[$key]['merchant_name'] = $merchant_info['merchant_name'];
      $plates[$key]['merchant_attr'] = $merchant_info['merchant_type'];

      // 处理商户账号
      $userName = getRow("SELECT username FROM ".DB_PREFIX."user WHERE merchant_id=".$value['merchant_id']);
      $plates[$key]['admin_name'] = $userName['username'];

      // 认证处理
      $merchant_certificate_info = getRow("SELECT is_checked from ".DB_PREFIX."merchant_certificate WHERE merchant_id=".$value['merchant_id']);
      $plates[$key]['is_certificated'] = $merchant_certificate_info['is_checked'];
    }

    $this->res['plates'] = $plates;

    // 标签页
    if(isset($is_checked)){
      $this->res['is_checked'] = $is_checked;
    }else{
      $this->res['is_checked'] = -2;
    }

    // 获取商户认证信息ajax请求url
    $this->res['getCheckMerchantPlateUrl'] = linkurl('review/getCheckMerchantPlateByAjax');

     // 商户认证信息ajax请求url
    $this->res['checkMerchantPlateUrl'] = linkurl('review/checkMerchantPlateByAjax');

    return $this->res;
  }

  /**
   *  @description  获取商户板块审核结果信息
   *  @param        string $plate_id
   *  @return       string json串
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function getCheckMerchantPlateByAjax(){
    $data = array('code'=>0,'msg'=>'提取商户板块审核资料成功','data'=>'');
    if(! empty($_POST['plate_id'])){
      $plate_id = $_POST['plate_id'];
      $plate_info = getRow("SELECT * FROM ".DB_PREFIX."merchant_plate WHERE plate_id=".$plate_id);

      // 判断认证信息是否通过
      $certificate_info = getRow("SELECT is_checked,certificate_type FROM ".DB_PREFIX."merchant_certificate WHERE merchant_id=".$plate_info['merchant_id']);
      if($certificate_info){
        $plate_info['certificate_opened'] = $certificate_info['is_checked'];
        if(-1 == $certificate_info['is_checked']){
          if(1 == $certificate_info['certificate_type'])
            $plate_info['check_reason'] = "您的企业信息认证不通过，请您重新进行认证";
          if(2 == $certificate_info['certificate_type'])
            $plate_info['check_reason'] = "您的个人信息认证不通过，请您重新进行认证";
        }
      }
      $data = array('code'=>0,'msg'=>'提取商户板块审核资料成功','data'=>$plate_info);
    }else{
      $data = array('code'=>1,'msg'=>'缺少参数：merchant_id','data'=>'');
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  审核商户板块(同意或者拒绝)
   *  @param        array $_post
   *  @return       string json串
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function checkMerchantPlateByAjax(){
    $data = array('code'=>0,'msg'=>'操作成功','data'=>'');
    if(empty($_POST['plate_id'])){
      $data = array('code'=>1,'msg'=>'缺少参数：plate_id','data'=>'');
    }else if(empty($_POST['is_checked'])){
      $data = array('code'=>1,'msg'=>'缺少参数：is_checked','data'=>'');
    }else if(empty($_POST['checked_reason'])){
      $data = array('code'=>1,'msg'=>'缺少参数：checked_reason','data'=>'');
    }else{
      $plate_id = (int)$_POST['plate_id'];
      $is_checked = (int)$_POST['is_checked'];
      $checked_reason = htmlspecialchars($_POST['checked_reason']);
      if(exeSql("UPDATE hb_merchant_plate SET is_checked=".$is_checked.",check_reason='".$checked_reason."' WHERE plate_id=".$plate_id)){
        $data = array('code'=>0,'msg'=>'操作成功','data'=>linkurl('review/checkPlateList'));
      }else{
       $data = array('code'=>1,'msg'=>'数据库异常错误','data'=>'');
      }
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  企业认证详情页
   *  @param        none
   *  @return       array $this->res
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function merchantCertificate(){
    $this->getMenu();

    // 认证的企业商户id
    $merchant_id = !empty($_GET['merchant_id']) ? $_GET['merchant_id'] : 1;

    // 获取企业认证信息
    $merchant_info = getRow("SELECT * FROM ".DB_PREFIX."merchant_certificate WHERE merchant_id=".$merchant_id);

    $this->res['merchant_certificate'] = $merchant_info;
    $this->res['certificateUrl'] = linkurl('review/certificateByAjax');
    $this->res['unCertificateUrl'] = linkurl('review/unCertificateByAjax');
    $this->res['checkPlateListUrl'] = linkurl('review/checkPlateList');

    return $this->res;
  }

  /**
   *  @description  个人认证详情页
   *  @param        none
   *  @return       array $this->res
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function personalCertificate(){
    $this->getMenu();

    // 认证的个人商户id
    $merchant_id = !empty($_GET['merchant_id']) ? $_GET['merchant_id'] : 1;

    // 获取个人认证信息
    $personal_info = getRow("SELECT * FROM ".DB_PREFIX."merchant_certificate WHERE merchant_id=".$merchant_id);

    $this->res['personal_certificate'] = $personal_info;
    $this->res['certificateUrl'] = linkurl('review/certificateByAjax');
    $this->res['unCertificateUrl'] = linkurl('review/unCertificateByAjax');
    $this->res['checkPlateListUrl'] = linkurl('review/checkPlateList');

    return $this->res;
  }

  /**
   *  @description  个人企业确认认证
   *  @param        none
   *  @return       string josn串
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function certificateByAjax(){
    $data = array('code'=>0,'msg'=>'确认认证成功','data'=>'');
    if(! empty($_POST['merchant_id'])){
      $merchant_id = $_POST['merchant_id'];
      if(exeSql("UPDATE hb_merchant_certificate SET is_checked='1' WHERE merchant_id=".$merchant_id)){
        $data = array('code'=>0,'msg'=>'确认认证成功','data'=>linkurl('review/checkPlateList'));
      }else{
       $data = array('code'=>1,'msg'=>'数据库异常错误','data'=>'');
      }
    }else{
      $data = array('code'=>1,'msg'=>'缺少参数：merchant_id','data'=>'');
    }
    echo json_encode($data);exit;
  }
  
  /**
   *  @description  个人企业取消认证
   *  @param        none
   *  @return       string josn串
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function unCertificateByAjax(){
    $data = array('code'=>0,'msg'=>'取消认证成功','data'=>'');
    if(! empty($_POST['merchant_id'])){
      $merchant_id = $_POST['merchant_id'];
      if(exeSql("UPDATE hb_merchant_certificate SET is_checked='-1' WHERE merchant_id=".$merchant_id)){
        $data = array('code'=>0,'msg'=>'取消认证成功','data'=>linkurl('review/checkPlateList'));
      }else{
       $data = array('code'=>1,'msg'=>'数据库异常错误','data'=>'');
      }
    }else{
      $data = array('code'=>1,'msg'=>'缺少参数：merchant_id','data'=>'');
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  处理某个板块下的商品列表
   *  @param        array $_GET
   *  @return       array $this->res
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27/10:00
   */
  public function checkPlateProductList(){
    $this->getMenu();
    // 处理板块id
    $plate_id = !empty($_GET['plate_id']) ? $_GET['plate_id'] : 0;
    // 处理商户id
    $merchant_id = !empty($_GET['merchant_id']) ? $_GET['merchant_id'] : 0;

    // 处理分页
    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $limit = 10;
    $all_arr = getRow("SELECT count(product_id) as all_nums FROM ".DB_PREFIX."merchant_plate_product 
                       WHERE merchant_id=".$merchant_id." AND plate_id=".$plate_id);
    $count = $all_arr['all_nums'];
    if($count < $limit){
      $this->getPages($page,1);
    }else{
      $this->getPages($page,ceil($count/$limit));
    }

    // 拉取商户的自家商品
    $sql = "SELECT p.product_id,p.image,pd.name,p.price,p.proxyprice,p.model,p.quantity,p.sales,p.status,p.merchant_id
            FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON p.product_id=pd.product_id   
            WHERE p.merchant_id=".$merchant_id." order by p.date_added desc LIMIT ".($page-1)*$limit.",".$limit;
    $myProducts = getData($sql);
    if(!empty($myProducts)){
      foreach($myProducts as $key=>$value){
        $myProducts[$key]['price'] = sprintf("%.2f",$value['price']);
        $myProducts[$key]['proxyprice'] = sprintf("%.2f",$value['proxyprice']);
      }
      $this->res['myProducts'] = $myProducts;
    }else{
      $this->res['myProducts'] = 0;
    }

    $this->res['enabledMeProductUrl'] = linkurl('review/enabledMeProduct');
    $this->res['forbiddenMeProductUrl'] = linkurl('review/forbiddenMeProduct');


    return $this->res;
  }


  /**
   *  @description  我的商品页面，启用商品
   *  @param        
   *  @return       
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27
   */
  public function  enabledMeProduct(){
    $data = array('code'=>0,'msg'=>"success",'data'=>true);
    if(empty($_POST['product_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：product_id",'data'=>false);
    }else{
      $del_sql = "UPDATE ".DB_PREFIX."product SET status=1 WHERE product_id=".$_POST['product_id'];
      if(!exeSql($del_sql)){
        $data = array('code'=>2,'msg'=>"数据库异常",'data'=>false);
      }
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  我的商品页面，禁用商品
   *  @param        
   *  @return       
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-27
   */
  public function forbiddenMeProduct(){
    $data = array('code'=>0,'msg'=>"success",'data'=>true);
    if(empty($_POST['product_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：product_id",'data'=>false);
    }else{
      $del_sql = "UPDATE ".DB_PREFIX."product SET status=0 WHERE product_id=".$_POST['product_id'];
      if(!exeSql($del_sql)){
        $data = array('code'=>2,'msg'=>"数据库异常",'data'=>false);
      }
    }
    echo json_encode($data);exit;
  }



}