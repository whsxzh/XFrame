<?php

include 'xcontrol/cart.php';  // 不用引入base.php 因为 cart.php 已经引入

/**
 *
 * User: lcb
 * Date: 2017/5/15
 */
class groupOrder extends base{

    /**
     * 团购下单接口 调用现有的接口
     * api/index.php?m=groupOrder&act=confirmOrder
     *
     *  addressid
     *  prdList
     *      product_item_id	产品规格id	string	没有传空
            productid	产品编号（必填）	string	会员订单必填 测试环境：4019,正式：7883
            group_id	团购编号	string
            group_type	团购类型	string	1为参团，2为开团
            join_id
     * @return array
     */
    public function confirmOrder(){
        if(!isset($_POST['prdlist'])){
            exit(json_encode(array('retcode'=>100, 'msg'=>'商品参数不能为空')));
        }
        if(!isset($_POST['addressid']) || $_POST['addressid']<1){
            exit(json_encode(array('retcode'=>100, 'msg'=>'地址参数不能为空')));
        }
        if(!isset($_POST['prdlist'][0]['productid']) || $_POST['prdlist'][0]['productid']<1){
            exit(json_encode(array('retcode'=>100, 'msg'=>'商品id参数不能为空')));
        }
        if(!isset($_POST['prdlist'][0]['group_id']) || $_POST['prdlist'][0]['group_id']<1){
            exit(json_encode(array('retcode'=>100, 'msg'=>'团购id参数不能为空')));
        }
        $_POST['prdlist'][0]['number'] = 1;
        $_POST['prdlist'][0]['type'] = 2;
        $_POST['prdlist'][0]['sale_id'] = 0;
        // 团购状态：1为进行中，2为已结束,3为已关闭  join_status

        $groupBuyInfo = getRow('select group_status from hb_groupby where group_id='.(int)$_POST['prdlist'][0]['group_id'].' and product_id='.(int)$_POST['prdlist'][0]['productid']);
        if(!$groupBuyInfo || $groupBuyInfo['group_status'] != 1){
            exit(json_encode(array('retcode'=>100, 'msg'=>'没有该团购信息')));
        }

        //$_POST['prdlist'][0]['join_id'] = $groupBuyInfo['join_id'];
        //$_POST['prdlist'][0]['group_type'] = $groupBuyInfo['add_customer_id'] && $groupBuyInfo['add_customer_id'] == $this->customer_id ? 2 : 1;
        $_POST['prdlist'][0]['join_id'] = intval($_POST['prdlist'][0]['join_id']);
        $_POST['prdlist'][0]['group_type'] = intval($_POST['prdlist'][0]['group_type']);

        //$_POST['prdList']['product_item_id'] = 2;
        $result = call_user_func(array(new cart, 'confirmOrder'), $_POST);
        /*$result = array(
            'retcode' => 0,
            'msg' => 'success',
            'data' =>
                array(
                    'orderids' => '1021080',
                    'order_num' => 'ssdf20170517ravy',
                    'balance' => '0.00',
                    'total' => '66.00',
                    'order_time' => 1495001864,
                    'is_open_free_group' => 1));*/
        // is_open_free_group 0：按照支付流程走，1：跳转到支付成功页面
        if(isset($result['retcode']) && $result['retcode'] == 0 && isset($result['data'])){
            $preUrl = isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
            $backUrl = urlencode(str_replace('api/index.php','', $preUrl).'web/buy_group/detail.html?productid='.$_POST['prdlist'][0]['productid'].'&groupid='.$_POST['prdlist'][0]['group_id']);
            // var_dump($preUrl);exit(); /hi2017/hi2017/api/index.php
            if(isset($result['data']['is_open_free_group']) && $result['data']['is_open_free_group']==0
                && isset($result['data']['order_num']) && $result['data']['order_num']
                && isset($result['data']['orderids']) && $result['data']['orderids']){
                $result['data']['back_url'] = str_replace('api/index.php','', $preUrl).'highup/index.php?route=share/pay/payIndex&orderid='.$result['data']['orderids'].'&backurl='.$backUrl.'&group_id='.$_POST['prdlist'][0]['group_id'];
                $_SESSION['hi_groupBuyPayBackUrl'] = $backUrl;
            }
            if(isset($result['data']['is_open_free_group']) && $result['data']['is_open_free_group']==1){
                //$result['data']['back_url'] = str_replace('api/index.php','', $preUrl).'web/buy_group/detail.html?productid='.$_POST['prdlist'][0]['productid'].'&groupid='.$_POST['prdlist'][0]['group_id'];
                $result['data']['back_url'] = str_replace('api/index.php','', $preUrl).'web/buy_group/success.html?productid='.$_POST['prdlist'][0]['productid'].'&groupid='.$_POST['prdlist'][0]['group_id'].'&backurl='.$backUrl;
            }
        }
        return $result;
    }

}