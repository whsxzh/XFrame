<?php
/**
 *  @description   我的板块(me)控制器，主要负责商户入驻功能
 *  @author        godloveevin@yeah.net
 *  @d/t           2017-03-15/10:30
 */

use OSS\OssClient;
use OSS\Core\OssException;
require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';

//面向对象的control 类
include "xcontrol/base.php";
include "lib/pagination.php";


class me extends base
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

  public function me(){
    $this->__construct();
  }

  /**
   *  @description  我的板块
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-15/10:00
   */
  public function index(){
    $this->getMenu();

    // 首页跳转url
    $this->res['commonIndexUrl'] = linkurl('common/index');

    // 我的板块首页跳转url
    $this->res['meIndexUrl'] = linkurl('me/index');

    // 申请我的板块跳转url
    $this->res['meAddUrl'] = linkurl('me/add');

    // 编辑我的板块跳转url
    $this->res['meEditUrl'] = linkurl('me/edit');

    // 禁用我的板块跳转url
    $this->res['meForbiddenUrl'] = linkurl('me/forbidden');

    // 开启我的板块跳转url
    $this->res['meUnforbiddenUrl'] = linkurl('me/unForbidden');

    // 处理分页
    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $limit = 10;
    $all_arr = getRow("SELECT count(plate_id) as all_nums FROM ".DB_PREFIX."merchant_plate WHERE merchant_id=".$_SESSION['merchant_id']);
    $count = $all_arr['all_nums'];
    if($count < $limit){
        $this->getPages($page,1);
      }else{
        $this->getPages($page,ceil($count/$limit));
      }

    $merchant_plate_info = getData("select * from hb_merchant_plate where merchant_id=".$_SESSION['merchant_id']);
    foreach($merchant_plate_info as $key=>$value){
       $merchant_plate_info[$key]['date_created'] = date("Y-m-d",$value['date_created']);
    }
    $this->res['plate'] = $merchant_plate_info;

    return $this->res;
   }

  /**
   *  @description  申请我的板块
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-16/10:10
   */
  public function add(){
    $this->getMenu();
    // 首页跳转url
    $this->res['commonIndexUrl'] = linkurl('common/index');

    // 我的板块首页跳转url
    $this->res['meIndexUrl'] = linkurl('me/index');

    if($_POST){
       // 商户信息
       $merchant_info = getRow("select has_plate,merchant_type from hb_merchant where merchant_id=".$_SESSION['merchant_id']);

       // 新增板块数据
       $insert_data = array();

       // 处理板块的名称
       $insert_data['plate_name'] = $_POST['plate_name'] ? htmlspecialchars($_POST['plate_name']) : '';

       // 处理板块的商品选择方式
       $insert_data['prdlink_type'] = $_POST['prdlink_type'] ? htmlspecialchars($_POST['prdlink_type']) : '';

       // 处理板块的是否开通上传商品
       $insert_data['is_upload_products'] = $_POST['is_upload_products'] ? htmlspecialchars($_POST['is_upload_products']) : '';

       // 处理板块的审核状态，默认是待审核置0值；0：待审核；-1：审核不通过；1：审核通过
       $insert_data['is_checked'] = 0;

       // 审核不通过的原因，默认值为空字符串
       $insert_data['check_resson'] = '待审核';

       // 处理板块是否开启，默认开启置1值；1：开启；0：禁用
       $insert_data['is_opened'] = 0;

       // 处理记录录入时间
       $insert_data['date_created'] = time();

       // 处理记录修改时间，默认值为0
       $insert_data['date_modify'] = 0;

       // 处理板块所属商家，为当前登录的管理所属的商户
       $insert_data['merchant_id'] = $_SESSION['merchant_id'] ? $_SESSION['merchant_id'] : 0 ;

       // 根据商户的商户类型字段，处理板块的商户类型
       if(1 == $merchant_info['merchant_type']){
          $insert_data['merchant_type'] = "商户入驻";
       }else if(2 == $merchant_info['merchant_type']){
          $insert_data['merchant_type'] = "个人推荐";
       }         
       // 处理板块图片
       if($_FILES){
           $plate_img = $this->upload_img($_FILES);
           $insert_data['plate_img'] = $plate_img[0];
       }else{
          $insert_data['plate_img'] = '';
       }
       if(! saveData("hb_merchant_plate",$insert_data)){
          echo "Fail! Insert data to table hb_merchant_plate...";
       }else{
          // 判断商户是否需要支付开通板块的保证金
          if($merchant_info['has_plate']){
             // 已经开通过板块，无需再次支付开通板块的保证金
             echo "<script>alert('申请成功，等待后台审核');window.location.href='".$this->res['meIndexUrl']."';</script>";
          }else{
             // 商户首次开通，需要支付开通板块的保证金
             if(1 == $merchant_info['merchant_type']){
                // 企业认证
                if(getRow("select id from hb_merchant_certificate where merchant_id=".$_SESSION['merchant_id'])){
                  redirect(linkurl('me/index'));
                }else{
                  redirect(linkurl('me/merchantCertificate'));  
                }
             }else if(2 == $merchant_info['merchant_type']){
                // 个人认证
              if(getRow("select id from hb_merchant_certificate where merchant_id=".$_SESSION['merchant_id'])){
                  redirect(linkurl('me/index'));
                }else{
                  redirect(linkurl('me/personalCertificate'));
                }
             }
          }
       };
    }else{
       return $this->res;
    }
  }

  /**
   *  @description  编辑我的板块
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-16/11:00
   */
  public function edit(){
    $this->getMenu();

    // 后台首页跳转url
    $this->res['commonIndexUrl'] = linkurl('common/index');

    // 我的板块首页跳转url
    $this->res['meIndexUrl'] = linkurl('me/index');

    $plate_id = $_GET['plate_id'] ? $_GET['plate_id'] : '';

    if(empty($_POST)){
       $plate_info = getRow("select * from ".DB_PREFIX."merchant_plate where plate_id=".$plate_id);
       $this->res['plate_info'] = $plate_info;
    }else{
       // 处理图片问题
       if($_FILES){
           $plate_img = $this->upload_img($_FILES);
           $_POST['plate_img'] = $plate_img[0];
       }else{
          $_POST['plate_img'] = '';
       }

       foreach($_POST as $key=>$value){
          $_POST[$key] = htmlspecialchars($value);
       }
       // 处理修改时间
       $_POST['date_modify'] = time();

       if(! saveData("hb_merchant_plate",$_POST)){
          echo "数据库异常错误";
       }else{
          echo "<script>alert('编辑成功');window.location.href='".$this->res['meIndexUrl']."';</script>";
       }
    }
    return $this->res;
  }

  /**
   *  @description  删除我的板块
   *  @param        none
   *  @return       int success返回1，fail返回0
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-16/11:00
   */
  public function del(){
    $this->getMenu();

    // 我的板块首页跳转url
    $this->res['meIndexUrl'] = linkurl('me/index');

    if(empty($_GET['plate_id'])){
      echo "<script>alert('操作异常');window.location.href='".$this->res['meIndexUrl']."';</script>";
    }else{
      $plate_id = $_GET['plate_id'];
      if(exeSql("delete from hb_merchant_plate where plate_id=".$plate_id)){
        echo "<script>alert('操作成功');window.location.href='".$this->res['meIndexUrl']."';</script>";
      }
    }
  }

  /**
   *  @description  禁用我的板块
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-16/10:30
   */
  public function forbidden(){
    $data = array('code'=>0,'msg'=>'禁用成功','data'=>'');
    if(! empty($_POST['plate_id'])){
      $plate_id = $_POST['plate_id'];
      if(exeSql("UPDATE hb_merchant_plate SET is_opened='0' WHERE plate_id=".$plate_id)){
        $data = array('code'=>0,'msg'=>'禁用成功','data'=>linkurl('me/index'));
      }else{
       $data = array('code'=>1,'msg'=>'数据库异常错误','data'=>'');
      }
    }else{
      $data = array('code'=>1,'msg'=>'缺少参数：plate_id','data'=>'');
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  开启我的板块
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-16/10:30
   */
  public function unForbidden(){
    $data = array('code'=>0,'msg'=>'开启成功','data'=>'');
    if(! empty($_POST['plate_id'])){
      $plate_id = $_POST['plate_id'];
      // 一个商户没有开启的板块时，才可以开启
      $had_open_plate = getRow("SELECT plate_id FROM ".DB_PREFIX."merchant_plate 
                                WHERE merchant_id=".$_SESSION['merchant_id']." AND is_opened=1");
      if($had_open_plate){
        $data = array('code'=>1,'msg'=>'开启失败，您已经有开启的板块','data'=>linkurl('me/index'));
      }else{
        if(exeSql("UPDATE hb_merchant_plate SET is_opened='1' WHERE plate_id=".$plate_id)){
          $data = array('code'=>0,'msg'=>'开启成功','data'=>linkurl('me/index'));
        }else{
         $data = array('code'=>1,'msg'=>'数据库异常错误','data'=>'');
        }
      }
    }else{
      $data = array('code'=>1,'msg'=>'缺少参数：plate_id','data'=>'');
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  个人认证
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-17/10:30
   */
  public function personalCertificate(){
    // 我的板块首页跳转url
    $this->res['meIndexUrl'] = linkurl('me/index');

    // 个人认证
    $this->getMenu();
    $merchant_id = $_SESSION['merchant_id'];

    // 获取个人公司信息
    $merchant_info = getRow("select merchant_name,mer_address,mer_phone from hb_merchant where merchant_id=".$merchant_id);

    // 用户输入的数据
    if(! empty($_POST)){
      // 过滤特殊字符
      foreach($_POST as $key=>$value){
        $_POST[$key] = htmlspecialchars($value);
      }

      // 处理营业执照图片
      if($_FILES){
        $merchant_business_icense_img = $this->upload_img($_FILES);
        $_POST['merchant_business_icense_img'] =$merchant_business_icense_img[0];
      }

      $insert_sql = "INSERT INTO hb_merchant_certificate(
                    merchant_id,
                    merchant_name,
                    mer_address,
                    mer_phone,
                    industrial_commercial_num,
                    merchant_business_icense_img,
                    principal_name,
                    principal_tel,
                    certificate_type,
                    is_checked
                    ) VALUES (
                    '".$merchant_id."',
                    '".$merchant_info['merchant_name']."',
                    '".$merchant_info['mer_address']."',
                    '".$merchant_info['mer_phone']."',
                    '".$_POST['industrial_commercial_num']."',
                    '".$_POST['merchant_business_icense_img']."',
                    '".$_POST['principal_name']."',
                    '".$_POST['principal_tel']."',2,0)";
      if(exeSql($insert_sql)){
        echo "<script>alert('认证信息录入成功，等待审核');</script>";
        redirect(linkurl('me/pay'));
      }else{
        echo "<script>alert('异常错误');window.location.href='".$this->res['meIndexUrl']."';</script>";
      }
    }else{
      return $this->res;
    }
  }

  /**
   *  @description  企业认证
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-17/10:30
   */
  public function merchantCertificate(){
    // 我的板块首页跳转url
    $this->res['meIndexUrl'] = linkurl('me/index');

    // 企业认证
    $this->getMenu();
    $merchant_id = $_SESSION['merchant_id'];

    // 用户输入的数据
    if(! empty($_POST)){
      // 过滤特殊字符
      foreach($_POST as $key=>$value){
        $_POST[$key] = htmlspecialchars($value);
      }

      // 处理营业执照图片
      if($_FILES){
        $merchant_business_icense_img = $this->upload_img($_FILES);
        $_POST['merchant_business_icense_img'] = $merchant_business_icense_img[0];
      }

      $insert_sql = "INSERT INTO hb_merchant_certificate(
                    merchant_id,
                    merchant_name,
                    mer_address,
                    mer_phone,
                    industrial_commercial_num,
                    merchant_business_icense_img,
                    principal_name,
                    principal_tel,
                    certificate_type,
                    is_checked
                    ) VALUES (
                    '".$merchant_id."',
                    '".$_POST['merchant_name']."',
                    '".$_POST['mer_address']."',
                    '".$_POST['mer_phone']."',
                    '".$_POST['industrial_commercial_num']."',
                    '".$_POST['merchant_business_icense_img']."',
                    '".$_POST['principal_name']."',
                    '".$_POST['principal_tel']."',1,0)";
      if(exeSql($insert_sql)){
        echo "<script>alert('认证信息录入成功，等待审核认证');</script>";
        redirect(linkurl('me/pay'));
      }else{
        echo "<script>alert('异常错误');window.location.href='".$this->res['meIndexUrl']."';</script>";
      }
    }else{
      return $this->res;
    }
  }

  /**
   *  @description  支付保证金
   *  @param        none
   *  @return       bool true/false
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-17/17:30
   */
  public function pay(){
    $this->getMenu();
    $this->res['payUrl'] = linkurl('me/pay');
    if(! empty($_POST)){
      // 下单公共参数集
      $deposit_data = array();

      // 处理保证金订单号
      $deposit_data['despoit_no'] = $this->generateDespoitNo();

      // 处理支付者id信息
      $deposit_data['buyyer_id'] = $_SESSION['userid'] ? $_SESSION['userid']: '';

      // 处理保证金金额
      $deposit_data['total'] = $_POST['total'] ? htmlspecialchars($_POST['total']) : '';

      // 处理收款方id信息
      $root_info = getRow("select user_id from hb_merchant where merchant_id=1");
      $deposit_data['root_id'] = $root_info['user_id'];

      // 处理当前时间
      $deposit_data['date_added'] = time();

      // 处理支付方式
      if("alipay" == $_POST['payment']){        
        $deposit_data['payment'] = $_POST['payment'];

        if(exeSql("INSERT INTO hb_merchant_deposit (
                  despoit_no,total,payment,
                  buyyer_id,root_id,date_added,status) 
                  VALUES('".$deposit_data['despoit_no']."',
                          '".$deposit_data['total']."',
                          '".$deposit_data['payment']."',
                          '".$deposit_data['buyyer_id']."',
                          '".$deposit_data['root_id']."',
                          '".$deposit_data['date_added']."',1)")){
          // 构造支付请求的参数数组
          $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
          $price = sprintf("%.2f",$deposit_data['total']);

          // 同步通知地址
          $return_url = linkurl('me/alipay_return');

          // 异步通知地址
          $notify_url = linkurl('me/alipay_notify');

          $subject = "嗨企货仓平台商户入驻保证金收入";

          $body = "商户入驻保证金";

          // 组装支付表单
          $url = $this->getRequestUrlForAlipay($return_url, $subject, $body, $deposit_data['despoit_no'], 0, $price, $notify_url);

          // 请求网关支付
          echo json_encode(array("url"=>$url));exit;
        }else{
          // 下单失败
          echo json_encode("异常错误");exit;
        }
      }else if("wxpay" == $_POST['payment']){
        // 组装微信支付表单
        // 请求微信支付网关

      }else{
        // 添加其他支付方式
        // ....
      }
    }else{

    }
    return $this->res;
  }

  /**
   * @desctiption 组装支付宝的支付表单
   * @param       $return_url
   * @param       $subject
   * @param
   * @return 
   * @author       godloveevin@yeah.net
   * @d/t          2017-03-20
   */
  public function  getRequestUrlForAlipay( $return_url,  $subject, $body, $order_no, $lfee=0, $price=0.01, $notify_url ) {
      global  $data , $service ;
       //支付宝配置信息
       $partner ='2088421631589825';
       $scode ='vzbdhkurnz2exh6lyqimqmgm0sfxvmzd';
       $charset='UTF-8';

       // 支付宝交易类型
       $data [ 'service' ] =  'create_direct_pay_by_user' ; //[即时到账] create_partner_trade_by_buyer[担保交易]
       // 合作商户编号
       $data [ 'partner' ] =  $partner ;
       // 请求返回地址
       $data [ 'return_url' ] =  $return_url ;
       // 默认编码
       $data [ '_input_charset' ] =  $charset ;
       // 默认支付渠道
       $data [ 'paymenthod' ] =  'bankPay' ;
       // 默认的网银
       $data [ 'defaultbank' ] =  'ICBCB2C' ;
       // 商品名称
       $data [ 'subject' ] =  $subject ;
       // 商品展示URL
       $data [ 'show_url' ] =  '' ;
       // 异步通知返回
       $data [ 'notify_url' ] =  $notify_url;
       // 商品简介
       $data [ 'body' ] =  $body ;
       // 商户订单号
       $data [ 'out_trade_no' ] =  $order_no ;
       // 物流配送费用
       $data [ 'logistics_fee' ] =  $lfee ;
       // 物流费用付款方式
       $data [ 'logistics_payment' ] =  'SELLER_PAY' ; //SELLER_PAY(卖家支付)、BUYER_PAY(买家支付)、BUYER_PAY_AFTER_RECEIVE(货到付款)
       // 物流配送方式
       $data [ 'logistics_type' ] =  'POST' ; //物流配送方式：POST(平邮)、EMS(EMS)、EXPRESS(其他快递)
       // 价格
       $data [ 'price' ] =  $price;
       //$data['total_fee'] = '10.00';
       // 付款方式
       $data [ 'payment_type' ] =  '1' ;
       // 商品数量
       $data [ 'quantity' ] =  '1' ;
       // 卖家email
       $data [ 'seller_email' ] =  'haiqilaiiwant@163.com' ;
       $data  =  array_filter ( $data );

       ksort ( $data ); reset ( $data );
       $data [ 'sign' ] =  md5 ( urldecode ( http_build_query ( $data )). $scode );
       $data [ 'sign_type' ] =  'MD5' ;
       $url  =  'https://www.alipay.com/cooperate/gateway.do?' . http_build_query ( $data );
       return  $url ;
    }


   /**
    *  @description  上传图片至阿里云服务器(http://haiqihuocang.oss-cn-hangzhou.aliyuncs.com)
    *  @param        none
    *  @return       array $imgArr  图片地址索引数组
    *  @author       godloveevin@yeah.net
    *  @d/t          2017-03-17/10:30
    */
    private function upload_img($uploadfiles){
      $file = $uploadfiles['plate_img'];
      $imgArr =  array();                              
      $length = count($uploadfiles);
      for($i=0;$i<$length;$i++){
         $name = $file['name'];

         //得到文件类型，并且都转化成小写
         $type = strtolower(substr($name, strrpos($name, '.') + 1)); 
         $allow_type = array('jpg', 'jpeg', 'gif', 'png');
         if (!in_array($type, $allow_type)) {
            echo 2;  exit;
         }
         if (!is_uploaded_file($file['tmp_name'])) {
            echo 3;exit;
         }

         //设置随机数长度
         $filename = explode(".",$name);
         $filename[0] = rand(1,100000000);
         $name = implode(".",$filename);
         $uploadfile = date("Ymd").time().$name;
         if (!empty($name)) {
               try{
                  $object = "plate_img/".$uploadfile;
                  $file_local_path = $file["tmp_name"];
                  $accessKeyId = OSS_ACCESS_KEY_ID;
                  $accessKeySecret = OSS_ACCESS_KEY_SECRET;
                  $endpoint = OSS_ENDPOINT;
                  $bucket = OSS_BUCKET;
                  $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                  $ossClient->multiuploadFile($bucket,$object,$file_local_path);
                  $img_url = OSS_IMG_ENDPOINT."/".$object;
                  if(!empty($img_url)){
                     //上传成功
                     $imgArr[] = $img_url;
                  }else{
                     //上传失败
                     echo 5;exit;
                  }    
               }catch(OssException $e){
                  //上传失败
                  echo 5;exit;
               }                 
         }else{
            //上传失败   
            echo 5;exit;
         } 
      } 
      return $imgArr;exit;
    }

   /**
    * @description     判断是否为合法的身份证号码
    * @param           string $id
    * @return          bool true/false 
    * @author          godloveevin@yeah.net
    * @d/t             2017-03-17
    */
  private function is_idcard( $id ){
      $id = strtoupper($id);
      $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
      $arr_split = array();
      if(!preg_match($regx, $id)){
         return FALSE;
      }
      // 检查15位
      if(15==strlen($id)){
         $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
         @preg_match($regx, $id, $arr_split);
         //检查生日日期是否正确
         $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
         if(!strtotime($dtm_birth)){
            return FALSE;
         }else{
            return TRUE;
         }
      }else{
         // 检查18位
         $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
         @preg_match($regx, $id, $arr_split);
         $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
         // 检查生日日期是否正确
         if(!strtotime($dtm_birth)){
            return FALSE;
         }else{
            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign = 0;
            for ( $i = 0; $i < 17; $i++ ){
               $b = (int) $id{$i};
               $w = $arr_int[$i];
               $sign += $b * $w;
            }
            $n = $sign % 11;
            $val_num = $arr_ch[$n];
            if ($val_num != substr($id,17, 1)){
               return FALSE;
            } else{
               return TRUE;
            }
         }
      }
   }

  /**
    * @description    产生保证金订单号，唯一不重复
    * @param          none
    * @return         string  $despoit_no
    * @author         godloveevin@yeah.net
    * @d/t            2017-03-20
    */
  private function generateDespoitNo(){
    return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
  }

  /**
    * @description    支付宝支付完成的同步通知返回处理，此处只是作为一个成功支付的显示页面，
    *                 不做任何实际有关订单的处理，一切以异步通知为准（修改订单状态，审核等其他业务逻辑）
    * @param          none
    * @return         none
    * @author         godloveevin@yeah.net
    * @d/t            2017-03-22
    */
  public function alipay_return(){
    $this->getMenu();

    $this->res['meIndexUrl'] = linkurl("me/index");

    if('T' == $_GET['is_success']){
      // 处理支付宝同步通知的结果
      $buyyer_account = !empty($_GET['buyer_email']) ? htmlspecialchars($_GET['buyer_email']): '';
      $seller_account = !empty($_GET['seller_email']) ? htmlspecialchars($_GET['seller_email']): '';
      $remark = (!empty($_GET['body']) || !empty($_GET['subject'])) ? 
                (htmlspecialchars($_GET['body']).'-'.htmlspecialchars($_GET['subject'])) : '';
      if(!exeSql("UPDATE hb_merchant_deposit SET buyyer_account='".$buyyer_account."', 
                  seller_account='".$seller_account."',remark='".$remark."',status=2 
                  WHERE despoit_no=".$_GET['out_trade_no'])){
          echo 'fail';
      }
    }

    return $this->res;
  }


  /**
    * @description    支付宝支付完成的异步通知返回处理，无需任何展示页面，用户看不到的后台运行
    *                 一切以异步通知为准（修改订单状态，审核等其他业务逻辑）
    * @param          none
    * @return         none
    * @author         godloveevin@yeah.net
    * @d/t            2017-03-22
    */
  public function alipay_notify(){
    // 打开支付记录日志文件
    $file_haddle = fopen("../logs/deposit_log.txt","a+");

    // 提取支付宝异步通知的参数
    $notify_data = $_POST;

    $notify_json = json_encode($notify_data);

    fwrite($file_haddle, $notify_json);
    echo 'success';
  }

  /**
   *  @description  编辑我的板块商品
   *  @param        none
   *  @return       none
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-22/11:00
   */
  public function editProduct(){
    $this->getMenu();

    // 逐个删除板块商品的ajax请求url
    $this->res['meDelPlatePrdUrl'] = linkurl('me/delPlatePrdByAjax');

    // 批量删除板块商品的ajax请求url
    $this->res['meDelPlatePrdsUrl'] = linkurl('me/delPlatePrdsByAjax');

    // 商品库页面跳转url
    $this->res['meGetPlatformProductsUrl'] = linkurl('me/getPlatformProducts');

    // 我的板块商品跳转url
    $this->res['meEditProductUrl'] = linkurl('me/editProduct');

    if(empty($_GET['plate_id'])){
      echo "异常错误";exit;
    }else{
      $plate_id = $_GET['plate_id'];
      // 确认是否审核通过
      $plate_info = getRow("SELECT * FROM ".DB_PREFIX."merchant_plate WHERE plate_id=".$plate_id);
      $this->res['plate_info'] = $plate_info;
      if($plate_info['is_checked'] == 1){
        // 我的商品页面跳转url
        $this->res['meGetMeProducts'] = linkurl('me/getMeProducts');
      }

      // 嗨企货仓平台推荐商品数据集
      $system_recommend_prds = array();

      // 如果板块的商品选择方式是"嗨起推荐"，则从商品推荐表中拉取10个商品，作为板块的默认商品
      $merchant_id = getRow("SELECT merchant_id FROM ".DB_PREFIX."merchant WHERE merchant_name LIKE '嗨企货仓'");

      // 自行推荐的商品集
      $recommend_prds = array();

      // 分页处理
      $page = !empty($_GET['page']) ? $_GET['page'] : 1;
      $limit = 10;
      $count_arr = getRow("SELECT count(*) as count FROM ".DB_PREFIX."merchant_plate_product WHERE plate_id=".$plate_id);
      $count = $count_arr['count'];

      // 处理嗨企货仓推荐，并且是首页时的商品集
      if(('嗨企推荐' == $plate_info['prdlink_type'] || '1' == $plate_info['prdlink_type'])
        && 1 == $page){
        // 处理系统推荐商品ids
        $recommend_prd_ids = getData("SELECT product_id FROM ".DB_PREFIX."product_recommend 
                                      WHERE merchant_id=".$merchant_id['merchant_id']." LIMIT 0,10");

        // 查询板块商品的商品id集
        $pp_ids = '';

        // 处理存储板块商品表
        // 组装板块商品表中待保存的数组
        foreach($recommend_prd_ids as $key=>$value){
          $pp_data[$key]['plate_id'] = $plate_id;
          $pp_data[$key]['product_id'] = $value['product_id'];
          $pp_data[$key]['date_created'] = time();
          $pp_data[$key]['merchant_id'] = $merchant_id['merchant_id'];
          $pp_ids .= $value['product_id'].',';
        }

        $pp_ids = substr($pp_ids,0,strlen($pp_ids)-1);
        if(empty($pp_ids)) $pp_ids = 0;

        // 处理是否已经批量插入过系统推荐的商品数据
        if(getRow("SELECT product_id FROM ".DB_PREFIX."merchant_plate_product 
                   WHERE merchant_id=".$merchant_id['merchant_id']." AND plate_id=".$plate_id)){
          $system_recommend_prds = $this->getProductInfos($pp_ids,$plate_id);
        }else{
          if(saveDataMuti('hb_merchant_plate_product',$pp_data)){
            $system_recommend_prds = $this->getProductInfos($pp_ids,$plate_id);
          }else{
            echo '数据库异常错误';exit;
          }
        }

        if(10 > count($system_recommend_prds)){          
          $recommend_prds = getData("SELECT product_id FROM ".DB_PREFIX."merchant_plate_product WHERE plate_id=".$plate_id." 
                  LIMIT ".(($page-1)*$limit+count($system_recommend_prds)).",".($limit-count($system_recommend_prds)));
          $pp_ids = '';
          foreach($recommend_prds as $key=>$value){
            $pp_ids .= $value['product_id'].',';
          }
          $pp_ids = substr($pp_ids,0,strlen($pp_ids)-1);
          if(empty($pp_ids)) $pp_ids = 0;
          $recommend_prds = $this->getProductInfos($pp_ids,$plate_id);
          $count = count($recommend_prds);
        }
        $recommend_prds = array_merge($system_recommend_prds,$recommend_prds);
      }else{
        // 个人选择，则不会有系统推荐的商品，所以，建议商家选择：嗨企推荐
        $prd_ids = '';
        $recommend_ids = getData("SELECT product_id FROM ".DB_PREFIX."merchant_plate_product 
                                  WHERE plate_id=".$plate_id." LIMIT ".($page-1)*$limit.",".$limit);
        foreach($recommend_ids as $key=>$value){
          $prd_ids .= $value['product_id'].',';
        }
        $prd_ids = substr($prd_ids,0,strlen($prd_ids)-1);
        if(empty($prd_ids)) $prd_ids = 0;
        $recommend_prds = $this->getProductInfos($prd_ids,$plate_id);
      }

      // 封装系统推荐商品结果集
      $this->res['recommend_prds'] = $recommend_prds;

      // 处理分页功能
      $this->res['editProductUrl'] = linkurl('me/editProduct');
      
      if($count < $limit){
        $this->getPages($page,1);
      }else{
        $this->getPages($page,ceil($count/$limit));
      }

      $this->res['count'] = $count;
      
      return $this->res;
    }
  }

  /**
   *  @description 拉取板块商品的系统推荐商品
   *  @param       string $pp_ids 系统推荐商品id集
   *  @param       string $plate_id 板块id
   *  @return      array  $prd_data 系统商品结果集
   *  @author      godloveevin@yeah.net
   *  @d/t         2017-03-22
   */
  public function getProductInfos($prd_ids,$plate_id){
    $prd_data = array();
    $sql = "SELECT p.product_id,p.image,pd.name,p.price,p.proxyprice,p.model,p.quantity,p.sales,p.sort_order,p.merchant_id
            FROM ".DB_PREFIX."merchant_plate_product mpp LEFT JOIN ".DB_PREFIX."product p ON mpp.product_id = p.product_id
            LEFT JOIN ".DB_PREFIX."product_description pd ON p.product_id=pd.product_id 
            WHERE p.product_id in(".$prd_ids.") AND mpp.plate_id=".$plate_id." order by mpp.date_created desc";
    $prd_data = getData($sql);
    $merchant_info = '';
    foreach($prd_data as $key=>$value){
      $merchant_info = getRow("SELECT merchant_name FROM ".DB_PREFIX."merchant WHERE merchant_id=".$value['merchant_id']);
      if(! empty($merchant_info['merchant_name'])){
        $prd_data[$key]['merchant_name'] = $merchant_info['merchant_name'];
      }else{
        $prd_data[$key]['merchant_name'] = '';
      }
      $merchant_info = '';
      $prd_data[$key]['price'] = sprintf("%.2f",$value['price']);
      $prd_data[$key]['proxyprice'] = sprintf("%.2f",$value['proxyprice']);
    }
    return $prd_data;
  }

  /**
   *  @description  逐个删除板块内的商品
   *  @param        none
   *  @return       json串
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-23
   */
  public function delPlatePrdByAjax(){
    $data = array('code'=>0,'msg'=>"success",'data'=>true);
    if(empty($_POST['plate_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：plate_id",'data'=>false);
    }else if(empty($_POST['product_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：product_id",'data'=>false);
    }else{
      $del_sql = "DELETE FROM ".DB_PREFIX."merchant_plate_product WHERE plate_id=".$_POST['plate_id']." AND product_id=".$_POST['product_id'];
      if(!exeSql($del_sql)){
        $data = array('code'=>2,'msg'=>"数据库异常",'data'=>false);
      }
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  批量删除板块内的商品
   *  @param        none
   *  @return       json串
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-23
   */
  public function delPlatePrdsByAjax(){
    // 子错误处理
    $item_data = array();
    $data = array('code'=>0,'msg'=>"操作成功",'data'=>array());
    if(empty($_POST['plate_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：plate_id",'data'=>false);
    }else if(empty($_POST['product_id_array']) && !is_array($_POST['product_id_array'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：product_id_array",'data'=>false);
    }else{
      foreach($_POST['product_id_array'] as $key=>$value){
        $del_sql = "DELETE FROM ".DB_PREFIX."merchant_plate_product WHERE plate_id="
                    .$_POST['plate_id']." AND product_id=".$value;
        if(!exeSql($del_sql)){
          $item_data[$key] = array('num'=>$key,'notice'=>"商品已经删除过了",'product_id'=>$value);
        }
      }
      $data = array('code'=>0,'msg'=>"操作成功",'data'=>$item_data);
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  商品库页面，平台的商品，除了本商户自己的商品之外的所有商品
   *  @param        none
   *  @return       array $this->res
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-23
   */
  public function getPlatformProducts(){
    $this->getMenu();

    // 板块商品页面跳转url
    $this->res['meEditProductUrl'] = linkurl('me/editProduct');

    // 商品库页面跳转url
    $this->res['meGetPlatformProductsUrl'] = linkurl('me/getPlatformProducts');

    // 逐个从商品库中添加商品到商户的某个板块
    $this->res['meAddPrdInMeFromPlatformUrl'] = linkurl('me/addPrdInMeFromPlatform');

    // 批量从商品库中添加商品到商户的某个板块
    $this->res['meAddPrdsInMeFromPlatformUrl'] = linkurl('me/addPrdsInMeFromPlatform');

    if(empty($_GET['plate_id'])){
      echo "异常错误";exit;
    }else{
      $plate_id = $_GET['plate_id'];
      // 确认是否审核通过
      $plate_info = getRow("SELECT * FROM ".DB_PREFIX."merchant_plate WHERE plate_id=".$plate_id);
      $this->res['plate_info'] = $plate_info;
      if($plate_info['is_checked'] == 1){
        // 我的商品页面跳转url
        $this->res['meGetMeProducts'] = linkurl('me/getMeProducts');
      }

      // 板块商品总数
      $count_arr = getRow("SELECT count(*) as count FROM ".DB_PREFIX."merchant_plate_product WHERE plate_id=".$plate_id);
      $count = $count_arr['count'];
      $this->res['count'] = $count;

      // 处理平台商品库
      $page = !empty($_GET['page']) ? $_GET['page'] : 1;
      $limit = 10;
      $all_prds = getRow("SELECT count(p.product_id) as all_prds_nums FROM ".DB_PREFIX."product p 
                          LEFT JOIN ".DB_PREFIX."product_description pd ON p.product_id=pd.product_id");
      $all_prds_nums =  $all_prds['all_prds_nums'];

      $where = '';
      // 处理搜索条件：商品名
      if(!empty($_GET['product_name'])){
        $where .= "WHERE pd.name LIKE '%".htmlspecialchars($_GET['product_name'])."%'";
      }

      $sql = "SELECT p.product_id,p.image,pd.name,p.price,p.proxyprice,p.model,p.quantity,p.sales,p.sort_order,p.merchant_id
              FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON p.product_id=pd.product_id ".$where."   
              order by p.date_added desc LIMIT ".($page-1)*$limit.",".$limit;
      $products = getData($sql);
      if (is_array($products)) {
        foreach($products as $key=>$value){
          $merchant_info = getRow("SELECT merchant_name FROM ".DB_PREFIX."merchant WHERE merchant_id=".$value['merchant_id']);
          $products[$key]['merchant_name'] = $merchant_info['merchant_name'];
          $products[$key]['price'] = sprintf("%.2f",$value['price']);
          $products[$key]['proxyprice'] = sprintf("%.2f",$value['proxyprice']);
        }
        $this->res['platformProducts'] = $products;
      }else{
        $this->res['platformProducts'] = array();
      }

      // 分页处理
      if($all_prds_nums < $limit){
        // 最多一页
        $this->getPages($page,1);
      }else{
        // 至少两页
        $this->getPages($page,ceil($all_prds_nums/$limit));
      }
    }
    return $this->res;
  }

  /**
   *  @description  从商品库中单个添加商品到商户的某一个板块下
   *  @param        none
   *  @return       json字符串，前端页面以ajax post的方式请求服务器数据
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-23
   */
  public function addPrdInMeFromPlatform(){
    $data = array('code'=>0,'msg'=>"操作成功",'data'=>true);
    if(empty($_POST['plate_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：plate_id",'data'=>false);
    }else if(empty($_POST['product_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：product_id",'data'=>false);
    }else{
      $insert_data['plate_id'] = $_POST['plate_id'];
      $insert_data['product_id'] = $_POST['product_id'];
      $insert_data['date_created'] = time();
      // 处理商品所属的商户id
      $product = getRow("SELECT merchant_id FROM ".DB_PREFIX."product WHERE product_id=".$_POST['product_id']);
      if($product){
        $insert_data['merchant_id'] = $product['merchant_id'];
      }
      // 不可重复添加
      if(getRow("SELECT plate_product_id FROM ".DB_PREFIX."merchant_plate_product WHERE product_id=".$_POST['product_id']." 
                 AND plate_id=".$_POST['plate_id'])){
        $data = array('code'=>2,'msg'=>"操作失败，不可重复添加",'data'=>false);
      }else{
        if(!saveData("hb_merchant_plate_product",$insert_data)){
          $data = array('code'=>3,'msg'=>"操作失败，数据库异常",'data'=>false);
        }
      }
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  从商品库中批量添加商品到商户的某一个板块下
   *  @param        
   *  @return       
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-23
   */
  public function addPrdsInMeFromPlatform(){
    // 子错误处理
    $item_data = array();
    $data = array('code'=>0,'msg'=>"操作成功",'data'=>array());
    if(empty($_POST['plate_id'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：plate_id",'data'=>false);
    }else if(empty($_POST['product_id_array']) && !is_array($_POST['product_id_array'])){
      $data = array('code'=>1,'msg'=>"缺少必要参数：product_id_array",'data'=>false);
    }else{
      foreach($_POST['product_id_array'] as $key=>$value){
        $insert_data['plate_id'] = $_POST['plate_id'];
        $insert_data['product_id'] = $value;
        $insert_data['date_created'] = time();
        // 处理商品所属的商户id
        $product = getRow("SELECT merchant_id FROM ".DB_PREFIX."product WHERE product_id=".$value);
        if($product){
          $insert_data['merchant_id'] = $product['merchant_id'];
        }
        // 不可重复添加
        if(getRow("SELECT plate_product_id FROM ".DB_PREFIX."merchant_plate_product WHERE product_id=".$value." 
                   AND plate_id=".$_POST['plate_id'])){
          $item_data[$key] = array('num'=>$key,'notice'=>"商品已经添加过了",'product_id'=>$value);
        }else{
          if(!saveData("hb_merchant_plate_product",$insert_data)){
            $data = array('code'=>3,'msg'=>"操作失败，数据库异常",'data'=>false);
          }
        }
      }
      $data = array('code'=>0,'msg'=>"操作成功",'data'=>$item_data);
    }
    echo json_encode($data);exit;
  }

  /**
   *  @description  我的商品页面，本商户自己的商品列表
   *  @param        none
   *  @return       array $this->res
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-23
   */
  public function getMeProducts(){
    $this->getMenu();

    // 板块商品页面跳转url
    $this->res['meEditProductUrl'] = linkurl('me/editProduct');

    // 商品库页面跳转url
    $this->res['meGetPlatformProductsUrl'] = linkurl('me/getPlatformProducts');

    // 逐个从商品库中添加商品到商户的某个板块
    $this->res['meAddPrdInMeFromPlatform'] = linkurl('me/addPrdInMeFromPlatform');

    if(empty($_GET['plate_id'])){
      echo "异常错误";exit;
    }else{
      $plate_id = $_GET['plate_id'];
      // 确认是否审核通过
      $plate_info = getRow("SELECT * FROM ".DB_PREFIX."merchant_plate WHERE plate_id=".$plate_id);
      $this->res['plate_info'] = $plate_info;
      if($plate_info['is_checked'] == 1){
        // 我的商品页面跳转url
        $this->res['meGetMeProducts'] = linkurl('me/getMeProducts');
      }

      $count_arr = getRow("SELECT count(*) as count FROM ".DB_PREFIX."merchant_plate_product WHERE plate_id=".$plate_id);
      $count = $count_arr['count'];
      $this->res['count'] = $count;

      // 处理商户自家的商品列表
      $limit = 10;
      $all_prds_arr = getRow("SELECT count(product_id) as all_prds_nums FROM ".DB_PREFIX."product WHERE merchant_id=".$_SESSION['merchant_id']);
      $all_prds_nums = $all_prds_arr['all_prds_nums'];

      $page = !empty($_GET['page']) ? $_GET['page'] : 1 ;

      // 拉取商户的自家商品
      $sql = "SELECT p.product_id,p.image,pd.name,p.price,p.proxyprice,p.model,p.quantity,p.sales,p.status,p.merchant_id
              FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON p.product_id=pd.product_id   
              WHERE p.merchant_id=".$_SESSION['merchant_id']." order by p.date_added desc LIMIT ".($page-1)*$limit.",".$limit;
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
      
      // 分页处理
      if($all_prds_nums < $limit){
        // 最多一页
        $this->getPages($page,1);
      }else{
        // 至少两页
        $this->getPages($page,ceil($all_prds_nums/$limit));
      }
    }

    // 上传商品的跳转url
    $this->res['productAddIndexUrl'] = linkurl('product/addindex');

    // 编辑商品的跳转url
    $this->res['productEditIndexUrl'] = linkurl('product/editindex');

    // 启用商品的ajax请求
    $this->res['enabledMeProductUrl'] = linkurl('me/enabledMeProduct');

    // 禁用商品的ajax请求
    $this->res['forbiddenMeProductUrl'] = linkurl('me/forbiddenMeProduct');

    // 获取商品规格库存
    $this->res['addQuantityList_url'] = linkurl("me/addQuantityList");

    // 编辑库存
    $this->res['addQuantity_url'] = linkurl("me/addQuantity");

    return $this->res;
  }

  /**
   *  @description  我的商品页面，启用商品
   *  @param        
   *  @return       
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-23
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
   *  @d/t          2017-03-23
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

  /**
   *  @description  补充库存,获取商品库存或各规格库存
   *  @param        string $_POST['product_id']
   *  @return       array $options 规格数组
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-26
   */
  public function addQuantityList(){
    $product_id = !empty($_POST['product_id']) ? $_POST['product_id'] : 0;

    $sql = "select product_item_id,product_options as `option`,quantity 
            from `" .DB_PREFIX. "product_item` where product_id = '" .$product_id. "' and status = '0'";
    $option = getData($sql);

    if(!$option){
      $sql = "select quantity from `" .DB_PREFIX. "product` where product_id = '" .$product_id. "'";
      $option = getData($sql);
      $option[0]['option'] = '默认属性';
      $option[0]['product_item_id'] = '0';
    }
    echo json_encode($option);exit;
  }

  /**
   *  @description  补充库存,修改商品库存
   *  @param        string $_POST['product_id']
   *  @param        string $_POST['product_item_id']
   *  @param        string $_POST['quantity']
   *  @return       array $options 规格数组
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-26
   */
  public function addQuantity(){
    $post = $_POST;
    $item = array();
    $all_quantity = 0;
    foreach($post['product_item_id'] as $key=>$val){
      foreach($post['quantity'] as $k=>$v){
        if($key==$k){
          $item[]=array(
            'product_item_id'=>$val,
            'quantity'=>$v
          );
          $all_quantity += $v;
        }
      }
    }
    //type=1上架，type=2下架
    if($post['type'] == 1){
      $sql = "update `" .DB_PREFIX. "product` set quantity=quantity+".$all_quantity.",
              status=1 where product_id = '" .$post['product_id']. "'";
    }else{
      $sql = "update `" .DB_PREFIX. "product` set quantity=quantity+".$all_quantity.",
              status=0 where product_id = '" .$post['product_id']. "'";
    }
    exeSql($sql);

    foreach($item as $key=>$val){
      if($val['product_item_id'] > 0){
        $item_sql = "update `" .DB_PREFIX. "product_item` set quantity=quantity+"
                     .$val['quantity']." where product_item_id = '" .$val['product_item_id']. "'";
        exeSql($item_sql);
      }
    }
    echo 0;exit;
  }

  /**
   *  @description  我的商品库
   *  @return       array $options 规格数组
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-26
   */
  public function getMerchantProductList()
  {
    $this->getMenu();

    $get = $_GET;
    if(isset($get['product_name'])){
      str_replace("%2B","+",$get['product_name']);
    }

    if(!isset($get['status'])){
      $get['status'] = 1;
    }

    if(!isset($get['sort_type'])){
      $get['sort_type'] = 0;
    }

    $this->res['get'] = $get;

    $page=1;
    if(isset($_GET['page']))
      $page=$_GET['page'];

    $limit=10;
    $start=($page-1)*$limit;

    $wherestr="";

    if(isset($get['status']) &&  $get['status']== 2){
      $wherestr .= " and p.status = 1 and p.quantity < 1";
    }else if(isset($get['status']) &&  $get['status']== 3){
      $wherestr .= " and p.status = 0";
    }else{
      $wherestr .= " and p.status = 1 and p.quantity > 0";
    }

    if(isset($get['select_name']) && isset($get['select_bijiao']) && isset($get['num'])){
      switch($get['select_name']){
        case 1:
          $tiaojian = 'p.price';
          break;
        case 2:
          $tiaojian = 'p.quantity';
          break;
        case 3:
          $tiaojian = 'p.sales';
          break;
        case 4:
          $tiaojian = 'p.sort_order';
          break;
      }
      switch($get['select_bijiao']){
        case 1:
          $tiaojian .= '>';
          break;
        case 2:
          $tiaojian .= '<';
          break;
        case 3:
          $tiaojian .= '=';
          break;
      }

      $wherestr .= " and ".$tiaojian.$get['num'];
    }

    if(isset($get['category'])){
      $wherestr .= " and pc.category_id=".$get['category'];
    }
    $data = array();
    if(isset($get['product_name'])){
      $data['name'] = "%" .$get['product_name']. "%";
      $wherestr .= " and pd.name like :name";
    }

    if(isset($get['model'])){
      $wherestr .= " and p.model='".$get['model']."'";
    }

    // 处理商户
    $wherestr .= " and p.merchant_id=".$_SESSION['merchant_id'];

    if($get['sort_type'] == 1){
      $order_by = " p.sort_order desc,p.date_added desc ";
    }else if($get['sort_type'] == 2){
      $order_by = " p.sort_order asc,p.date_added desc ";
    }else{
      $order_by = " p.date_added desc,p.sort_order desc ";
    }
    $sql="SELECT p.`product_id`, p.`model`, p.`quantity`, p.`image`,
          p.`manufacturer_id`, p.`price`,  p.`points`, p.sales, p.proxyprice,
          p.marketprice, p.sort_order, p.`status`, p.brand_id, p.`date_modified`,
          pc.category_id, b.name as bname, pd.name, c.name as cname 
          FROM `hb_product` as p,hb_product_description as pd,hb_product_to_category as pc,hb_category_description as c,hb_manufacturer as b
          where  p.product_id=pd.product_id and  p.product_id=pc.product_id and pc.category_id=c.category_id
          and pc.type = 1 and p.brand_id=b.manufacturer_id $wherestr order by $order_by limit $start,$limit";

    $dt = getData($sql,$data);
    foreach($dt as $key=>$val){
      $dt[$key]['price'] = sprintf("%.2f",$val['price']);
      $dt[$key]['proxyprice'] = sprintf("%.2f",$val['proxyprice']);
      $dt[$key]['marketprice'] = sprintf("%.2f",$val['marketprice']);
      $dt[$key]['edit_url'] = linkurl('product/editIndex')."&product_id=".$val['product_id'];
      //用于列表操作选项判断
      if($get['status'] == 1){
        $dt[$key]['click_status'] = 1;
      }
      $dt[$key]['review_url'] = linkurl("product/getReviewList")."&product_id=".$val['product_id'];
    }
    $this->res['dt']=$dt;
    $this->res['cat']=$this->getCat();

    $count_arr = getRow("SELECT count(p.`product_id`) as count_nums
          FROM `hb_product` as p,hb_product_description as pd,hb_product_to_category as pc,hb_category_description as c,hb_manufacturer as b
          where  p.product_id=pd.product_id and  p.product_id=pc.product_id and pc.category_id=c.category_id
          and pc.type = 1 and p.brand_id=b.manufacturer_id $wherestr order by $order_by");
    if( $count_arr['count_nums'] < $limit ){
      $this->getPages($page,1);
    } else {
      $this->getPages($page,ceil($count_arr['count_nums']/$limit));
    }

    $this->res['url'] = linkurl("me/getMerchantProductList");
    $this->res['shelvea_url'] = linkurl("product/onOffShelves");
    $this->res['showprd_url'] = linkurl("product/showPrd");
    $this->res['addQuantityList_url'] = linkurl("me/addQuantityList");
    $this->res['addQuantity_url'] = linkurl("me/addQuantity");
    $this->res['addLikePrd_url'] = linkurl("product/addLikePrd");
    $this->res['delPrd_url'] = linkurl("product/delPrd");
    $this->res['changeSort_url'] = linkurl("product/changeSort");
    return $this->res;
  }

  /**
   *  @description  获取商品分类信息
   *  @return       array $options 规格数组
   *  @author       godloveevin@yeah.net
   *  @d/t          2017-03-26
   */
  public function getCat()
  {
    // 修改：新增和编辑商品时，商品分类信息由商户管理人员自行选择平台预先设定好的分类
    $dt=getData("select c.category_id,cd.name,c.parent_id,c.sort_order,c.image from hb_category as c 
                 left join hb_category_description as cd on c.category_id=cd.category_id 
                 where  c.status=1 and c.type=0 order by c.category_id desc");
    $cat=array();
    foreach ($dt as $key => $value) {      
      $cat[$value['category_id']]=$value;
    }
    krsort($cat);
    foreach ($cat as $key => $value) {        
      if($value['parent_id']>0)
        $cat[$value['parent_id']]['son'][]=$cat[$key];
    }
    $menu=array();    
    foreach ($cat as $key => $value) {
      if(isset($value['parent_id']) && $value['parent_id']==0)
        $menu[$key]=$value;
    }
    return $menu;
  }

}