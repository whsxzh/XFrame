<?php
include 'autoload.php';
/**
 * 邀请码模块
 * User: lcb
 * Date: 2017/6/2
 */
class invite extends base
{
    public function setInviteCodeId(){
        $inviteCodeId = isset($_REQUEST['inviteCodeId']) && $_REQUEST['inviteCodeId'] ? $_REQUEST['inviteCodeId'] : '';
        $appType = isset($_REQUEST['appType']) && $_REQUEST['appType'] ? $_REQUEST['appType'] : 'common';
        if(!$inviteCodeId){
            $this->error(2, '邀请码Id必须填写');
        }
        $inviteCodeInfo = getRow('SELECT date_available,invitecode,customer_id,invitecode_id,merchant_id FROM hb_invitecode WHERE `invitecode_id`='.(int)$inviteCodeId. ' AND `status`=0 LIMIT 1');
        if(!$inviteCodeInfo){
            $this->error(3, '邀请码不存在或者无效');
        }
        $_SESSION['default'][$appType.'_invitecode_id'] = $inviteCodeInfo;
        if(isset($_REQUEST['debug']) && 'debug' == $_REQUEST['debug']){
            var_dump($_SESSION['default'][$appType.'_invitecode_id']);
            exit();
        }
        $this->success([], 'success','', false);
    }

    /**
     * 请求成功返回数据
     * @param        $returnData
     * @param string $msg
     * @param string $backUrl
     * @return array
     */
    public function success($returnData = [], $msg = 'success', $backUrl = '', $return = true)
    {
        $this->res = [
            'retcode' => 0,
            'msg'     => $msg,
            'data'    => $returnData,
            'url'     => $backUrl,
        ];
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
    public function error($errorCode = -1, $msg = '', $backUrl = '', $return = false)
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