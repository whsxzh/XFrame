<?php
include 'autoload.php';

/**
 * 用户管理模块
 * User: lcb
 * Date: 2017/6/2
 */
class user extends base
{
    /**
     * 登录
     * 1 手机号 存在 +验证码登录  不存在 +验证码进入注册流程 注册成功之后登录
     * 2、手机号 + 密码   存在登录成功 否则登录失败
     * @param telephone  手机号
     * @param pwd        密码
     * @param from       从什么业务过来
     * @param binding    是否绑定第三方登录
     * @param loginType  账号类型   默认是 手机号+验证码登录  如果没有密码 也走这种方式验证登录流程  5 手机号+密码 登录
     * @param reqType    请求类型   默认是登录   5 注册  不传该参数 会自动判断 有该用户自动进入登录  没有就进入注册
     * @param validate   短信验证码
     * @param inviteid   邀请码id
     */
    public function login()
    {
        Helper::writeLog(var_export($_REQUEST, true));
        $account         = isset($_POST['telephone']) ? $_POST['telephone'] : '';  // 手机号
        $pwd             = isset($_POST['pwd']) && $_POST['pwd'] ? $_POST['pwd'] : '';  // 密码
        $from            = isset($_POST['from']) ? $_POST['from'] : '';  // 从什么业务过来的
        $binding         = isset($_POST['binding']) ? $_POST['binding'] : '';  // 是否第三方绑定登录
        $loginType       = isset($_POST['loginType']) ? $_POST['loginType'] : 1;
        $validate        = isset($_POST['validate']) ? $_POST['validate'] : '';
        $inviteId        = isset($_POST['inviteid']) ? $_POST['inviteid'] : '';
        $url             = isset($_POST['url']) && $_POST['url'] ? urldecode($_POST['url']) : '';
        $groupBuyBackUrl = isset($_POST['groupBuyBackUrl']) && $_POST['groupBuyBackUrl'] ? urldecode($_POST['groupBuyBackUrl']) : '';

        if (empty($account)) {
            $this->error(2);
        }

        if (!$account || !$this->checkTelephone($account)) {
            $this->error(ErrorCode::ILLEGAL_TELEPHONE);
        }

        if('null' == strtolower($inviteId)){
            $inviteId = '';
        }

        /**
        'common_invitecode_id' =>
        array (size=5)
            'date_available' => string '0' (length=1)
            'invitecode' => string '雅洁' (length=6)
            'customer_id' => string '3030' (length=4)
            'invitecode_id' => string '196' (length=3)
            'merchant_id' => null
         */
        $sessionHasInviteCode = false;
        if (!$inviteId && isset($_SESSION['default']) && isset($_SESSION['default']['common_invitecode_id'])) {
            $sessInviteCode = $_SESSION['default']['common_invitecode_id'];
            if (isset($sessInviteCode['invitecode_id']) && $sessInviteCode['invitecode_id']) {
                $inviteId             = $sessInviteCode['invitecode_id'];
                $sessionHasInviteCode = true;
            }
            unset($sessInviteCode);
        }

        if('null' == strtolower($inviteId)){
            $inviteId = '';
        }

        $customerM    = CustomerModel::getInstance();
        // 分享页面 proxyid  lcb 6-12
        if (!$inviteId && isset($_SESSION['default']) && isset($_SESSION['default']['proxyid']) && $_SESSION['default']['proxyid']) {
            $shareCustomerId = $_SESSION['default']['proxyid'];
            // 查询用户信息
            $shareCustomerInfo = $customerM->getCustomerByCustomerId($shareCustomerId);
            if (!$shareCustomerInfo) {
                unset($shareCustomerId);
            } else if ($shareCustomerInfo['status'] != 1) {
                unset($shareCustomerId, $shareCustomerInfo);
            } else {
                // 查询邀请码  TODO 需要判断邀请码的有效期？
                $shareInviteCodeInfo = getRow('select invitecode_id,date_available,end_date from hb_invitecode where `status`=0 and customer_id=' . intval($shareCustomerId) . ' limit 1');
                if (isset($shareInviteCodeInfo['invitecode_id']) && $shareInviteCodeInfo['invitecode_id']) {
                    $inviteId = $shareInviteCodeInfo['invitecode_id'];
                }
            }
        }

        $resultLogin = $resultRegister = false;
        // 先判断是否存在这个用户 存在就走登录  不存在就走注册
        $customerInfo = $customerM->getCustomerByTelephone($account);
        if ($customerInfo) {
            if (5 == $loginType) {
                // 手机号+密码登录
                $resultLogin = $this->loginByPwd($customerInfo, $account, $pwd);
            } else {
                // 走手机号+验证码登录 流程
                $resultLogin = $this->loginByCode($customerInfo, $account, $validate, $inviteId);
            }
        } else {
            if (5 == $loginType) {
                // 老的注册流程
                // $resultRegister = $this->registerByPwd();
            } else {
                // 走手机号+验证码注册 流程
                $resultRegister = $this->registerByCode($account, $validate, $inviteId);
            }
            // 注册成功 设置session
            if ($resultRegister) {
                $customerInfo = $customerM->getCustomerByTelephone($account);
                $this->setLoginStatus($customerInfo);
            }
        }

        /*if ($groupBuyBackUrl) {
            $url = TEST_IP . '/' . ltrim($groupBuyBackUrl, '/');
        }*/

        if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] && !$url){
            $url = $_SERVER['HTTP_REFERER'];
        }

        if ($sessionHasInviteCode && isset($_SESSION['default']) && isset($_SESSION['default']['common_invitecode_id'])) { //使用完毕了
            $_SESSION['default']['common_invitecode_id'] = null;
        }

        //保存登录信息
        $customerM->addLoginAttempt($customerInfo['customer_id']);
        $this->loginAfter($customerInfo, $from, $binding);

        if('null' == strtolower($url)){
            $url = '';
        }


        $this->success(['type' => $resultLogin ? 'login' : 'register'], 'success', $url, false);
    }

    public function testFun()
    {
        $id       = 7185;
        $customer = CustomerModel::getInstance()->getCustomerByCustomerId($id);
        /*var_dump($customer, $_SESSION);
        $this->setLoginStatus($customer);
        var_dump($_SESSION);*/
        /*$customerInfo = CustomerModel::getInstance()->getCustomerByTelephone(17091619160);
        var_dump($customerInfo);*/
        //var_dump($_SESSION);
        /*$_SESSION['default']['proxyid'] = 36;
        var_dump($_SESSION);*/
        exit();
    }

    /**
     * 手机号+密码登录
     * @param $customerInfo
     * @param $telephone
     * @param $pwd
     * @return bool
     */
    private function loginByPwd($customerInfo, $telephone, $pwd)
    {
        if (!$customerInfo) {
            $this->error(2, '账号不存在');
        }
        if ($customerInfo['status'] != 1) {
            $this->error(2, '您的账号已被禁用');
        }
        if (!$pwd) {
            $this->error(2, '密码必须填写');
        }
        $pwd = $this->makePwd($customerInfo['salt'], $pwd);
        if ($pwd != $customerInfo['password']) {
            $this->error(2, '账号或密码错误');
        }

        return $this->setLoginStatus($customerInfo);
    }

    /**
     * 设置登录信息
     * @param $customerInfo
     */
    private function setLoginStatus($customerInfo)
    {
        $_SESSION['default']['customer_id'] = $customerInfo["customer_id"];
        $_SESSION['default']['customerid']  = $customerInfo['customer_id'];
        $_SESSION['default']['passkey']     = sha1(md5($customerInfo["customer_id"] . $customerInfo["password"] . $customerInfo["salt"]));

        return true;
    }

    private function loginAfter($customerInfo, $from, $binding)
    {
        // by lcb  5-22 第三方绑定信息需要使用
        if ($from == 'groupbuy' && $binding && $customerInfo['customer_id']) {
            $thirdLoginBinding = isset($_SESSION['default']['thirdLoginBinding']) ? $_SESSION['default']['thirdLoginBinding'] : '';
            $data              = array(
                'customerid'        => $customerInfo['customer_id'],
                'customer_group_id' => 3
            );
            if (isset($thirdLoginBinding['type']) && 1 == $thirdLoginBinding['type']) {
                $data['qq_openid_share'] = isset($thirdLoginBinding['openid']) && $thirdLoginBinding['openid'] ? $thirdLoginBinding['openid'] : '';
            } else {
                $data['wechat_openid_share'] = isset($thirdLoginBinding['openid']) && $thirdLoginBinding['openid'] ? $thirdLoginBinding['openid'] : '';
            }
            CustomerModel::getInstance()->updateCustomer($data);
            $_SESSION['default']['thirdLoginBinding'] = null;
        }
    }


    /**
     * 根据手机号和验证码登录
     * @param     $telephone
     * @param     $validate
     * @param int $inviteid
     * @return mixed
     */
    private function loginByCode($customerInfo, $telephone, $validate, $inviteId = 0)
    {
        if (!$customerInfo) {
            $this->error(2, '账号不存在');
        }
        if ($customerInfo['status'] != 1) {
            $this->error(2, '您的账号已被禁用');
        }
        //进行判断
        if (empty($telephone) || empty($validate)) {
            $this->error(ErrorCode::PARAM_ERROR);
        }

        //进行验证码检测
        if (!$this->checkSmsCode($telephone, $validate, 'login')) {
            $this->error(ErrorCode::SMS_CODE_ERROR);
        }
        //判断之前是不是会员，如果是则无需更改，如果不是则成为会员
        if (isset($customerInfo['merchant_id']) && $customerInfo['merchant_id'] == 0 && $inviteId) {
            $this->updateMerchantInfo($telephone, $inviteId);
        }
        $this->setLoginStatus($customerInfo);

        return true;
    }

    /**
     * 手机号 + 短信验证码 注册
     * @param     $telephone
     * @param int $validate
     * @param int $inviteId
     * @return mixed
     */
    private function registerByCode($telephone, $validate = 0, $inviteId = 0)
    {
        //进行判断
        if (empty($telephone) || empty($validate)) {
            $this->error(ErrorCode::PARAM_ERROR, ' 手机号和验证码不能为空');
        }

        //进行验证码检测
        if (!$this->checkSmsCode($telephone, $validate, 'register')) {
            $this->error(ErrorCode::SMS_CODE_ERROR);
        }

        $salt = token(9);
        // 生成密码
        $rawPwd  = mt_rand(100000, 999999);  // 原生密码
        $savePwd = $this->makePwd($salt, $rawPwd);  // 加密之后的密码

        //插入数据,并返回customerid
        $insertSql = "INSERT INTO hb_customer 
          SET customer_id=default,
          customer_group_id = '1',
          store_id = '1',
          sex='1',
          firstname = '" . $telephone . "',
          lastname = '',
          card = '',
          telephone = '" . $telephone . "',
          custom_field = '',
          salt = '" . $salt . "',
          password = '" . $savePwd . "',
          ip = '',
          qq = '',
          qq_openid = '',
          wechat = '',
          unionid = '',
          wechat_openid = '',
          newsletter = '0',
          sharetimes = '0',
          realname_error_num = '0',
          status = '1',
          approved = '0',
          birthday = '631123200',
          date_added = NOW(),
          headurl = '',
          is_set_pwd = '0',
          proxy_time = ''";

        exeSql($insertSql);
        $customer_uuid = getLastId();
        if (!$customer_uuid) {
            $this->error(ErrorCode::REGISTER_ERROR);
        }

        //修改融云的token
        $customer_last = CustomerModel::getInstance()->maxCustomerId();
        $customer_id   = @$customer_last['customer_id'] + 2;

        $header_url    = isset($post['headurl']) ? $post['headurl'] : '';
        $rongyun_token = $this->getRongYunToken($customer_id, $header_url);
        //修改
        // $customer_update = exeSql("UPDATE `hb_customer` SET customer_id=" . $customer_id . ",rctoken='" . $rongyun_token . "' WHERE customer_uuid = '" . (int)$customer_uuid . "' LIMIT 1");
        $customer_update = CustomerModel::getInstance()->updateCustomerByKey($customer_uuid, ['customer_id' => $customer_id, 'rctoken' => $rongyun_token]);
        if (!$customer_update || $customer_update->rowCount() == 0) {
            $this->error(ErrorCode::REGISTER_ERROR);
        }

        // 注册成功了 发送密码短信
        if (!$this->sendSmsPwd($telephone, $rawPwd)) {
            Helper::writeLog($telephone . ' 密码短信发送失败');
        }

        //如果包含inviteid
        if ($inviteId) {
            $this->updateMerchantInfo($telephone, $inviteId);
        }

        $this->updateBlance($customer_id);

        return $customer_id;
    }

    /**
     * 发送密码短信
     * @param $telephone
     * @param $rawPwd
     * @return bool
     */
    private function sendSmsPwd($telephone, $rawPwd)
    {
        Helper::writeLog(__CLASS__ . '->' . __FUNCTION__ . ' ' . $telephone);
        include_once '../iwantcdm/lib/sms.php';
        $sms = new Sms();
        if (!$rawPwd) {
            Helper::writeLog('密码短信发送失败：密码不能为空');

            return false;
        }
        //查询是否存在该用户
        $customerInfo = CustomerModel::getInstance()->getCustomerByTelephone($telephone);
        if (!$customerInfo || $customerInfo['status'] != 1) {
            Helper::writeLog('密码短信发送失败：用户不存在或者被禁用');

            return false;
        }
        // '【嗨企货仓】您的登录密码是：' . $rawPwd
        $smsContent = '【嗨企货仓】亲，您的默认登录密码已生成：'.$rawPwd.'，可使用密码登录来唤醒APP啦。戳这里登录app修改默认密码 http://t.cn/RXGwfzP';
        $resultSendSms    = $sms->send($telephone, $smsContent, true, 'api');
        $arrResultSendSms = json_decode($resultSendSms, 'json');

        return isset($arrResultSendSms["retcode"]) && $arrResultSendSms["retcode"] == 0;
    }

    /**
     * 根据密码注册
     * @param $telephone
     * @param $password
     * @param $invitecode_id
     * @param $validate
     */
    private function registerByPwd($telephone, $password, $invitecode_id, $validate)
    {
        $headurl         = isset($_POST['headurl']) ? $_POST['headurl'] : HTTP_SERVER . 'image/' . 'placeholder_circle.png';
        $groupBuyBackUrl = isset($_POST["groupBuyBackUrl"]) ? $_POST["groupBuyBackUrl"] : '';
        $valitype        = 1;  // 验证码类型 1 表示注册

        $customerModel = CustomerModel::getInstance();
        if (empty($telephone) || empty($validate) || empty($password)) {
            $this->error(2, '参数错误，请重新注册');
        }
        //验证码是否正确  @todo 密码登录是否需要短信验证码？
        if (!$this->checkSmsCode($telephone, $validate, 'regiseter')) {
            $this->error(2, '验证码错误');
        }

        $customerinfo  = array(
            'firstname'         => $telephone,
            'lastname'          => '',
            'telephone'         => $telephone,
            'password'          => $password,
            'card'              => '',
            'customer_group_id' => 1,
            'headurl'           => $headurl,
            'clientid'          => '',
            'invitecode_id'     => $invitecode_id,
            "parent_id"         => 0,
            "merchant_id"       => 0
        );
        $customer_uuid = $customerModel->addCustomer($customerinfo);
        if (!$customer_uuid) {
            $this->error(2, '注册失败，请重新注册');
        }
        //获取该用户上个用户的信息
        $resss = $customerModel->maxCustomerId();
        //修改customer_id
        $customer_id = @$resss["customer_id"] + 2;
        // $customerModel->updateCustomerIdByUuid($customer_id, $cus["customer_uuid"]);
        $header_url      = isset($post['headurl']) ? $post['headurl'] : '';
        $rongyun_token   = $this->getRongYunToken($customer_id, $header_url);
        $customer_update = CustomerModel::getInstance()->updateCustomerByKey($customer_uuid, ['customer_id' => $customer_id, 'rctoken' => $rongyun_token]);
        if (!$customer_update || $customer_update->rowCount() == 0) {
            $this->error(ErrorCode::REGISTER_ERROR);
        }

        $this->updateBlance($customer_id);

        //登录，存session
        $_SESSION['default']['customerid']  = $customer_id;
        $_SESSION['default']['customer_id'] = $customer_id;
        //返回json数据
        $proxyid   = $_SESSION['default']['proxyid'];
        $productid = $_SESSION['default']['productid'];
        $price     = $_SESSION['default']['differenceprice'];
        $url       = TEST_IP . '/index.php?route=share/share&proxyid=' . $proxyid . '&productid=' . $productid . '&price=' . $price;
        // 5-22 lcb
        if ($groupBuyBackUrl) {
            $url = TEST_IP . '/' . ltrim($groupBuyBackUrl, '/');
        }
        $this->success('', '注册成功', $url, false);
    }

    /**
     * 添加余额表信息
     * @param $customerId
     * @return bool|PDOStatement
     */
    private function updateBlance($customerId)
    {
        $balance = array(
            'customer_id'      => $customerId,
            'balance'          => '0.00',
            'availabe_balance' => '0.00',
        );

        return CustomerModel::getInstance()->addBlance($balance);
    }

    /**
     * 更新会员信息
     * @param $telephone
     * @param $inviteid
     * @return bool|PDOStatement
     */
    private function updateMerchantInfo($telephone, $inviteId)
    {
        //不是会员，查出对应的parent_id,merchant_id
        $inviteInfo = getRow("select customer_id from hb_invitecode where invitecode_id=" . intval($inviteId));
        if (!empty($inviteInfo) && isset($inviteInfo['customer_id']) && $inviteInfo['customer_id']) {
            //修改他的会员状态
            return exeSql("update hb_customer 
              set 
              merchant_id=1,
              parent_id=" . $inviteInfo['customer_id'] . ",
              invitecode_id=" . intval($inviteId) . ",
              proxy_time = now()
               where 
               telephone=" . $telephone
            );
        }

        return false;
    }

    /**
     * 获取rongyun token
     * @param $customer_id
     * @return mixed
     */
    private function getRongYunToken($customer_id, $header_url)
    {
        require_once 'lib/Rongyun.php';
        $r = new Rongyun();

        return $r->getRctoken($customer_id, "张三", $header_url ? $header_url : 'http://iwant-u.cn/image/placeholder_circle.png');
    }

    /**
     * 生成验证密码
     * @param $salt
     * @param $rawPwd
     * @return string
     */
    private function makePwd($salt, $rawPwd)
    {
        return sha1($salt . sha1($salt . sha1($rawPwd)));
    }

    /**
     * 生成passkey
     * @param $customer_id
     * @param $password
     * @param $salt
     * @return string
     */
    private function makePasskey($customer_id, $password, $salt)
    {
        return sha1(md5($customer_id . $password . $salt));
    }

    public function test()
    {
        //var_dump(Helper::getFields(DB_PREFIX.'customer'));
        var_dump(CustomerModel::getInstance()->getFields());
    }

    public function clearCache()
    {
        if (isset($_GET['key']) && $_GET['key']) {
            setCache('hqhc_' . $_GET['key'] . '_fields', null, 2);
        }
    }

    /**
     * 注册
     */
    private function register()
    {
        $telephone       = isset($_POST['telephone']) ? $_POST['telephone'] : null;
        $code            = isset($_POST['code']) ? $_POST['code'] : null;
        $validate        = isset($_POST['vali']) ? $_POST['vali'] : null;
        $password        = isset($_POST['password']) ? $_POST['password'] : null;
        $nickname        = isset($_POST['nickname']) ? $_POST['nickname'] : null;
        $headurl         = isset($_POST['headurl']) ? $_POST['headurl'] : HTTP_SERVER . 'image/' . 'placeholder_circle.png';
        $openid          = isset($_POST["openid"]) ? $_POST["openid"] : null;
        $type            = isset($_POST["type"]) ? $_POST["type"] : null;
        $groupBuyBackUrl = isset($_POST["groupBuyBackUrl"]) ? $_POST["groupBuyBackUrl"] : '';
        $valitype        = 1;
        //如果邀请码存在,获取邀请码的id
        if ($code) {
            $sql   = "select customer_id,invitecode_id from " . DB_PREFIX . "invitecode where invitecode='" . addslashes($code) . "'";
            $query = getRow($sql);
            if (isset($query['customer_id'])) {
                $invitecode_id = $query['invitecode_id'];
                $parent_id     = $query['customer_id'];
                $invitecode_id = (int)$invitecode_id;
                $parent_id     = (int)$parent_id;
                $merchant_id   = 1;
            } else {
                $this->error(2, '邀请码无效!如无邀请码，则无需输入');
            }
        } else {
            $merchant_id   = null;
            $invitecode_id = null;
            $parent_id     = null;
        }
        $customerModel = CustomerModel::getInstance();
        $this->load->model('account/transaction');
        if (empty($telephone) || empty($validate) || empty($password)) {
            $this->error(2, '参数错误，请重新注册');
        }
        //根据用户编号修改qq_openid或者wechat_openid_share
        //验证码是否正确
        $validateTrue = $this->checkPhoneCode($validate, $telephone, $valitype);
        if (!$validateTrue) {
            $this->error(2, '验证码错误');
        }
        $typ    = '用户注册';
        $return = $customerModel->getValidate($telephone, $typ);
        date_default_timezone_set("Asia/Shanghai");
        $validateTime = strtotime($return['dat']);
        if (time() > $validateTime + 10 * 60) {
            $this->error(2, '验证码过期');
        }
        //查询是否已经注册
        $customer = $customerModel->getCustomerByTelephone($telephone);
        if (!empty($type)) {
            //第三方注册
            if (!$customer) {
                //未注册
                $customerinfo = array(
                    'firstname'         => $nickname,
                    'lastname'          => '',
                    'telephone'         => $telephone,
                    'password'          => $password,
                    'card'              => '',
                    'customer_group_id' => 1,
                    'headurl'           => $headurl,
                    'clientid'          => '',
                    'invitecode_id'     => $invitecode_id,
                    "parent_id"         => $parent_id,
                    "merchant_id"       => $merchant_id
                );
                //添加用户表记录
                $return = $customerModel->addcustomerinfo($customerinfo);
                //获取该手机号的customer信息
                $cus = $customerModel->getCustomerByTelephone($telephone);
                //获取该用户上个用户的信息
                $resss = $customerModel->selectBefore($cus["customer_uuid"]);
                //type为1是qq注册，type为2是微信注册
                if ($type == 1) {
                    $data = array(
                        "wechat_openid"   => '',
                        "qq_openid_share" => $openid,
                        "customer_uuid"   => @$return
                    );
                } else {
                    $data = array(
                        "wechat_openid_share" => $openid,
                        "qq_openid_share"     => '',
                        "customer_uuid"       => @$return
                    );
                }
                //修改用户的qq、微信信息
                $this->model_account_customer->updateqqorwet($data);
                //修改customer_id
                $customer_id = @$resss["customer_id"] + 2;
                $customerModel->updateCustomerIdByUuid($customer_id, $cus["customer_uuid"]);

                //添加余额表信息
                $balance = array(
                    'customer_id'      => $customer_id,
                    'balance'          => '0.00',
                    'availabe_balance' => '0.00',
                );
                $this->model_account_transaction->addBlance($balance);
                //加入登录   cgl  11.16
                $customerModel->addLoginAttempt($customer_id);

                //登录，存session
                $_SESSION['default']['customerid'] = $customer_id;

                $proxyid   = $_SESSION['default']['proxyid'];
                $productid = $_SESSION['default']['productid'];
                $price     = $_SESSION['default']['differenceprice'];
                $url       = TEST_IP . '/index.php?route=share/share&proxyid=' . $proxyid . '&productid=' . $productid . '&price=' . $price;
                // 5-22 lcb
                if ($groupBuyBackUrl) {
                    $url = TEST_IP . '/' . ltrim($groupBuyBackUrl, '/');
                }
                $this->error(1, '注册成功', $url);
            } else {
                //已经注册过
                $this->error(3, '该手机已被注册，是否同步信息');
            }
        } else {
            //手机号注册
            if ($customer) {
                $this->error(2, '该手机号已被注册');
            }
            $customerinfo = array(
                'firstname'         => $telephone,
                'lastname'          => '',
                'telephone'         => $telephone,
                'password'          => $password,
                'card'              => '',
                'customer_group_id' => 1,
                'headurl'           => $headurl,
                'clientid'          => '',
                'invitecode_id'     => $invitecode_id,
                "parent_id"         => $parent_id,
                "merchant_id"       => $merchant_id
            );
            $return       = $this->model_account_customer->addcustomerinfo($customerinfo);

            if ($return) {
                //获取该手机号的customer信息
                $cus = $customerModel->getCustomerByTelephone($telephone);
                //获取该用户上个用户的信息
                $resss = $customerModel->selectBefore($cus["customer_uuid"]);
                //修改customer_id
                $customer_id = @$resss["customer_id"] + 2;
                $customerModel->updateCustomerIdByUuid($customer_id, $cus["customer_uuid"]);

                //添加余额表信息
                $balance = array(
                    'customer_id'      => $customer_id,
                    'balance'          => '0.00',
                    'availabe_balance' => '0.00',
                );
                $this->model_account_transaction->addBlance($balance);
                //加入登录  cgl  11.16
                $customerModel->addLoginAttempt($customer_id);
                //登录，存session
                $_SESSION['default']['customerid']  = $customer_id;
                $_SESSION['default']['customer_id'] = $customer_id;
                //返回json数据
                $proxyid   = $_SESSION['default']['proxyid'];
                $productid = $_SESSION['default']['productid'];
                $price     = $_SESSION['default']['differenceprice'];
                $url       = TEST_IP . '/index.php?route=share/share&proxyid=' . $proxyid . '&productid=' . $productid . '&price=' . $price;
                // 5-22 lcb
                if ($groupBuyBackUrl) {
                    $url = TEST_IP . '/' . ltrim($groupBuyBackUrl, '/');
                }
                $this->success('', '注册成功', $url, false);
            } else {
                $this->error(2, '注册失败，请重新注册');
            }
        }
    }

    /**
     * //验证短信发送验证码是否正确 1 用户注册  设置资金密码  2 找回密码
     * @param $code
     * @param $telephone
     * @param $valitype
     * @return bool
     */
    private function checkPhoneCode($code, $telephone, $valitype)
    {
        if ($valitype == 1) {
            $is_exist = CustomerModel::getInstance()->checkCode($telephone, $code, '用户注册');
        } else if ($valitype == 2) {
            $is_exist = CustomerModel::getInstance()->checkCode($telephone, $code, '找回密码');
        }
        if (!empty($is_exist)) {
            //存在
            return true;
        } else {
            return false;
        }
    }

    /**
     * 找回密码
     */
    public function findPwd()
    {

    }

    /**
     * 重置密码
     */
    public function resetPwd()
    {

    }

    /**
     * 发送短信验证码
     * type 发送验证码的类型，1注册登录，2修改密码，3设置资金密码，4轮盘抽奖
     */
    public function sendSmsCode()
    {
        Helper::writeLog(var_export($_REQUEST, true));
        include_once '../iwantcdm/lib/sms.php';
        $sms       = new Sms();
        $telephone = $_POST['telephone'];
        if (!$telephone || !$this->checkTelephone($telephone)) {
            $this->error(ErrorCode::ILLEGAL_TELEPHONE);
        }
        //查询是否存在该用户
        $customerInfo = CustomerModel::getInstance()->getCustomerByTelephone($telephone);
        if ($customerInfo) {
            //已注册
            $type = "用户登录";
        } else {
            //没注册
            $type = "用户注册";
        }
        $rand             = mt_rand(1111, 9999);
        $resultSendSms    = $sms->send($telephone, '【嗨企货仓】您的验证码是：' . $rand, true, 'api');
        $arrResultSendSms = json_decode($resultSendSms, true);
        if (isset($arrResultSendSms["retcode"]) && $arrResultSendSms["retcode"] == 0) {
            Helper::writeLog(__CLASS__ . '->' . __FUNCTION__ . ' success 1 ' . var_export($arrResultSendSms, true));
            //发送成功  检测这个手机号码是否已经发送过一次
            $isSind = getRow("select id from hb_customer_validate where mobile = '" . $telephone . "' and typ='" . $type . "' and statu=0");
            if (isset($isSind['id']) && $isSind['id']) {
                $updateSql = "update hb_customer_validate set validate=" . $rand . ",dat=now() where id=" . $isSind['id'] . " limit 1";
                Helper::writeLog('$updateSql=' . $updateSql);
                $r = exeSql($updateSql);
                Helper::writeLog('$r=' . $r->rowCount());
            } else {
                $insertSql = "insert into hb_customer_validate set mobile = '" . $telephone . "',validate='" . $rand . "',typ='" . $type . "',statu=0,dat=now()";
                Helper::writeLog('$insertSql=' . $insertSql);
                $r = exeSql($insertSql);
                Helper::writeLog('$r=' . $r->rowCount());
            }
            Helper::writeLog(__CLASS__ . '->' . __FUNCTION__ . ' success 2');
            $this->success([], 'success', '', false);
        } else {
            //发送失败
            Helper::writeLog(__CLASS__ . '->' . __FUNCTION__ . ' error 1 ' . $resultSendSms);
            $this->error(ErrorCode::SMS_CODE_SEND_ERROR);
        }
        Helper::writeLog(__CLASS__ . '->' . __FUNCTION__ . ' error 2');
        $this->error(ErrorCode::SMS_CODE_SEND_ERROR);
    }

    /**
     * 获取图片验证码
     */
    public function getImageCode()
    {
        echo (new Verify(['length'=>4, 'fontSize'=>35,'useCurve'=>false]))->entry();
    }

    /**
     * 检查短信验证码
     * @param        $telephone  手机号
     * @param        $validate   短信验证码
     * @param string $type       验证码类型   login  register
     * @return array|bool|mixed|string
     */
    private function checkSmsCode($telephone, $validate, $type = '')
    {
        if (!$telephone || !$validate) {
            return false;
        }
        $where = 'WHERE mobile="' . $telephone . '" AND validate=' . intval($validate);
        if ('login' == $type) {
            $where .= ' AND typ="用户登录"';
        } else if ('register' == $type) {
            $where .= ' AND typ="用户注册"';
        }

        return getRow('select * from hb_customer_validate ' . $where . '  order by id desc LIMIT 1');
    }

    /**
     * 检查手机号格式
     * @param $mobile
     * @return int
     */
    private function checkTelephone($mobile)
    {
        return preg_match("/^13[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|14[0-9]{9}$/", $mobile);
    }

    /**
     * 检查是否是post请求
     */
    protected function isPost()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
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
            'msg'     => $msg ? $msg : ErrorCode::getErrorMessage($errorCode),
            'url'     => $backUrl,
        ];
        Helper::writeLog($this->res);
        Helper::writeLog(debug_backtrace());
        if ($return) {
            return $this->res;
        }
        exit(json_encode($this->res));
    }


}