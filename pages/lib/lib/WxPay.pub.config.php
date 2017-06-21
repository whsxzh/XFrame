<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wxa0133e6cf029e7b6';//wx1b1d371d37fa578d
	//受理商ID，身份标识
	const MCHID = '1416755602';//1396079002
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = '2dxfdsafdsf2323A2dsAdpxxzYT2dQlx';//1jthgvugvdkkKYGUt3aeeRFTFGdd8e6p
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = '3cbc7c1d819375c1db80ccb0f68c15ed';//d4be8620a62c6022ff067c1c525c8dfc
	
	//=======【JSAPI路径设置】===================================
	//获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
	const JS_API_CALL_URL = 'http://iwant-u.cn/catalog/wxpay.php';//'http://192.168.0.88/high/login.php';TEST_IP.
	
	//=======【证书路径设置】=====================================
	//证书路径,注意应该填写绝对路径     仅支付不需要
//	const SSLCERT_PATH = '/www/web/iwant/public_html/highup/admin/controller/sale/cert/apiclient_cert1.pem';
//	const SSLKEY_PATH = '/www/web/iwant/public_html/highup/admin/controller/sale/cert/apiclient_key1.pem';
	const SSLCERT_PATH = '/home/wwwroot/hi2017/iwantcdm/controller/sale/cert/apiclient_cert1.pem';
	const SSLKEY_PATH = '/home/wwwroot/hi2017/iwantcdm/controller/sale/cert/apiclient_key1.pem';

	
	//=======【异步通知url设置】===================================
	//异步通知url，商户根据实际开发过程设定
	const NOTIFY_URL = 'http://iwant-u.cn/share_wx_notify.php';//TEST_IP.

	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;
}
	
?>