<?php

//面向对象的control 类

class base
{
	var $res=array("retcode"=>0,'msg'=>'success');
	var $customer_id=0;
	var $passkey="";

	function __construct($customer_type = 1)
	{
		//$customer_type=1需要验证customerid和passkey，$customer_type=2不需要验证customerid和passkey，
		if($customer_type == 1){
			$a=isset($_POST["passkey"]) && isset($_POST["customerid"]);
			$b=(isset($_SESSION["default"]["customer_id"]) || isset($_SESSION["default"]["customerid"])) && isset($_SESSION["default"]["passkey"]);
			$d=isset($_POST["unionid"]) || isset($_POST["openid"]);
			$e=isset($_COOKIE["passkey"]) && isset($_COOKIE["customer_id"]);
			$c=$a || $b || $d || $e;

			if($c)
			{
				// unset($_SESSION["default"]);
				$customer_id="";
				if(isset($_POST["customerid"])){
					$customer_id = $_POST["customerid"];
				}else if(isset($_SESSION["default"]["customer_id"])){
					$customer_id=$_SESSION["default"]["customer_id"];
				}else if(isset($_COOKIE["customer_id"])){
					$customer_id=$_COOKIE["customer_id"];
				}else if(isset($_SESSION["default"]["customerid"])){
                    $customer_id=$_SESSION["default"]["customerid"];
                }
				if(isset($_POST["passkey"])){
					$req_key=@$_POST["passkey"];
				}else if(isset($_SESSION["default"]["passkey"])){
					$req_key=$_SESSION["default"]["passkey"];
				}else if(isset($_COOKIE["passkey"])){
					$req_key=$_COOKIE["passkey"];
				}
				

				$customer_info=getRow("SELECT * FROM hb_customer where customer_id='".(int)$customer_id."'");
				//查询是否有信息  unionid  cgl  2017-4-6
				if(isset($_POST["unionid"]) || isset($_POST["openid"])){
					//是否缓存
					//$cache_user=getCache("user".$_POST["openid"]);
					// if(!empty($_COOKIE["customer_id"])){
					// 	// $users=json_decode($cache_user,"json");
					// 	$customer_info=getRow("SELECT * FROM hb_customer where customer_id = '".$_COOKIE["customer_id"]."' ");	
					// }else{
					// 	if(!empty($_COOKIE["openid"]) && !empty($_COOKIE["unionid"])){
					// 		$_POST["unionid"]=$_COOKIE["unionid"];
					// 		$_POST["openid"]=$_COOKIE["openid"];
					// 	}
					// 	$customer_info=getRow("SELECT * FROM hb_customer where unionid='".$_POST["unionid"]."' or wechat_openid='".$_POST["openid"]."' or wechat_openid_share = '".$_POST["openid"]."' ");	
					// }
					
					if(!empty($customer_info)){
						$req_key=sha1(md5($customer_info["customer_id"].$customer_info["password"].$customer_info["salt"]));
						$customer_id=$customer_info["customer_id"];
					}
				}

				if(!empty($customer_info)){
					$cus_key=sha1(md5($customer_info["customer_id"].$customer_info["password"].$customer_info["salt"]));
					//给请求的参数赋值
					$_POST["customerid"]=$customer_id;
					//判断加密的字符串是否正确
					if($req_key!=$cus_key){
						if($_SERVER["QUERY_STRING"]=="m=groupbuylist&act=index"){
							return $this->res;
						}else{
							//不通过   禁止访问API
							$this->res["retcode"]=7000;
							$this->res["msg"]='用户信息不匹配';
							return $this->res;
						}
					}else{
						$this->passkey=$req_key;
						$this->customer_id=$customer_id;
						$_SESSION["default"]["customer_id"]=$customer_id;
						$_SESSION["default"]["passkey"]=$req_key;
					}
				}else{
					$this->res["retcode"]=1101;
					$this->res["msg"]='用户不存在';
					unset($_SESSION["default"]["customer_id"]);
					return $this->res;
				}
			}
			else
			{
				unset($_SESSION["default"]["customer_id"]);
				//参数不正确
				$this->res["retcode"]=1000;
				$this->res["msg"]='参数错误';
				return $this->res;
			}
		}else{
			$this->customer_id=0;
			$this->res = array(
				'retcode' 	=> 0,
				'msg' 		=> 'success'
			);
		}

   	}

	function who()
	{

		echo "I am ok,good!";
	}


    /**
     * 检查是否是post请求
     */
    protected function isPost()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' != $_SERVER['REQUEST_METHOD']) {
            $this->error(ErrorCode::ILLEGAL_REQUEST);
        }
    }

    /**
     * 请求成功返回数据
     * @param        $returnData
     * @param string $msg
     * @param string $backUrl
     * @return array
     */
    protected function success($returnData = [], $msg = 'success', $backUrl = '', $return = true)
    {
        /*$this->res = [
            'retcode' => 0,
            'msg'     => $msg,
            'data'    => $returnData,
            'url'     => $backUrl,
        ];*/
        if(!isset($returnData['retcode']) || 0 != $returnData['retcode']){
            $returnData['retcode'] = 0;
        }
        if(!isset($returnData['msg'])){
            $returnData['msg'] = $msg ? $msg : 'success';
        }
        $this->res = $returnData;
        if ($return) {
            return $this->res;
        }
        exit(json_encode($this->res));
    }

    /**
     * 请求失败返回
     * @param int    $errorCode
     * @param string $msg
     * @param string $backUrl
     * @param string $return 错误了 默认就退出了
     * @return array
     */
    protected function error($errorCode = -1, $msg = '', $backUrl = '', $return = false)
    {
        $this->res = [
            'retcode' => $errorCode,
            'msg'     => ErrorCode::getErrorMessage($errorCode) . $msg,
            'url'     => $backUrl,
        ];
        if ($return) {
            return $this->res;
        }
        exit(json_encode($this->res));
    }


}