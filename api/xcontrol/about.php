<?php
include 'autoload.php';

/**
 * User: lcb
 * Date: 2017/6/8
 */
class about
{
    /**
     * 关于我们
     * 返回相对地址 方便app端根据测试、线上环境自动匹配域名
     */
    public function index()
    {
        $scheme = isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] : 'http';
        $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : 'haiqihuocang.cn';
        $path = isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : '';
        if(!$path){
            $path = isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : '';
        }
        $path = str_ireplace('/api/index.php', '', $path);
        $url = $scheme.'://'.$host.'/'.ltrim($path, '/');
        exit(json_encode([
            'retcode' => 0,
            'msg'     => 'success',
            'data'    => [
                'aboutMeUrl'       => $url.'/web/app/about.html',
                'userAgreementUrl' => $url.'/web/app/user.html'
            ]
        ]));
    }
}