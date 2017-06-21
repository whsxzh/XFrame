<?php
include "xcontrol/base.php";
class cart extends base{
    /*
     * 加入购物车
     * wangzhicaho 17.3.21
     * customerid	    用户id，必填
     * passkey		    用户key，必填
     * productid	    商品id，必填
     * product_item_id	商品规格id，必填,没有规格时为0
     * number           商品数量，必填
     */
    function addCart(){
        $post = $_POST;
        if(!isset($post['productid']) || !isset($post['product_item_id']) || !isset($post['number'])){
            $this->res = array(
                'retcode'   =>  4000,
                'msg'       =>  '参数错误'
            );
            return $this->res;
        }

        $product_info = getRow("select * from `hb_product` where product_id = '" .(int)$post['productid']. "' and status = '1'");
        if(!$product_info){
            $this->res = array(
                'retcode'   =>  4009,
                'msg'       =>  '商品已经下架，请换购其他商品'
            );
            return $this->res;
        }
        //修改   cgl  2017-4-11 增加限时抢购的编号

        if(isset($post["sale_id"])){
            $sale_id=$post["sale_id"];
        }else{
            $sale_id=0;
        }
        //cgl  2017-4-24  判断数量
        // if($sale_id>0){
        //     $post['number']=1;
        // }

        //商品总库存
        $quantity = $product_info['quantity'];

        //获取购物车相同商品的详情
        $cart_info = getRow("select * from `hb_cart` where customer_id = '" .(int)$this->customer_id. "' and product_id = '" .(int)$post['productid']. "' and product_item_id = '" .(int)$post['product_item_id']. "' and status = '0'");
        if($cart_info && (!isset($post['cartid']) || $post['cartid'] != $cart_info['cart_id'])){
            $quantity -= $cart_info['quantity'];
        }

        if($quantity < $post['number']){
            $this->res = array(
                'retcode'   =>  4001,
                'msg'       =>  '超过商品库存，请减少数量'
            );
            return $this->res;
        }

        if($post['product_item_id'] > 0){
            $product_item = getRow("select * from `hb_product_item` where product_item_id = '" .(int)$post['product_item_id']. "' and product_id = '" .(int)$post['productid']. "' and status = '0'");

            if(!$product_item){
                $this->res = array(
                    'retcode'   =>  4002,
                    'msg'       =>  '商品规格已改变，请重新选择'
                );
                return $this->res;
            }
            //商品规格库存
            $item_quantity = $product_item['quantity'];
            if($cart_info && (!isset($post['cartid']) || $post['cartid'] != $cart_info['cart_id'])){
                $item_quantity -= $cart_info['quantity'];
            }

            if($item_quantity < $post['number']){
                $this->res = array(
                    'retcode'   =>  4003,
                    'msg'       =>  '超过商品库存，请减少数量'
                );
                return $this->res;
            }

        }

        if(isset($post['cartid'])){
            //cgl  2017-4-24  判断数量
            // if($sale_id>0){
            //     $post['number']=1;
            // }
            //编辑时，改为和已存在的商品相同的规格时
            if($cart_info && $cart_info['product_item_id'] > 0 && $post['cartid'] != $cart_info['cart_id']){
                exeSql("update `hb_cart` set quantity = (quantity+" .(int)$post['number']. ") where customer_id = '" .(int)$this->customer_id. "' and product_id = '" .(int)$post['productid']. "' and cart_id = '" .(int)$cart_info['cart_id']. "' and product_item_id = '" .(int)$post['product_item_id']. "' and sale_id='".$sale_id."' ");
                exeSql("delete from `hb_cart` where customer_id = '" .(int)$this->customer_id. "' and product_id = '" .(int)$post['productid']. "' and cart_id = '" .(int)$post['cartid']. "'");
                $this->res['data']['cartid'] = $cart_info['cart_id'];
            }else{
                // if($sale_id>0){
                //     $post['number']=1;
                // }
                exeSql("update `hb_cart` set quantity = '" .(int)$post['number']. "',product_item_id = '" .(int)$post['product_item_id']. "' where customer_id = '" .(int)$this->customer_id. "' and product_id = '" .(int)$post['productid']. "' and cart_id = '" .(int)$post['cartid']. "' and sale_id='".$sale_id."' ");
                $this->res['data']['cartid'] = $post['cartid'];
            }
            return $this->res;
        }

        if($cart_info){
            //cgl  2017-4-24  判断数量
            // if($sale_id>0){
            //     $post['number']=1;
            // }
            exeSql("update `hb_cart` set quantity = quantity+'" .(int)$post['number']. "' where customer_id = '" .(int)$this->customer_id. "' and product_id = '" .(int)$post['productid']. "' and product_item_id = '" .(int)$post['product_item_id']. "'");
            $this->res['data']['cartid'] = $cart_info['cart_id'];
        }else{
            // if($sale_id>0){
            //     $post['number']=1;
            // }
            exeSql("insert into `hb_cart` set quantity = '" .(int)$post['number']. "', customer_id = '" .(int)$this->customer_id. "', product_id = '" .(int)$post['productid']. "', product_item_id = '" .(int)$post['product_item_id']. "', date_added = current_timestamp(), date_updated = 0 ,sale_id='".$sale_id."' ");
            $this->res['data']['cartid'] = getLastId();
        }

        return $this->res;
    }

    /*
     * 获取购物车列表
     * wangzhichao 17.3.22
     */
    function cartlist(){
        //获取用户的所属商户id
        $merchant = getRow("select merchant_id from `hb_customer` where customer_id = '" .(int)$this->customer_id. "'");
        $merchant_id = $merchant['merchant_id'];

        //获取购物商品列表   cgl  增加限时抢购id
        $cart_list = getData("select c.cart_id as cartid,
                              c.product_id as productid,
                              c.quantity as `number`,
                              c.product_item_id,
                              c.status,
                              c.sale_id,
                              p.image as productimg,
                              p.price,
                              p.proxyprice,
                              p.marketprice as originalprice,
                              p.freetype,
                              p.freecondition,
                              p.freight,
                              p.derate_money,
                              pd.name as productname,
                              w.name as merchantname,
                              w.needauth,
                              IFNULL(pi.product_options,'') AS `option`,
                              pi.price as item_price,
                              pi.proxyprice as item_proxyprice,
                              pi.image as item_image
                              from `hb_cart` as c
                              inner join `hb_product` as p on c.product_id = p.product_id
                              inner join `hb_product_description` as pd on p.product_id = pd.product_id
                              inner join `hb_warehouse` as w on p.manufacturer_id = w.warehouse_id
                              left join `hb_product_item` as `pi` on pi.product_item_id = c.product_item_id and pi.product_id = c.product_id
                              where c.customer_id = '" .(int)$this->customer_id. "' order by c.date_added desc");

        $validprdlist = array();//有效商品列表
        $invalidprdlist = array();//无效商品列表
        if($cart_list){
            foreach($cart_list as $key=>$val){
                //购物车商品现价与市场价
                $val['originalprice'] = sprintf("%.2f",$val['originalprice']);
                if(!empty($val['item_proxyprice'])){
                    if($merchant_id > 0){
                        $val['finalprice'] = sprintf("%.2f",$val['item_proxyprice']);
                    }else{
                        $val['finalprice'] = sprintf("%.2f",$val['item_price']);
                    }
                    if(!empty($val['item_image'])){
                        $val['productimg'] = $val['item_image'];
                    }
                }else{
                    if($merchant_id > 0){
                        $val['finalprice'] = sprintf("%.2f",$val['proxyprice']);
                    }else{
                        $val['finalprice'] = sprintf("%.2f",$val['price']);
                    }
                }
                //cgl  2017-4-11 新增限时抢购状态
                $val['is_sale_activity']=0;
                if($val["sale_id"]>0){
                    $val['is_sale_activity']=1;//是限时抢购
                    //判断价格  以及是否过期   //抢购中
                    $sale_product=getRow("select * from hb_salelimit as a,hb_product_sale_price as b where a.sale_id =b.sale_id and b.product_id = '".$val["productid"]."' and a.status=1 and UNIX_TIMESTAMP(date_end)>'".time()."' and a.sale_id = '".$val["sale_id"]."' and UNIX_TIMESTAMP(date_start)<='".time()."'  ");
                    if(!empty($sale_product)){
                        $val['finalprice'] = sprintf("%.2f",$sale_product['sale_price']);
                        //新增  cgl 2017-4-25  都为1
                        // exeSql("update hb_cart set quantity=1 where customer_id = '".$this->customer_id."' and sale_id = '".$val["sale_id"]."' ");
                        // $val['number']=1;
                    }
                    //判断是否过期   过期了
                    $is_out_sale=getRow("select * from hb_salelimit where status=1 and sale_id = '".$val["sale_id"]."' and UNIX_TIMESTAMP(date_end) < '".time()."'  ");
                    if(!empty($is_out_sale)){
                        $val['is_sale_activity']=0;
                        $val['status'] = 1 ;
                        //改变成失效状态
                        exeSql("update hb_cart set status=1 where customer_id = '".$this->customer_id."' and status=0 and sale_id = '".$val["sale_id"]."' ");
                        $val["sale_id"]=0;
                    }
                }
                $val["sale_id"]=$val["sale_id"];

                unset($val['price'],$val['proxyprice'],$val['item_price'],$val['item_proxyprice'],$val['item_image']);
                //是否参与分享减免及减免金额,1参与，0不参与
                $val['derate_money'] = sprintf("%.2f",$val['derate_money']);
                if($val['derate_money'] > 0){
                    $val['is_derate'] = 1;
                }else{
                    $val['is_derate'] = 0;
                }
                //分别放入有效商品列表和无效商品列表
                if ($val['status'] == 1) {
                    unset($val['status']);
                    $invalidprdlist[] = $val;
                } else {
                    unset($val['status']);
                    $validprdlist[] = $val;
                } 
            }
        }

        $this->res[ 'data'] = array(
                                    'validprdlist'=>$validprdlist,
                                    'invalidprdlist'=>$invalidprdlist,
                                );
        $page=isset($_POST['page'])?$_POST['page']:1;
        $count=20;
        $start=($page-1)*$count;
        if(empty($validprdlist)){
                $newprdlist = getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
            FORMAT(p.price,2) as price,
            FORMAT(p.marketprice,2) as originalprice,
            FORMAT(p.proxyprice,2) as proxyprice,
            pd.name as productname,
            p.product_id as productid,
            p.quantity
            from hb_product as p,hb_product_description as pd,hb_product_look as pl  where  pl.product_id=p.product_id and p.status=1 and p.product_id=pd.product_id and p.product_id in (select  product_id from hb_product_look where customer_id='".$_POST['customerid']."') order by pl.id DESC limit 10");
        
            /*zxx 增加限制条件*/
            foreach($newprdlist as $key=>$val){
                $newprdlist[$key]['originalprice'] = sprintf("%.2f",$val['originalprice']);
                if($merchant_id > 0){
                    $newprdlist[$key]['finalprice'] = sprintf("%.2f",$val['proxyprice']);
                }else{
                    $newprdlist[$key]['finalprice'] = sprintf("%.2f",$val['price']);
                }
                unset($newprdlist[$key]['price'],$newprdlist[$key]['proxyprice']);
            }
            $this->res[ 'data']['newprdlist'] = $newprdlist;
        }
        return $this->res;
    }

    /*
     * 新版本的获取购物车列表
     * zxx 2017-5-22
     */
    function cartlist1(){
        //获取用户的所属商户id
        $merchant = getRow("select merchant_id from `hb_customer` where customer_id = '" .(int)$this->customer_id. "'");
        $merchant_id = isset($merchant['merchant_id']) ? $merchant['merchant_id'] : -1;

        //获取购物商品列表   cgl  增加限时抢购id
        $cart_list = getData("select c.cart_id as cartid,
                              c.product_id as productid,
                              c.quantity as `number`,
                              c.product_item_id,
                              c.status,
                              c.sale_id,
                              p.image as productimg,
                              p.price,
                              p.proxyprice,
                              p.marketprice as originalprice,
                              p.freetype,
                              p.freecondition,
                              p.freight,
                              p.derate_money,
                              pd.name as productname,
                              w.name as merchantname,
                              w.needauth,
                              IFNULL(pi.product_options,'') AS `option`,
                              pi.price as item_price,
                              pi.proxyprice as item_proxyprice,
                              pi.image as item_image
                              from `hb_cart` as c
                              inner join `hb_product` as p on c.product_id = p.product_id
                              inner join `hb_product_description` as pd on p.product_id = pd.product_id
                              inner join `hb_warehouse` as w on p.manufacturer_id = w.warehouse_id
                              left join `hb_product_item` as `pi` on pi.product_item_id = c.product_item_id and pi.product_id = c.product_id
                              where c.customer_id = '" .(int)$this->customer_id. "' order by c.date_added desc");

        $validprdlist = array();//有效商品列表
        $invalidprdlist = array();//无效商品列表
        if($cart_list){
            foreach($cart_list as $key=>$val){
                //购物车商品现价与市场价
                $val['originalprice'] = sprintf("%.2f",$val['originalprice']);
                if(!empty($val['item_proxyprice'])){
                    if($merchant_id > 0){
                        $val['finalprice'] = sprintf("%.2f",$val['item_proxyprice']);
                    }else{
                        $val['finalprice'] = sprintf("%.2f",$val['item_price']);
                    }
                    if(!empty($val['item_image'])){
                        $val['productimg'] = $val['item_image'];
                    }
                }else{
                    if($merchant_id > 0){
                        $val['finalprice'] = sprintf("%.2f",$val['proxyprice']);
                    }else{
                        $val['finalprice'] = sprintf("%.2f",$val['price']);
                    }
                }
                //cgl  2017-4-11 新增限时抢购状态
                $val['is_sale_activity']=0;
                if($val["sale_id"]>0){
                    $val['is_sale_activity']=1;//是限时抢购
                    //判断价格  以及是否过期   //抢购中
                    $sale_product=getRow("select * from hb_salelimit as a,hb_product_sale_price as b where a.sale_id =b.sale_id and b.product_id = '".$val["productid"]."' and a.status=1 and UNIX_TIMESTAMP(date_end)>'".time()."' and a.sale_id = '".$val["sale_id"]."' and UNIX_TIMESTAMP(date_start)<='".time()."'  ");
                    if(!empty($sale_product)){
                        $val['finalprice'] = sprintf("%.2f",$sale_product['sale_price']);
                        //新增  cgl 2017-4-25  都为1
                        // exeSql("update hb_cart set quantity=1 where customer_id = '".$this->customer_id."' and sale_id = '".$val["sale_id"]."' ");
                        // $val['number']=1;
                    }
                    //判断是否过期   过期了
                    $is_out_sale=getRow("select * from hb_salelimit where status=1 and sale_id = '".$val["sale_id"]."' and UNIX_TIMESTAMP(date_end) < '".time()."'  ");
                    if(!empty($is_out_sale)){
                        $val['is_sale_activity']=0;
                        $val['status'] = 1 ;
                        //改变成失效状态
                        exeSql("update hb_cart set status=1 where customer_id = '".$this->customer_id."' and status=0 and sale_id = '".$val["sale_id"]."' ");
                        $val["sale_id"]=0;
                    }
                }
                $val["sale_id"]=$val["sale_id"];

                //unset($val['price'],$val['proxyprice'],$val['item_price'],$val['item_proxyprice'],$val['item_image']);
                // lcb 6-13 显示会员价和非会员价  上面一行被注释
                $val['price'] = isset($val['item_price']) && $val['item_price']>0 ? $val['item_price'] : $val['price'];
                $val['price'] = sprintf('%.2f', $val['price']);
                $val['proxyprice'] = isset($val['item_proxyprice']) && $val['item_proxyprice']>0 ? $val['item_proxyprice'] : $val['proxyprice'];
                $val['proxyprice'] = sprintf('%.2f', $val['proxyprice']);
                unset($val['item_price'],$val['item_proxyprice'],$val['item_image']);

                //是否参与分享减免及减免金额,1参与，0不参与
                $val['derate_money'] = sprintf("%.2f",$val['derate_money']);
                if($val['derate_money'] > 0){
                    $val['is_derate'] = 1;
                }else{
                    $val['is_derate'] = 0;
                }
                //分别放入有效商品列表和无效商品列表
                if ($val['status'] == 1) {
                    unset($val['status']);
                    $invalidprdlist[] = $val;
                } else {
                    unset($val['status']);
                    $validprdlist[] = $val;
                } 
            }
        }

        $this->res[ 'data'] = array(
                                    'validprdlist'=>$validprdlist,
                                    'invalidprdlist'=>$invalidprdlist,
                                );
        $page=isset($_POST['page'])?$_POST['page']:1;
        $count=10;
        $start=($page-1)*$count;
        //用户足迹
        

        $newprdlist = getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
        p.price as price,
        p.marketprice as originalprice,
        p.proxyprice as proxyprice,
        pd.name as productname,
        p.product_id as productid,
        p.quantity
        from hb_product as p,hb_product_description as pd left join hb_product_look as pl on pl.product_id=pd.product_id where   p.status=1 and p.product_id=pd.product_id and pl.customer_id='".$_POST['customerid']."' order by pl.id DESC limit ".$start.", ".$count);
    //echo $newprdlist;exit;
        /*zxx 增加限制条件*/
        foreach($newprdlist as $key=>$val){
            $newprdlist[$key]['originalprice'] = sprintf("%.2f",$val['originalprice']);
            if($merchant_id > 0){
                $newprdlist[$key]['finalprice'] = sprintf("%.2f",$val['proxyprice']);
            }else{
                $newprdlist[$key]['finalprice'] = sprintf("%.2f",$val['price']);
            }
            //unset($newprdlist[$key]['price'],$newprdlist[$key]['proxyprice']);
            // lcb 6-13 显示会员价和非会员价  上面一行被注释
            $newprdlist[$key]['price'] = sprintf('%.2f', $val['price']);
            $newprdlist[$key]['proxyprice'] = sprintf('%.2f', $val['proxyprice']);
        }

        $this->res[ 'data']['newprdlist'] = $newprdlist;
        $this->res['retcode'] = 0;
        $this->res['msg'] = 'success';
        return $this->res;
    }

    /*
     * 确认订单
     * wangzhichao 17.3.22    cgl  2017-3-29  添加分享赚差价参数  
     * $proxyid = str_replace("\"", '', htmlspecialchars_decode($item['proxyid']));    //会员编号
       $differenceprice = str_replace("\"", '', htmlspecialchars_decode($item['differenceprice']));   //差价
     */
    function confirmOrder(){
        // lcb 5-17 不显示notice 不然ajax返回异常
        error_reporting(E_ALL ^E_NOTICE);
        $post = $_POST;
        //!isset($post['addressid']) ||   || empty($post['addressid'])  cgl 修改
        if( !isset($post['prdlist'])  || empty($post['prdlist'] )){
            $this->res = array(
                'retcode'       =>4000,
                'msg'           =>'参数错误'
            );
            return $this->res;
        }

        $customer_info=getRow("SELECT * FROM hb_customer where customer_id='".(int)$this->customer_id."'");
        $customer_balance = getRow("SELECT * FROM hb_balance where customer_id='".(int)$this->customer_id."'");

        $address=array();
        if(isset($_POST["type"]) && @$_POST["type"]==2 ){
            //类型为2    微信地址为获取用户的收货地址     cgl  2017-3-27
            $accept_name=isset($_POST["accept_name"])?$_POST["accept_name"]:null;//收货人
            $telephone=isset($_POST["telephone"])?$_POST["telephone"]:null;
            $code=isset($_POST["code"])?$_POST["code"]:null;//邮编
            $province=isset($_POST["province"])?$_POST["province"]:null;//省
            $city=isset($_POST["city"])?$_POST["city"]:null;//市
            $country=isset($_POST["country"])?$_POST["country"]:null;//区/县
            $detail_info=isset($_POST["detail_info"])?$_POST["detail_info"]:null;//地址详情
            if(!$accept_name || !$telephone || !$province || !$city || !$country || !$detail_info ){
                $this->res = array(
                        'retcode'=>1000,
                        'msg'=>'微信地址参数错误'
                    );
                return $this->res;
            }else{
                $province=mb_substr($province,0,2,"utf-8");
                $city=mb_substr($city,0,2,"utf-8");
                $country1=mb_substr($country,0,2,"utf-8");

                $country=getRow("select * from hb_country where name = '".$province."' ");//省
                $zone=getRow("select * from hb_zone where name = '".$city."' ");//市
                $city=getRow("select * from hb_city where name = '".$country1."' ");//区
                
                $address['custom_field']=json_encode(array("1"=>$telephone));//手机号码
                $address['firstname']=$accept_name;//收货人
                $address['address_1']=$detail_info;//具体地址
                $address['city']=@$zone["name"];//市
                $address['postcode']=$code;
                $address['country']=@$country["name"];//省
                $address['country_id']=@$country["country_id"];
                $address['zone']=@$city["name"];//区
                $address['zone_id']=@$city["zone_id"];
            }
            
        }else{
            $address = getRow("SELECT a.*,c.name as country,z.name as zone FROM `hb_address` as a
                           inner join `hb_country` as c on c.country_id = a.country_id
                           inner join `hb_zone` as z on z.zone_id = a.zone_id
                           where address_id='".@(int)$post['addressid']."' and customer_id = '" .(int)$this->customer_id. "'");
        }
        //开始回滚事务
        beginTransaction();

        $laststr = '';
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';    //字符池
        for($i = 0; $i < 4; $i++)
        {
            $laststr .= $pattern{mt_rand(0,35)};    //生成php随机数
        }
        date_default_timezone_set('PRC');
        $order_num = 'ssdf' . date('Ymd',time()) . $laststr;

//        $post['prdlist'] = array(
////            '0'=>array(
////                'quantity'=>2,
////                'product_item_id'=>0,
////                'product_id'=>3512,
////                'price'=>123,
////                'freight'=>0,
////                'is_share'=>0
////            ),
//            '1'=>array(
//                'cart_id'=>3862,
//                'quantity'=>2,
//                'product_item_id'=>0,
//                'product_id'=>3516,
//                'price'=>123,
//                'freight'=>0,
//                'is_share'=>0
//            ),
//            '2'=>array(
//                'cart_id'=>3861,
//                'quantity'=>2,
//                'product_item_id'=>0,
//                'product_id'=>3516,
//                'price'=>123,
//                'freight'=>0,
//                'is_share'=>0
//            )
//
//        );
        if(!is_array($post['prdlist'])){
            $post['prdlist'] =  json_decode($post['prdlist'], true);
        }

               

        // 如果下单时选择了优惠券
        if (isset($post['couponinfo'])) {
            $couponinfo = json_decode($post['couponinfo'],true);
        }else{
            $couponinfo = array();
        }

        $has_coupon_product = array();
        // 统计哪些商品具有使用已经选好的优惠券资格
        foreach( $post['prdlist'] as $key=>$val ){
            // 过滤没有优惠券的商品            
            if ($couponinfo) {
                foreach($couponinfo as $ck=>$ck_value){                        
                    $coupon = getRow('select type from hb_coupon where coupon_id='.$ck_value['coupon_id']);
                    if (1 == $coupon['type']) {
                        // 全部商品优惠券
                        $has_coupon_product[$key]['discount'] = $ck_value['discount'];
                        $has_coupon_product[$key]['coupon_id'] = $ck_value['coupon_id'];
                        $has_coupon_product[$key]['product_id'] = $val['productid'];
                        $has_coupon_product[$key]['total'] = $val['price']*$val['number'];
                    }else if (2 == $coupon['type']) {
                        // 部分商品优惠券
                        if(getRow("select product_id from hb_coupon_product where coupon_id=".$ck_value['coupon_id']."
                                   and product_id=".$val['productid'])){
                            $has_coupon_product[$key]['discount'] = $ck_value['discount'];
                            $has_coupon_product[$key]['coupon_id'] = $ck_value['coupon_id'];
                            $has_coupon_product[$key]['product_id'] = $val['productid'];
                            $has_coupon_product[$key]['total'] = $val['price']*$val['number'];
                        }
                    }else if (3 == $coupon['type']) {
                        // 部分商品分类优惠券
                        $product_ids = getData("select ptc.product_id,ptc.category_id from hb_product_to_category as ptc 
                                                where ptc.category_id in(
                                                select c.category_id from hb_category as c 
                                                where parent_id in(select c1.category_id from hb_category as c1 
                                                where parent_id in(select category_id from hb_coupon_category where coupon_id=".$ck_value['coupon_id']."))
                                                )");
                        foreach($product_ids as $k=>$v){
                                $product_ids[$k] = $v['product_id'];
                            }
                        if($product_ids && in_array($val['productid'],$product_ids)){
                            $has_coupon_product[$key]['discount'] = $ck_value['discount'];
                            $has_coupon_product[$key]['coupon_id'] = $ck_value['coupon_id'];
                            $has_coupon_product[$key]['product_id'] = $val['productid'];
                            $has_coupon_product[$key]['total'] = $val['price']*$val['number'];
                        }
                    }else if (4 == $coupon['type']) {
                        // 部分品牌优惠券
                        $manufacturer_id_arr = getData("select manufacturer_id from hb_coupon_manufacturer where coupon_id=".$ck_value['coupon_id']);
                        $manufacturer_ids = '';
                        foreach($manufacturer_id_arr as $m_value){
                            $manufacturer_ids .= $m_value['manufacturer_id'].',';
                        }
                        $manufacturer_ids = substr($manufacturer_ids,0,strlen($manufacturer_ids)-1);
                        $products = getData("select product_id from hb_product where brand_id in(".$manufacturer_ids.")");
                        if($products && in_array($val['productid'],$products)){
                            $has_coupon_product[$key]['discount'] = $ck_value['discount'];
                            $has_coupon_product[$key]['coupon_id'] = $ck_value['coupon_id'];
                            $has_coupon_product[$key]['product_id'] = $val['productid'];
                            $has_coupon_product[$key]['total'] = $val['price']*$val['number'];
                        }
                    }
                }
            }
        }

        // 统计优惠券商品的总额
        $has_coupon_product_total = 0;
        if($has_coupon_product){
            foreach($has_coupon_product as $v)
                $has_coupon_product_total += $v['total'];
        }
        // 计算下单的总价（多订单之和）
        $count_price = 0;
        $count_price1 = 0;

        $ids_array = array();
        $arr = array();
        foreach($post['prdlist'] as $key=>$val){
            if(!isset($val["is_share"])){
                $val["is_share"]=0;
            }
            if(!isset($val["sale_id"])){
                $val["sale_id"]=0;//没有传值sale_id   为0
            }
            //cgl  2017-4-17  增加限定为会员卡
            if(@$val['productid']=="7883"){
               $val["type"]==4;
            }

            //修改会员订单  cgl  2017-4-11
            if(!isset($val["number"]) || $val["type"]==4){
                $val["number"]=1;
            }
            //没有type  默认为1
            if(!isset($val['type'])){
               $val['type']=1;
            }
            $change = 0;
            //cgl 17.3.29  新增   为团购时，数量为1 || $val['type']==3
            if($val['type'] == 2 ){
                $val['number']=1;
            }
            if(!isset($val["product_item_id"])){
                $val['product_item_id']=0;
            } 
            if($val["type"]==4){
                //会员订单   判断当前用户是否是会员
                if($customer_info){
                    if($customer_info["merchant_id"]==1){
                        $this->res = array(
                                'retcode'=>4010,
                                'msg'=>'该用户已是会员'
                            );
                            continue ;
                    }
                }

            }

            //只能购买一次
            if($val["type"]==3){
                $buy_once=getRow("SELECT * FROM hb_order AS a JOIN hb_order_product AS b ON a.order_id  =b.`order_id` WHERE a.order_type=3 AND b.product_id='".$val["productid"]."' and a.customer_id = '".$this->customer_id."'  and a.order_status_id in (2,3,4,5) and a.relate_id='".$val["sale_id"]."' ");
                
                if($buy_once){
                    //已经购买过一次了
                    $this->res = array(
                            'retcode'=>4006,
                            'msg'=>'你已经购买过限时抢购商品了'
                        );
                        continue ;
                }
                //不能提交  cgl  2017-4-11
                
                $is_start=getRow("select * from hb_salelimit where UNIX_TIMESTAMP(date_start)>'".time()."' and status=1 and sale_id = '".$val["sale_id"]."' ");
                if($is_start){
                    //是否已经开始
                    $this->res = array(
                            'retcode'=>4007,
                            'msg'=>'限时抢购商品还未开始'
                        );
                        continue ;
                }
                $is_end=getRow("select * from hb_salelimit where UNIX_TIMESTAMP(date_end)<'".time()."' and status=1 and sale_id = '".$val["sale_id"]."' ");
                if($is_end){
                    //已经结束了  
                    $this->res = array(
                            'retcode'=>4008,
                            'msg'=>'限时抢购商品已经结束'
                        );
                        continue ;
                }
            }

            //是否是成为会员订单  0是普通订单，1是会员订单
            $is_member_status=0;
            if(isset($val["type"]) && @$val["type"]==4){//cgl  2017-4-10  修改
                $is_member_status=1;
            }
            // $is_member_status=$val["is_member_status"];
            //判断商品是否已经下架或发生修改以及库存不足的问题
            $product = getRow("select p.* ,pd.name from `hb_product` as p inner join `hb_product_description` as pd on pd.product_id = p.product_id where p.product_id = '" .$val['productid']. "' and p.status = '1'");

            if(!$product){
                // echo "789";
                $change = 1;//商品已经下架或不存在
            }else{
                if($product['quantity'] < $val['number']){
                    $change = 2;//商品库存不足
                }
            }

            if($val['product_item_id'] > 0){
                //购物车商品有规格，实际商品规格已被删除
                $item = getRow("select * from `hb_product_item` where product_item_id = '" .$val['product_item_id']. "' and product_id = '" .$val['productid']. "' and status = '0'");
                if(empty($item)){
                    // echo "235";
                    $change = 1;//商品已经下架或不存在
                }else{
                    if($item['quantity'] < $val['number']){
                        $change = 2;//商品库存不足
                    }
                }
            }else{
                //购物车商品无规格，实际商品规格已经添加了新的规格
                $item = getRow("select * from `hb_product_item` where product_id = '" .$val['productid']. "' and status = 0 ");
                if(!empty($item)){
                    // echo "123";
                    $change = 1;//商品已经下架或不存在
                }else{// cgl  修改 
                    $item = getRow("select * from `hb_product` where product_id = '" .$val['productid']. "' and status = 1 ");
                    $item['product_options']=NULL;
                }
            }

            if($change == 0){
                //团购
                if($val['type'] == 2){
                    $groupby = getRow("select * from hb_groupby where product_id='".$val['productid']."' and group_id = '".$val['group_id']."' and group_status = 1 and UNIX_TIMESTAMP(end_time)>= '".time()."' ");
                    if(empty($groupby)){
                        $this->res = array(
                            'retcode'=>3101,
                            'msg'=>'团购活动异常'
                        );
                        continue ;
                    }
                    //1为参团，2为开团
                    if($val['group_type'] == 1){
                        //1为参团
                        $group = getRow("select * from hb_group_join where group_id='".$val['group_id']."' and product_id='".$val['productid']."' and join_id='".$val['join_id']."' and join_status=1 ");
                        if($group){
                            //判断是否参加过该团
                            $is_join = getRow("select * from hb_group_join_info where customer_id = '".$this->customer_id."' and product_id = '".$val['productid']."' and join_id = '".$val['join_id']."' and status=1 ");
                            if($is_join){
                                $this->res = array(
                                    'retcode'=>3103,
                                    'msg'=>'已经参加过该团'
                                );
                                continue ;
                            }
                            //判断该团人数是否已满
                            $group_info = getRow("select *,count(gji.join_id) as total from hb_groupby as g left join hb_group_join as gj on g.group_id=gj.group_id
                                                  left join hb_group_join_info as gji on gj.join_id=gji.join_id
                                                  where g.group_id ='".$val['group_id']."' and g.product_id = '".$val['productid']."' and g.group_status=1 and gj.group_id ='".$val['group_id']."' and gj.product_id = '".$val['productid']."' and gj.join_status=1 and gji.join_id = '".$val['join_id']."' and gji.status=1 ");
                            if($group_info["groupnum"] <= $group_info["total"]){
                                $this->res = array(
                                    'retcode'=>3104,
                                    'msg'=>'该团人数已满'
                                );
                                continue ;
                            }

                        }else{
                            $this->res = array(
                                'retcode'=>3105,
                                'msg'=>'该团不存在'
                            );
                            continue ;
                        }
                    }else if($val['group_type'] == 2){
                        //2为开团，判断是否开过同样的团
                        $open_group = getRow("select * from hb_group_join as gj left join hb_group_join_info as gji on gj.group_id=gji.group_id  where gji.customer_id = '".$this->customer_id."' and gji.type=2 and gji.group_id = '".$val['group_id']."' and gji.product_id = '".$val['productid']."' and gj.product_id = '".$val['productid']."' and gj.group_id = '".$val['group_id']."' and gj.add_customer_id = '".$this->customer_id."' and gj.join_status=1 ");
                        if($open_group){
                            $this->res = array(
                                'retcode'=>3100,
                                'msg'=>'已经开过团了'
                            );
                            continue ;
                        }
                    }
                }
                

                // if(!isset($val["price"])){
                    //判断价格
                    if($customer_info["merchant_id"]==0 ){//市场价
                        $val['price']=$item["price"];
                    }else{//会员价
                        $val['price']=$item["proxyprice"];
                    }
                    //修改团购订单  价格  2017-4-18
                    if($val['type'] == 2){
                         $group_price = getRow("select * from hb_groupby where product_id='".$val['productid']."' and group_id = '".$val['group_id']."' and group_status = 1 ");
                        if(!empty($group_price)){
                            $val["price"]=$group_price["groupprice"];     
                        }
                       
                    }

                //是否是限时抢购商品  cgl  2017-4-11
                    if($val["type"]==3 && isset($val["sale_id"])){
                        //查询价格
                        $price=getRow("select sale_price from hb_product_sale_price as c where c.product_id='".$val["productid"]."' and c.sale_id='".$val["sale_id"]."' ");
                        if($price){
                            $val['price']=$price["sale_price"];
                        }else{
                            //不能到订单  没有限时抢购价格
                            $this->res = array(
                                'retcode'=>4009,
                                'msg'=>'限时抢购商品没有价格'
                            );
                            continue ;
                        }
                    }
                    
                    // foreach($post['prdlist'] as $key=>$val){
                    
                    // }
                    //修改运费   cgl  2017-4-5  增加  易稳华  增加运费问题
                    // if(!isset($val["freight"])){
                    //     $val["freight"] = 0;
                    // }else{
                        // if($count_price>=80){
                          $val["freight"] = 0;  
                        // }else{
                        //     $val["freight"] = 10;  //运费是10元  
                        //     $val['freight'] = (($val['price']*$val['number'])/$count_price)*$val['freight'];    
                        // }
                        
                    // }

                    // print_r($customer_info);
                // }
                //分享减免
                $derate_money = $product['derate_money'];
                $total = (float)($val['price']*$val['number']+$val['freight']);
                //cgl 2017-5-31 修改订单  0元购与数量无关
                $val["total_all"]=$val['price'];//$total;  //cgl 2017-5-27 增加 下面判断是否0元购

                if($val['is_share'] == 2 && $product['derate_money'] >= 0 ){
                    $total = (float)$total-(float)$derate_money;
                }

                // 使用优惠券
                if($has_coupon_product){
                    foreach($has_coupon_product as $k=>$v){
                        if($val['productid'] == $v['product_id']){
                            $coupon_id = $v['coupon_id'];
                            $coupon_discount = ($v['total']*$v['discount'])/$has_coupon_product_total;
                            $total -= $coupon_discount;
                            //cgl 增加 2017-5-27
                            $val["coupon_id"]=$v["coupon_id"];
                            $val["coupon_discount"]=$v['discount'];
                        }
                    }
                }else{
                    $coupon_id = 0;
                    $coupon_discount = 0;
                }
                $order_status_id=1;//支付状态
                //团购免单  开团  cgl 2017-5-3 
                if(@$val['group_type'] == 2 && @$val["type"]==2){
                    
                    $is_open_free_group = getRow("select * from hb_groupby where group_id='".$val['group_id']."' and product_id='".$val['productid']."' and group_status=1 ");
                    if(!empty($is_open_free_group)){
                        if($is_open_free_group["is_open_free"]==1){ //开团免单
                            $total=0;
                            $val['freight']=0;
                            $order_status_id=2;
                        }
                    }
                }
                //计算总金额 cgl 2017-5-3
                $count_price += $total;
                $count_price1+=(float)$val['price']*$val['number'];

                //修改   cgl  不能有收货地址
                if($val["type"]==4){
                    $address["custom_field"]="NULL";
                    $address["firstname"]="NULL";
                    $address["address_1"]="NULL";
                    $address["city"]="NULL";
                    $address["postcode"]="NULL";
                    $address["country"]="NULL";
                    $address["country_id"]="NULL";
                    $address["zone"]="NULL";
                    $address["zone_id"]="NULL";
                }

                //新增订单数据   增加isset 判断 没有的设置空 解决报错 lcb 5-17
                $data = array(
                    'shipping_custom_field'=>isset($address['custom_field']) ? $address['custom_field'] : '',
                    'image'=>$product['image'],
                    'firstname'=>isset($customer_info['firstname']) && $customer_info['firstname']?$customer_info['firstname']:'',
                    'shipping_firstname'=>isset($address['firstname']) ? $address['firstname'] : '',
                    'shipping_address_1'=>isset($address['address_1']) ? $address['address_1'] : '',
                    'order_type'=>$val['type'],
                    'join_id'=>'',
                    'relate_id'=>json_encode(array())
                );

                if($val['type'] == 2 ){
                    $data['join_id'] = @$val['join_id'];
                    $data['relate_id'] = json_encode(array("join_id"=>@$val["join_id"],"group_id"=>$val["group_id"],"product_id"=>$val['productid'],"group_type"=>$val['group_type']));
                }
                //增加限时抢购   cgl   2017-4-11 
                if($val['type'] == 3){
                    $data['relate_id'] = $val["sale_id"];
                }

                exeSql("insert into `hb_order` set
                        customer_id = '" .(int)$customer_info['customer_id']. "',
                        customer_group_id = '1',
                        firstname = :firstname,
                        telephone = '" .$customer_info['telephone']. "',
                        shipping_firstname = :shipping_firstname,
                        shipping_address_1 = :shipping_address_1,
                        shipping_city = '" .$address['city']. "',
                        shipping_postcode = '" .$address['postcode']. "',
                        shipping_country = '" .$address['country']. "',
                        shipping_country_id = '" .$address['country_id']. "',
                        shipping_zone = '" .$address['zone']. "',
                        shipping_zone_id = '" .$address['zone_id']. "',
                        shipping_custom_field = :shipping_custom_field,
                        total = '" .$total. "',
                        order_status_id = '".$order_status_id."',
                        date_added = '" .time(). "',
                        date_modified = now(),
                        status = '0',
                        image = :image,
                        isback = '0',
                        isreview = '0',
                        order_num = '" .$order_num. "',
                        is_blance = '0',
                        freight ='".$val['freight']."',
                        invoicefee = '0',
                        balance_money = '0',
                        merchant_id = '" .$product['merchant_id']. "',
                        warehouse_id = '" .$product['manufacturer_id']. "',
                        is_share = '" .$val['is_share']. "',
                        derate_money = '" .$product['derate_money']. "',
                        return_add_money = '" .$product['return_add_money']. "',
                        order_type = :order_type,
                        order_type_status=1,
                        relate_id = :relate_id,
                        join_id = '" .$data['join_id']. "',
                        is_member_status='".$is_member_status."',
                        take_down_money='".$total."',
                        coupon_id='".$coupon_id."',
                        coupon_discount='".$coupon_discount."'
                        ",$data);

                $order_id = getLastId();

                if($order_id <= 0){
                    $this->res = array(
                        'retcode'=>4003,
                        'msg'=>'创建订单失败'
                    );
                    continue ;
                }

                //新增order_product表数据
                $order_product = array(
                    'order_id'=>$order_id,
                    'product_id'=>$product['product_id'],
                    'name'=>$product['name'],
                    'model'=>$product['model'],
                    'quantity'=>$val['number'],
                    'price'=>$val['price'],
                    'total'=>$total,
                    'tax'=>0,
                    'reward'=>0,
                    'date_added'=>time(),
                    'product_item_id'=>$val['product_item_id'],
                    'product_item_name'=>!empty($item)?$item['product_options']:''

                );
                saveData('hb_order_product',$order_product);
                $order_product_id = getLastId();

                if($order_product_id <= 0){
                    $this->res = array(
                        'retcode'=>4004,
                        'msg'=>'创建订单失败'
                    );
                    continue ;
                }
                // 下单成功，如果有使用优惠券的话，更新用户优惠券的状态
                /*if (isset($coupon_id) && (0 != $coupon_id)) {
                    exeSql("UPDATE hb_coupon_customer SET status=1 WHERE coupon_id=".$coupon_id." and customer_id=".$post['customerid']."");
                }*/
                

                //cgl  2017-3-29  添加赚差价
                if(!empty($val['proxyid']) && !empty($val['differenceprice']) ){
                    $dataout['target_id'] = $this->customer_id;//购买人
                    $dataout['customer_id'] = $val['proxyid'];//分享人
                    $dataout['differenceprice'] = $val['differenceprice'];
                    $dataout['order_id'] = $order_id;
                    $dataout["data_added"]=date("Y-m-d H:i:s",time());
                    saveData("hb_orderout",$dataout);
                    // $this->model_checkout_order->addOrderOut($dataout);
                }

                //扣除商品的库存
                $product_result = exeSql("UPDATE hb_product SET quantity = (quantity - '". (int)$val['number'] ."') WHERE product_id = '". (int)$product['product_id'] ."'");
                //通过获取受影响的行数来判断是否删除成功
                if($product_result->rowCount() == 0){
                    $this->res = array(
                        'retcode'=>4005,
                        'msg'=>'创建订单失败'
                    );
                    continue ;
                }


                //扣除商品规格库存
                if($val['product_item_id'] > 0){
                    $product_item_result = exeSql("UPDATE hb_product_item SET quantity = (quantity - '". (int)$val['number'] ."') WHERE product_id = '". (int)$product['product_id'] ."' and product_item_id = '" .(int)$val['product_item_id']. "'");
                    //通过获取受影响的行数来判断是否删除成功
                    if($product_item_result->rowCount() == 0){
                        $this->res = array(
                            'retcode'=>4006,
                            'msg'=>'创建订单失败'
                        );
                        continue ;
                    }
                }


                //删除购物车
                if(isset($val['cartid']) && $val['cartid'] > 0){
                    $del_cart_result = exeSql("DELETE FROM hb_cart WHERE customer_id = '" . (int)$this->customer_id . "' AND cart_id = '" . (int)$val['cartid'] . "' and product_id = '" .(int)$val['productid']. "'");
                    //通过获取受影响的行数来判断是否删除成功
                    if($del_cart_result->rowCount() == 0){
                        $this->res = array(
                            'retcode'=>4007,
                            'msg'=>'清除购物车失败'
                        );
                        continue ;
                    }
                }
                //新增 数组 cgl 2017-5-3
                $val["order_id"]=$order_id;
                $val["total"]=$total;
                $arr[]=$val;
                $ids_array[] = $order_id;
            }else if($change == 1){
                if(isset($val['cartid']) && $val['cartid'] > 0){
                    $cart_ids[] = $val['cartid'];
                }
                $this->res = array(
                    'retcode'=>4001,
                    'msg'=>'商品已经下架或不存在'
                );
                    continue ;
            }else{
                if(isset($val['cartid']) && $val['cartid'] > 0){
                    $cart_ids[] = $val['cartid'];
                }
                $this->res = array(
                    'retcode'=>4002,
                    'msg'=>'商品库存不足'
                );
                continue;
            }

        }

        if($this->res['retcode'] == 0){
            commit();
            $ids = implode(',',$ids_array);
            $is_open_free_groups=0;

            $count_price2=$count_price;

            //修改运费问题 cgl 2017-5-3 
            if(!empty($arr)){
                foreach($arr as $k=>$val){
                    //cgl 2017-5-27 增加
                    //使用0元购的优惠券都是10元
                    
                    if($val["coupon_id"]>0 && $val["coupon_discount"]>0 && $val["coupon_discount"]==$val["total_all"] ){
                      
                      $val["freight"] = 10;
                      
                      // if($count_price>0){
                      //   $val['freight'] = (($val['total'])/$count_price2)*$val['freight'];    
                      // }else{
                      //   $count_price=0;
                        $val["freight"]=$val["freight"];//($k+1);
                      // }   
                    }else{
                      //没有使用优惠券
                      if($count_price1>=80){
                        $val["freight"] = 0;  //满80包邮
                      }else{
                        $val["freight"] = 10;  //运费是10元 
                        if($count_price>0){
                          $val['freight'] = (($val['total'])/$count_price2)*$val['freight'];
                        }else{
                          $count_price=0;
                          $val['total']=0;//5-26 增加 cgl
                          $val["freight"]=$val["freight"]/($k+1);
                        }
                      }
                    }
                    $count_price+=$val["freight"];
                    $val['total']+=$val["freight"];
                    $val['total']=sprintf("%.2f",$val['total']);

                    // if($count_price1>=80){
                    //   $val["freight"] = 0;  
                    // }else{
                    //     if($count_price1>0){
                    //         $val["freight"] = 10;  //运费是10元  
                    //         //cgl 2017-5-27 增加
                    //         if($val["total"]<0){
                    //           $val["total"]=0;
                    //         }
                    //         if($count_price>0){
                    //             $val['freight'] = (($val['total'])/$count_price2)*$val['freight'];    
                    //         }
                    //         if(@$val['group_type'] == 2 && @$val["type"]==2){
                    //             $is_open_free_group = getRow("select * from hb_groupby where group_id='".$val['group_id']."' and product_id='".$val['productid']."' and group_status=1 ");
                    //             if(!empty($is_open_free_group)){
                    //                 if($is_open_free_group["is_open_free"]==1){ //开团免单
                    //                     $val["freight"]=0;
                    //                 }
                    //             }
                    //         }
                            
                    //     }else{
                    //         //小于等于0的情况
                    //         // $val['total']=0;
                    //         // $count_price=0;
                    //         // print_r($val);
                    //         if($val["price"]*$val["number"]<80 && $count_price<=0){
                    //             $val["freight"]=10;
                    //         }else{
                    //             $val["freight"]=0;
                    //             $val['total']=0;//5-26 增加 cgl
                    //         }
                    //         if($val['total']<0 && $val["price"]*$val["number"]>=80){
                    //             $val['total']=0;
                    //         }
                    //         if($count_price<0 && $val["price"]*$val["number"]>=80){
                    //             $count_price=0;
                    //             $val['total']=0;//5-26 增加 cgl
                    //         }
                            
                    //         if(@$val['group_type'] == 2 && @$val["type"]==2){
                    //             $is_open_free_group = getRow("select * from hb_groupby where group_id='".$val['group_id']."' and product_id='".$val['productid']."' and group_status=1 ");
                    //             if(!empty($is_open_free_group)){
                    //                 if($is_open_free_group["is_open_free"]==1){ //开团免单
                    //                     $val["freight"]=0;
                    //                     $count_price=0;
                    //                 }
                    //             }
                    //         }
                    //         $val["freight"]=$val["freight"]/($k+1);
                    //     }
                    //     $count_price+=$val["freight"];
                    //     $val['total']+=$val["freight"];
                    //     $val['total']=sprintf("%.2f",$val['total']);
                    // }
                    exeSql("update hb_order set freight = '".$val["freight"]."',total='".$val['total']."',take_down_money = '".$val["total"]."' where order_id = '".$val["order_id"]."' ");

                    //团购免单  开团  cgl 2017-5-3 
                    if(@$val['group_type'] == 2 && @$val["type"]==2){
                        $is_open_free_group = getRow("select * from hb_groupby where group_id='".$val['group_id']."' and product_id='".$val['productid']."' and group_status=1 ");
                        if(!empty($is_open_free_group)){
                            if($is_open_free_group["is_open_free"]==1){ //开团免单
                               $is_open_free_groups=1;
                                //新增  cgl 运费问题 2017-5-3   
                                //修改  cgl 返利情况为0
                                exeSql("update hb_order set freight = 0,return_add_money=0,total=0,is_open_free=1 where order_id = '".$val["order_id"]."' ");
                                //修改订单状态
                                //添加团购团的数据
                                $customer=getRow("select * from hb_customer where customer_id='".$this->customer_id."' ");
                                $product_id=$val["productid"];
                                $group_id=$val["group_id"];
                                $v["customer_id"]=$this->customer_id;
                                $v["order_id"]=$val["order_id"];
                                
                                saveData("hb_group_join",array(
                                    "join_status"=>1,
                                    "date_modified"=>date("Y-m-d H:i:s"),
                                    "product_id"=>$product_id,
                                    "group_id"=>$group_id,
                                    "add_customer_id"=>$this->customer_id,
                                    "add_customer_name"=>@$customer["firstname"],
                                    "add_customer_image"=>@$customer["headurl"],
                                    "date_added"=>date("Y-m-d H:i:s")
                                    ));
                                $join_id= getLastId();
                                exeSql("INSERT INTO hb_group_join_info set status=1,date_modified=NOW(),date_added=NOW(),join_id= '".$join_id."' ,product_id= '".$product_id."' ,customer_id='".$v["customer_id"]."'  ,type=2,group_id='".$group_id."' ");
                                $arr=array("join_id"=>$join_id,"group_id"=>$group_id,"product_id"=>$product_id,"group_type"=>2);
                                $arr1=json_encode($arr);
                                exeSql("update hb_order set relate_id= '".$arr1."',join_id='".$join_id."',order_type_status=2 where order_id= '".$v["order_id"]."' and customer_id='".$v["customer_id"]."'  ");

                            }
                        }
                    }
                }
            }
            

            $this->res['data'] = array(
                'orderids'=>$ids,
                'order_num'=>$order_num,
                'balance'=>isset($customer_balance['availabe_balance'])?sprintf("%.2f",$customer_balance['availabe_balance']):'0.00',
                "total"=>sprintf("%.2f",$count_price),
                "order_time"=>time(),
                "is_open_free_group"=>$is_open_free_groups

            );
        }else{
            rollBack();
        }
        //将库存不足或商品下架的购物车置为无效
        if(isset($cart_ids) && !empty($cart_ids)){
            foreach($cart_ids as $key=>$val){
                exeSql("update `hb_cart` set status = 1 where cart_id = '" .(int)$val. "'");
            }
        }
        return $this->res;
    }
}