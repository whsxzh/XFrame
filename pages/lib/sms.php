<?php
/**
 * 发短信    cgl  2017-2-27  封装
 */
$appKey    = "0vnjpoadnni7z";                //appKey//测试  pgyu6atqyymvu
$appSecret = "GO1qA5EO6ycw6V";             //secret//测试  TPuvcGzqvAU
const   SERVERAPIURL = 'http://api.cn.ronghub.com';    //IM服务地址
const   SMSURL       = 'http://api.sms.ronghub.com';          //短信服务地址
$format = "json";                //数据格式 json/xml

class Sms
{
    //下发 短信  旧接口
    function sendSingleMt($mobile, $msg)
    {
        // 在这里拦截转发到新接口 6-1 by lcb
        return $this->send($mobile, $msg, true);

        //预定义参数，参数说明见文档
        // $msg="【嗨企货仓】".$msg;
        $host    = "si.800617.com:4400";
        $mobile  = $mobile;
        $message = urlencode(iconv("UTF-8", "GB2312", "$msg"));
        // $message="【嗨企货仓】".$message;

        $request = "/SendLenSms.aspx?un=hzssdf-1&pwd=5ffcc9&mobile=" . $mobile . "&msg=" . $message;
        $content = $this->_dopostrequest($host, 80, $request);
        $data    = array("mobile" => $mobile, "msg" => $message, "results" => $content);
        // $this->DBW->insert('lee_sms_log', $data);
        $res = json_encode(simplexml_load_string($content));

        if (@$res["Result"]) {
            //成功
            return json_encode(array("retcode" => 0));
        } else {
            //失败
            return json_encode(array("retcode" => 1000));
        }

        // $this->response->addHeader('Content-Type: application/json');
        // $this->response->setOutput(json_encode($json));
    }

    //下发 短信
    function sendTophone($customerid, $msg)
    {
        //预定义参数，参数说明见文档
        $host     = "si.800617.com:4400";
        $customer = getRow("select * from hb_customer where customer_id = '" . $customerid . "' ");
        $mobile   = $customer["telephone"];
        $mobile   = $mobile;
        $message  = urlencode(iconv("UTF-8", "GB2312", "$msg"));
        $message  = "【嗨企货仓】" . $message;

        // 在这里拦截转发到新接口 6-1 by lcb
        return $this->send($mobile, "【嗨企货仓】" . $msg, true);

        $request = "/SendLenSms.aspx?un=hzssdf-1&pwd=5ffcc9&mobile=" . $mobile . "&msg=" . $message;
        $content = $this->_dopostrequest($host, 80, $request);
        $data    = array("mobile" => $mobile, "msg" => $message, "results" => $content);
        // $this->DBW->insert('lee_sms_log', $data);
        $res = json_encode(simplexml_load_string($content));

        if (@$res["Result"]) {
            //成功
            return json_encode(array("retcode" => 0));
        } else {
            //失败
            return json_encode(array("retcode" => 1000));
        }

        // $this->response->addHeader('Content-Type: application/json');
        // $this->response->setOutput(json_encode($json));
    }

    function _dopostrequest($host, $port, $request)
    {
        $httpGet = "GET " . $request . " HTTP/1.1\r\n";
        $httpGet .= "Host: $host\r\n";
        $httpGet .= "Connection: Close\r\n";
        $httpGet .= "Content-type: text/plain\r\n";
        $httpGet .= "Content-length: " . strlen($request) . "\r\n";
        $httpGet .= "\r\n";
        $httpGet .= $request;
        $httpGet .= "\r\n\r\n";

        return $this->_httpsend($host, $port, $httpGet);
    }

    function _httpsend($host, $port, $request)
    {
        $result = "";
        $fp     = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($fp) {
            fwrite($fp, $request);
            while (!feof($fp)) {
                $result .= fread($fp, 1024);
            }
            fclose($fp);
        } else {
            //超时了
            return json_encode(array("retcode" => 1207));
            // return ErrorInfo::$smsGateWayTimeout;//超时标志
        }
        list($header, $foo) = explode("\r\n\r\n", $result);
        list($foo, $content) = explode($header, $result);
        $content = str_replace("\r\n", "", $content);

        return $content;
    }

    //by cgl new
    /**
     * >0	成功,系统生成的任务id或自定义的任务id
        0	失败
        -1	用户名或者密码不正确
        -2	必填选项为空
        -3	短信内容0个字节
        -5	余额不够
        -10	用户被禁用
        -11	短信内容超过500字
        -12	无扩展权限（ext字段需填空）
        -13	IP校验错误
        -14	内容解析异常
        -990	未知错误
     * @param $mobile  多个手机号使用半角逗号分隔
     * @param $content
     * @param $returnJson  true false  是否返回json结果
     * @param $appType  app类型  api  iwantcdm catalog  highup ...
     * @return mixed
     */
    function send($mobile, $content, $returnJson = false, $appType = '')
    {
        self::smsLog(func_get_args());
        $username = "clyllshql";
        $pwd      = "ydrgiic6";
        $password = md5($username . "" . md5($pwd));
        $mobile   = $mobile;//"18782559175";
        //$content  = $content;//"您的验证码是：123456【嗨企货仓】";
        $content  = urlencode($content);
        $url      = "http://sms-cly.cn/smsSend.do?";

        $param = http_build_query(
            array(
                'username' => $username,
                'password' => $password,
                'mobile'   => $mobile,
                'content'  => $content
            )
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $result = curl_exec($ch);
        curl_close($ch);

        self::smsLog('end:'.$result);

        if($returnJson){
            if($result && $result>0){
                return json_encode(['retcode'=>0]);
            }else{
                return json_encode(['retcode'=>1000]);
            }
        }

        return $result;
    }

    protected static function smsLog($message){
        @file_put_contents(__DIR__.'/smsLog_'.date('ym').'.txt','['.date('d H:i:s').']'.(is_array($message) ? var_export($message, true) : $message)."\n",FILE_APPEND);
    }
}

?>