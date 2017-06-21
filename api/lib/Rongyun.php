<?php
	class Rongyun{
		private $appKey="0vnjpoadnni7z" ;                //appKey//测试  pgyu6atqyymvu
	    private $appSecret="GO1qA5EO6ycw6V" ;             //secret//测试  TPuvcGzqvAU
	    const   SERVERAPIURL = 'http://api.cn.ronghub.com';    //IM服务地址
	    const   SMSURL = 'http://api.sms.ronghub.com';          //短信服务地址
	    private $format="json" ;                //数据格式 json/xml
		/**
	     * 获取融云的token值
	     */
	    public function getRctoken($userid,$name,$img){
	        $appKey = "0vnjpoadnni7z";                //appKey  pgyu6atqyymvu 测试环境  正式环境 0vnjpoadnni7z
	        $appSecret = "GO1qA5EO6ycw6V";             //secret  TPuvcGzqvAU 测试环境  正式环境 GO1qA5EO6ycw6V
	        
	        // $appKey = "0vnjpoadnni7z";                //appKey  pgyu6atqyymvu 
	        // $appSecret = "GO1qA5EO6ycw6V";             //secret  TPuvcGzqvAU

	       // $r=new ControllerApiSend($appKey,$appSecret,"json");
	       $userId=@intval($userid);
	       $name=$name;
	       $img=$img;

	       $p=$this->getToken($userId,$name,$img);
	       $json=json_decode($p)->token;
	       return $json;
	    }
	    /**
	     * 获取 Token 方法
	     * @param $userId   用户 Id，最大长度 32 字节。是用户在 App 中的唯一标识码，必须保证在同一个 App 内不重复，重复的用户 Id 将被当作是同一用户。
	     * @param $name     用户名称，最大长度 128 字节。用来在 Push 推送时，或者客户端没有提供用户信息时，显示用户的名称。
	     * @param $portraitUri  用户头像 URI，最大长度 1024 字节。
	     * @return json|xml
	     */
	    public function getToken($userId, $name, $portraitUri) {
	        try{
	            if(empty($userId))
	                throw new Exception('用户 Id 不能为空');
	            if(empty($name))
	                throw new Exception('用户名称 不能为空');
	            if(empty($portraitUri))
	                throw new Exception('用户头像 URI 不能为空');
	            $ret = $this->curl('/user/getToken',array('userId'=>$userId,'name'=>$name,'portraitUri'=>$portraitUri));
	            if(empty($ret))
	                throw new Exception('请求失败');
	            return $ret;
	        }catch (Exception $e) {
	            print_r($e->getMessage());
	        }
	    }
	    // public function curlPost($url,$postFields){
	    //     $postFields = http_build_query($postFields);
	    //     $ch = curl_init ();
	    //     curl_setopt ( $ch, CURLOPT_POST, 1 );
	    //     curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	    //     curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	    //     curl_setopt ( $ch, CURLOPT_URL, $url );
	    //     curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
	    //     $result = curl_exec ( $ch );
	    //     curl_close ( $ch );
	    //     return $result;
	    // }
	    
	    /**
	     * 创建http header参数
	     * @param array $data
	     * @return bool
	     */
	    private function createHttpHeader() {
	        $nonce = mt_rand();
	        $timeStamp = time();
	        $sign = sha1($this->appSecret.$nonce.$timeStamp);
	        return array(
	            'RC-App-Key:'.$this->appKey,
	            'RC-Nonce:'.$nonce,
	            'RC-Timestamp:'.$timeStamp,
	            'RC-Signature:'.$sign,
	        );
	    }

	    /**
	     * 重写实现 http_build_query 提交实现(同名key)key=val1&key=val2
	     * @param array $formData 数据数组
	     * @param string $numericPrefix 数字索引时附加的Key前缀
	     * @param string $argSeparator 参数分隔符(默认为&)
	     * @param string $prefixKey Key 数组参数，实现同名方式调用接口
	     * @return string
	     */
	    private function build_query($formData, $numericPrefix = '', $argSeparator = '&', $prefixKey = '') {
	        $str = '';
	        foreach ($formData as $key => $val) {
	            if (!is_array($val)) {
	                $str .= $argSeparator;
	                if ($prefixKey === '') {
	                    if (is_int($key)) {
	                        $str .= $numericPrefix;
	                    }
	                    $str .= urlencode($key) . '=' . urlencode($val);
	                } else {
	                    $str .= urlencode($prefixKey) . '=' . urlencode($val);
	                }
	            } else {
	                if ($prefixKey == '') {
	                    $prefixKey .= $key;
	                }
	                if (is_array($val[0])) {
	                    $arr = array();
	                    $arr[$key] = $val[0];
	                    $str .= $argSeparator . http_build_query($arr);
	                } else {
	                    $str .= $argSeparator . $this->build_query($val, $numericPrefix, $argSeparator, $prefixKey);
	                }
	                $prefixKey = '';
	            }
	        }
	        return substr($str, strlen($argSeparator));
	    }
	    /**
	     * 发起 server 请求
	     * @param $action
	     * @param $params
	     * @param $httpHeader
	     * @return mixed
	     */
	    public function curl($action, $params,$contentType='urlencoded',$module = 'im',$httpMethod='POST') {
	        switch ($module){
	            case 'im':
	                $action = self::SERVERAPIURL.$action.'.'.$this->format;
	                break;
	            case 'sms':
	                $action = self::SMSURL.$action.'.json';
	                break;
	            default:
	                $action = self::SERVERAPIURL.$action.'.'.$this->format;
	        }
	        $httpHeader = $this->createHttpHeader();
	        $ch = curl_init();
	        if ($httpMethod=='POST' && $contentType=='urlencoded') {
	            $httpHeader[] = 'Content-Type:application/x-www-form-urlencoded';
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->build_query($params));
	        }
	        if ($httpMethod=='POST' && $contentType=='json') {
	            $httpHeader[] = 'Content-Type:Application/json';
	            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params) );
	        }
	        if ($httpMethod=='GET' && $contentType=='urlencoded') {
	            $action .= strpos($action, '?') === false?'?':'&';
	            $action .= $this->build_query($params);
	        }
	        curl_setopt($ch, CURLOPT_URL, $action);
	        curl_setopt($ch, CURLOPT_POST, $httpMethod=='POST');
	        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
	        curl_setopt($ch, CURLOPT_HEADER, false);
	        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        $ret = curl_exec($ch);
	        if (false === $ret) {
	            $ret =  curl_errno($ch);
	        }
	        curl_close($ch);
	        return $ret;
	    }
	}
?>