<?php
include "includes/GetMacAddr.php";
class active{

    /**
     * 根据invitecode_id 获取 luckDrawId
     * lcb 2017-05-12
     */
    private function getLuckDrawId($inviteCodeId){
        $luckDrawInfo = getRow('select id from hb_luckdraw_description where invitecode_id='.(int)$inviteCodeId);
        if($luckDrawInfo && isset($luckDrawInfo['id'])){
            return $luckDrawInfo['id'];
        }
        return 0;
    }


    /*
     * 获取活动说明图片
     * wangzhichao 17.3.31
     */
    function getActiveImage(){
        $invite_id = $_POST['invite_id'];
        $image = getRow("select image from hb_luckdraw_description where invitecode_id = '" .(int)$invite_id. "'");
        if($image){
            return $image['image'];
        }else{
            return false;
        }

    }

    /*
     * 获取邀请码
     * wangzhichao 17.4.13
     */
    function getInvite(){
        $invite_id = $_POST['invitecode_id'];
        $invitecode = getRow("select * from hb_invitecode where invitecode_id = '" .(int)$invite_id. "'");
        if($invitecode){
            return $invitecode['invitecode'];
        }else{
            return false;
        }
    }

    /*
     * 获取省市县
     * wangzhichao 17.4.1
     */
    function getCountry(){
        if(getCache("address_select")){
            $provice_array = getCache("address_select");
        }else{
            $city = getData("SELECT city_id,name,zone_id FROM hb_city WHERE status = '1' ORDER BY city_id ASC");//县级
            $city_array = array();
            foreach($city as $key=>$val){
                $city_array[$val['zone_id']]['children'][] = array('value'=>$val['city_id'],'text'=>$val['name']);

            }

            $zone_array = array();
            foreach($city_array as $key=>$val){
                $zone = getRow("SELECT zone_id,name,country_id FROM hb_zone WHERE zone_id = '" . (int)$key . "' AND status = '1'");//县级
                $zone_array[$zone['country_id']]['children'][] = array('value'=>$zone['zone_id'],'text'=>$zone['name'],'children'=>$val['children']);

            }

            $provice_array = array();
            foreach($zone_array as $key=>$val){
                $provice = getRow("SELECT country_id,name FROM hb_country WHERE country_id = '" .$key. "' and  status = '1'");
                $provice_array[$provice['country_id']] = array('value'=>$provice['country_id'],'text'=>$provice['name'],'children'=>$val['children']);
            }
            ksort($provice_array);
            $provice_array = array_values($provice_array);
            setCache('address_select',$provice_array,3600*24);
        }

        return $provice_array;
    }

    /*
     * 发送验证码
     * wangzhichao 17.4.1
     */
    function sendMsg(){
        include_once '../iwantcdm/lib/sms.php';
        $sms=new Sms();

        $telephone = $_POST['telephone'];
        if(!preg_match("/^13[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|14[0-9]{9}$/",$telephone)){
            //你的手机号不正确
            return array('retcode'=>1000,'msg'=>'手机号不正确');
        }else{
        
            $rand=mt_rand(1111,9999);
            //发送
            //zxx 2017-4-20
            if(isset($_POST['type']) && @$_POST['type'] == 0){
                $return=$sms->sendSingleMt($telephone,"【嗨企货仓】你好，你当前操作：领取优惠券验证，你的验证码是：".$rand);
            }else{
                
                $is_have = getRow("select * from hb_luckdraw_telephone where telephone = '" .$telephone. "'");
                if(!empty($is_have)){
                    return array('retcode'=>1001,'msg'=>'已经抽过奖，不能重复抽奖');
                }
                $customer = getRow("select * from hb_customer where telephone = '" .$telephone. "' and merchant_id > 0");
                if(!empty($customer)){
                    return array('retcode'=>1002,'msg'=>'您已经是会员，不能进行抽奖');
                }


                $return=$sms->sendSingleMt($telephone,"【嗨企货仓】你好，你当前操作：轮.盘.抽.奖，你的验证码是：".$rand);
            }
            
            $res=json_decode($return,"json");

            //获取发送到底是否成功
            if(!$res || @$res["retcode"]!=0){
                return array('retcode'=>1002,'msg'=>'发送失败');
            }

            /**
             * 检测这个手机号码是否已经发送过一次
             */
            $is_find=getRow("select * from hb_customer_validate where mobile = '" .$telephone. "' and typ='轮盘抽奖' and statu=0");
            if($is_find){
                exeSql("update hb_customer_validate set validate='" .$rand. "',statu=0,dat=now() where mobile = '" .$telephone. "' and typ='轮盘抽奖'");
            }else{
                exeSql("insert into hb_customer_validate set mobile = '" .$telephone. "',validate='" .$rand. "',typ='轮盘抽奖',statu=0,dat=now()");
            }
            return array('retcode'=>0,'msg'=>'success');
        }
    }

    /*
     * 验证短信验证码
     * wangzhichao 17.4.1
     */
    function checkMsg(){
        $post = $_POST;

        $is_have = getRow("select * from hb_luckdraw_telephone where telephone = '" .$post['telephone']. "'");
        if(!empty($is_have)){
            return array('retcode'=>1001,'msg'=>'已经抽过奖，不能重复抽奖');
        }

        $customer = getRow("select * from hb_customer where telephone = '" .$post['telephone']. "' and merchant_id > 0");
        if(!empty($customer)){
            return array('retcode'=>1002,'msg'=>'您已经是会员，不能进行抽奖');
        }

        $is_ok = getRow("select * from hb_customer_validate where mobile = '" .$post['telephone']. "' and validate = '" .$post['validate']. "' and typ='轮盘抽奖' and statu=0");
        if(empty($is_ok)){
            return array('retcode'=>1000,'msg'=>'验证码错误');
        }
        return array('retcode'=>0,'msg'=>'success');
    }

    function getLuckDraw(){
        $invite_id = $_POST['invite_id'];
        //奖品为商品
        $product = getData("select lp.*,pd.name,p.image from hb_luckdraw_product lp INNER join hb_product p on p.product_id = lp.product_id INNER join hb_product_description pd on pd.product_id = lp.product_id where lp.invitecode_id = '" .(int)$invite_id. "' and lp.status = 0 and lp.quantity > 0 and lp.quantity-lp.`lock` > 0 and lp.probability>0 and p.status = 1 and p.quantity>0");
        $product_probability = getRow("select sum(probability) as probability from hb_luckdraw_product lp INNER join hb_product p on p.product_id = lp.product_id INNER join hb_product_description pd on pd.product_id = lp.product_id where lp.invitecode_id = '" .(int)$invite_id. "' and lp.status = 0 and lp.quantity > 0 and lp.quantity-lp.`lock` > 0 and lp.probability>0 and p.status = 1 and p.quantity>0");
        if($product_probability){
            $product_probability = $product_probability['probability'];
        }else{
            $product_probability = 0;
        }

        //奖品为优惠券
        $coupon = getRow("select lp.*,c.discount from hb_luckdraw_product lp inner join hb_coupon c on c.coupon_id = lp.coupon_id where lp.invitecode_id = '" .(int)$invite_id. "' and lp.status = 0 and lp.type = 1 and lp.quantity > 0 and lp.quantity-lp.`lock` > 0 and lp.probability>0 and c.status=1 and c.is_delete=0");
        if($coupon){
            $product[] = $coupon;
            $product_probability += $coupon['probability'];
        }
        //不中奖的概率
        $product[] = array(
            'rank'=>0,
            'probability'=>100-$product_probability
        );

        //抽奖
        $randNum = mt_rand(1, 100);
        $star = 0;
        $end = 0;
        $luck_product = array();
        foreach($product as $key=>$val){
            $end += $val['probability'];
            if($randNum>$star && $randNum<=$end){
                $luck_product = $val;
                break;
            }else{
                $star = $end;
            }
        }

        //记录抽奖手机号
        //lcb 增加活动id字段 5-12
        saveData('hb_luckdraw_telephone',array('telephone'=>$_POST['telephone'], 'luckdraw_description_id'=>$this->getLuckDrawId($invite_id)));

        //验证奖品是否足够，不够就不给中奖
        if($luck_product['rank']>0){
            if($luck_product['product_item_id'] > 0){
                $product_item_info = getRow("select * from hb_product_item where product_id = '" .$luck_product['product_id']. "' and product_item_id = '" .$luck_product['product_item_id']. "'");
                if(empty($product_item_info) || $product_item_info['quantity']-$luck_product['lock'] < 1){
                    $luck_product = array(
                        'rank'=>0,
                        'probability'=>100-$product_probability
                    );
                    return $luck_product;
                }

            }else{
                $product_item_info = getRow("select * from hb_product_item where product_id = '" .$luck_product['product_id']. "'");
                if(!empty($product_item_info) || $luck_product['quantity']-$luck_product['lock'] < 1){
                    $luck_product = array(
                        'rank'=>0,
                        'probability'=>100-$product_probability
                    );
                    return $luck_product;
                }
            }
            if($luck_product['type']==1){
                $coupon = getRow("select * from hb_coupon where coupon_id = '" .$luck_product['coupon_id']. "'");
                if(empty($coupon) || $coupon['release_total']-$luck_product['lock'] < 1){
                    $luck_product = array(
                        'rank'=>0,
                        'probability'=>100-$product_probability
                    );
                    return $luck_product;
                }
            }
            exeSql("update hb_luckdraw_product set `lock` = `lock`+1 where id = '" .(int)$luck_product['id']. "'");
//            $data = array('rid'=>$rid,'product'=>array('product_id'=>$luck_product['product_id'],'product_item_id'=>$luck_product['product_item_id'],'image'=>$luck_product['image'],'name'=>$luck_product['name']));
        }
        return $luck_product;
    }

    function sendRedPacket(){
        require_once("../iwantcdm/lib/aop/AopClient.php");
        require_once("../iwantcdm/lib/aop/request/AlipayFundTransToaccountTransferRequest.php");
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2017041906809186';//2016082601805285
        $aop->rsaPrivateKeyFilePath = '../iwantcdm/lib/key/rsa2_private_key.pem';
        $aop->alipayPublicKey='../iwantcdm/lib/key/rsa2_alipay_public_key.pem';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayFundTransToaccountTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"3142321423432\"," .
            "\"payee_type\":\"ALIPAY_LOGONID\"," .
            "\"payee_account\":\"13067839532\"," .
            "\"amount\":\"0.1\"," .
            "\"payer_show_name\":\"上海交通卡退款\"," .
            "\"payee_real_name\":\"张三\"," .
            "\"remark\":\"转账备注\"" .
            "  }");
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }


    /*
     * 成为会员、添加地址并生成订单
     * wanzhichao 17.4.5
     */
    function createOrder(){
        header('Content-type:text/html;charset=utf-8');
        //配置您申请的appkey，用于实名认证
        $appkey = "39e7639dbabaf5a849579cd2b5029348";
        $url = "http://op.juhe.cn/idcard/query";

        $post = $_POST;
        $salt = token(9);

        beginTransaction();
        $data = array('retcode'=>0,'msg'=>'success');

        if(empty($post['invite_id']) || empty($post['telephone']) || $post['product_id'] <= 0 || empty($post['province']) || empty($post['zone']) || empty($post['city']) || empty($post['name']) || empty($post['tel']) || empty($post['card']) || empty($post['address'])){
            return $data = array("retcode"=>1000,"msg"=>'参数错误');
        }

        $invite = getRow("select * from hb_invitecode where invitecode_id = :invite_id",$post);
        if(empty($invite)){
            return $data = array("retcode"=>1001,"msg"=>'邀请码不存在');
        }
        //实名认证
        $check_name = getRow("select * from hb_card where  card = '" .$post['card']. "'");
        if(empty($check_name)){
            //验证身份证信息
            $params = array(
                "idcard" => $post['card'],//身份证号码
                "realname" => $post['name'],//真实姓名
                "key" => $appkey,//应用APPKEY(应用详细页查询)
            );
            $paramstring = http_build_query($params);
            $content = juhecurl($url,$paramstring);
            $result = json_decode($content,true);
            if(empty($result) || @$result['result']['res']!=1){
                $data = array(
                    "retcode"=>1103,
                    "msg"=>"身份证信息不符合"
                );
                return $data;
            }
            
            exeSql("INSERT INTO hb_card SET `name` = '" .$post['name']. "',card = '" .$post['card']. "'");
        }else{
            if($check_name['name'] != $post['name']){
                $data = array(
                    "retcode"=>1103,
                    "msg"=>"身份证信息不符合"
                );
                return $data;
            }
        }


        //注册
        $password = substr($post['telephone'], -6);
        $customer = getRow("select * from hb_customer where telephone = :telephone and merchant_id = 0",$post);

        if(empty($customer)){
            exeSql("INSERT INTO hb_customer SET customer_group_id = '1', store_id = '1',sex='1', firstname = '" . $post['telephone'] . "',lastname = '" . $post['name'] . "',card = '" . $post['card'] . "', telephone = '" . $post['telephone'] . "', custom_field = '', salt = '" . $salt . "', password = '" . sha1($salt . sha1($salt . sha1($password))) . "', newsletter = '0', ip = '', status = '1', approved = '0', date_added = NOW(),headurl = '',proxy_time = now()");//merchant_id='1' ,parent_id='" .(int)$invite['customer_id']. "',invitecode_id='" .(int)$invite['invitecode_id']. "',
            $customer_uuid = getLastId();


            if(!$customer_uuid){
                $data = array(
                    "retcode"=>9000,
                    "msg"=>"生成订单失败"
                );
            }

            $last_customer = getRow("SELECT * from `hb_customer` where customer_uuid < " .$customer_uuid. " order by customer_uuid desc ");
            $customer_id = @$last_customer["customer_id"]+2;
            exeSql("update hb_customer set customer_id = '" .(int)$customer_id. "' where customer_uuid = '" .(int)$customer_uuid. "'");


            $customer = getRow("select * from hb_customer where customer_uuid = '" .$customer_uuid. "'");

            //增加资金记录
            $balance = array(
                'customer_id'=>$customer_id,
                'balance'=>'0.00',
                'availabe_balance'=>'0.00',
                "date_added"=>date("Y-m-d H:i:s",time()),
                "date_modified"=>date("Y-m-d H:i:s",time())
            );
            $balance_insert = saveData("hb_balance",$balance);
            if($balance_insert->rowCount() == 0){
                $this->res = array(
                    'retcode'	=>9002,
                    'msg'		=>"注册失败，请重新操作"
                );
            }

//            exeSql("update hb_invitecode set times = (times+1) where invitecode_id = '" .(int)$post['invite_id']. "'");
        }else{
            $customer_id = $customer['customer_id'];
        }


        //生成收货地址
        $city = getRow("select * from hb_city where  city_id = :city",$post);

        $address_data = array(
            'customer_id'=>$customer_id,
            'firstname'=>$post['name'],
            'lastname'=>'/',
            'company'=>'/',
            'address_1'=>$post['address'],
            'address_2'=>'/',
            'city'=>$city['name'],
            'postcode'=>$post['mail'],
            'country_id'=>$post['province'],
            'zone_id'=>$post['zone'],
            'custom_field'=>json_encode(array('1'=>$post['tel'])),
            'idcard'=>$post['card']
        );
        saveData('hb_address',$address_data);
        $address_id = getLastId();


        $address = getRow("SELECT a.*,c.name as country,z.name as zone FROM `hb_address` as a
                           inner join `hb_country` as c on c.country_id = a.country_id
                           inner join `hb_zone` as z on z.zone_id = a.zone_id
                           where address_id='".(int)$address_id."'");


        $product = getRow("select p.* ,pd.name from `hb_product` as p inner join `hb_product_description` as pd on pd.product_id = p.product_id where p.product_id = '" .$post['product_id']. "' and p.status = '1'");

        $item = array();
        if($post['product_item_id'] > 0){
            $item = getRow("select * from `hb_product_item` where product_item_id = '" .$post['product_item_id']. "' and product_id = '" .$post['product_id']. "' and status = '0'");
            if(!empty($item)){
                $product['proxyprice'] = $item['proxyprice'];
            }
        }


        //新增订单数据
        $order_data = array(
            'shipping_custom_field'=>$address['custom_field'],
            'image'=>$product['image'],
            'firstname'=>empty($customer['firstname'])?"NULL":$customer['firstname'],
            'shipping_firstname'=>$address['firstname'],
            'shipping_address_1'=>$address['address_1'],
            'order_type'=>5,
            'join_id'=>'',
            'relate_id'=>json_encode(array())
        );

        //生成支付单号
        $laststr = '';
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';    //字符池
        for($i = 0; $i < 4; $i++)
        {
            $laststr .= $pattern{mt_rand(0,35)};
        }
        date_default_timezone_set('PRC');
        $order_num = 'ssdf' . date('Ymd',time()) . $laststr;

        //生成订单
        exeSql("insert into `hb_order` set
                        customer_id = '" .(int)$customer['customer_id']. "',
                        customer_group_id = '1',
                        firstname = :firstname,
                        telephone = '" .$customer['telephone']. "',
                        shipping_firstname = :shipping_firstname,
                        shipping_address_1 = :shipping_address_1,
                        shipping_city = '" .$address['city']. "',
                        shipping_postcode = '" .$address['postcode']. "',
                        shipping_country = '" .$address['country']. "',
                        shipping_country_id = '" .$address['country_id']. "',
                        shipping_zone = '" .$address['zone']. "',
                        shipping_zone_id = '" .$address['zone_id']. "',
                        shipping_custom_field = :shipping_custom_field,
                        total = '" .$product['proxyprice']. "',
                        order_status_id = '2',
                        date_added = '" .time(). "',
                        date_modified = now(),
                        status = '0',
                        image = :image,
                        isback = '0',
                        isreview = '0',
                        order_num = '" .$order_num. "',
                        is_blance = '0',
                        freight = '0',
                        invoicefee = '0',
                        balance_money = '0',
                        merchant_id = '" .$product['merchant_id']. "',
                        warehouse_id = '" .$product['manufacturer_id']. "',
                        is_share = '0',
                        derate_money = '" .$product['derate_money']. "',
                        return_add_money = '" .$product['return_add_money']. "',
                        order_type = :order_type,
                        order_type_status='',
                        relate_id = :relate_id,
                        join_id = '" .$order_data['join_id']. "'
                        ",$order_data);

        $order_id = getLastId();

        if($order_id <= 0){
            $data = array(
                'retcode'=>1003,
                'msg'=>'创建订单失败'
            );
            return $data;
        }

        //新增order_product表数据
        $order_product = array(
            'order_id'=>$order_id,
            'product_id'=>$product['product_id'],
            'name'=>$product['name'],
            'model'=>$product['model'],
            'quantity'=>1,
            'price'=>$product['proxyprice'],
            'total'=>$product['proxyprice'],
            'tax'=>0,
            'reward'=>0,
            'date_added'=>time(),
            'product_item_id'=>$post['product_item_id'],
            'product_item_name'=>!empty($item)?$item['product_options']:''

        );
        saveData('hb_order_product',$order_product);
        $order_product_id = getLastId();

        if($order_product_id <= 0){
            $data = array(
                'retcode'=>1004,
                'msg'=>'创建订单失败'
            );
            return $data;
        }

        //exeSql("insert into hb_luckdraw_record set customer_id = '" .$customer_id. "',invitecode_id='" .$post['invite_id']. "',order_id='" .$order_id. "',type=1,date_added='" .time(). "'");

        //获取活动id
        $luckDrawDescriptionId = $this->getLuckDrawId($post['invite_id']);
        exeSql("insert into hb_luckdraw_record set customer_id = '" .$customer_id. "',invitecode_id='" .$post['invite_id']. "',order_id='" .$order_id. "',type=1,date_added='" .time(). "',luckdraw_description_id=".$luckDrawDescriptionId);
        $record_id = getLastId();
        if(!$record_id){
            $data = array(
                'retcode'=>1008,
                'msg'=>'领取失败'
            );
            return $data;
        }


        //扣除商品的库存
        $product_result = exeSql("UPDATE hb_product SET quantity = (quantity-1) WHERE product_id = '". (int)$post['product_id'] ."'");
        //通过获取受影响的行数来判断是否删除成功
        if($product_result->rowCount() == 0){
            $data = array(
                'retcode'=>1005,
                'msg'=>'创建订单失败'
            );
            return $data;
        }


        //扣除商品规格库存
        if($post['product_item_id'] > 0){
            $product_item_result = exeSql("UPDATE hb_product_item SET quantity = (quantity-1) WHERE product_id = '". (int)$post['product_id'] ."' and product_item_id = '" .(int)$post['product_item_id']. "'");
            //通过获取受影响的行数来判断是否删除成功
            if($product_item_result->rowCount() == 0){
                $data = array(
                    'retcode'=>1006,
                    'msg'=>'创建订单失败'
                );
                return $data;
            }
        }

        //解除奖品的锁定状态
        $product_item_result = exeSql("UPDATE hb_luckdraw_product SET `lock` = (`lock`-1),quantity = (quantity-1) WHERE product_id = '". (int)$post['product_id'] ."' and invitecode_id = '" .(int)$post['invite_id']. "'");
        //通过获取受影响的行数来判断是否修改成功
        if($product_item_result->rowCount() == 0){
            $data = array(
                'retcode'=>1007,
                'msg'=>'创建订单失败'
            );
            return $data;
        }

        if($data['retcode'] == 0){
            commit();
        }else{
            rollBack();
        }
        return $data;
    }

    /*
     * 成为会员、添加地址并获取优惠券
     * wanzhichao 17.4.5
     */
    function getCoupon(){
        $post = $_POST;
        $salt = token(9);


        $data = array('retcode'=>0,'msg'=>'success');

        if(empty($post['invite_id']) || empty($post['telephone']) || empty($post['coupon_id'])){
            return $data = array("retcode"=>1000,"msg"=>'参数错误');
        }

        $invite = getRow("select * from hb_invitecode where invitecode_id = :invite_id",$post);
        if(empty($invite)){
            return $data = array("retcode"=>1001,"msg"=>'邀请码不存在');
        }

        $merchant_customer = getRow("select * from hb_customer where telephone = :telephone and merchant_id > 0",$post);
        if($merchant_customer){
            $data = array(
                'retcode'=>1009,
                'msg'=>'不能重复领取奖品'
            );
            return $data;
        }
        beginTransaction();
        //注册
        $password = substr($post['telephone'], -6);
        $customer = getRow("select * from hb_customer where telephone = :telephone and merchant_id = 0",$post);
        if(empty($customer)){
            exeSql("INSERT INTO hb_customer SET customer_group_id = '1', store_id = '1',sex='1', firstname = '" . $post['telephone'] . "',lastname = '',card = '', telephone = '" . $post['telephone'] . "', custom_field = '', salt = '" . $salt . "', password = '" . sha1($salt . sha1($salt . sha1($password))) . "', newsletter = '0', ip = '', status = '1', approved = '0', date_added = NOW(),headurl = '',proxy_time = now()");//,merchant_id='1' ,parent_id='" .(int)$invite['customer_id']. "',invitecode_id='" .(int)$invite['invitecode_id']. "'
            $customer_uuid = getLastId();
            if(!$customer_uuid){
                $data = array(
                    'retcode'=>1005,
                    'msg'=>'领取失败'
                );
                return $data;
            }
            $last_customer = getRow("SELECT * from `hb_customer` where customer_uuid < " .$customer_uuid. " order by customer_uuid desc ");
            $customer_id = @$last_customer["customer_id"]+2;
            $customer_update = exeSql("update hb_customer set customer_id = '" .(int)$customer_id. "' where customer_uuid = '" .(int)$customer_uuid. "'");

//            $invite_update = exeSql("update hb_invitecode set times = (times+1) where invitecode_id = '" .(int)$post['invite_id']. "'");

            if($customer_update->rowCount() == 0){
                $data = array(
                    'retcode'=>1008,
                    'msg'=>'领取失败'
                );
                return $data;
            }
        }else{
            $customer_id = $customer['customer_id'];
        }

        //扣除优惠券库存
        if($post['coupon_id'] > 0){
            $coupon_update = exeSql("UPDATE hb_coupon SET release_total = (release_total-1) WHERE coupon_id = '". (int)$post['coupon_id'] ."'");
            $coupon_insert = exeSql("insert into hb_coupon_customer set coupon_id = '" .$post['coupon_id']. "',customer_id='" .$customer_id. "',date_added='" .time(). "',status=0");

            //通过获取受影响的行数来判断是否删除成功
            if($coupon_update->rowCount() == 0 || $coupon_insert->rowCount() == 0){
                $data = array(
                    'retcode'=>1006,
                    'msg'=>'领取失败'
                );
                return $data;
            }
        }

        //exeSql("insert into hb_luckdraw_record set customer_id = '" .$customer_id. "',invitecode_id='" .$post['invite_id']. "',coupon_id='" .$post['coupon_id']. "',type=2,date_added='" .time(). "'");
        //获取活动id 中奖纪录表需要关联活动id lcb
        $luckDrawDescriptionId = $this->getLuckDrawId($post['invite_id']);
        exeSql("insert into hb_luckdraw_record set customer_id = '" .$customer_id. "',invitecode_id='" .$post['invite_id']. "',coupon_id='" .$post['coupon_id']. "',type=2,date_added='" .time(). "',luckdraw_description_id=".$luckDrawDescriptionId);
        $record_id = getLastId();
        if(!$record_id){
            $data = array(
                'retcode'=>1008,
                'msg'=>'领取失败'
            );
            return $data;
        }

        //解除奖品的锁定状态
        $product_update = exeSql("UPDATE hb_luckdraw_product SET `lock` = (`lock`-1),quantity = (quantity-1) WHERE coupon_id = '". (int)$post['coupon_id'] ."' and invitecode_id = '" .(int)$post['invite_id']. "' and status = 0");
        //通过获取受影响的行数来判断是否修改成功
        if($product_update->rowCount() == 0){
            $data = array(
                'retcode'=>1007,
                'msg'=>'领取失败'
            );
            return $data;
        }

        if($data['retcode'] == 0){
            commit();
        }else{
            rollBack();
        }
        return $data;
    }
    /**
     * 
     */
    function randnum($total,$div){
       $total = $total; //待划分的数字
       $div = $div; //分成的份数
       $area = floor($total/$div); //各份数间允许的最大差值
       return $area;
       $average = round($total / $div);
       $sum = 0;
       $result = array_fill( 1, $div, 0 );
         
       for( $i = 1; $i < $div; $i++ ){
            //根据已产生的随机数情况，调整新随机数范围，以保证各份间差值在指定范围内

             $min = 0;
             $max = round( $area / 2 );
            
             
            //产生各份的份额
            $random = rand( $min, $max );
            $sum += $random;
            $result[$i] = $average + $random;
       }
         
       //最后一份的份额由前面的结果决定，以保证各份的总和为指定值
       $result[$div] = $average - $sum;
       foreach( $result as $temp ){
       $data[]=$temp;
       }
       return $data;
   }


    /**
     * 现金红包
     * zxx 2017-5-25
     */
    function getmoney(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            if(!empty($_POST['telephone']) && @$_POST['telephone']!="" && !empty($_POST['customerid']) ){
                $telephone=$_POST['telephone'];

                //先判断支付宝账号是不是符合格式
                if(preg_match("/^1[34578]\d{9}$/", $telephone) || preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i",$telephone)){
                    
                    //数据库中查询该用户是否已经领取过红包
                    if(getRow("select id from hb_redpacket_customer where (telephone='".$telephone."' or customer_id=".$_POST['customerid'].")")){
                        //已经领取过了
                        $this->res['msg']="红包已经领取过了";
                        $this->res["retcode"]=1002;
                    }else{
                        //可以领取红包
                        $timestamp=time();

                        //拼接订单号
                        $sult=rand(1000,9999);
                        $out_trade_no=$timestamp.$sult;

                        //获取到红包的信息
                        $redpacket=getRow("select * from hb_redpacket where  status=1");
                        if(!empty($redpacket)){
                            $total=$redpacket['relase_total']-$redpacket['get_total'];//红包总额 
                            $num=$redpacket['relase_times']-$redpacket['get_times'];// 红包数量
                            
                            //判断用户有没有资格领取
                            $customer_info=getRow("select date_added from hb_customer where customer_id= ".$_POST['customerid']);

                            //查询用户注册时间
                            if(@$customer_info['date_added']>=$redpacket['date_start'] && $customer_info['date_added']<=$redpacket['date_end']){
                                if($num>0){
                                    $min=0.01;//每个人最少能收到0.01元 
                                    // $arr=array();  
                                    // for ($i=1;$i<$num;$i++) 
                                    // { 
                                    //      $safe_total=($total-($num-$i)*$min)/($num-$i)*2;//随机安全上限 
                                    //      $money=mt_rand($min,$safe_total*100)/100; 
                                    //      $total=$total-$money; 
                                    //      $arr[]=$money;
                                    // } 
                                    // $arr[]=$total;
                                    // $amount=$arr[0];
                                    // 
                                    if($num==1){
                                        $amount=$total*100;
                                    }else{
                                        $safe_total=$total/$num*2;
                                        $amount=mt_rand($min*100,$safe_total*100);
                                    }
                                    
                                    $amounts=$amount;
                                    //$amounts=1;
                                    $amount=$amount/100;
                                    //组装内容
                                    $biz_content=array(
                                        "out_trade_no"=>$out_trade_no,
                                        "wish_word"=>"恭喜发财",
                                        "amount"=>$amounts,
                                        "receiver_out_uid"=>$telephone,
                                        "receiver_alipay_account"=>$telephone,
                                        'notify_url'=>"www.baidu.com",
                                        'receive_timeout'=>"24"
                                    );


                                    //请求获取红包接口
                                    $res=$this->getRedPacket($telephone,$biz_content,$timestamp);
                                    $timestamps=date('Y-m-d H:i:s',$timestamp);
                                    if($res->code==2000){
                                        //领取成功,插入数据库
                                        exeSql("insert into hb_redpacket_customer (customer_id,redpacket_id,date_add,get_amount,status,telephone ) values ('".$_POST['customerid']."','".$redpacket['redpacket_id']."','".$timestamps."','".$amount."',1,'".$telephone."')");
                                        //修改红包的数据
                                        exeSql("update hb_redpacket set get_total=get_total+".$amount.",get_times=get_times+1 where redpacket_id=".$redpacket['redpacket_id']);
                                        //领取失败
                                        $this->res['msg']="领取中,请前往支付宝账单查看";
                                        $this->res["retcode"]=0;
                                    }else{
                                        //领取失败
                                        $this->res['msg']=$res->msg;
                                        $this->res["retcode"]=1003;
                                    }
                                    
                                }else{
                                    //领取失败
                                    $this->res['msg']="红包已经领光了";
                                    $this->res["retcode"]=1004;
                                }
                            }else{
                                 $this->res['msg']="没有领取资格";
                                 $this->res["retcode"]=1005;
                            }
                           // exit;
                        }else{
                             $this->res['msg']="现金红包已经失效";
                             $this->res["retcode"]=1001;
                        }
                    }
                }else{
                    $this->res['msg']="支付宝格式不正确";
                    $this->res["retcode"]=1006; 
                }
            }else{
                $this->res['msg']="参数错误";
                $this->res["retcode"]=1000;
            }

        }else{
                $this->res['msg']="请求方式错误"; //请求方式错误
                $this->res['retcode']=1180; //请求方式错误
        }
        return $this->res;
    }

    /**
     *现金红包
     * zxx 2017-5-12
     */
    
    function getRedPacket($telephone,$biz_content,$timestamp){
        //身份信息
        $appsecret="2642f73c1038131d93b11b52653598f5";
        $url="http://api.rp.yundingdang.com/solution/web-api/v1.0/rp/gamerp/send?";
        $request_id="";
        $version="1.0";
        $app_id="75f908cf0e8fa1d69cc828a79c664ef4";
        $biz_content=json_encode($biz_content);
        $sign_type="md5";

        //md5加密处理
        $sign=md5("app_id=".$app_id."&biz_content=".$biz_content."&secret=2642f73c1038131d93b11b52653598f5&sign_type=md5&timestamp=".$timestamp."&version=1.0");

        //在拼装一次请求内容
        $data=array(
            "app_id"=>$app_id,
            "timestamp"=>$timestamp,
            "version"=>"1.0",
            "sign"=>$sign,
            "biz_content"=>$biz_content,
            "sign_type"=>"md5"
        );

        //进行格式处理
        $strParam="";
        foreach ($data as $key => $val) {
            if($key!='' && $val!=''){
                $strParam.=$key."=".urlencode($val)."&";
            }
        }
        $strParam=trim($strParam,'&');
        $url.=$strParam;
        $res=$this->curlPost($url,'',"POST");
        $res=json_decode($res);
        return $res;
    }



        /*
         *发送curl请求
         *zxx 2017-5-12
         */
        function curlPost($url,$data,$method){
                $ch=curl_init(); //初始化
                curl_setopt($ch,CURLOPT_URL,$url); //请求地址
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //请求方式
                curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
                //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                if($method=="POST"){//5.post方式的时候添加数据
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $tmpInfo = curl_exec($ch);//6.执行
                
                if (curl_errno($ch)) {//7.如果出错
                    return curl_error($ch);
                }
                curl_close($ch);//8.关闭
                return $tmpInfo;
            }

        /**
         * 活动商品
         * zxx 2017-6-19
         */
        function getActivityGoods(){
            if($_SERVER['REQUEST_METHOD']=='POST'){
                $category_id=isset($_POST['categoryid'])?$_POST['categoryid']:0;
                $info=getData("select p.product_id,p.image,pd.name,ptc.activity_price from hb_product as p left join hb_product_description as pd on p.product_id=pd.product_id left join hb_product_to_activity as ptc on p.product_id=ptc.product_id where ptc.category_id='".$category_id."' and ptc.status=1 and p.status=1");

                foreach ($info as $key => $value) {
                    $info[$key]['url']="/index.php?route=share/share&productid=".$value['product_id']."&price=0.0&activity=".$category_id;
                }
                $this->res["data"]=$info;
                $this->res['msg']="请求成功";
                $this->res['retcode']=0;
            }else{
                $this->res['msg']="请求参数错误";
                $this->res['retcode']=1001;
            }
            return $this->res;
        }
}