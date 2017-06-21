<?php
/**
 * 描述 : 公共API SDK
 * 作者 : zxx 2017-4-13
 */
class openSdk {
    public static $lastData = array();                                                                                  //最后请求的数据
    private static $info = array(                                                                                       //配置信息
        'name'  => 'HT_13777817587',                                            //api的测试帐号密码，先用这个，测好后，我会给你们正式的API帐号密码
        'key'   => 'e0d7d122196bacbca5f893c645a12753',
        'url'   => 'http://api.haitungongyinglian.hk/index.php/',                           //正式地址
        'debug' => true
    );
    private static $token = array(                                                                                      //sdk维护的令牌
        'time' => 0
    );

    /**
     * 描述 : 自动初始化
     * 作者 : Edgar.lee
     */
    public static function init() {
        self::$token += self::$info;
    }

    /**
     * 描述 : 请求API接口
     * 作者 : Edgar.lee
     */
    public static function &api($type, $data = null) {
        $token = &self::$token;

        if( time() - 300 > $token['time'] ) {                                                                           //令牌过期
            $token['name'] = self::$info['name'];
            $token['key'] = self::$info['key'];
            $temp = self::request('token');

            if( $temp['status'] === '20000' ) {
                if( isset($temp['data']['fileName']) ) {
                    $file = self::getTempPath(false) .'/'. $temp['data']['fileName'];                                   //临时文件路径
                    $temp = json_decode(file_get_contents($file), true);                                                //得到解析数据
                    unlink($file);                                                                                      //删除临时文件
                } else {
                    $temp = &$temp['data'];
                }
                $token = $temp + $token;                                                                                //更新申请帐号
            } else {
                return $temp;
            }
        }

        $index = &self::request($type, $data);                                                                          //正式请求
        $token['time'] = $index['time'];                                                                                //更新过期时间
        return $index;
    }

    /**
     * 描述 : 推送端安全校验
     * 返回 :
     *      true=通过, false=失败
     * 作者 : Edgar.lee
     */
    public static function check() {
        $token = &self::$token;
        $token['debug'] = !empty($_GET['debug']);

        if(
            //$_SERVER['REMOTE_ADDR'] === gethostbyname(parse_url($token['url'], PHP_URL_HOST)) &&                      //IP 比对
            isset($_GET['md5']) && isset($_GET['type']) && isset($_GET['time']) && isset($_GET['name']) &&              //必须结构存在
            $_GET['name'] === $token['name'] &&                                                                         //用户名比对
            $_GET['time'] <= $_SERVER['REQUEST_TIME'] + 300 &&                                                          //未超时
            $_GET['time'] >= $_SERVER['REQUEST_TIME'] - 300                                                             //未过期
        ) {
            $data = isset($_POST['data']) ? self::slashesDeep($_POST['data'], 'stripslashes') : '';                     //提前post数据
            $md5 = &$_GET['md5'];
            unset($_GET['md5']);
            ksort($_GET);

            if( md5(join($_GET) . $token['key'] . $data) === $md5 ) {
                $_POST = (array)self::crypt($data);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 描述 : 对接服务端的令牌请求
     * 作者 : Edgar.lee
     */
    public static function token() {
        $file = self::getTempPath(true);
        file_put_contents($file, json_encode($_POST));

        return array(
            'status' => '20000',
            'data'   => array(
                'fileName' => basename($file)
            )
        );
    }

    /**
     * 描述 : 加密/解密数据
     *     &data : 字符串=解密信息,数组=加密数据
     * 作者 : Edgar.lee
     */
    public static function &crypt(&$data) {
        $token = &self::$token;
        if( is_string($data) ) {                                                                                        //解密数据
            empty($token['debug']) && $data = self::rc4(self::$token['key'], $data, false);
            $result = json_decode($data, true);
        } else {                                                                                                        //加密数据
            self::$token['time'] && $data['time'] = time();
            $result = json_encode($data);
            empty($token['debug']) && $result = self::rc4(self::$token['key'], $result, true);
        }
        return $result;
    }

    /**
     * 描述 : 发送请求数据
     * 作者 : Edgar.lee
     */
    private static function &request($type, &$post = null) {
        $last = &self::$lastData;
        $token = &self::$token;
        $data = array(                                                                                                  //生成加密post数据
            'data' => $post === null ? '' : self::crypt($post)
        );
        $get = array(                                                                                                   //按键值排序的get
            'debug' => 'on',
            'name'  => &$token['name'],
            'time'  => time(),
            'type'  => &$type
        );
        if( empty($token['debug']) ) unset($get['debug']);
        $get['md5'] = md5(join($get) . $token['key'] . $data['data']);
        $last['request'] = array('get' => &$get, 'post' => $data);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $token['url'] . '?' . http_build_query($get));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $post === null || curl_setopt($ch,CURLOPT_POSTFIELDS, $data);                                                   //加密的POST数据
        $last['response'] = $data = curl_exec($ch);
        curl_close($ch);

        $data = $data[0] === '{' ? json_decode($data, true) : self::crypt($data);
        empty($data['status']) && trigger_error('A response error has been triggered : ' . print_r($last, true));       //错误,作为记录使用
        return $data;
    }

    /**
     * 描述 : 深度加/删反斜杠
     * 参数 :
     *     &data : 指定替换的数组
     *      func : addslashes(默认)=添加反斜杠, stripslashes=删除反斜杠
     * 作者 : Edgar.lee
     */
    private static function &slashesDeep(&$data, $func = 'addslashes') {
        $waitList = array(&$data);                                                                                      //待处理列表

        do {
            $wk = key($waitList);
            $wv = &$waitList[$wk];
            unset($waitList[$wk]);

            if( is_array($wv) ) {
                $result = array();                                                                                      //结果列表
                foreach($wv as $k => &$v) {
                    $result[$func($k)] = &$v;
                    $waitList[] = &$v;
                }
                $wv = $result;
            } else if( is_string($wv) ) {
                $wv = $func($wv);
            }
        } while( !empty($waitList) );

        return $data;
    }

    /**
     * 描述 : 获取临时路径
     * 参数 :
     *      isFile : 是否生成临时文件,true(默认)=返回临时文件路径,false=返回临时文件夹路径
     * 返回 :
     *      返回路径
     * 作者 : Edgar.lee
     */
    private static function getTempPath($isFile = true) {
        static $tempDir = null;
        if( $tempDir === null )
        {
            if( function_exists('sys_get_temp_dir') )                                                           //php > 5.2.1
            {
                $tempDir = sys_get_temp_dir();
            } else {
                ($tempDir = getenv('TMP')) || ($tempDir = getenv('TEMP')) || ($tempDir = getenv('TMPDIR'));     //读取相关环境变量
                if( !$tempDir && $tempDir = tempnam(__FILE__,'') )                                              //环境变量读取失败,尝试创建临时文件
                {
                    is_file($tempDir) && unlink($tempDir);
                    $tempDir = dirname($tempDir);
                }
            }
        }

        return rtrim(strtr($isFile ? tempnam($tempDir, '') : $tempDir, '\\', '/'), '/');
    }

    /**
     * 描述 : 文本加密与解密
     * 参数 :
     *      key    : 加密解密文本密码
     *      txt    : 需要加解密的文本
     *      base64 : 加密解密标识,null(默认)=无编码加密解密, true=base64加密,false=base64解密
     *      level  : 加密级别 1=简单线性加密, >1 = RC4加密数字越大越安全越慢, 默认=256
     * 返回 :
     *      加密或解密后的明码字符串
     * 演示 :
     *      rc4('密码', '测试', true);
     *      随机加密字符,如:Yegw4WXcMOth/DvC
     *      rc4('密码', rc4('密码', '测试', true), false);
     *      测试
     * 作者 : Edgar.lee
     */
    private static function rc4($pwd, $txt, $base64 = null, $level = 256) {
        $base64 === false && $txt = base64_decode($txt);                                                                //base64解密
        $result = '';
        $kL = strlen($pwd);
        $tL = strlen($txt);

        if( $level > 1 ) {                                                                                              //非线性加密
            for ($i = 0; $i < $level; ++$i) {
                $key[$i] = ord($pwd[$i % $kL]);
                $box[$i] = $i;
            }

            for ($j = $i = 0; $i < $level; ++$i) {
                $j = ($j + $box[$i] + $key[$i]) % $level;
                $tmp = $box[$i];
                $box[$i] = $box[$j];
                $box[$j] = $tmp;
            }

            for ($a = $j = $i = 0; $i < $tL; ++$i) {
                $a = ($a + 1) % $level;
                $j = ($j + $box[$a]) % $level;

                $tmp = $box[$a];
                $box[$a] = $box[$j];
                $box[$j] = $tmp;

                $k = $box[($box[$a] + $box[$j]) % $level];
                $result .= chr(ord($txt[$i]) ^ $k);
            }
        } else {                                                                                                        //简单线性加密
            for($i = 0; $i < $tL; ++$i) {
                $result .= $txt[$i] ^ $pwd[$i % $kL];
            }
        }

        $base64 && $result = base64_encode($result);                                                                    //base64加密
        return $result;
    }
}
openSdk::init();