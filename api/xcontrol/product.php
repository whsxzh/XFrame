<?php
//include "xcontrol/base.php";
include 'autoload.php';

class product extends base
{

    function __construct()
    {
        parent::__construct();
        $this->passkey     = @$_SESSION["default"]['passkey'];
        $this->customer_id = @$_SESSION["default"]['customer_id'];
    }

    function checkproduct()
    {
        $productids = @$_POST["productids"];
        $productid  = explode(',', $productids);
        $off_shelve = array();

        foreach ($productid as $val) {
            $product = getRow("select status from hb_product where product_id = '" . (int)$val . "'");
            if (empty($product) || $product['status'] == 0) {
                $off_shelve[] = $val;
            }
        }
        $customerinfo     = getRow("select * from hb_customer where customer_id='" . @$_POST["customerid"] . "' ");
        $availabe_balance = getRow("select * from hb_balance where customer_id='" . @$_POST["customerid"] . "' ");
        //判断是否为企业用户  cgl  2017-2-27  新增
        if (@$customerinfo["proxy_status"] == 1) {
            $company_status = 1;
        } else {
            $company_status = 0;//不是企业用户
        }
        $this->res["availabe_balance"] = empty($availabe_balance["availabe_balance"]) ? "0.00" : sprintf("%.2f", $availabe_balance["availabe_balance"]);
        $this->res["is_company"]       = $company_status;
        $this->res['retcode']          = 0;
        $this->res['off_shelve']       = @$off_shelve;

        return $this->res;
    }

    /*
     * 获取商品规格
     * wangzhichao 17.3.21
     * customerid	用户id，选填
     * passkey		用户key，选填
     * productid	商品id，必填
     */
    function getPrdOption()
    {
        $merchant_id = 0;
        $post        = $_POST;

        //不传递customerid为零时，不验证customerid的正确性
        if (!isset($_POST['customerid']) || $_POST['customerid'] <= 0) {
            parent::__construct(2);
        }

        if (!isset($post['productid'])) {
            $this->res = array(
                'retcode' => 1000,
                'msg'     => '参数错误'
            );

            return $this->res;
        }

        if ($this->customer_id > 0) {
            $customer_info = getRow("select merchant_id from `hb_customer` where customer_id = '" . (int)$this->customer_id . "'");
            if ($customer_info) {
                $merchant_id = $customer_info['merchant_id'];
            }
        }
        $sale_id = isset($_POST["sale_id"]) ? $_POST["sale_id"] : 0;  //cgl 2017-4-21
        //获取商品的默认价格图片及库存，用于未选择属性时商品的展示
        $product_info = getRow("select p.quantity as stock,
								p.image as productimg,
								p.marketprice,
								p.price,
								p.proxyprice,
								pd.name as productname
								from `hb_product` as p,`hb_product_description` as pd
								where p.product_id = pd.product_id and p.product_id = '" . (int)$post['productid'] . "' and p.status = '1'");


        if ($product_info) {
            //根据是否是会员选取商品的现价
            $product_info['originalprice'] = sprintf("%.2f", $product_info['marketprice']);
            if ($merchant_id > 0) {
                $product_info['finalprice'] = sprintf("%.2f", $product_info['proxyprice']);
            } else {
                $product_info['finalprice'] = sprintf("%.2f", $product_info['price']);
            }

            $product_info['price'] = sprintf("%.2f", $product_info['price']);
            $product_info['proxyprice'] = sprintf("%.2f", $product_info['proxyprice']);

            //暂时不用

            // $group=getRow("select * from hb_groupby where product_id = '".$post['productid']."' and group_status=1 and UNIX_TIMESTAMP(start_time)<= '".time()."' ");
            // if(!empty($group)){
            // 	$product_info["is_open_group_free"]=$group["is_open_free"];
            // 	$product_info['finalprice']=$group["groupprice"];
            // }else{
            // 	$product_info["is_open_group_free"]=0;
            // }
            // //开团免单
            // if($product_info["is_open_group_free"]==1){
            // 	$product_info['finalprice']="0.00";
            // }

            if (!empty($sale_id)) {   //cgl 2017-4-21
                //查询价格
                $is_end = getRow("select * from hb_salelimit where UNIX_TIMESTAMP(date_end)<'" . time() . "' and status=1 and sale_id = '" . $sale_id . "' ");
                if ($is_end) {
                    //已经结束了
                    return $this->res = array(
                        'retcode' => 4008,
                        'msg'     => '限时抢购商品已经结束'
                    );
                    die;
                }
                $sale_price = getRow("select * from hb_product_sale_price where product_id = '" . (int)$post['productid'] . "' and sale_id ='" . $sale_id . "' ");
                if (!empty($sale_price)) {
                    $product_info["finalprice"] = $sale_price["sale_price"];
                }
            }
            // lcb 6-13 需要显示price proxyprice
            //unset($product_info['marketprice'],$product_info['price'],$product_info['proxyprice']);
            unset($product_info['marketprice']);
            $this->res['data'] = $product_info;

            //获取商品的规格属性和相应的价格图片及库存
            $product_item = getData("select product_item_id,
								  	product_options,
								  	product_id,
								  	quantity as stock,
								  	price,
								  	proxyprice,
								  	image
									from `hb_product_item`
									where product_id = '" . (int)$post['productid'] . "' and status = '0'");

            if ($product_item) {
                //根据是否是会员选取商品的现价
                foreach ($product_item as $key => $val) {
                    if ($merchant_id > 0) {
                        $product_item[$key]['finalprice'] = sprintf("%.2f", $val['proxyprice']);
                    } else {
                        $product_item[$key]['finalprice'] = sprintf("%.2f", $val['price']);
                    }
                    //unset($product_item[$key]['price'],$product_item[$key]['proxyprice']); // lcb 6-13 需要显示price proxyprice
                    $product_item[$key]['price'] = sprintf("%.2f", $val['price']);
                    $product_item[$key]['proxyprice'] = sprintf("%.2f", $val['proxyprice']);
                    $product_item[$key]['originalprice'] = $product_item[$key]['price'];

                    if (empty($val['image'])) {
                        $product_item[$key]['image'] = $product_info['productimg'];
                    }
                }
                $this->res['data']['option'] = $product_item;
            } else {
                $this->res['data']['option'] = array();
            }

            $this->res['retcode'] = 0;// lcb 6-13
            $this->res['msg']     = 'success';// lcb 6-13

            return $this->res;

        } else {
            $this->res = array(
                'retcode' => 1000,
                'msg'     => '商品已经下架，请换购其他商品'
            );

            return $this->res;
        }
    }

    /**
     * 商品详情
     * updated from highup/index.php?route=api/product/getprddetail
     * lcb
     */
    public function getprddetail()
    {
        header('Content-Type:application/json');
        $this->isPost();
        $server_url = 'https://haiqihuocang.com/product_detail/';
        $json       = array();
        if (!isset($_POST['productid']) || !$productid = $_POST['productid']) {
            $this->error(1000, 'error');
        }
        $json['retcode'] = 0;
        $customerid      = isset($_POST['customerid']) && $_POST['customerid'] ? $_POST['customerid'] : 0;
        $prddetail       = null;
        $productid       = str_replace("\"", '', htmlspecialchars_decode($productid));
        //阿里云的图片宽度
        $width = isset($_POST["width"]) ? $_POST["width"] : null;

        // update the points of this product.
        //新增限时活动id  cgl  2017-4-11
        $sale_id = str_replace("\"", '', htmlspecialchars_decode(isset($_POST['sale_id']) ? $_POST['sale_id'] : 0));
        if (!$sale_id) {
            $sale_id = 0;
        }

        $imglist = ProductModel::getInstance()->getProductImgs($productid);
        if ($customerid) {
            $merchantid = CustomerModel::getInstance()->getMerchantId($customerid);
            if ($merchantid == -1) {
                $this->error(1200, 'error'); // 查询错误
            } else {
                $prddetail = ProductModel::getInstance()->getProductForAppdetail($productid, $merchantid, $sale_id);
            }
        } else {
            $prddetail = ProductModel::getInstance()->getProductForAppdetail($productid, 0, $sale_id);
        }
        ProductModel::getInstance()->updatePoints($productid, $customerid);

//				$prdoption = $this->model_catalog_product->getPrdOptionNames($productid);
        if ($prddetail) {
            // 判断产品详情图片各种情况的代码
            if ($imglist) {
                if (count($imglist) === 1) {
                    if ($imglist[0]['image'] === '' || $imglist[0]['image'] === null) {
                        $imglist = array();
                    } else {
                        $imglist[0] = $imglist[0]['image'];
                        $imglist[0] = $this->handleImage($imglist[0]);
                    }
                } else {
                    $index = 0;
                    foreach ($imglist as $item) {
                        if ($item['image'] == '') {
                            unset($imglist[$index]);
                        } else {
                            $item            = $item['image'];
                            $item            = $this->handleImage($item);
                            $imglist[$index] = $item;
                        }
                        $index++;
                    }
                }
                $prddetail['imglist'] = $imglist;
            } else {
                $imglist              = array();
                $prddetail['imglist'] = $imglist;
            }
            if ($prddetail['finalprice'] == 0) {
                $prices = 0;
            } else {
                $prices = $prddetail['finalprice'] / $prddetail['originalprice'] * 10;
                // $prddetail['finalprice']/$prddetail['finalprice']*10;
            }
            $prddetail["productname"] = htmlspecialchars_decode($prddetail["productname"]);

            $originalprice = sprintf("%.2f", $prddetail['originalprice']);
            $finalprice    = sprintf("%.2f", $prddetail['finalprice']);
            $derate_money  = sprintf("%.2f", $prddetail['derate_money']);

            $prddetail['productimg'] = $this->handleImage($prddetail['productimg']);
            $prddetail['discount']   = sprintf("%.1f", $prices);
            $desc                    = ProductModel::getInstance()->getProductDesc($productid);
            $basic_description       = isset($desc['basic_description']) ? $desc['basic_description'] : '';  //商品描述

            $a = htmlspecialchars_decode(htmlspecialchars_decode($desc['description']));
            // $b=preg_replace('/(<img.+src=\"?.+)(\/high\/)(.+\.(jpg|gif|bmp|bnp|png)\"?.+>)/i',"\${1}".TEST_IP."/high/\${3}",$a);
            $c = htmlspecialchars(htmlspecialchars($this->get_img_thumb_url($a, "?x-oss-process=image/resize,w_" . $width . "/quality,q_100", $width)));

            $prddetail['productdesc']       = html_entity_decode($c, ENT_QUOTES, 'UTF-8');
            $prddetail['freetype']          = intval($prddetail['freetype']);
            $prddetail['finalprice']        = $finalprice;
            $prddetail['originalprice']     = $originalprice;
            $prddetail['basic_description'] = $basic_description;
            $prddetail['derate_money']      = $derate_money;

            $prddetail['price']      = isset($prddetail['price']) && $prddetail['price'] ? sprintf("%.2f", $prddetail['price']) : 0.00;
            $prddetail['proxyprice'] = isset($prddetail['proxyprice']) && $prddetail['proxyprice'] ? sprintf("%.2f", $prddetail['proxyprice']) : 0.00;

            //cgl 2017-6-10  增加分享的内容
            if ($customerid > 0) {
                $customers = getRow("SELECT firstname from hb_customer where customer_id= '" . $customerid . "' ");
                if (!empty($customers) && !empty($customers["firstname"])) {
                    $prddetail["sharecontent"] = $customers["firstname"] . "邀请你来嗨企货仓一起High~~";
                } else {
                    $prddetail["sharecontent"] = "一个奇怪的人邀请你来嗨企货仓一起High~~";
                }
            } else {
                $prddetail["sharecontent"] = '';//"一个奇怪的人邀请你来嗨企货仓一起High~~";
            }


            //新增一个客服功能需要的商品链接
            $prddetail['service_url'] = $server_url . $productid;
            //新增，两个字段  团购使用


            if ($derate_money > 0) {
                $prddetail['is_derate'] = 1;//分享减免
            } else {
                $prddetail['is_derate'] = 0;//不参与分享减免
            }
            $prddetail['derate_money'] = $derate_money;


            if ($customerid) {
                $iswished = WishlistModel::getInstance()->checkIfWished($customerid, $productid);
                if ($iswished) {
                    $prddetail['iswished'] = $iswished;
                } else {
                    $prddetail['iswished'] = 0;
                }
            } else {
                $prddetail['iswished'] = 0;
            }
            $prddetail['needauth'] = (int)$prddetail['needauth'];

            //获取评价数量
            $prddetail['cmtnum'] = ProductModel::getInstance()->getCmtnum($productid);

            $json['prddetail'] = $prddetail;
        } else {
            // $json['prddetail'] = new stdClass();
            $json['retcode'] = 404;

            //优选商品 wangzhichao 17.2.21
            if (@$merchantid == 0) {
                $merchant_id = 1;
            } else {
                $merchant_id = $merchantid;
            }
            $newprdlist = ProductModel::getInstance()->getRecommendPrd($merchant_id);
            if (empty($newprdlist)) {
                $json['newprdlist'] = array();
            } else {
                $index = 0;
                foreach ($newprdlist as $item) {
                    $item['isenough'] = 1;
                    if ($item['quantity'] == 0) {
                        $item['isenough'] = 2;
                    } else if ($item['quantity'] <= 10 && $item['quantity'] > 0) {
                        $item['isenough'] = 0;
                    }

                    $item['productimg'] = $this->handleImage($item['productimg']);
                    $item['link']       = '';
                    if ($merchant_id < 1) {
//								$item['originalprice'] = sprintf("%.2f",$item['originalprice']);
                        $item['finalprice'] = sprintf("%.2f", $item['finalprice']);
                    } else {
//								$item['originalprice'] = sprintf("%.2f",$item['finalprice']);
                        $item['finalprice'] = sprintf("%.2f", $item['proxyprice']);
                    }
                    //unset($item['proxyprice']);
                    $item['proxyprice']         = sprintf("%.2f", $item['proxyprice']);
                    $item['price']              = sprintf("%.2f", $item['price']);
                    $json['newprdlist'][$index] = $item;
                    $index++;
                }
            }

        }

        return $json;
        //return $this->success($json);
    }

    /**
     * @param $imgbefore
     * @return string
     * @description 处理图片地址 pw
     */
    public function handleImage($imgbefore)
    {
        if ((strpos($imgbefore, 'http://') === false) || (strpos($imgbefore, 'http://') > 0)) {
            $imgafter = TEST_IP1 . '/image/' . $imgbefore;
        } else {
            $imgafter = $imgbefore;
        }

        return $imgafter;
    }

    //替换图片的路径
    private function get_img_thumb_url($content = "", $suffix = "", $width)
    {
        if (preg_match("/(<img.+src=\"?.+)(\/high\/)(.+\.(jpg|gif|bmp|bnp|png)\"?.+>)/i", $content)) {
            $pregRule = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
            $content  = preg_replace($pregRule, '<img src="' . TEST_IP1 . '${1}" >', $content);
        } else {
            //阿里云
            if (!empty($width)) {
                $pregRule = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
                $content  = preg_replace($pregRule, '<img src="${1}' . $suffix . '" >', $content);
            }
        }

        return $content;
    }


}