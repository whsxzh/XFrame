<?php
	
	function send($mobile,$content){
		$username = "clyllshql";
		$pwd = "ydrgiic6";
		$password = md5($username."".md5($pwd));
		$mobile = $mobile;//"18782559175";
		$content = $content;//"您的验证码是：123456【嗨企货仓】";
		$url = "http://sms-cly.cn/smsSend.do?";
	
		$param = http_build_query(
			array(
				'username'=>$username,
				'password'=>$password,
				'mobile'=>$mobile,
				'content'=>@iconv("GB2312","UTF-8",$content)
			)
		);
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$param);
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
		
?>