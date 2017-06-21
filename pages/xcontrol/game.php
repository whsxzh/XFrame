<?php

use OSS\OssClient;
use OSS\Core\OssException;

require_once '.././aliyun-oss/aliyun-oss-php-sdk-2.2.1.phar';
require_once '.././aliyun-oss/autoload.php';

include "xcontrol/base.php";

//include "xcontrol/product.php";
class game extends base
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * 大转盘&翻牌子管理
     * @return array
     */
    public function manage()
    {
        $this->getMenu();
        $this->res['addurl']        = linkurl('game/addSeller');
        $this->res['editStatusUrl'] = linkurl('game/addSeller');
        $this->res['redirectUrl']   = linkurl('game/manage');
        $start                      = 0;
        $size                       = 10000;
        $sql                        = 'select ld.id,ld.`type`,ld.`commit`,ld.invitecode_id,ld.url,ld.image,ld.`status`,i.invitecode,ld.description from ' . DB_PREFIX . 'luckdraw_description ld,' . DB_PREFIX . 'invitecode i where i.invitecode_id=ld.invitecode_id limit ' . $start . ',' . $size;
        $listData                   = getData($sql);
        foreach ($listData as &$item) {
            $item['typeName']      = $this->getTypeName($item['type']);
            $item['userDetailUrl'] = linkurl('game/getList', array('luckDrawId' => $item['id']));
            $item['userWinnerUrl'] = linkurl('game/getWinnerList', array('luckDrawId' => $item['id']));
            $item['getDetailUrl']  = linkurl('game/getDetail', array('luckDrawId' => $item['id']));
            $item['editUrl']       = linkurl('game/addSeller', array('luckDrawId' => $item['id']));
        }
        $this->res['listData'] = $listData;

        return $this->res;
    }

    /**
     * 获取活动类型名称
     * @param $type
     * @return string
     */
    private function getTypeName($type)
    {
        switch ($type) {
            case 1:
                $typeName = '老版大转盘';
                break;
            case 2:
                $typeName = '新版大转盘';
                break;
            case 3:
                $typeName = '翻牌子';
                break;
            default:
                $typeName = '';
        }

        return $typeName;
    }

    /**
     * 添加、编辑商家活动
     * @author lcb
     */
    public function addSeller()
    {
        $this->getMenu();
        if ($_POST) {
            $luckDrawId = isset($_POST['luckDrawId']) ? intval($_POST['luckDrawId']) : 0;
            $opType     = isset($_POST['opType']) ? trim($_POST['opType']) : '';
            if ($luckDrawId && 'changeStatus' != $opType) {
                // 保存编辑信息
                $_POST['id']            = intval($luckDrawId);

                if (isset($_FILES['image']) && isset($_FILES['image']['error']) && 0 == $_FILES['image']['error']) {
                    $uploadImageUrl = $this->uploadImage($_FILES['image']);
                }
                if ($uploadImageUrl) {
                    $_POST['image'] = $uploadImageUrl;
                }
                $updateResult = saveData(DB_PREFIX . 'luckdraw_description', $_POST);
                redirect(linkurl('game/manage'));
            } else if ('changeStatus' == $opType) {
                // 保存状态更新信息
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

                if ($id < 1) {
                    exit(json_encode(array('retcode' => 100, 'msg' => '参数id错误')));
                }

                $rowData = getRow('select id,`status` from ' . DB_PREFIX . 'luckdraw_description where id=:id', array('id' => $id));
                if (empty($rowData)) {
                    exit(json_encode(array('retcode' => 101, 'msg' => '该记录不存在')));
                }

                $status       = $rowData['status'] == 1 ? 0 : 1;
                $updateResult = saveData(DB_PREFIX . 'luckdraw_description', array('id' => $id, "status" => $status));
                if ($updateResult) {
                    exit(json_encode(array('retcode' => 0, 'msg' => '修改成功')));
                }
                exit(json_encode(array('retcode' => 999, 'msg' => '修改失败')));
            } else {
                // 保存新增信息
                $_POST['status']        = 0;

                if (isset($_FILES['image']) && isset($_FILES['image']['error']) && 0 == $_FILES['image']['error']) {
                    $uploadImageUrl = $this->uploadImage($_FILES['image']);
                }
                if ($uploadImageUrl) {
                    $_POST['image'] = $uploadImageUrl;
                }

                $updateResult = saveData(DB_PREFIX . 'luckdraw_description', $_POST);
                redirect(linkurl('game/manage'));
            }
        } else {
            $luckDrawId = isset($_GET['luckDrawId']) ? intval($_GET['luckDrawId']) : 0;
            if ($luckDrawId) {
                $rowData              = getRow('select id,`type`,`status`,description,image,url,invitecode_id,commit from ' . DB_PREFIX . 'luckdraw_description where id=:id', array('id' => $luckDrawId));
                $this->res['rowData'] = $rowData;
            }
            $userId        = $_SESSION['userid']; // 后台用户  user表
            $adminUserInfo = getRow('select merchant_id,telephone from ' . DB_PREFIX . 'user where user_id=' . $userId . ' limit 1');
            $customerInfo  = [];
            if (isset($adminUserInfo['telephone']) && $adminUserInfo['telephone']) {
                $customerInfo = getRow('select customer_id,telephone from ' . DB_PREFIX . 'customer where telephone=' . $adminUserInfo['telephone'] . ' limit 1');
            }
            if(isset($customerInfo['customer_id']) && $customerInfo['customer_id']) {
                // @todo 是否使用商家id来取邀请码  date_available 需要判断是否到期
                // $userInfo=getRow('select merchant_id from ".DB_PREFIX."user where user_id='.$userId);
                // $inviteCodeList = getData('select invitecode_id,invitecode,date_available from ' . DB_PREFIX . 'invitecode where `status`=0 and customer_id=' . $customerInfo['customer_id']);
            }else if(isset($adminUserInfo['merchant_id']) && $adminUserInfo['merchant_id']) {
                // @todo merchant_id 是否可以关联？
                // $inviteCodeList = getData('select invitecode_id,invitecode,date_available from ' . DB_PREFIX . 'invitecode where `status`=0 and merchant_id=' . $adminUserInfo['merchant_id']);
            }
            $inviteCodeList = getData('select invitecode_id,invitecode,date_available from ' . DB_PREFIX . 'invitecode where `status`=0 and date_available<'.time());
            if(isset($rowData) && $rowData){
                foreach($inviteCodeList as &$item){
                    $item['selected'] = '';
                    if($rowData['invitecode_id'] == $item['invitecode_id']){
                        $item['selected'] = 'selected="selected"';
                    }
                }
            }

            //getData("select c.lastname,c.firstname,i.telephone,i.type,i.status,i.invitecode,i.date_added,i.end_date,(select count(customer_id) from hb_customer where invitecode_id=i.invitecode_id) as times from hb_invitecode as i,hb_customer as c where i.customer_id=c.customer_id  order by invitecode_id desc ");
            //getData("select c.lastname,c.firstname,i.telephone,i.type,i.status,i.invitecode_id,i.invitecode,i.end_date,(select count(customer_id) from hb_customer where invitecode_id=i.invitecode_id) as times from hb_invitecode as i,hb_customer as c where i.customer_id=c.customer_id AND i.`status`=0 GROUP BY c.`customer_id`  order by invitecode_id desc");
            //$hasInvitecodeCustomers = getData('select c.lastname,c.firstname,i.telephone,c.customer_id from hb_invitecode as i,hb_customer as c where i.customer_id=c.customer_id AND i.`status`=0 GROUP BY c.`customer_id`');
            //$this->res['inviteCodeCustomerList'] = $hasInvitecodeCustomers;
            $this->res['inviteCodeList'] = $inviteCodeList;
            $this->res['addUrl']         = linkurl('game/addSeller');
            $this->res['backUrl']        = linkurl('game/manage');
        }

        return $this->res;
    }

    /**
     * 文件上传方法
     * @param $uploadFile
     * @return string
     */
    private function uploadImage($uploadFile)
    {
        if (empty($uploadFile['name'])) {
            return '';
        }

        // @ todo 需要验证文件大小 类型？
        $filename    = explode(".", $uploadFile['name']);
        $filename[0] = rand(1, 100000000); //设置随机数长度
        $name        = implode(".", $filename);
        $saveName    = date("Ymd") . time() . $name;

        try {
            $accessKeyId     = OSS_ACCESS_KEY_ID;
            $accessKeySecret = OSS_ACCESS_KEY_SECRET;
            $endpoint        = OSS_ENDPOINT;  //注意域名前不能加bucket的名字
            $bucket          = OSS_BUCKET;

            $object          = "luckdraw_img/" . $saveName;
            $file_local_path = $uploadFile["tmp_name"];
            $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->multiuploadFile($bucket, $object, $file_local_path);  //上传至阿里云OSS

            $img_url = OSS_IMG_ENDPOINT . "/" . $object;

            return $img_url;
        } catch (OssException $e) {
            // print $e->getMessage();
            return '';
        }

        return '';
    }

    /*
     * 参加游戏活动用户列表
     * zxx 2017-5-10  lcb 5-12
     */
    public function getList()
    {
        $this->getMenu();
        $this->res['backUrl'] = linkurl('game/manage');

        $id = isset($_GET['luckDrawId']) ? intval($_GET['luckDrawId']) : 0;
        if ($id < 1) {
            $this->jsEchoError('参数id错误', linkurl('game/manage'));
        }

        $luckDrawInfo              = getRow('select id,`commit` from ' . DB_PREFIX . 'luckdraw_description where id=' . $id . ' limit 1');
        $this->res['luckDrawInfo'] = $luckDrawInfo;

        /*$invite = getData("select ld.commit,ld.type,ld.image,ld.url,ld.status,i.invitecode from hb_luckdraw_description ld
                            left join hb_invitecode i on i.invitecode_id = ld.invitecode_id
                            where ld.type = '" .$type. "' and  i.status = 0 order by ld.id desc limit " .$start. "," .$limit);
        $this->res['data'] = $invite;*/

        $phoneList     = getData('select t.telephone,c.lastname,c.customer_id from ' . DB_PREFIX . 'luckdraw_telephone t,' . DB_PREFIX . 'customer c where c.telephone=t.telephone and luckdraw_description_id=' . $id);
        $winnerList    = getData('select customer_id from ' . DB_PREFIX . 'luckdraw_record where luckdraw_description_id=' . $id);
        $newWinnerList = array();
        if ($winnerList) {
            foreach ($winnerList as $winner) {
                $newWinnerList[$winner['customer_id']] = 1;
            }
        }
        $customerIds = array();
        foreach ($phoneList as $item) {
            $customerIds[] = $item['customer_id'];
        }
        $inviteCodes = $inviteCodeList = array();
        if ($customerIds) {
            $inviteCodes = getData('select invitecode,customer_id from ' . DB_PREFIX . 'invitecode where customer_id in (' . implode(',', $customerIds) . ')');
            foreach ($inviteCodes as $inviteCode) {
                $inviteCodeList[$inviteCode['customer_id']] = $inviteCode['invitecode'];
            }
        }

        foreach ($phoneList as &$item) {
            $item['isWin']      = isset($newWinnerList[$item['customer_id']]) ? '是' : '否';
            $item['inviteCode'] = isset($inviteCodeList[$item['customer_id']]) ? $inviteCodeList[$item['customer_id']] : '';
        }

        $this->res['luckDrawUserList'] = $phoneList;

        return $this->res;
    }

    /*
     * 中奖名单
     * 祝翔翔 2017-5-3
     */
    public function getWinnerList()
    {
        $this->getMenu();
        $this->res['backUrl'] = linkurl('game/manage');
        $id                   = isset($_GET['luckDrawId']) ? intval($_GET['luckDrawId']) : 0;
        if ($id < 1) {
            $this->jsEchoError('参数id错误', linkurl('game/manage'));
        }

        $luckDrawInfo              = getRow('select id,`commit` from ' . DB_PREFIX . 'luckdraw_description where id=' . $id . ' limit 1');
        $this->res['luckDrawInfo'] = $luckDrawInfo;

        // 取出奖品
        $arrProducts = $arrCoupons = array();
        $arrProducts = $this->getProductName($luckDrawInfo['id']);
        $arrCoupons  = $this->getCouponName($luckDrawInfo['id']);

        $luckDrawUserList = getData('SELECT c.lastname,c.telephone,c.customer_id,lr.`order_id`,lr.coupon_id,lr.`type`,i.invitecode FROM ' . DB_PREFIX . 'luckdraw_record lr,' . DB_PREFIX . 'customer c,' . DB_PREFIX . 'invitecode i WHERE i.`status`=0 AND i.invitecode_id=lr.invitecode_id AND lr.luckdraw_description_id=' . $id . ' AND c.customer_id=lr.customer_id');

        //取出用户id 用于取地址
        $arrCustomerIds = $arrOrderIds = array();
        foreach ($luckDrawUserList as $item) {
            if ($item['order_id']) {
                $arrOrderIds[] = $item['order_id'];
            }
            $arrCustomerIds[] = $item['customer_id'];
        }
        $arrAddressNew = array();
        if ($arrCustomerIds) {
            $arrAddress = getData('select customer_id,firstname,telephone,shipping_firstname,shipping_address_1,shipping_city,shipping_country,shipping_zone from ' . DB_PREFIX . 'order where customer_id in (' . implode(',', $arrCustomerIds) . ')');
            foreach ($arrAddress as $address) {
                $arrAddressNew[$address['customer_id']] = $address;
            }
        }
        $arrProductsNew = array();
        if ($arrOrderIds) {
            $arrOrderInfos = getData('select order_id,product_id,`name` from ' . DB_PREFIX . 'order_product where order_id in (' . implode(',', $arrOrderIds) . ')');
            foreach ($arrOrderInfos as $order) {
                $arrProductsNew[$order['order_id']] = $order;
            }
        }

        foreach ($luckDrawUserList as &$item) {
            $item['productName'] = $item['productName2'] = $item['address'] = '';
            if ($item['order_id'] && isset($arrProductsNew[$item['order_id']])) {
                $item['productName']  = isset($arrProductsNew[$item['order_id']]['name']) ? $arrProductsNew[$item['order_id']]['name'] : '';
                $item['productName2'] = isset($arrProductsNew[$item['order_id']]['product_id']) ? $arrProductsNew[$item['order_id']]['product_id'] : '';
            } else if ($item['coupon_id']) {
                $item['productName'] = isset($arrCoupons[$item['coupon_id']]) ? $arrCoupons[$item['coupon_id']] : '';
            }
            if(isset($arrAddressNew[$item['customer_id']])){
                $item['address'] .= isset($arrAddressNew[$item['customer_id']]['shipping_country']) ? $arrAddressNew[$item['customer_id']]['shipping_country'] : '';
                $item['address'] .= isset($arrAddressNew[$item['customer_id']]['shipping_zone']) ? $arrAddressNew[$item['customer_id']]['shipping_zone'] : '';
                $item['address'] .= isset($arrAddressNew[$item['customer_id']]['shipping_city']) ? $arrAddressNew[$item['customer_id']]['shipping_city'] : '';
                $item['address'] .= isset($arrAddressNew[$item['customer_id']]['shipping_address_1']) ? $arrAddressNew[$item['customer_id']]['shipping_address_1'] : '';
            }
            if (!$item['lastname'] && isset($arrAddressNew[$item['customer_id']])) {
                $item['lastname'] = isset($arrAddressNew[$item['customer_id']]['shipping_firstname']) ? $arrAddressNew[$item['customer_id']]['shipping_firstname'] : '';
            }
        }

        $this->res['luckDrawUserList'] = $luckDrawUserList;

        return $this->res;
    }

    /**
     * 获取虚拟奖品
     * @param $luckDrawId
     * @return array
     */
    private function getCouponName($luckDrawId)
    {
        $data   = getData('select c.name,lp.`coupon_id` from ' . DB_PREFIX . 'luckdraw_product lp, ' . DB_PREFIX . 'coupon c where c.`coupon_id`=lp.`coupon_id` and lp.luckdraw_description_id=' . $luckDrawId);
        $return = array();
        if ($data) {
            foreach ($data as $row) {
                $return[$row['coupon_id']] = $row['name'];
            }
        }

        return $return;
    }

    /**
     * 获取实体奖品
     * @param $luckDrawId
     * @return array
     */
    private function getProductName($luckDrawId)
    {
        $data   = getData('select pd.name,lp.`product_id` from ' . DB_PREFIX . 'luckdraw_product lp, ' . DB_PREFIX . 'product_description pd where pd.`product_id`=lp.`product_id` and lp.luckdraw_description_id=' . $luckDrawId);
        $return = array();
        if ($data) {
            foreach ($data as $row) {
                $return[$row['product_id']] = $row['name'];
            }
        }

        return $return;
    }

    public function getDetail()
    {
        $this->getMenu();
        $this->res['editStatusUrl'] = linkurl('game/addPrize');
        $this->res['redirectUrl']   = linkurl('game/manage');

        $id = isset($_GET['luckDrawId']) ? intval($_GET['luckDrawId']) : 0;
        if ($id < 1) {
            $this->jsEchoError('参数id错误', linkurl('game/manage'));
        }
        $this->res['addUrl']       = linkurl('game/addPrize', array('luckDrawId' => $id));
        $luckDrawInfo              = getRow('select id,`commit` from ' . DB_PREFIX . 'luckdraw_description where id=' . $id . ' limit 1');
        $this->res['luckDrawInfo'] = $luckDrawInfo;

        $listData = getData('
        SELECT 
            pd.name,
            lp.`product_id`,
            lp.quantity,
            lp.rank,
            lp.probability,
            lp.`type`,
            lp.`status`,
            lp.coupon_id,
            lp.id 
        FROM ' . DB_PREFIX . 'luckdraw_product lp, ' . DB_PREFIX . 'product_description pd where pd.`product_id`=lp.`product_id` and lp.`product_id`>0 and lp.luckdraw_description_id=' . $id . '
        UNION 
        SELECT 
          c.name,
          lp.`product_id`,
          lp.quantity,
          lp.rank,
          lp.probability,
          lp.`type`,
          lp.`status`,
          lp.coupon_id,
          lp.id 
        FROM
          ' . DB_PREFIX . 'luckdraw_product lp,' . DB_PREFIX . 'coupon c WHERE c.`coupon_id` = lp.`coupon_id`  and lp.`coupon_id`>0 AND lp.luckdraw_description_id = ' . $id . '
        UNION 
        SELECT 
          lp.red_packet as `name`,
          lp.`product_id`,
          lp.quantity,
          lp.rank,
          lp.probability,
          lp.`type`,
          lp.`status`,
          lp.coupon_id,
          lp.id 
        FROM
          ' . DB_PREFIX . 'luckdraw_product lp WHERE  lp.`red_packet`>0 AND lp.luckdraw_description_id = ' . $id);
        foreach ($listData as &$item) {
            $item['editUrl'] = linkurl('game/addPrize', array('id' => $item['id']));
            if (2 == $item['type']) {
                $item['name'] = '红包金额=' . $item['name'];
            }
        }
        $this->res['productList'] = $listData;

        return $this->res;
    }

    /**
     * 添加、编辑商家奖品
     * @author lcb
     */
    public function addPrize()
    {
        $this->getMenu();
        if ($_POST) {
            $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $opType = isset($_POST['opType']) ? trim($_POST['opType']) : '';
            if ($id && 'changeStatus' != $opType) {
                // 保存编辑信息
                $arrSaveData                = $_POST;
                $type = intval($_POST['type']);
                if (!in_array($type, [0, 1, 2])) {
                    $this->jsEchoError('奖品类型错误');
                }
                if (2 == $type) {
                    if (!isset($_POST['red_packet']) || !is_numeric($_POST['red_packet']) || $_POST['red_packet'] < 0) {
                        $this->jsEchoError('红包类型奖品红包金额必须填写');
                    }
                    $arrSaveData['red_packet'] = $_POST['red_packet'];
                }

                $luckDrawId = intval($_POST['luck_draw_id']);

                $updateResult = saveData(DB_PREFIX . 'luckdraw_product', $arrSaveData);
                redirect(linkurl('game/getDetail', array('luckDrawId' => $luckDrawId)));
            } else if ('changeStatus' == $opType) {
                // 保存状态更新信息
                if ($id < 1) {
                    exit(json_encode(array('retcode' => 100, 'msg' => '参数id错误')));
                }
                $rowData = getRow('select id,`status`,luckdraw_description_id from ' . DB_PREFIX . 'luckdraw_product where id=:id', array('id' => $id));
                if (empty($rowData)) {
                    exit(json_encode(array('retcode' => 101, 'msg' => '该记录不存在')));
                }
                $status       = $rowData['status'] == 1 ? 0 : 1;
                $updateResult = saveData(DB_PREFIX . 'luckdraw_product', array('id' => $id, 'status' => $status));
                if ($updateResult) {
                    exit(json_encode(array('retcode' => 0, 'msg' => '修改成功', 'data' => array('url' => linkurl('game/getDetail', array('luckDrawId' => $rowData['luckdraw_description_id']))))));
                }
                exit(json_encode(array('retcode' => 999, 'msg' => '修改失败')));
            } else {
                // 保存新增信息
                $arrSaveData                            = $_POST;
                $arrSaveData['status']                  = 0;

                $from = '';
                if (0 == $arrSaveData['type']) {
                    $from = 'getGoods';
                } else if (0 == $arrSaveData['type']) {
                    $from = 'getCoupon';
                }
                $backUrl = linkurl('game/addPrize', array('luckDrawId' => $arrSaveData['luckdraw_description_id'], 'from' => $from));
                $backUrl = str_ireplace('&amp;', '&', $backUrl);

                if (!in_array($arrSaveData['type'], [0, 1, 2])) {
                    $this->jsEchoError('奖品类型错误', $backUrl);
                }
                if (!$arrSaveData['probability'] || $arrSaveData['probability'] > 100) {
                    $this->jsEchoError('概率必须设置，并且不能大于100', $backUrl);
                }
                //根据 type 和 coupon_id 查询 coupon_image  或 type和product_id 查询product_item_id
                if (0 == $arrSaveData['type']) {
                    $arrSaveData['product_id'] = intval($_POST['product_id']);
                    if (!$arrSaveData['product_id']) {
                        $this->jsEchoError('商品必须选择', $backUrl);
                    }
                    $productInfo = getRow('select product_id,quantity from ' . DB_PREFIX . 'product where product_id=' . $arrSaveData['product_id'] . ' limit 1');
                    if (!$productInfo) {
                        $this->jsEchoError('商品不存在', $backUrl);
                    }
                    if (!$arrSaveData['quantity'] || $arrSaveData['quantity'] > $productInfo['quantity']) {
                        $this->jsEchoError('数量必须设置，并且不能大于库存', $backUrl);
                    }
                    // 多种规格 优先选择 价格低的    status @todo 是否要判断可用性  status=0
                    $productItemInfo = getRow('select product_item_id from ' . DB_PREFIX . 'product_item where product_id=' . $arrSaveData['product_id'] . ' and `status`=0 order by price asc limit 1');
                    if (!$productItemInfo) {
                        $this->jsEchoError('商品规格不存在或不可用', $backUrl);
                    }
                    $arrSaveData['product_item_id'] = $productItemInfo['product_item_id'];
                } else if (1 == $arrSaveData['type']) {
                    $arrSaveData['coupon_id'] = intval($_POST['coupon_id']);
                    if (!$arrSaveData['coupon_id']) {
                        $this->jsEchoError('优惠券必须选择', $backUrl);
                    }
                    // 优惠券状态，1：未使用；2：已使用；3：已失效（过期，作废）
                    $productInfo = getRow('select coupon_id,release_total,get_total,`name`,image,date_end,date_start,is_delete from ' . DB_PREFIX . 'coupon where `status`=1 and coupon_id=' . $arrSaveData['coupon_id'] . ' limit 1');
                    if (!$productInfo) {
                        $this->jsEchoError('优惠券不存在', $backUrl);
                    }
                    if ($productInfo['date_end'] < time()) {
                        $this->jsEchoError('优惠券已经过期', $backUrl);
                    }
                    if ($productInfo['date_start'] > time()) {
                        $this->jsEchoError('优惠券还没有开始使用', $backUrl);
                    }
                    if ($productInfo['is_delete']) {
                        $this->jsEchoError('优惠券已经被删除', $backUrl);
                    }
                    if (!$arrSaveData['quantity'] || $arrSaveData['quantity'] > ($productInfo['release_total'] - $productInfo['get_total'])) {
                        $this->jsEchoError('数量必须设置，并且不能大于库存', $backUrl);
                    }
                    $arrSaveData['coupon_image'] = $productInfo['image'];
                } else if (2 == $arrSaveData['type']) {
                    $arrSaveData['red_packet'] = intval($_POST['red_packet']);
                    if (!$arrSaveData['red_packet']) {
                        $this->jsEchoError('红包金额必须填写', $backUrl);
                    }
                } else {
                    $this->jsEchoError('奖品类型必须选择', $backUrl);
                }
                // red_packet product_item_id coupon_id coupon_image
                // var_dump($_POST, $arrSaveData);exit();
                $updateResult = saveData(DB_PREFIX . 'luckdraw_product', $arrSaveData);
                if (getLastId()) {
                    unset($_SESSION['prizeGetGoods'], $_SESSION['savePrizeTempData'], $_SESSION['hi_prizeGetCoupon']);
                    redirect(linkurl('game/getDetail', array('luckDrawId' => $arrSaveData['luckdraw_description_id'])));
                } else {
                    $this->jsEchoError('添加奖品失败了', $backUrl);
                }
            }
        } else {
            $productId  = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $luckDrawId = isset($_GET['luckDrawId']) ? intval($_GET['luckDrawId']) : 0;
            if ($luckDrawId && !$productId) {
                // 添加奖品
                $luckDrawData                         = getRow('select id,`type`,`status`,description,image,url,invitecode_id,`commit` from ' . DB_PREFIX . 'luckdraw_description where id=:id', array('id' => $luckDrawId));
                $this->res['addData']                 = $luckDrawData;
                $this->res['luckdraw_description_id'] = $luckDrawId;
            } else if ($productId) {
                // 编辑奖品
                $productData           = getRow('select * from ' . DB_PREFIX . 'luckdraw_product where id=:id limit 1', array('id' => $productId));
                $this->res['editData'] = $productData;
                if (isset($productData['coupon_id']) && $productData['coupon_id']) {
                    $couponInfo              = getRow('select `name` from  ' . DB_PREFIX . 'coupon where `coupon_id`=' . $productData['coupon_id'] . ' limit 1');
                    $this->res['couponName'] = $couponInfo['name'];
                } else if (isset($productData['product_id']) && $productData['product_id']) {
                    $productInfo              = getRow('select `name` from ' . DB_PREFIX . 'product_description where `product_id`=' . $productData['product_id'] . ' limit 1');
                    $this->res['productName'] = $productInfo['name'];
                }

            }

            if (isset($_GET['from']) && 'getGoods' == $_GET['from']) {
                $this->res['prizeGetGoods']     = isset($_SESSION['hi_prizeGetGoods']) ? $_SESSION['hi_prizeGetGoods'] : '';
                $this->res['savePrizeTempData'] = isset($_SESSION['hi_savePrizeTempData']) ? $_SESSION['hi_savePrizeTempData'] : '';
                $this->res['prizeGetCoupon']    = isset($_SESSION['hi_prizeGetCoupon']) ? $_SESSION['hi_prizeGetCoupon'] : '';
            } else if (isset($_GET['from']) && 'getCoupon' == $_GET['from']) {
                $this->res['prizeGetGoods']     = isset($_SESSION['hi_prizeGetGoods']) ? $_SESSION['hi_prizeGetGoods'] : '';
                $this->res['savePrizeTempData'] = isset($_SESSION['hi_savePrizeTempData']) ? $_SESSION['hi_savePrizeTempData'] : '';
                $this->res['prizeGetCoupon']    = isset($_SESSION['hi_prizeGetCoupon']) ? $_SESSION['hi_prizeGetCoupon'] : '';
            }

            $this->res['addUrl']               = linkurl('game/addPrize');
            $this->res['getGoodsUrl']          = linkurl('game/getGoodsList', array('luckdraw_description_id' => $luckDrawId));
            $this->res['getCouponUrl']         = linkurl('game/getCoupon', array('luckdraw_description_id' => $luckDrawId));
            $this->res['savePrizeTempDataUrl'] = linkurl('game/savePrizeTempData');
        }

        return $this->res;
    }

    /**
     * js输出错误信息
     * @param        $message 错误信息
     * @param string $backUrl 跳转url  可选参数
     */
    private function jsEchoError($message, $backUrl = '')
    {
        exit('<script>alert("' . $message . '");' . ($backUrl ? 'location.href="' . $backUrl . '";' : '') . '</script>');
    }

    /**
     * 选择商品或者优惠券之前保存奖品临时数据
     */
    public function savePrizeTempData()
    {
        if (isset($_POST['opType']) && 'saveTempData' == $_POST['opType']) {
            unset($_POST['opType']);
            $_SESSION['hi_savePrizeTempData'] = $_POST;
            exit(json_encode(array('retcode' => 0, 'msg' => 'success', 'data' => $_SESSION['hi_savePrizeTempData'])));
        }
        exit(json_encode(array('retcode' => 1, 'msg' => 'error', 'data' => $_POST)));
    }

    /**
     * 获取商品列表
     * @return array
     */
    public function getGoodsList()
    {
        if (isset($_POST['opType']) && $_POST['opType'] == 'getGoods') {
            if (isset($_POST['productId']) && $_POST['productId']) {
                $_SESSION['hi_prizeGetGoods'] = $_POST;
                exit(json_encode(array('retcode' => 0, 'msg' => 'success', 'data' => array('luckdraw_description_id' => $_SESSION['hi_savePrizeTempData']['luckdraw_description_id']))));
            }
            exit(json_encode(array('retcode' => 1, 'msg' => '没有选择商品')));
        }
        $this->getMenu();
        $where = '';
        if (isset($_GET['name']) && $_GET['name']) {
            $where = ' and pd.name like \'%' . addslashes($_GET['name']) . '%\'';
        }
        $page = 1;
        if (isset($_GET['page'])) {
            $page = intval($_GET['page']);
            if ($page < 1) {
                $page = 1;
            }
        }
        $start = ($page - 1) * 20;
        $sql   = 'SELECT p.`product_id`,
            p.`model`,
            p.`quantity`,
            p.`image`,
            p.`manufacturer_id`,
            p.`price`, 
            p.`points`,
            p.marketprice,
            p.proxyprice,
            p.`status`,
            p.brand_id,
            b.name as bname,
            pd.name
          FROM `hb_product` as p,hb_product_description as pd,hb_manufacturer as b where  p.`status`=1 and  p.product_id=pd.product_id  and p.brand_id=b.manufacturer_id ' . $where . ' limit ' . $start . ',20';

        $dt              = getData($sql);
        $this->res['dt'] = $dt;

        $total                    = getRow('SELECT count(*) as count 
        FROM `hb_product` as p,hb_product_description as pd,hb_manufacturer as b where p.`status`=1 and  p.product_id=pd.product_id  and p.brand_id=b.manufacturer_id ' . $where, 60);
        $total                    = $total['count'];
        $total_page               = ceil($total / 20);
        $this->res['is_end_page'] = 1;
        if ($page == $total_page) {
            $this->res['is_end_page'] = 0;
        }
        $this->getPages($page, $total_page);
        $this->res["addAd"]           = linkurl("common/addAd");
        $this->res["searchName"]      = isset($_GET['name']) ? $_GET['name'] : '';
        $this->res["getGoodsList"]    = linkurl("game/getGoodsList");
        $this->res["backAddPrizeUrl"] = linkurl("game/addPrize");

        return $this->res;
    }

    public function getCoupon()
    {
        if (isset($_POST['opType']) && $_POST['opType'] == 'getCoupon') {
            if (isset($_POST['couponId']) && $_POST['couponId']) {
                $_SESSION['hi_prizeGetCoupon'] = $_POST;
                exit(json_encode(array('retcode' => 0, 'msg' => 'success', 'data' => array('luckdraw_description_id' => $_SESSION['hi_savePrizeTempData']['luckdraw_description_id']))));
            }
            exit(json_encode(array('retcode' => 1, 'msg' => '没有选择优惠券')));
        }
        $this->getMenu();
        $where = '';
        if (isset($_GET['name']) && $_GET['name']) {
            $where = ' and c.name like \'%' . addslashes($_GET['name']) . '%\'';
        }
        if (isset($_GET['type']) && $_GET['type']) {
            $where .= ' and c.`type`=' . (int)$_GET['type'];
        }
        $page = 1;
        if (isset($_GET['page'])) {
            $page = intval($_GET['page']);
            if ($page < 1) {
                $page = 1;
            }
        }
        $start = ($page - 1) * 20;
        $sql   = 'SELECT c.coupon_id,c.`name`,c.`type`,c.date_start,c.date_end,c.image,c.content,c.discount_desn
           FROM `hb_coupon` as c where  c.`status`=1 and  c.is_delete=0 ' . $where . ' limit ' . $start . ',20';

        $dt = getData($sql);
        foreach ($dt as &$item) {
            $item['typeName']   = $this->getCouponTypeName($item['type']);
            $item['date_start'] = date('Y-m-d H:i:s', $item['date_start']);
            $item['date_end']   = date('Y-m-d H:i:s', $item['date_end']);
        }
        $this->res['dt'] = $dt;

        $total                    = getRow('SELECT count(c.coupon_id) as count 
        FROM `hb_coupon` as c where c.`status`=1 and  c.is_delete=0 ' . $where, 60);
        $total                    = $total['count'];
        $total_page               = ceil($total / 20);
        $this->res['is_end_page'] = 1;
        if ($page == $total_page) {
            $this->res['is_end_page'] = 0;
        }
        $this->getPages($page, $total_page);
        $this->res["searchName"]      = isset($_GET['name']) ? $_GET['name'] : '';
        $this->res["searchType"]      = isset($_GET['type']) ? $_GET['type'] : '';
        $this->res["getGoodsList"]    = linkurl("game/getCoupon");
        $this->res["backAddPrizeUrl"] = linkurl("game/addPrize");

        return $this->res;
    }

    /**
     * 优惠券类型：1全部商品，2部分商品，3部分商品类别，4部分品牌
     * @param $type
     * @return string
     */
    private function getCouponTypeName($type)
    {
        $typeName = '';
        switch ($type) {
            case 1:
                $typeName = '全部商品';
                break;
            case 2:
                $typeName = '部分商品';
                break;
            case 3:
                $typeName = '部分商品类别';
                break;
            case 4:
                $typeName = '部分品牌';
                break;
            default:
                break;
        }

        return $typeName;
    }

    /*
     * 中奖名单
     * 祝翔翔 2017-5-3
     */
    function dzp()
    {
        $this->getMenu();

        return $this->res;
    }

    /*
     * 新增大转盘
     * 王志超 17.4.20
     */
    function addLuck()
    {
        include_once '../lib/picture.php';
        $post = $_POST;
        if (!isset($post['type'])) {
            return $this->res = array(
                'retcode' => 1000,
                'msg'     => '参数错误'
            );
        }

        //index_type=1时加载页面，index_type=2时提交表单
        if ($post['index_type'] == 1) {
            if (isset($post['luck_id']) && $post['luck_id'] > 0) {
                $luck_description             = getRow("select * from hb_luckdraw_description where id = '" . $post['luck_id'] . "'");
                $this->res['uck_description'] = $luck_description;
            }
            $this->res['invite'] = getData("select * from hb_invitecode where status = 0");

            return $this->res;
        }

        //商品主图
        $image    = $_FILES['image'];
        $up_image = upload_img($image);
        if ($up_image) {
            $post['image'] = $up_image;
        }

        if (isset($post['luck_id']) && $post['luck_id'] > 0) {
            //type=1时大转盘，type=2翻牌子
            if ($post['type'] == 1) {
                $post['url'] = $_SERVER['HTTP_HOST'] . "/hi2017/hi2017/web/active/luckdraw/index.html?luck_id=" . $post['luck_id'];
//                $post['url'] = $_SERVER['HTTP_HOST']."/web/active/luckdraw/index.html?luck_id=".$post['luck_id'];//测试或正式url
            }

            exeSql("update hb_luckdraw_description set type=:type,commit=:commit,invitecode_id=:invitecode_id,url=:url,status=0 where id='" . (int)$post['luck_id'] . "'", $post);

            if ($up_image) {
                exeSql("update hb_luckdraw_description set image='" . $up_image . "'");
            }

            echo "<script>alert('aa')</script>>";
            exit;
        } else {
            exeSql("insert into hb_luckdraw_description set type=:type,commit=:commit,invitecode_id=:invitecode_id,image=:image,status=0", $post);
            $id = getLastId();
            //type=1时大转盘，
            if ($post['type'] == 1) {
                $url = $_SERVER['HTTP_HOST'] . "/hi2017/hi2017/web/active/luckdraw/index.html?luck_id=" . $id;
//                $url = $_SERVER['HTTP_HOST']."/web/active/luckdraw/index.html?luck_id=".$id;//测试或正式url
                exeSql("update hb_luckdraw_description set url='" . $url . "'");
            }
            echo "<script>alert('aa')</script>>";
            exit;
        }

    }

    /*
     * 大转盘中奖列表
     * 王志超 17.4.21
     */
    function getLuckOrder()
    {
        $this->getMenu();
        $get            = $_GET;
        $get['luck_id'] = 20;
        $page           = 1;
        if (isset($_GET['page']))
            $page = $_GET['page'];

        $limit = 20;
        $start = ($page - 1) * $limit;

        $list = getData("select o.shipping_firstname,
                          o.shipping_country,
                          o.shipping_zone,
                          o.shipping_city,
                          o.shipping_address_1,
                          o.shipping_custom_field,
                          i.invitecode
                          from hb_luckdraw_record lr
                          left join hb_invitecode i on i.invitecode_id = lr.invitecode_id
                          left join hb_order o on o.order_id = lr.order_id
                          left join hb_order_product op on op.order_id = lr.order_id
                          left join hb_product_description pd on pd.product_id = op.product_id
                          left join hb_coupon c on c.coupon_id = lr.coupon_id
                          where lr.invitecode_id = '" . $get['luck_id'] . "' and lr.luckdraw_description_id='" . $get['luck_id'] . "' order by lr.date_added desc limit " . $start . "," . $limit . "");

        $total                    = getRow("select count(*) as total from hb_luckdraw_record where invitecode_id = '" . $get['luck_id'] . "'", 600);
        $total                    = $total['total'];
        $total_page               = ceil($total / 20);
        $this->res['is_end_page'] = 1;
        if ($page == $total_page) {
            $this->res['is_end_page'] = 0;
        }

        $this->getPages($page, $total_page);

        return $this->res;
    }

    /*
     * 启用和禁用大转盘
     * 王志超 17.4.21
     */
    function upOffLuck()
    {
        $post = $_POST;

        if (!isset($post['luck_id']) || !isset($post['status'])) {
            return $this->res = array(
                'retcode' => 1000,
                'msg'     => '参数错误'
            );
        }

        exeSql("update hb_luckdraw_description set status = '" . $post['status'] . "' where id = '" . $post['luck_id'] . "'");

        return $this->res;
    }

    /*
     * 大转盘奖品列表
     * 王志超 17.4.20
     */
    function getProductList()
    {
        $get  = $_GET;
        $type = isset($get['type']) ? $get['type'] : 1;

        if ($type == 1) {
            $product = getData("select * from hb_active_product where invitecode_id = '" . $get['invite'] . "'");
        } else {
            $product = getData("select lp.*,pd.name,c.discount from hb_luckdraw_product lp
                                LEFT JOIN hb_product p on lp.product_id = p.product_id
                                LEFT JOIN hb_product_description pd on lp.product_id = pd.product_id
                                LEFT JOIN hb_product_item pi on lp.product_item_id = pi.product_item_id
                                LEFT JOIN hb_coupon c on c.coupon_id = lp.coupon_id
                                where lp.invitecode_id = '" . $get['invite'] . "'");
        }
    }

    function addLuckPrd()
    {
        $post = $_POST;

        //新增或编辑奖品
        if (!empty($post)) {
            if ($post['type'] == 0) {
                $columns = " product_id = '" . $post['product_id'] . "',product_item_id = '" . $post['product_item_id'] . "',quantity = '" . $post['quantity'] . "',rank = '" . $post['rank'] . "',probability='" . $post['probability'] . "',type = '" . $post['type'] . "' ";
            } else if ($post['type'] == 1) {
                $columns = " coupon_id = '" . $post['coupon_id'] . "',quantity = '" . $post['quantity'] . "',rank = '" . $post['rank'] . "',probability='" . $post['probability'] . "',type = '" . $post['type'] . "' ";
            } else {
                $columns = " red_packet = '" . $post['red_packet'] . "',quantity = '" . $post['quantity'] . "',rank = '" . $post['rank'] . "',probability='" . $post['probability'] . "',type = '" . $post['type'] . "' ";
            }

            if (isset($post['luck_product_id'])) {
                exeSql("update hb_luckdraw_product set " . $columns . " where id = '" . $post['luck_product_id'] . "'");
            } else {
                exeSql("insert into hb_luckdraw_product set luckdraw_description_id = '" . $post['luckdraw_description_id'] . "',invitecode_id = '" . $post['invitecode_id'] . "'" . $columns . "");
            }
        }

        $get = $_GET;
        if (isset($get['luck_product_id'])) {
            $prd_info              = getRow("select lp.id,lp.product_id,
                                  lp.product_item_id,
                                  lp.quantity,
                                  lp.rank,
                                  lp.probability,
                                  lp.type,
                                  lp.coupon_id,
                                  pd.name,
                                  pi.product_options,
                                  c.name as coupon_name,
                                  c.discount
                                  from hb_luckdraw_product lp
                                  left join hb_product_description pd on pd.product_id = lp.product_id
                                  left join hb_product_item pi on pi.product_item_id = lp.product_item_id
                                  left join hb_coupon c on c.coupon_id = lp.coupon_id
                                  where lp.id = '" . $get['luck_product_id'] . "'");
            $this->res['prd_info'] = $prd_info;
        }

        return $this->res;
    }

}