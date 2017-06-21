<?php

/**
 * User: lcb
 * Date: 2017/5/26
 */
class ErrorCode
{
    const PARAM_ERROR    = 1;
    const PARAM_IS_EMPTY = 2;
    const MUST_BE_LOGIN  = 10;

    const REGISTER_ERROR      = 300;
    const LOGIN_ERROR         = 310;
    const SMS_CODE_ERROR      = 320;
    const SMS_CODE_SEND_ERROR = 321;
    const IMG_CODE_ERROR      = 330;

    const ILLEGAL_REQUEST   = 1180;
    const ILLEGAL_TELEPHONE = 1300;


    /**
     * 根据错误代号获取消息
     * @param $errorCode
     * @return string
     */
    public static function getErrorMessage($errorCode)
    {
        $arrMessages = [
            1 => '参数错误',
            2 => '参数不能为空',

            10 => '必须登录',


            300 => '注册失败',
            310 => '登录失败',
            320 => '短信验证码错误',
            321 => '短信验证码发送失败',
            330 => '图片验证码错误',

            1180 => '非法请求',
            1300 => '非法手机号',
        ];

        return isset($arrMessages[$errorCode]) && $arrMessages[$errorCode] ? $arrMessages[$errorCode] : '';
    }

}