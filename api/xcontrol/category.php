<?php

include 'autoload.php';

/**
 * 新版商品分类
 * User: lcb
 * Date: 2017/6/12
 */
class category extends base
{

    /**
     * 获取所有类别
     */
    public function getcategories()
    {
        $this->isPost();
        $json = [];
        // 已登录
        if (isset($_POST['customerid']) && $_POST['customerid']) {
            $customerid = str_replace("\"", '', htmlspecialchars_decode($_POST['customerid']));
            $loginInfo  = CustomerModel::getInstance()->getLoginAttempts($customerid);
            if ($loginInfo) {
                // 登录用户
                $merchantid = CustomerModel::getInstance()->getMerchantId($customerid);
                if ($merchantid == -1) {
                    $this->error(1200, '没有商家编号');
                }
                // CategoryModel::getInstance()->getCategoryList();
                //获取属性列表
                $categorylist         = CategoryModel::getInstance()->getCategoryList($merchantid);
                $json['categorylist'] = [];
                $json['categorylist'] = $this->parseCateList($categorylist);
                //获取商品
                $defaultprdlist = $this->getcolprdlist(0, $merchantid);

                $json['defaultprdlist'] = [];
                if ($defaultprdlist['defaultprdlist']) {
                    $json['defaultprdlist'] = $defaultprdlist['defaultprdlist'];
                }

            } else {
                $this->error(1101, 'error');
            }
        } else {
            // 未登录的情况
            $merchantid           = 0;
            $categorylist         = CategoryModel::getInstance()->getCategoryList($merchantid);
            $json['categorylist'] = [];
            $json['categorylist'] = $this->parseCateList($categorylist);

            $defaultprdlist         = $this->getcolprdlist(0, $merchantid);
            $json['defaultprdlist'] = [];
            if ($defaultprdlist['defaultprdlist']) {
                $json['defaultprdlist'] = $defaultprdlist['defaultprdlist'];
            }
        }
        $json['retcode'] = 0;

        return $this->success($json);
    }


    /**
     * @param $categoryid
     * @param $type
     * @param $offset   AND p.`quantity` > '0'
     * @param $count    AND p.`quantity` > '0'
     * @return mixed
     * @author      lcb
     * @description This method is for api/home/searchproduct interface and for api/home/getprdlist(列表排列)
     */
    private function getProductsBySearchResultId($categoryid, $merchantid, $type, $offset, $count)
    {
        //获取该分类id下的所有二级分类，并拼接成字符串用于查询
        $chil_category_id = getData("select category_id from `hb_category` where parent_id = '" . (int)$categoryid . "'");

        if (!empty($chil_category_id)) {
            $chil_category_array = '(';
            foreach ($chil_category_id as $key => $val) {
                $chil_category_array .= $val['category_id'] . ',';
            }
            $chil_category_array = rtrim($chil_category_array, ',');
            $chil_category_array .= ')';
        }

        if ($merchantid == 0) {
            $sql = "SELECT DISTINCT p.quantity, p.date_added AS addedtime, p.sales AS salenumber, p.product_id as productid, p.image as productimg, pd.`name` as productname, p.marketprice AS originalprice, p.price as finalprice,p.price,p.proxyprice FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c on (p.product_id = p2c.product_id) left join " . DB_PREFIX . "category c on c.category_id = p2c.category_id  WHERE p.status = '1'  AND c.type=0 AND p2c.type=1 AND (p2c.category_id = '" . (int)$categoryid . "' or c.parent_id = '" . (int)$categoryid . "'";
            if (isset($chil_category_array)) {
                $sql .= " or c.parent_id in " . $chil_category_array;
            }
            $sql .= ") ORDER BY " . $type . " LIMIT " . $offset . "," . $count;

            return getData($sql);
        } else {
            $sql = "SELECT DISTINCT p.quantity, p.date_added AS addedtime, p.sales AS salenumber, p.product_id as productid, p.image as productimg, pd.`name` as productname, p.marketprice AS originalprice, p.proxyprice as finalprice,p.price,p.proxyprice FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c on (p.product_id = p2c.product_id) left join " . DB_PREFIX . "category c on c.category_id = p2c.category_id WHERE p.status = '1'  AND c.type=0 AND p2c.type=1 AND p.merchant_id = '" . (int)$merchantid . "' AND (p2c.category_id = '" . (int)$categoryid . "' or c.parent_id = '" . (int)$categoryid . "'";
            if (isset($chil_category_array)) {
                $sql .= "or c.parent_id in " . $chil_category_array;
            }
            $sql .= ") ORDER BY " . $type . " LIMIT " . $offset . "," . $count;

            return getData($sql);
        }
    }

    /**
     * @author      lcb
     * @description 获取全部商品   AND p.`quantity` > '0'  AND p.`quantity` > '0'
     */
    private function getAllProducts($merchantid, $type, $offset, $count)
    {
        if ($merchantid == 0) {
            return getData("SELECT DISTINCT p.quantity, p.date_added AS addedtime, p.sales AS salenumber, p.product_id as productid, p.image as productimg, pd.`name` as productname, p.marketprice AS originalprice, p.price as finalprice,p.price,p.proxyprice FROM hb_product p LEFT JOIN hb_product_description pd ON (p.product_id = pd.product_id) WHERE p.status = '1'  AND p.merchant_id = '1' ORDER BY $type LIMIT $offset, $count");
        } else {
            return getData("SELECT DISTINCT p.quantity, p.date_added AS addedtime, p.sales AS salenumber, p.product_id as productid, p.image as productimg, pd.`name` as productname, p.marketprice AS originalprice, p.proxyprice as finalprice,p.price,p.proxyprice FROM hb_product p LEFT JOIN hb_product_description pd ON (p.product_id = pd.product_id) WHERE p.status = '1'  AND p.merchant_id = '" . (int)$merchantid . "' ORDER BY $type LIMIT $offset, $count");
        }
    }

    /**
     * @param $categoryid
     * @param $merchantid
     * @return array
     * @author      lcb
     * @description 分类 - 获取 按两列排列方式显示的 产品列表
     */
    public function getcolprdlist($categoryid = 0, $merchantid = 0, $from = '')
    {
        $this->isPost();
        $returndata = $json = [];
        $offset     = 0;
        $count      = 10;
        $type       = 1; // 0:最新 1:销量 2:最终价格升序 3:最终价格降序 4:折扣升序 5:折扣降序
        $typelist   = array(
            0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
            1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
            2 => 'p.quantity!=0 DESC,p.price,p.quantity DESC',
            3 => 'p.quantity!=0 DESC,p.price DESC,p.quantity DESC',
            4 => 'p.quantity!=0 DESC,p.price/p.marketprice,p.quantity DESC',
            5 => 'p.quantity!=0 DESC,p.price/p.marketprice DESC,p.quantity DESC',
        );

        if (isset($_POST['offset'])) {
            $offset = intval($_POST['offset']);
        }
        if (isset($_POST['type'])) {
            $type = intval($_POST['type']);
        }
        $returndata['displaymode'] = CategoryModel::getInstance()->getCatDisplayMode($categoryid); // 返回给第一个接口
        if ($merchantid == 0) {
            if (isset($_POST['categoryid'])) {
                // 请求的此接口
                $categoryid = str_replace("\"", '', htmlspecialchars_decode($_POST['categoryid']));
                // update points 修改分类的浏览量
                CategoryModel::getInstance()->updatePoints($categoryid);
                if (!empty($_POST['customerid'])) {
                    $customerid = str_replace("\"", '', htmlspecialchars_decode($_POST['customerid']));
                    $loginInfo  = CustomerModel::getInstance()->getLoginAttempts($customerid);
                    if (!$loginInfo) {
                        $this->error(1101, 'error');
                    }
                    // 已登录用户
                    $merchantid = CustomerModel::getInstance()->getMerchantId($customerid);
                    if ($merchantid == -1) {
                        $this->error(1200, 'error');
                    }
                    if ($categoryid == 0) {
                        if ($merchantid > 0) {
                            $typelist = array(
                                0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
                                1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
                                2 => 'p.quantity!=0 DESC,p.proxyprice,p.quantity DESC',
                                3 => 'p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC',
                                4 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC',
                                5 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC',
                            );
                        }
                        $prdlist         = $this->getAllProducts($merchantid, $typelist[$type], $offset, $count);
                        $json['prdlist'] = $this->parsePrdList($prdlist);
                    } else {
                        $catemerchantid = CategoryModel::getInstance()->getCateMerchantId($categoryid);
                        if ($catemerchantid == -1) {
                            $this->error(1200, 'error');
                        } else {
                            if ($merchantid > 0) {
                                $typelist = array(
                                    0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
                                    1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
                                    2 => 'p.quantity!=0 DESC,p.proxyprice,p.quantity DESC',
                                    3 => 'p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC',
                                    4 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC',
                                    5 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC',
                                );
                            }
                            $prdlist         = $this->getProductsBySearchResultId($categoryid, $merchantid, $typelist[$type], $offset, $count);
                            $json['prdlist'] = $this->parsePrdList($prdlist);
                        }
                    }
                } else {
                    // 未登录用户
                    if ($categoryid == 0) {
                        $prdlist = $this->getAllProducts($merchantid, $typelist[$type], $offset, $count);
                    } else {
                        $prdlist = $this->getProductsBySearchResultId($categoryid, $merchantid, $typelist[$type], $offset, $count);
                    }
                    $json['prdlist'] = $this->parsePrdList($prdlist);
                }
            } else {
                // 从 getcategories 传入，且merchantid = 0
                if (empty($categoryid)) {
                    $categoryid = 0;
                }

                if ($categoryid == 0) {
                    $prdlist = $this->getAllProducts($merchantid, $typelist[$type], $offset, $count);
                } else {
                    $prdlist = $this->getProductsBySearchResultId($categoryid, $merchantid, $typelist[$type], $offset, $count);
                }
                $returndata['defaultprdlist'] = $json['prdlist'] = $this->parsePrdList($prdlist);
            }
        } else {
            // 从getcategories接口传入，且 merchantid 不为0
            $typelist = array(
                0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
                1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
                2 => 'p.quantity!=0 DESC,p.proxyprice,p.quantity DESC',
                3 => 'p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC',
                4 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC',
                5 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC',
            );
            if ($categoryid == 0) {
                $prdlist = $this->getAllProducts($merchantid, $typelist[$type], $offset, $count);
            } else {
                $prdlist = $this->getProductsBySearchResultId($categoryid, $merchantid, $typelist[$type], $offset, $count);
            }

            $returndata['defaultprdlist'] = $this->parsePrdList($prdlist);
        }
        $json['retcode'] = 0;
        if ('getcategories' == $from && isset($returndata)) {
            return $returndata;
        }

        return $this->success($json);
    }

    private function parsePrdList($prdList)
    {
        if (!$prdList) {
            return [];
        }
        $returnPrdList = [];
        $index         = 0;
        foreach ($prdList as $item) {
            $item['link'] = '';
            if ($item['productimg'] != '') {
                $item['productimg'] = self::handleImage($item['productimg']);
            }
            if ($item['quantity'] == 0) {
                $item['isenough'] = '2';
            } else if ($item['quantity'] < 10) {
                $item['isenough'] = '0';
            } else {
                $item['isenough'] = '1';
            }
            if ((time() - strtotime($item['addedtime'])) > 3600 * 24 * 3) {
                $item['isnew'] = '0';
            } else {
                $item['isnew'] = '1';
            }
            $item['originalprice'] = sprintf("%.2f", $item['originalprice']);
            $item['finalprice']    = sprintf("%.2f", $item['finalprice']);
            $item['price']         = isset($item['price']) && $item['price'] ? sprintf("%.2f", $item['price']) : 0.00;
            $item['proxyprice']    = isset($item['price']) && $item['price'] ? sprintf("%.2f", $item['proxyprice']) : 0.00;
            unset($item['quantity']);
            unset($item['addedtime']);
            $returnPrdList[$index] = $item;
            $index++;
        }

        return $returnPrdList;
    }


    private function parseCateList($categoryList)
    {
        if (!$categoryList) {
            return [];
        }

        $totalprd = [
            'categoryid'   => '0',
            'categoryname' => '全部商品',
            'displaymode'  => 0,
            'categoryimg'  => 'catalog/gd/product/yanjing1-xq4.jpg'
        ];
        array_unshift($categorylist, $totalprd);
        $index    = 0;
        $cateList = [];
        foreach ($categorylist as $category) {
            $category['displaymode']  = CategoryModel::getInstance()->getCatDisplayMode($category['categoryid']);
            $category['categorylink'] = '';
            if ($category['categoryimg'] != '') {
                $category['categoryimg'] = self::handleImage($category['categoryimg']);
            }
            $cateList[$index] = $category;
            $index++;
        }

        return $cateList;
    }

    /**
     * @param $imgbefore
     * @return string
     * @description 处理图片地址 pw
     */
    private static function handleImage($imgbefore)
    {
        if ((strpos($imgbefore, 'http://') === false) || (strpos($imgbefore, 'http://') > 0)) {
            return TEST_IP1 . '/image/' . $imgbefore;
        }

        return $imgbefore;
    }


    /**
     * 首页活动分类的产品
     */
    public function indexactivity()
    {
        $this->isPost();

        $type     = 1; // 0:最新 1:销量 2:最终价格升序 3:最终价格降序 4:折扣升序 5:折扣降序
        $typelist = array(
            0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
            1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
            2 => 'p.quantity!=0 DESC,p.price,p.quantity DESC',
            3 => 'p.quantity!=0 DESC,p.price DESC,p.quantity DESC',
            4 => 'p.quantity!=0 DESC,p.price/p.marketprice,p.quantity DESC',
            5 => 'p.quantity!=0 DESC,p.price/p.marketprice DESC,p.quantity DESC',
        );
        $count    = 10;
        $json     = [];

        //类别编号
        $categoryid = isset($_POST["categoryid"]) ? $_POST["categoryid"] : 0;
        //没登录显示为默认的

        //用户编号
        $customerid = isset($_POST["customerid"]) ? $_POST["customerid"] : 0;
        //排列方式
        $type = isset($_POST["type"]) ? $_POST["type"] : $type;
        //页码
        $offset = isset($_POST["offset"]) ? intval($_POST["offset"]) : 1;

        $offset     = ($offset - 1) * $count;
        $merchantid = 0;

        if($categoryid){
            CategoryModel::getInstance()->updatePoints($categoryid);
        }

        if ($customerid) {
            //判断是否登录
            $customerid = str_replace("\"", '', htmlspecialchars_decode($customerid));
            $loginInfo  = CustomerModel::getInstance()->getLoginAttempts($customerid);
            if ($loginInfo) {
                // 已登录用户
                $merchantid = CustomerModel::getInstance()->getMerchantId($customerid);
                if ($merchantid == -1) {
                    $merchantid = 1; // TODO
                }
            }
        }
        if ($merchantid == 0) {
            $merchantid1 = 1;
        } else {
            $merchantid1 = $merchantid;
            $typelist    = array(
                0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
                1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
                2 => 'p.quantity!=0 DESC,p.proxyprice,p.quantity DESC',
                3 => 'p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC',
                4 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC',
                5 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC',
            );
        }

        $all = ProductModel::getInstance()->sel_product_by_cat($merchantid1, $typelist[$type], $offset, $count, $categoryid);
        if (!empty($all)) {
            //不为空
            foreach ($all as $k => $v) {
                if ($v['quantity'] == 0) {
                    $all[$k]['isenough'] = '2';
                } else if ($v["quantity"] < 10) {
                    //不够
                    $all[$k]["isenough"] = 0;
                } else {
                    //够
                    $all[$k]["isenough"] = 1;
                }
                $all[$k]["link"] = '';

                if ($v['productimg'] === '') {
                    $all[$k]['productimg'] = '';
                } else {
                    $all[$k]['productimg'] = self::handleImage($v['productimg']);
                }
                $all[$k]['originalprice'] = sprintf("%.2f", $v["marketprice"]);
                if ($merchantid == 0) {
                    //不是VIP
                    $all[$k]['finalprice'] = sprintf("%.2f", $v["price"]);
                } else if ($merchantid == 1) {
                    //VIP
                    $all[$k]['finalprice'] = sprintf("%.2f", $v["proxyprice"]);
                } else if ($merchantid != 0 && $merchantid != 1) {
                    //商户的代理
                    $all[$k]['finalprice'] = sprintf("%.2f", $v["proxyprice"]);
                }

                unset($v['marketprice']);

                if ((time() - strtotime($v['addedtime'])) > 3600 * 24 * 3) {
                    $all[$k]['isnew'] = '0';
                } else {
                    $all[$k]['isnew'] = '1';
                }
            }
            $a = array();
            if (!empty($all)) {
                foreach ($all as $k => $v) {
                    $a[$k]["salenumber"]    = $v["salenumber"];
                    $a[$k]["productid"]     = $v["productid"];
                    $a[$k]["productimg"]    = $v["productimg"];
                    $a[$k]["link"]          = $v["link"];
                    $a[$k]["productname"]   = $v["productname"];
                    $a[$k]["originalprice"] = $v["originalprice"];
                    $a[$k]["finalprice"]    = $v["finalprice"];
                    $a[$k]["isnew"]         = $v["isnew"];
                    $a[$k]["isenough"]      = $v["isenough"];
                    $a[$k]["salenumber"]    = $v["salenumber"];

                    if (isset($v["proxyprice"]) && $v["proxyprice"]) $a[$k]['proxyprice'] = sprintf("%.2f", $v["proxyprice"]);
                    if (isset($v["price"]) && $v["price"]) $a[$k]['price'] = sprintf("%.2f", $v["price"]);
                }
            }
            $json["prdlist"] = $a;
        } else {
            $json["prdlist"] = [];
        }
        $json["retcode"] = 0;

        return $this->success($json);
    }



    //

    /**
     * @author      pengwei
     * @description 分类 - 获取 按单列排列方式显示的 产品列表
     * updated at 2016-09-11   6-12 lcb
     * done
     */
    public function getrankprdlist()
    {
        $this->isPost();
        $json   = [];
        $offset = 0;
        $count  = 10;
        if (isset($_POST['offset'])) {
            $offset = intval($_POST['offset']);
        }
        if (!isset($_POST['categoryid']) || !$_POST['categoryid']) {
            $this->error(1000, 'error');
        }
        $categoryid = str_replace("\"", '', htmlspecialchars_decode($_POST['categoryid']));
        if (!empty($_POST['customerid'])) {
            //登录的用户
            $customerid = str_replace("\"", '', htmlspecialchars_decode($_POST['customerid']));
            $loginInfo  = CustomerModel::getInstance()->getLoginAttempts($customerid);
            if (!$loginInfo) {
                $this->error(1101, 'error');
            }
            $merchantid = CustomerModel::getInstance()->getMerchantId($customerid);
            if ($merchantid == -1) {
                $this->error(1200, 'error');
            }
            $catemerchantid = CategoryModel::getInstance()->getCateMerchantId($categoryid);
            if ($catemerchantid == -1) {
                $this->error(1200, 'error');
            }
            if ($merchantid == 0 && $catemerchantid == 1) {
                $prdlist         = CategoryModel::getInstance()->getProductsByCatId($categoryid, $merchantid, $offset, $count);
                $json['prdlist'] = array();
                if ($prdlist) {
                    $index = 0;
                    foreach ($prdlist as $item) {
                        $item['productimg']      = self::handleImage($item['productimg']);
                        $item['freetype']        = intval($item['freetype']);
                        $item['originalprice']   = sprintf("%.2f", $item['originalprice']);
                        $item['finalprice']      = sprintf("%.2f", $item['finalprice']);
                        $item['price']           = sprintf("%.2f", $item['price']);
                        $item['proxyprice']      = sprintf("%.2f", $item['proxyprice']);
                        $json['prdlist'][$index] = $item;
                        $index++;
                    }
                }
            } else {
                if ($merchantid != $catemerchantid) {
                    $this->error(1220, '分类-错误的类别编号');
                }
                $prdlist         = CategoryModel::getInstance()->getProductsByCatId($categoryid, $merchantid, $offset, $count);
                $json['prdlist'] = array();
                if ($prdlist) {
                    $index = 0;
                    foreach ($prdlist as $item) {
                        $item['productimg']      = self::handleImage($item['productimg']);
                        $item['freetype']        = intval($item['freetype']);
                        $item['originalprice']   = sprintf("%.2f", $item['originalprice']);
                        $item['finalprice']      = sprintf("%.2f", $item['finalprice']);
                        $item['price']           = sprintf("%.2f", $item['price']);
                        $item['proxyprice']      = sprintf("%.2f", $item['proxyprice']);
                        $json['prdlist'][$index] = $item;
                        $index++;
                    }
                }
            }
        } else {
            //未登录用户
            $merchantid      = 0;
            $prdlist         = CategoryModel::getInstance()->getProductsByCatId($categoryid, $merchantid, $offset, $count);
            $json['prdlist'] = array();
            if ($prdlist) {
                $index = 0;
                foreach ($prdlist as $item) {
                    $item['productimg']      = self::handleImage($item['productimg']);
                    $item['freetype']        = intval($item['freetype']);
                    $item['originalprice']   = sprintf("%.2f", $item['originalprice']);
                    $item['finalprice']      = sprintf("%.2f", $item['finalprice']);
                    $item['price']           = sprintf("%.2f", $item['price']);
                    $item['proxyprice']      = sprintf("%.2f", $item['proxyprice']);
                    $json['prdlist'][$index] = $item;
                    $index++;
                }
            }
        }
        $json['retcode'] = 0;

        return $this->success($json);
    }

    /*
     * author:王志超
     * 2016.12.15
     * 分类列表接口
     * 17.2.27 修改
     */
    public function index()
    {
        $merchantid = 1;
        $this->isPost();
        $json     = array();
        $bannerid = 16;//分类bannerid
        $banner   = BannerModel::getInstance()->getAppBanner($bannerid, $merchantid);

        foreach ($banner as $key => $val) {
            if ($val['type'] == 1) {
                $banner[$key]['content'] = $val['link'];
                $banner[$key]['typenum'] = 1;//H5
            } else {
                $banner[$key]['content'] = $val['itemid'];
                if ($val['subtype'] == 1) {
                    $banner[$key]['typenum'] = 2;//单个商品
                } else if ($val['subtype'] == 0) {
                    $banner[$key]['typenum'] = 3;//分类
                } else {
                    $banner[$key]['typenum'] = 4;//活动分类
                }
            }
        }

        $first_category = CategoryModel::getInstance()->getFirstCategory($merchantid);
        $category_list  = $first_category;
        if (!empty($category_list)) {
            foreach ($category_list as $key => $val) {
                $category_list[$key]['image'] = self::handleImage($val['image']);
                if (!empty($banner)) {
                    foreach ($banner as $k => $v) {
                        if ($key == $k) {
                            $category_list[$key]['banner_image']    = $v['imgurl'];
                            $category_list[$key]['banner_image_id'] = $v['banner_image_id'];
                            $category_list[$key]['type']            = $v['typenum'];
                            $category_list[$key]['content']         = $v['content'];
                            $category_list[$key]['title']           = $v['title'];
                            if ($v['typenum'] == 3 || $v['typenum'] == 4) {
                                $category_list[$key]['product_list'] = ProductModel::getInstance()->getProduct($v['subtype'], $v['itemid'], 0, 5);
                            }
                        }
                    }
                }
                /*2017-6-7 zxx 修改*/
                $second_category = CategoryModel::getInstance()->getChildCategory($val['category_id'], $merchantid, 2);

                if (!empty($second_category)) {
                    foreach ($second_category as $k => $v) {
                        $third_category = CategoryModel::getInstance()->getChildCategory($v['category_id'], $merchantid, 3);
                        if (!empty($third_category)) {
                            $second_category[$k]['third_category'] = $third_category;
                        } else {
                            unset($second_category[$k]);
                        }

                    }
                } else {
                    unset($category_list[$key]);
//						continue;
                }

                if (!empty($second_category)) {
                    $category_list[$key]['second_category'] = array_values($second_category);
                } else {
                    unset($category_list[$key]);
                }
            }
        }
        $json['category_list'] = array_values($category_list);
        $json['retcode']       = 0;

        return $this->success($json);
    }

    /*
     * author:王志超  lcb
     * 2016.12.15   6-12
     * 分类商品列表
     */
    public function getPrdList()
    {
        $this->isPost();

        $json        = array();
        $customer_id = isset($_POST['customerid']) ? $_POST['customerid'] : 0;
        $category_id = isset($_POST['categoryid']) ? $_POST['categoryid'] : 0;
        $offset      = isset($_POST['offset']) ? $_POST['offset'] : 0;
        $count       = 10;
        $type        = 1;
        $status      = 1;//默认普通分类

        //排序状态
        if (isset($_POST['type'])) {
            $type = intval($_POST['type']);
        }

        //活动分类 or 普通分类 的状态
        if (isset($_POST['status'])) {
            $status = intval($_POST['status']);
        }

        //用于排序
        $type_list = array(
            0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
            1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
            2 => 'p.quantity!=0 DESC,p.price,p.quantity DESC',
            3 => 'p.quantity!=0 DESC,p.price DESC,p.quantity DESC',
            4 => 'p.quantity!=0 DESC,p.price/p.marketprice,p.quantity DESC',
            5 => 'p.quantity!=0 DESC,p.price/p.marketprice DESC,p.quantity DESC',
        );

        //$send_merchant_id是用于查询的merchant_id
        if ($customer_id > 0) {
            $merchant_id = CustomerModel::getInstance()->getMerchantId($customer_id);
            if ($merchant_id > 0) {
                $type_list        = array(
                    0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
                    1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
                    2 => 'p.quantity!=0 DESC,p.proxyprice,p.quantity DESC',
                    3 => 'p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC',
                    4 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC',
                    5 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC',
                );
                $send_merchant_id = $merchant_id;
            } else {
                $send_merchant_id = 1;
            }
        } else {
            $merchant_id      = 0;
            $send_merchant_id = 1;
        }

        //商品列表
        if ($category_id != null || $category_id != '') {
            //category_id 为0时是全部商品列表
            if ($category_id == 0) {

                $product_list = ProductModel::getInstance()->getAllProduct($send_merchant_id, $type_list[$type], $offset, $count);

            } else {
                //更新分类的访问次数
                CategoryModel::getInstance()->updatePoints($category_id);

                if ($status == 1) {
                    $product_list = ProductModel::getInstance()->getProductByCategory($send_merchant_id, $type_list[$type], $offset, $count, $category_id);
                } else {
                    $product_list = ProductModel::getInstance()->sel_product_by_cat($send_merchant_id, $type_list[$type], $offset, $count, $category_id);
                }
            }

            if (!empty($product_list)) {
                //不为空
                $return_product_list = array();
                foreach ($product_list as $k => $v) {
                    $return_product_list[$k] = array(
                        'productid'   => $v['productid'],
                        'productname' => $v['productname'],
                        "salenumber"  => $v["salenumber"]
                    );

                    if ($v['quantity'] == 0) {
                        $return_product_list[$k]['isenough'] = 2;
                    } else if ($v["quantity"] < 10) {
                        //不够
                        $return_product_list[$k]["isenough"] = 0;
                    } else {
                        //够
                        $return_product_list[$k]["isenough"] = 1;
                    }

                    $return_product_list[$k]["link"] = '';

                    if ($v['productimg'] != '') {
                        $return_product_list[$k]['productimg'] = self::handleImage($v['productimg']);
                    }
                    $return_product_list[$k]['originalprice'] = sprintf("%.2f", $v["marketprice"]);
                    if ($merchant_id == 0) {
                        //不是VIP
                        $return_product_list[$k]['finalprice'] = sprintf("%.2f", $v["price"]);
                    } else if ($merchant_id == 1) {
                        //VIP
                        $return_product_list[$k]['finalprice'] = sprintf("%.2f", $v["proxyprice"]);
                    } else if ($merchant_id != 0 && $merchant_id != 1) {
                        //商户的代理
                        $return_product_list[$k]['finalprice'] = sprintf("%.2f", $v["proxyprice"]);
                    }

                    if (isset($v["price"]) && $v["price"]) $return_product_list[$k]['price'] = sprintf("%.2f", $v["price"]);
                    if (isset($v["proxyprice"]) && $v["proxyprice"]) $return_product_list[$k]['proxyprice'] = sprintf("%.2f", $v["proxyprice"]);

                    unset($v['marketprice']);

                    if ((time() - strtotime($v['addedtime'])) > 3600 * 24 * 3) {
                        $return_product_list[$k]['isnew'] = '0';
                    } else {
                        $return_product_list[$k]['isnew'] = '1';
                    }
                }

                $json["prdlist"] = $return_product_list;
            } else {
                $json["prdlist"] = array();
            }
        } else {
            $this->error(1000, 'error');
        }
        $json['retcode'] = 0;

        return $this->success($json);
    }

    public function getPrdList1()
    {
        $this->isPost();
        $json  = array();
        $page  = 1;
        $count = 10;

        $type      = 1; // 0:最新 1:销量 2:最终价格升序 3:最终价格降序 4:折扣升序 5:折扣降序
        $type_list = array(
            0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
            1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
            2 => 'p.quantity!=0 DESC,p.price,p.quantity DESC',
            3 => 'p.quantity!=0 DESC,p.price DESC,p.quantity DESC',
            4 => 'p.quantity!=0 DESC,p.price/p.marketprice,p.quantity DESC',
            5 => 'p.quantity!=0 DESC,p.price/p.marketprice DESC,p.quantity DESC',
        );
        if (isset($_POST['page'])) {
            $page = intval($_POST['page']);
        }
        $start = ($page - 1) * $count;

        if (isset($_POST['type'])) {
            $type = intval($_POST['type']);
        }
        $category_id = isset($_POST['categoryid']) ? $_POST['categoryid'] : '';
        $customer_id = isset($_POST['customerid']) ? $_POST['customerid'] : 0;

        //$send_merchant_id是用于查询的merchant_id
        if ($customer_id > 0) {
            $merchant_id = CustomerModel::getInstance()->getMerchantId($customer_id);

            if ($merchant_id > 0) {
                $type_list        = array(
                    0 => 'p.quantity!=0 DESC,p.sort_order DESC,p.date_added DESC,p.quantity DESC',
                    1 => 'p.quantity!=0 DESC,p.sales DESC,p.quantity DESC',
                    2 => 'p.quantity!=0 DESC,p.proxyprice,p.quantity DESC',
                    3 => 'p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC',
                    4 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC',
                    5 => 'p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC',
                );
                $send_merchant_id = $merchant_id;
            } else {
                $send_merchant_id = 1;
            }
        } else {
            $merchant_id      = 0;
            $send_merchant_id = 1;
        }

        if ($category_id != null || $category_id != '') {
            //获取分类的级别，一级分类还是三级分类或者是全部商品
            if ($category_id == 0) {
                $category_rank = 1;
            } else {
                $category_rank = CategoryModel::getInstance()->checkFirstCategory($category_id);
            }

            if ($category_rank) {
                if ($category_id == 0) {
                    //全部商品
                    $prd_list = ProductModel::getInstance()->getAllProducts($merchant_id, $type_list[$type], $start, $count);
                } else {
                    //一级分类及其子集分类的商品
                    $prd_list = CategoryModel::getInstance()->getProductsBySearchResultId($category_id, $merchant_id, $type_list[$type], $start, $count);
                    //更新分类的访问次数
                    CategoryModel::getInstance()->updatePoints($category_id);
                }

                if (!empty($prd_list)) {
                    foreach ($prd_list as $k => $v) {
                        if ($v['quantity'] == 0) {
                            $prd_list[$k]['isenough'] = 2;
                        } else if ($v["quantity"] < 10) {
                            //不够
                            $prd_list[$k]["isenough"] = 0;
                        } else {
                            //够
                            $prd_list[$k]["isenough"] = 1;
                        }

                        $prd_list[$k]["link"] = '';

                        if ($v['productimg'] != '') {
                            $prd_list[$k]['productimg'] = self::handleImage($v['productimg']);
                        }

                        if ((time() - strtotime($v['addedtime'])) > 3600 * 24 * 3) {
                            $prd_list[$k]['isnew'] = '0';
                        } else {
                            $prd_list[$k]['isnew'] = '1';
                        }
                    }
                    $json["prdlist"] = $prd_list;
                } else {
                    $json["prdlist"] = array();
                }
            } else {
                //是活动分类还是三级分类
                $category_type = CategoryModel::getInstance()->checkTypeCategory($category_id);

                if ($category_type) {
                    //活动分类下的商品
                    $prd_list = ProductModel::getInstance()->sel_product_by_cat($send_merchant_id, $type_list[$type], $start, $count, $category_id);
                } else {
                    //三级分类下的商品
                    $prd_list = ProductModel::getInstance()->getProductByCategory($send_merchant_id, $type_list[$type], $start, $count, $category_id);
                }

                //更新分类的访问次数
                CategoryModel::getInstance()->updatePoints($category_id);

                if (!empty($prd_list)) {
                    //不为空
                    $return_product_list = array();
                    foreach ($prd_list as $k => $v) {
                        $return_product_list[$k] = array(
                            'productid'   => $v['productid'],
                            'productname' => $v['productname'],
                            "salenumber"  => $v["salenumber"]
                        );

                        if ($v['quantity'] == 0) {
                            $return_product_list[$k]['isenough'] = 2;
                        } else if ($v["quantity"] < 10) {
                            //不够
                            $return_product_list[$k]["isenough"] = 0;
                        } else {
                            //够
                            $return_product_list[$k]["isenough"] = 1;
                        }

                        $return_product_list[$k]["link"] = '';

                        if ($v['productimg'] != '') {
                            $return_product_list[$k]['productimg'] = self::handleImage($v['productimg']);
                        }

                        $return_product_list[$k]['originalprice'] = sprintf("%.2f", $v["marketprice"]);
                        if ($merchant_id == 0) {
                            //不是VIP
                            $return_product_list[$k]['finalprice'] = sprintf("%.2f", $v["price"]);
                        } else if ($merchant_id == 1) {
                            //VIP
                            $return_product_list[$k]['finalprice'] = sprintf("%.2f", $v["proxyprice"]);
                        } else if ($merchant_id != 0 && $merchant_id != 1) {
                            //商户的代理
                            $return_product_list[$k]['finalprice'] = sprintf("%.2f", $v["proxyprice"]);
                        }

                        if (isset($v["price"]) && $v["price"]) $return_product_list[$k]['price'] = sprintf("%.2f", $v["price"]);
                        if (isset($v["proxyprice"]) && $v["proxyprice"]) $return_product_list[$k]['proxyprice'] = sprintf("%.2f", $v["proxyprice"]);

                        unset($v['marketprice']);

                        if ((time() - strtotime($v['addedtime'])) > 3600 * 24 * 3) {
                            $return_product_list[$k]['isnew'] = '0';
                        } else {
                            $return_product_list[$k]['isnew'] = '1';
                        }
                    }
                    $json["prdlist"] = $return_product_list;
                } else {
                    $json["prdlist"] = array();
                }
            }
        } else {
            $this->error(1000, 'error');
        }
        $json['retcode'] = 0;

        return $this->success($json);
    }

    public function changePrd()
    {
        $this->isPost();

        $json            = array();
        $count           = 5;
        $banner_image_id = isset($_POST['banner_image_id']) ? $_POST['banner_image_id'] : 0;
        $offset          = isset($_POST['offset']) ? $_POST['offset'] : 2;

        if ($banner_image_id) {
            $banner_image = BannerModel::getInstance()->getBannerImage($banner_image_id);
            if ($banner_image) {
                //计算商品的页数，可以轮回的进行操作
                $total = CategoryModel::getInstance()->getProductTotal($banner_image['subtype'], $banner_image['item_id']);
                $page  = ceil($total / $count);
                if ($offset > $page) {
                    $start = (($offset - 1) % $page) * $count;
                } else {
                    $start = ($offset - 1) * $count;
                }

                $product_list         = ProductModel::getInstance()->getProduct($banner_image['subtype'], $banner_image['item_id'], $start, $count);
                $json['product_list'] = $product_list;
            } else {
                $this->error(1000, 'error');
            }
        } else {
            $this->error(1000, 'error');
        }
        $json['retcode'] = 0;

        return $this->success($json);
    }


}