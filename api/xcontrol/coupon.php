<?php
/**
 *  @description  优惠券接口文件描述，包含了如下接口
 *	1：获取用户的优惠券列表接口  coupon/getUserCouponsListById
 *  2：根据购物车里的商品信息选择合适的优惠券接口  coupon/selectCouponListByProducts
 *  3：根据优惠券id获取优惠券可以使用的商品 coupon/getProductsByCouponId
 *  4:
 */
include "xcontrol/base.php";
class coupon extends base{

	/**
	 *  @description  获取用户的优惠券列表接口  coupon/getUserCouponsListById
	 *  APP端必传入参数
	 *  @param 		  string    passkey           用户key，必传参数
	 *  @param 		  string    customerid	      用户id，必传参数
     *  @param 		  int       page 			  页码，必传参数
	 *  @param   	  int       page_size         每页显示的条数，必传参数
	 *  @param 		  int       type              优惠券状态，枚举类型：0未使用，1已使用，2已过期，必传参数
	 *  APP端可选传入参数
	 *  @param 		  暂无
	 *  @return 	  json对象
	 *  @author 	  godloveevin@yeah.net
	 *  @d/t 		  2017-04-07
	 */
	public function getUserCouponsListById(){
        $serve=$_SERVER;
        $name1=$serve['SERVER_NAME'];
		$accept_data = $_POST;
        if (!isset($accept_data['page']) || !isset($accept_data['page_size']) || !isset($accept_data['type'])) {
            $this->res = array(
                'retcode'   =>  1000,
                'msg'       =>  '参数错误'
            );
            return $this->res;
        }

        //zxx 2017-6-8 修改

        if($accept_data['type']==0){
            //找出所有未使用的
            $coupon_id_arr=getData("select cc.coupon_id from hb_coupon_customer as cc left join hb_coupon as c on c.coupon_id=cc.coupon_id where customer_id=".$accept_data['customerid']." and c.status=1 and cc.status=0   
                                  limit ".($accept_data['page']-1)*$accept_data['page_size'].",".$accept_data['page_size']);
        }else if($accept_data['type']==1){
            //找出所有使用过的
            $coupon_id_arr=getData("select cc.coupon_id from hb_coupon_customer as cc left join hb_coupon as c on c.coupon_id=cc.coupon_id where customer_id=".$accept_data['customerid']." and c.status=1 and cc.status=1   
                                  limit ".($accept_data['page']-1)*$accept_data['page_size'].",".$accept_data['page_size']);
        }else if($accept_data['type']==2){
            //找出所有使用过的
            $coupon_id_arr=getData("select cc.coupon_id from hb_coupon_customer as cc left join hb_coupon as c on c.coupon_id=cc.coupon_id where customer_id=".$accept_data['customerid']." and  c.status=3   
                                  limit ".($accept_data['page']-1)*$accept_data['page_size'].",".$accept_data['page_size']);
        }
        if (! $coupon_id_arr) {
        	if(0 == $accept_data['type'])
        		$msg = '您没有未使用的优惠券';
        	if(1 == $accept_data['type'])
        		$msg = '您没有已使用的优惠券';
        	if(2 == $accept_data['type'])
        		$msg = '您没有已过期的优惠券';
        	$this->res = array(
                'retcode'   =>  0,
                'msg'       =>  $msg
            );
            return $this->res;
        }

        $coupon_ids = '';
        foreach($coupon_id_arr as $key=>$value){
        	$coupon_ids .= $value['coupon_id'].',';
        }
        $coupon_ids = substr($coupon_ids,0,strlen($coupon_ids)-1);
        $coupon_list_info = getData("select coupon_id,name,date_end,date_start,discount,discount_desn,min_limit_amount 
        							 from hb_coupon where coupon_id in(".$coupon_ids.")");
        $customer_id=isset($_POST['customerid'])?$_POST['customerid']:0;

        $invitecode=getRow("select invitecode_id from hb_invitecode where customer_id=".$customer_id);
        $invitecode=@$invitecode['invitecode_id'];
        foreach($coupon_list_info as $key=>$value){
        	// 处理金额显示方式
        	$coupon_list_info[$key]['discount'] = (int)$value['discount'];
            if($name1=='test.haiqihuocang.com'){
                $coupon_list_info[$key]['url'] = "http://test.haiqihuocang.cn/web/coupon/index.html?coupon_id=".$value['coupon_id']."&inviteid=".$invitecode;
            }else{
                $coupon_list_info[$key]['url'] = "http://www.haiqihuocang.cn/web/coupon/index.html?coupon_id=".$value['coupon_id']."&inviteid=".$invitecode;
            }
            
        	$coupon_list_info[$key]['min_limit_amount'] = (int)$value['min_limit_amount'];
        }
        $this->res['data'] = $coupon_list_info;
        return $this->res;
	}

    /*
     *优惠券试用商品范围
     * zxx 2017-4-27 
     */
     function getCouponProducts(){
        //获取customer_id
        $customer_id=isset($_POST['customerid'])?$_POST['customerid']:"";
        $type=isset($_POST['type'])?$_POST['type']:0;
        $page=isset($_POST['page'])?$_POST['page']:1;
        $count=10;
        $start=($page - 1)* $count;
        $rule=$this->getRule($type);
        if(empty($_POST['coupon_id'])){
             $this->res = array(
                'retcode'   =>  1000,
                'msg'       =>  '参数错误'
            );
               return $this->res;
        }
        //通过优惠券对应的type来获取商品的
        $type=getRow("select type,min_limit_amount from hb_coupon where coupon_id='".$_POST['coupon_id']."' and status!=3");
        if(empty($type)){
            $this->res = array(
                'retcode'   =>  1001,
                'msg'       =>  '该优惠券不存在或已经作废'
            );
              return $this->res;
        }
        $limit_amount= $type['min_limit_amount'];
        if($type['type'] ==1 ){
            //新品推荐商品
            $category_id=513;
            $products=getData("select distinct 
                    p.image as productimg,
                    p.date_added,
                    FORMAT(p.price,2) as price,
                    FORMAT(p.marketprice,2) as marketprice,
                    FORMAT(p.proxyprice,2) as proxyprice,
                    pd.name as productname,
                    p.product_id as productid,
                    p.quantity
                    from hb_product as p,hb_product_description as pd where p.status=1  and p.product_id=pd.product_id  order by ".$rule." limit ".$start.", ".$count."");
            $products=$this->getProductData($products,$customer_id);
        }else if($type['type'] ==2){
            //部分商品

            $sql="select distinct 
                    p.image as productimg,
                    p.date_added,
                    FORMAT(p.price,2) as price,
                    FORMAT(p.marketprice,2) as marketprice,
                    FORMAT(p.proxyprice,2) as proxyprice,
                    pd.name as productname,
                    p.product_id as productid,
                    p.quantity
                    from hb_product as p,hb_product_description as pd where p.status=1 and p.product_id=pd.product_id and p.product_id in (select product_id from hb_coupon_product where coupon_id='".$_POST['coupon_id']."') order by ".$rule." limit ".$start.", ".$count."";
            $products=getData($sql);
            $products=$this->getProductData($products,$customer_id);
        }else if($type['type'] ==3){
            //商品类别
            $category_id=getData("select category_id from hb_coupon_category where coupon_id=".$_POST['coupon_id']);
            $array=array();
            if(!empty($category_id)){

                foreach ($category_id as $key => $value) {
                    $array[]=$value['category_id'];
                }
                //找出二级分类
                $array1=array();
                if(!empty($array)){
                    foreach ($array as $key => $value) {
                       $array1=getData("select category_id from hb_category where parent_id=".$value." and status=1");
                       if(!empty($array1)){
                            foreach ($array1 as $key => $value1) {
                                //如果该分类id在数组中已经存在则不插入
                                if(!in_array($value1['category_id'], $array)){
                                    $array[]=$value1['category_id'];
                                }
                            }
                       }
                    }

                    if(!empty($array)){
                        foreach ($array as $key => $value) {
                           $array1=getData("select category_id from hb_category where parent_id=".$value." and status=1");
                           if(!empty($array1)){
                                foreach ($array1 as $key => $value1) {
                                    if(!in_array($value1['category_id'], $array)){
                                        $array[]=$value1['category_id'];
                                    }
                                }
                           }
                        }
                    }
                }
                $array=implode(',', $array);
                $product_id=getData("select product_id from hb_product_to_category where category_id in (".$array.")");
                $product_ids=array();
                //获取到所以的商品id
                if(!empty($product_id)){
                    foreach ($product_id as $key => $value) {
                         $product_ids[]=$value['product_id'];
                    }
                    $product_ids=implode(',', $product_ids);
                     $sql="select distinct 
                            p.image as productimg,
                            p.date_added,
                            FORMAT(p.price,2) as price,
                            FORMAT(p.marketprice,2) as marketprice,
                            FORMAT(p.proxyprice,2) as proxyprice,
                            pd.name as productname,
                            p.product_id as productid,
                            p.quantity
                            from hb_product as p,hb_product_description as pd where p.status=1  and p.product_id=pd.product_id and p.product_id in (".$product_ids.") order by ".$rule." limit ".$start.", ".$count."";
                           // echo $sql;exit;
                    $products=getData($sql);
                    $products=$this->getProductData($products,$customer_id);
                }else{
                    $products=array();
                }
            }
        }else if($type['type'] ==4){
            //部分品牌
            $sql="select distinct 
                    p.image as productimg,
                    p.date_added,
                    FORMAT(p.price,2) as price,
                    FORMAT(p.marketprice,2) as marketprice,
                    FORMAT(p.proxyprice,2) as proxyprice,
                    pd.name as productname,
                    p.product_id as productid,
                    p.quantity
                    from hb_product as p,hb_product_description as pd where p.status=1  and p.product_id=pd.product_id and p.manufacturer_id in (select manufacturer_id from hb_coupon_manufacturer  where coupon_id='".$_POST['coupon_id']."') order by ".$rule." limit ".$start.", ".$count."";

            //$sql="select manufacturer_id from hb_coupon_manufacturer  where coupon_id=".$_POST['coupon_id'];    
            $products=getData($sql);
            //var_dump($sql);exit;
            $products=$this->getProductData($products,$customer_id);
        }
        $this->res["data"]=$products;
        $this->res["retcode"]=0;
        $this->res["msg"]="请求成功";
        return $this->res;
     }   



        /*
        *把获取到的商品结果集进行价格，上新处理
        *zxx 2017-4-27
        */
        function getProductData($products,$customer_id){
            //var_dump($customer_id);exit();
            $customer=getRow("select merchant_id from hb_customer where customer_id='".$customer_id."' ");
            if(!empty($products)){
                foreach ($products as $key => $value) {
                    if(@$customer["merchant_id"]>0){
                        $products[$key]['finalprice']=$value['proxyprice'];//最终价格
                        $products[$key]['originalprice']=$value['marketprice'];//原价
                    }else{
                        $products[$key]['finalprice']=$value['price'];//最终价格
                        $products[$key]['originalprice']=$value['marketprice'];//原价
                    }
                    $products[$key]['finalprice']=str_replace(',', "", $products[$key]['finalprice']);
                    $products[$key]['originalprice']=str_replace(',', "", $products[$key]['originalprice']);
                    if($value["quantity"]==0){
                        //卖光了
                        $products[$key]["issaled"]=1;
                        $products[$key]["isenough"]=2;
                    }elseif($value["quantity"]<10){
                        //不够
                        $products[$key]["issaled"]=0;
                        $products[$key]["isenough"]=0;
                    }else{
                        //充足
                        $products[$key]["issaled"]=0;
                        $products[$key]["isenough"]=1;
                    }
                    $currenttime = time();
                    $prdaddedtime = $value['date_added'];
                    //转换成时间戳
                    $prdaddedtimestamp = strtotime($prdaddedtime);
                    if (($currenttime - $prdaddedtimestamp) > 3600 * 24 * 3) {
                        $products[$key]['isnew'] = '0';
                    } else {
                        $products[$key]['isnew'] = '1';
                    }
                    unset($products[$key]["is_saled"]);
                    unset($products[$key]["date_added"]);
                    unset($products[$key]["quantity"]);
                    //unset($products[$key]['price']);  // lcb 6-13 显示非会员价
                    unset($products[$key]['marketprice']);
                    //unset($products[$key]['proxyprice']); // lcb 6-13 显示会员价
                }   
            }
            return $products;   
        }


        /*
        *zxx 2017-3-17 生成排序规则
        */
        function getRule($sortRule){
            if($sortRule==1){
                $rule='p.quantity!=0 DESC,p.sales DESC,p.quantity DESC';
            }elseif($sortRule==2){
                $rule='p.quantity!=0 DESC,p.proxyprice,p.quantity DESC';
            }elseif($sortRule==3){
                $rule='p.quantity!=0 DESC,p.proxyprice DESC,p.quantity DESC';
            }elseif($sortRule==4){
                $rule='p.quantity!=0 DESC,p.proxyprice/p.marketprice,p.quantity DESC';
            }elseif($sortRule==5){
                $rule='p.quantity!=0 DESC,p.proxyprice/p.marketprice DESC,p.quantity DESC';
            }else{
                $rule='p.quantity!=0 DESC,p.date_added DESC,p.date_added DESC,p.quantity DESC';
            }
            return $rule;
        }

	/**
	 *  @description  根据购物车里的商品信息选择合适的优惠券接口  coupon/selectCouponListByProducts
	 *  实现思路：
	 *			step1：验证必传参数
	 *			step2：取出登录用户的所有可用的优惠券
	 *			step3：根据购物车的商品信息以及优惠券的使用规则过滤
	 *  APP端必传入参数
	 *  @param 		  string    passkey           用户key，必传参数
	 *  @param 		  string    customerid	      用户id，必传参数
	 *  @param 		  array     prolist 		  商品列表，必传参数
	 *  	 		  -----		int               numbers      每种商品的下单量，必传参数
	 *  	 		  -----		sting             price        商品单价（分享减免之前的价格），必传参数
	 *  	 		  -----		sting             product_id   商品id，必传参数
	 *  @param   	 
	 *  APP端可选传入参数
	 *	@param 		  array     prolist 		  商品列表，必传参数
	 *  	 		  -----		sting             brand_id     品牌id，可选参数，建议传
	 *  	 		  -----		sting             category_id  商品分类id，可选参数，建议传
	 *  @return 	  json对象
	 *  @author 	  godloveevin@yeah.net
	 *  @d/t 		  2017-04-07
	 */
	public function selectCouponListByProducts(){
		// 获取app端提交过的参数
		$accept_data = $_POST;
        // $array=array();
        // $array[]=array(
        //     "brand_id"=>'',
        //     "category_id"=>'',
        //     'numbers'=>1,
        //     'price'=>59,
        //     'product_id'=>420
        // );
        // $a=json_encode($array);var_dump($a);exit;
		// step1：验证必传参数
		if (!isset($accept_data['prolist']) || empty($accept_data['prolist'])) {
            $this->res = array(
                'retcode'   =>  1000,
                'msg'       =>  '参数错误'
            );
            return $this->res;
        }

        $prolist = json_decode($accept_data['prolist'],true);
        foreach($prolist as $key=>$value){
	        if (! isset($value['numbers']) || ! isset($value['price']) || ! isset($value['product_id'])) {
	            $this->res = array(
	                'retcode'   =>  1000,
	                'msg'       =>  '参数错误'
	            );
	            return $this->res;
	        }
	    }
        //2017-6-8 zxx 修改
        // step2：取出登录用户的所有可用优惠券
        $coupon_id_arr = getData("select cc.coupon_id from hb_coupon_customer as cc left join hb_coupon as c on c.coupon_id=cc.coupon_id where cc.customer_id=".$accept_data['customerid']." 
        					      and c.status=1");
        if (! $coupon_id_arr) {
        	$this->res = array(
                'retcode'   =>  0,
                'msg'       =>  '您没有未使用的优惠券',
                'data'      =>  array()
            );
            return $this->res;
        }

        $coupon_ids = '';
        foreach($coupon_id_arr as $c_value){
        	$coupon_ids .= $c_value['coupon_id'].',';
        }
        $coupon_ids = substr($coupon_ids,0,strlen($coupon_ids)-1);
        $coupon_list_info = getData("select coupon_id,type,name,date_end,date_start,discount,discount_desn,min_limit_amount 
        							 from hb_coupon where coupon_id in(".$coupon_ids.")");

        // 统计订单的总额
        $order_total = 0;
        foreach($prolist as $pk=>$pro){
            $order_total += $pro['numbers']*$pro['price'];
        }

        // step3：过滤优惠券
        foreach($coupon_list_info as $key=>$value){
            // 如果优惠券已经被选过用去下单了，并且订单没有取消，该未使用的优惠券不可再用来下单
            $order_info = getRow("select order_status_id from hb_order 
                                   where order_status_id != 6 and coupon_id=".$value['coupon_id']." and customer_id=".$accept_data['customerid']);
            $coupon_list_info[$key]['is_zero']=0;
            if($order_info){
                $coupon_list_info[$key]['is_available'] = 0;
                // $coupon_list_info[$key]['is_available_msg'] = '优惠券已使用过';
            }else{
                // 订单总额与优惠券的优惠额度过滤
                $is_checked = $this->isCheckedByPid($prolist,$value);
                //查看当前时间是不是大于优惠券的开始时间
                $time=time();
                //var_dump($time);exit;
                if($time<$value['date_start']){
                    $coupon_list_info[$key]['is_available'] = 0;
                }else{
                    if( ($order_total >= $value['min_limit_amount']) && ($is_checked>0) ){
                        $coupon_list_info[$key]['is_available'] = 1;
                        if($coupon_list_info[$key]['min_limit_amount']==$coupon_list_info[$key]['discount'] && $coupon_list_info[$key]['type']==2 ){
                            $coupon_list_info[$key]['is_zero']=1;
                        }
                    }else{
                        foreach($prolist as $pk=>$pro){
                            if ($value['min_limit_amount'] > $pro['numbers']*$pro['price']) {
                                // 根据商品总价过滤
                                $coupon_list_info[$key]['is_available'] = 0;
                                // $coupon_list_info[$key]['is_available_msg'] = '商品总价低于优惠券使用的最低消费额';
                            }else{
                                // 根据优惠券类别过滤
                                if (1 == $value['type']) {
                                    // 全部商品优惠券
                                    $coupon_list_info[$key]['is_available'] = 1;
                                }else if (2 == $value['type']) {
                                    // 部分商品优惠券
                                    if(! getRow("select product_id from hb_coupon_product where coupon_id=".$value['coupon_id']." and 
                                                 product_id=".$pro['product_id'])){
                                        $coupon_list_info[$key]['is_available'] = 0;
                                        // $coupon_list_info[$key]['is_available_msg'] = '商品不属于优惠券使用的商品范围';
                                    }else{
                                        $coupon_list_info[$key]['is_available'] = 1;
                                    }

                                    if($coupon_list_info[$key]['min_limit_amount']==$coupon_list_info[$key]['discount']){
                                        $coupon_list_info[$key]['is_zero']=1;
                                    }
                                }else if (3 == $value['type']) {
                                    $inOrOut = $this->inOrOutCouponCategory($pro['product_id'],$value['coupon_id']);
                                    if (! $inOrOut) {
                                        // 不在分类中
                                        $coupon_list_info[$key]['is_available'] = 0;
                                        // $coupon_list_info[$key]['is_available_msg'] = '商品不属于优惠券使用的商品分类中';
                                    }else{
                                        // 在分类中
                                        $coupon_list_info[$key]['is_available'] = 1;
                                    }
                                }else if (4 == $value['type']) {
                                    // 品牌优惠券
                                    // 获取优惠券的品牌id集
                                    // 判断商品是否在品牌id中
                                    $product = getData("select product_id from hb_product where brand_id in(
                                        select manufacturer_id from hb_coupon_manufacturer where coupon_id=".$value['coupon_id'].")");
                                    foreach($product as $k=>$v){
                                        $product[$k] = $v['product_id'];
                                    }
                                    if (!$product || !in_array($pro['product_id'],$product)) {
                                        $coupon_list_info[$key]['is_available'] = 0;
                                        // $coupon_list_info[$key]['is_available_msg'] = '商品不属于优惠券的品牌范围';
                                    }else{
                                        $coupon_list_info[$key]['is_available'] = 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 按照是否可用排序优惠券结果集
		foreach ($coupon_list_info as $key => $row) {
		    $is_available[$key]  = $row['is_available'];
		}
		array_multisort($is_available, SORT_DESC, $coupon_list_info);

		foreach($coupon_list_info as $key=>$value){
        	// 处理金额显示方式
        	$coupon_list_info[$key]['discount'] = (int)$value['discount'];
        	$coupon_list_info[$key]['min_limit_amount'] = (int)$value['min_limit_amount'];
        }

        $this->res['data'] = $coupon_list_info;
        return $this->res;
	}

//by xi
public function selectCouponListByProducts1(){

        //
        // 获取app端提交过的参数
        $accept_data = $_POST;

        // step1：验证必传参数
        if (!isset($accept_data['prolist']) || empty($accept_data['prolist'])) {
            $this->res = array(
                'retcode'   =>  1000,
                'msg'       =>  '参数错误'
            );
            return $this->res;
        }

        $ids="0";
        $prolist = json_decode($accept_data['prolist'],true);
        foreach($prolist as $key=>$value){
            if (! isset($value['numbers']) || ! isset($value['price']) || ! isset($value['product_id'])) {
                $this->res = array(
                    'retcode'   =>  1000,
                    'msg'       =>  '参数错误'
                );
                return $this->res;
            }
            $ids.=$value['product_id'];
        }
        //得到商品列表
        $products=getData("select product_id,manufacturer_id,category_id,price,proxyprice from hb_product where product_id in ($ids)");



        //
        // step2：取出登录用户的所有可用优惠券
        $coupon_id_arr = getData("select cc.coupon_id,co.name,co.type,co.discount,co.descont_desn,co.min_limit_amount,co.date_start,co.date_end from hb_coupon_customer as cc,hb_coupon as co where cc.customer_id=".$accept_data['customerid']." 
                                  and cc.status=0 and co.status<3 and co.coupon_id=cc.coupon_id");
        if (! $coupon_id_arr) {
            $this->res = array(
                'retcode'   =>  0,
                'msg'       =>  '您没有未使用的优惠券',
                //'data'      =>  array()
            );
            return $this->res;
        }



        $coupon_ids = '';
        foreach($coupon_id_arr as $c_value){
            $coupon_ids .= $c_value['coupon_id'].',';
        }
        $coupon_ids = substr($coupon_ids,0,strlen($coupon_ids)-1);
        $coupon_list_info = getData("select coupon_id,type,name,date_end,date_start,discount,discount_desn,min_limit_amount 
                                     from hb_coupon where coupon_id in(".$coupon_ids.")");

        // 统计订单的总额
        $order_total = 0;
        foreach($prolist as $pk=>$pro){
            $order_total += $pro['numbers']*$pro['price'];
        }

        // step3：过滤优惠券
        foreach($coupon_list_info as $key=>$value){
            // 如果优惠券已经被选过用去下单了，并且订单没有取消，该未使用的优惠券不可再用来下单
            $order_info = getRow("select order_id,coupon_id,customer_id,order_status_id from hb_order 
                                  where coupon_id=".$value['coupon_id']." and customer_id=".$accept_data['customerid']);
            if($order_info){
                if ($order_info['order_status_id'] == 6) {
                    $coupon_list_info[$key]['is_available'] = 1;
                }else{
                    $coupon_list_info[$key]['is_available'] = 0;
                }
            }

            // 订单总额与优惠券的优惠额度过滤
            if( ($order_total >= $value['min_limit_amount']) && $this->isCheckedByPid($prolist,$value) ){
                $coupon_list_info[$key]['is_available'] = 1;
            }else{
                foreach($prolist as $pk=>$pro){
                    if ($value['min_limit_amount'] > $pro['numbers']*$pro['price']) {
                        // 根据商品总价过滤
                        $coupon_list_info[$key]['is_available'] = 0;
                        // $coupon_list_info[$key]['is_available_msg'] = '商品总价低于优惠券使用的最低消费额';
                    }else{
                        // 商品分类优惠券
                        if (1 == $value['type']) {
                            // 全部商品优惠券
                            $coupon_list_info[$key]['is_available'] = 1;
                        }else if (2 == $value['type']) {
                            // 部分商品优惠券
                            if(! getRow("select product_id from hb_coupon_product where coupon_id=".$value['coupon_id']." and 
                                         product_id=".$pro['product_id'])){
                                $coupon_list_info[$key]['is_available'] = 0;
                                // $coupon_list_info[$key]['is_available_msg'] = '商品不属于优惠券使用的商品范围';
                            }else{
                                $coupon_list_info[$key]['is_available'] = 1;
                            }
                        }else if (3 == $value['type']) {
                            // 根据优惠券与商品分类id过滤
                            $category_id_arr = getData("select category_id from hb_coupon_category where coupon_id=".$value['coupon_id']);
                            $category_ids = '';
                            foreach($category_id_arr as $ck_value){
                                $category_ids .= $ck_value['category_id'].',';
                            }
                            $category_ids = substr($category_ids,0,strlen($category_ids)-1);
                            // 判断商品是否在分类中
                            $product = getData("select product_id from hb_product_to_category where category_id in(".$category_ids.")");
                            if (!$product || !in_array($pro['product_id'],$product)) {
                                // 不在分类中
                                $coupon_list_info[$key]['is_available'] = 0;
                                // $coupon_list_info[$key]['is_available_msg'] = '商品不属于优惠券使用的商品分类中';
                            }else{
                                // 在分类中
                                $coupon_list_info[$key]['is_available'] = 1;
                            }
                        }else if (4 == $value['type']) {
                            // 品牌优惠券
                            // 获取优惠券的品牌id集
                            $manufacture_id_arr = getData("select manufacturer_id from hb_coupon_manufacturer where coupon_id=".$value['coupon_id']);
                            $manufacture_ids = '';
                            foreach($manufacture_id_arr as $mk_value){
                                $manufacture_ids .= $mk_value['manufacturer_id'].',';
                            }
                            $manufacture_ids = substr($manufacture_ids,0,strlen($manufacture_ids)-1);
                            // 判断商品是否在品牌id中
                            $product = getData("select product_id from hb_product where brand_id in(".$manufacture_ids.")");
                            foreach($product_id_arr as $k=>$v){
                                $product_id_arr[$k] = $v['product_id'];
                            }
                            if (!$product || !in_array($pro['product_id'],$product)) {
                                $coupon_list_info[$key]['is_available'] = 0;
                                // $coupon_list_info[$key]['is_available_msg'] = '商品不属于优惠券的品牌范围';
                            }else{
                                $coupon_list_info[$key]['is_available'] = 1;
                            }
                        }
                    }
                }
            }
        }

        // 按照是否可用排序优惠券结果集
        foreach ($coupon_list_info as $key => $row) {
            $is_available[$key]  = $row['is_available'];
        }
        array_multisort($is_available, SORT_DESC, $coupon_list_info);

        foreach($coupon_list_info as $key=>$value){
            // 处理金额显示方式
            $coupon_list_info[$key]['discount'] = (int)$value['discount'];
            $coupon_list_info[$key]['min_limit_amount'] = (int)$value['min_limit_amount'];
        }

        $this->res['data'] = $coupon_list_info;
        return $this->res;
    }

    /**
     *  内部私有辅助方法，根据优惠券的类别和商品的级别（一级，二级，三级）过滤优惠券
     *  规则说明：
     *  
     *  @param        array     prolist           商品列表
     *  @param        array     value             单个优惠券信息
     *  @author       godloveevin@yeah.net
     *  @d/t          2017-04-14
     */
    private function isCheckedByPid($prolist,$value){
        $return = 1;
        foreach($prolist as $pk=>$pro){
            if ($value['min_limit_amount'] > $pro['numbers']*$pro['price']) {
                $return--;
            }else{
                if (1 == $value['type']) {
                    // 全部商品优惠券
                    $return++;
                    break;
                }else if (2 == $value['type']) {
                    // 部分商品优惠券
                    if(! getRow("select product_id from hb_coupon_product where coupon_id=".$value['coupon_id']." and 
                                 product_id=".$pro['product_id'])){
                        $return--;
                    }else{
                        $return++;
                        break;
                    }
                }else if (3 == $value['type']) {
                    // 根据优惠券与商品分类id过滤
                    $inOrOut = $this->inOrOutCouponCategory($pro['product_id'],$value['coupon_id']);
                    if (! $inOrOut) {
                        // 不在分类中
                        $return--;
                    }else{
                        // 在分类中
                        $return++;
                    }
                }else if (4 == $value['type']) {
                    // 品牌优惠券
                    // 获取优惠券的品牌id集
                    $product = getData("select product_id from hb_product where brand_id in(
                                    select manufacturer_id from hb_coupon_manufacturer where coupon_id=".$value['coupon_id'].")");
                    foreach($product as $k=>$v){
                        $product[$k] = $v['product_id'];
                    }
                    if (!$product || !in_array($pro['product_id'],$product)) {
                        $return--;
                    }else{
                        $return++;
                        break;
                    }
                }
            }
        }
        return $return;
    }

	/**
	 *  @description  根据优惠券id获取优惠券可以使用的商品列表 coupon/getProductsByCouponId
	 *  APP端必传入参数
	 *  @param 		  
	 *  @param 		  
	 *  @param   	 
	 *  APP端可选传入参数
	 *  @param
	 *  @return 	 
	 *  @author 	 godloveevin@yeah.net
	 *  @d/t 		 2017-04-05
	 */
	public function getProductsByCouponId(){

	}
	
    // 
    /**
     *  @description  判断购物车的商品是否在商品分类优惠券所包含的商品分类中      
     *  @param        string $pid 商品id
     *  @param        string $coupon_id 优惠券id
     *  @return       bool   $return true/false
     *  @author      godloveevin@yeah.net
     *  @d/t         2017-04-14
     */
    private function inOrOutCouponCategory($pid,$coupon_id){
        $return = true;
        // 根据商品id获取购物车某个商品的三个级别的商品分类id
        // 商品的直接分类为三级分类时：c1_id:一级分类id；c2_id:二级分类id；c3_id:三级分类id
        $product_category_id_arr = getRow("select c1.category_id as c1_id,c2.category_id as c2_id,c3.category_id as c3_id
                                            from hb_category as c1 
                                            left join hb_category as c2 on c2.parent_id=c1.category_id
                                            left join hb_category as c3 on c3.parent_id=c2.category_id
                                            where c3.category_id in(
                                            select ptc.category_id from hb_product_to_category as ptc where ptc.product_id=".$pid." and type=1
                                            )");
        if (!$product_category_id_arr) {
            // 商品的直接分类为二级分类时：c1_id:一级分类id；c2_id:二级分类id；
            $product_category_id_arr = getRow("select c1.category_id as c1_id,c2.category_id as c2_id
                                                from hb_category as c1 
                                                left join hb_category as c2 on c2.parent_id=c1.category_id
                                                where c2.category_id in(
                                                select ptc.category_id from hb_product_to_category as ptc where ptc.product_id=".$pid." and type=1
                                                )");
        }
        if (!$product_category_id_arr) {
            // 商品的直接分类为一级分类时：c1_id:一级分类id；
            $product_category_id_arr = getRow("select ptc.category_id as c1_id from hb_product_to_category as ptc 
                                                where ptc.product_id=".$pid." and type=1");
        }

        // 根据商品分类优惠券id获取商品分类id集
        // 优惠券分类表中的分类范围id（包括全是三级分类的优惠券分类id）
        $coupon_category_id_only_arr = getData("SELECT category_id FROM hb_coupon_category WHERE coupon_id =".$coupon_id);
        $coupon_category_id_arr = array();

        // 判断优惠券分类表的分类id是哪一个级别的分类id
        $coupon_category_id_arr = $this->getBackCouponCateIds($coupon_category_id_only_arr);

        // 合并优惠券所有的分类id集
        $coupon_category_id_arr = array_merge($coupon_category_id_only_arr,$coupon_category_id_arr);

        // 转成一维的，方便匹配
        foreach($coupon_category_id_arr as $k=>$v){
            $coupon_category_id_arr[$k] = $v['category_id'];
        }
        
        // 判断商品分类是否在优惠券分类范围内
        foreach($product_category_id_arr as $k => $v){
            if (in_array($v,$coupon_category_id_arr)) {
                $return = true;
                break;// 配到一个就终止循坏
            }else{
                 $return = false;
            }
        }

        return $return;
    }

    // 返回值：分类优惠券相关的下级分类id集
    private function getBackCouponCateIds($category_id_arr){
        $return = array();
        foreach($category_id_arr as $k=>$v){
            if(getData("SELECT category_id FROM hb_category WHERE parent_id in
                        (SELECT category_id from hb_category WHERE parent_id = ".$v['category_id'].")")){
                // 一级
                $return = array_merge($return,getData("SELECT category_id FROM hb_category WHERE parent_id in
                        (SELECT category_id from hb_category WHERE parent_id = ".$v['category_id'].")"));
            }else if(getData("SELECT category_id from hb_category WHERE parent_id = ".$v['category_id'])){
                // 二级
                $return = array_merge($return,getData("SELECT category_id from hb_category WHERE parent_id = ".$v['category_id']));
            }else {
                // 三级
                $return = array_merge($return,array());
            }
        }
        return $return;
    }

    /**
     * 新版优惠券h5
     * zxx 2017-5-12
     */
    function getCouponInfo(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            $coupon_id=isset($_REQUEST['coupon_id'])?$_REQUEST['coupon_id']:0;

            //检查coupon_id是大礼包还是单张优惠券
            if(strpos($coupon_id,',')){
                $coupon_ids=explode(',',$coupon_id);
                $count=count($coupon_ids);
                //找出最后一个优惠券的信息
                $coupon_id=$coupon_ids[$count-1];
            }else{
                $count=0;
            }
               

            //是优惠券，在判断是0元购还是普通优惠券
            $couponinfo=getRow("select * from hb_coupon where coupon_id='".$coupon_id."'");
            if($count>1){
                 $couponinfo['name']="优惠券大礼包";
            }
            //判断是否存在优惠券信息
            if(empty($couponinfo)){
                //优惠券不存在
                $this->res['retcode']=1001;
                $this->res['msg']="请求参数错误";  
            }else{
                 //判断是不是0元购
                if($couponinfo['discount']==$couponinfo['min_limit_amount'] && $couponinfo['discount']>15){
                    $msg="完成注册后立即获得抵用券";
                }else{
                    $msg="完成注册后立即获得优惠券";
                }
                //检查是不是大礼包
                if($count>1){
                    //大礼包
                    $msg="完成注册后立即获得优惠券大礼包";
                }

                $end_time=date("Y-m-d ",$couponinfo['date_end']);
                $num=$couponinfo['release_total']-$couponinfo['get_total'];
                //拼接返回信息
                $data=array(
                    'msg'=>$msg,
                    'end_time'=>$end_time,
                    'title'=>$couponinfo['name'],
                    'desc'=>$couponinfo['discount_desn'],
                    'image'=>$couponinfo['image'],
                    'num'=>$num
                );
                $this->res['data']=$data;
                $this->res['retcode']=0;
                $this->res['msg']="请求成功";   
            }          
            
        }else{
            //请求方式错误
            $this->res['retcode']=1000;
            $this->res['msg']="请求方式错误";            
        }

        return $this->res;
    }

    
    /**
     * 验证手机号是否已经领过
     */
    function checkTelephone(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            // 验证参数
            $now=time();
            if(!isset($_POST['telephone']) || !isset($_POST['coupon_id'])){
                //请求参数错误
                $this->res['retcode']=1001;
                $this->res['msg']="请求参数错误";  
            }else{
                $telephone = htmlspecialchars($_POST['telephone']);
                $telephone = htmlspecialchars($_POST['telephone']);

                //查出用户的customer_id
                $customer_id=getRow("select customer_id from hb_customer where telephone='".$telephone."'");
                if(empty($customer_id)){
                    $customer_id=0;
                }else{
                    $customer_id=$customer_id['customer_id'];
                }

                //分解优惠券
                $arr=explode(',', $_POST['coupon_id']);
                $c=0;
                foreach ($arr as $key => $coupon_id) {
                    // 发送优惠券
                    $coupon_info = getRow("SELECT coupon_id,name,discount,date_start,date_end,release_total,get_total,status,code FROM hb_coupon WHERE coupon_id=".$coupon_id);
                    if ($coupon_info['date_end'] < $now) {
                        // 是否已过期
                        $msg="已经过期";
                    }else if ( 0 >= ($coupon_info['release_total']-$coupon_info['get_total']) ) {
                        // 已领完
                        $msg="已经领完";
                    }else if (($coupon_info['date_end'] > $now) && ($coupon_info['status'] == 3)) {
                        // 已作废
                        $msg="已经作废";
                    }else{  
                        // 防止多次领取
                        if(@getRow("SELECT * FROM hb_coupon_customer WHERE coupon_id=".$coupon_id." and customer_id=".$customer_id)){
                            $msg="您已经领取过了";
                        }else{
                            $c++;
                        }
                    }
                }
               if($c>0){
                    $this->res['msg']="可以领取";
                    $this->res['retcode']=0;
               }else{
                    $this->res['msg']=$msg;
                    $this->res['retcode']=1002;
               }
           }
        }else{
            //请求方式错误
            $this->res['retcode']=1000;
            $this->res['msg']="请求方式错误";            
        }

        return $this->res;
    }

    /**
     *优惠券的注意事项
     * zxx 2017-5-16
     */
    function getContent(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            if(!empty($_POST['coupon_id'])){
                  //分解优惠券
                $arr=explode(',', $_POST['coupon_id']);
                $coupon_id=$arr[count($arr)-1];
                $content=getRow("select content from hb_coupon where coupon_id=".$coupon_id);
                if(!empty($content)){
                    $this->res['data']=nl2br($content['content']);
                    $this->res['retcode']=0;
                    $this->res['msg']="success"; 
                }
            }else{
                //请求参数错误
                $this->res['retcode']=1000;
                $this->res['msg']="请求方式错误";   
            }
        }else{
            //请求方式错误
            $this->res['retcode']=1000;
            $this->res['msg']="请求方式错误";    
        }

        return $this->res;
    }

    /**
     *查看优惠券和检查优惠券是否被查看
     * zxx 2017-5-17
     */
    
    function lookCouponStatus(){
        if($_SERVER['REQUEST_METHOD']=="POST"){
            if(!empty($_POST['customerid'])){
                $type=isset($_POST['type'])?$_POST['type']:0;

                //检查优惠券的动作，1：检查优惠券是否被查看，0：查看优惠券 
                if($type==0){
                    $res=getRow("select * from hb_coupon_customer where customer_id=".$_POST['customerid']." and coupon_look=0");
                    if(empty($res)){
                        //优惠券都被查看了
                        $status=0;
                    }else{
                        //优惠券有未被查看的
                        $status=1;
                    }
                }else{
                    //查看优惠券
                    exeSql("update hb_coupon_customer set coupon_look=1 where customer_id=".$_POST['customerid']);
                    $status=2;    
                }
                $this->res["data"]=$status;
            }else{
                //请求参数错误
                $this->res['retcode']=1001;
                $this->res['msg']="请求方式错误";  
            }
        }else{
            //请求方式错误
            $this->res['retcode']=1000;
            $this->res['msg']="请求方式错误";  
        }

        return $this->res;
    }

    /**
     * app内优惠券（新版）
     */
    function getAppCouponMsg(){
         if($_SERVER['REQUEST_METHOD']=="POST"){
            // 验证参数
            $now=time();
            if(!isset($_POST['coupon_id'])){
                //请求参数错误
                $this->res['retcode']=1001;
                $this->res['msg']="请求参数错误";  
            }else{
                $customer_id=isset($_POST['customer_id'])?$_POST['customer_id']:0;
                //分解优惠券
                $arr=explode(',', $_POST['coupon_id']);
                $count=count($arr);
                $amount=0;
                $c=0;
                foreach ($arr as $key => $coupon_id) {
                    // 发送优惠券
                    $coupon_info = getRow("SELECT coupon_id,name,discount,date_start,date_end,release_total,get_total,status FROM hb_coupon WHERE coupon_id=".$coupon_id);
                    $amount+=@$coupon_info['discount'];
                    if (@$coupon_info['date_end'] < $now) {
                        // 是否已过期
                        $msg="已经过期";
                        $code=1004;
                    }else if ( 0 >= ($coupon_info['release_total']-$coupon_info['get_total']) ) {
                        // 已领完
                        $msg="已经领完";
                        $code=1003;
                    }else if (($coupon_info['date_end'] > $now) && ($coupon_info['status'] == 3)) {
                        // 已作废
                        $msg="已经作废";
                        $code=1002;
                    }else{  
                        // 防止多次领取
                        if(@getRow("SELECT * FROM hb_coupon_customer WHERE coupon_id=".$coupon_id." and customer_id=".$customer_id)){
                            $msg="您已经领取过了";
                            $code=1001;
                        }else{
                            $c++;
                        }
                    }
                }
               if($c>0){
                    $this->res['msg']="可以领取";
                    $this->res['retcode']=0;
               }else{
                    $this->res['msg']=$msg;
                    $this->res['retcode']=$code;
               }

            if($count>1){
                $coupon_info['name']="优惠券大礼包";
            }   
            @$coupon_info['discount']=$amount;
            @$coupon_info['date_start']=date("Y-m-d H:i:s",@$coupon_info['date_start']);
            @$coupon_info['date_end']=date("Y-m-d H:i:s",@$coupon_info['date_end']);

            //查出优惠券的适用范围
            $type=getRow("select type from hb_coupon where coupon_id=".@$coupon_info['coupon_id']);
            if(@$type['type']==1){
                $msg="全场通用";
            }elseif(@$type['type']==2){
                $msg="仅限指定商品适用";
            }elseif(@$type['type']==3){
                $msg="仅限指定类别适用";
            }elseif(@$type['type']==4){
                $msg="仅限指定品牌适用";
            }
            unset($coupon_info['release_total']);
            unset($coupon_info['get_total']);
            unset($coupon_info['status']);
            @$coupon_info['msg']=$msg;
            $this->res["data"]=$coupon_info;
           }
         }else{
            //请求方式错误
            $this->res['retcode']=1001;
            $this->res['msg']="请求方式错误";  
        }

        return $this->res;
    }

    /**
     * app内领优惠券
     */
    function getAppCoupon(){
        if($_SERVER['REQUEST_METHOD']=="POST"){
            // 验证参数
            $now=time();
            if(!isset($_POST['coupon_id']) || !isset($_POST['customer_id'])){
                //请求参数错误
                $this->res['retcode']=1001;
                $this->res['msg']="请求参数错误";  
            }else{
                $customer_id=$_POST['customer_id'];
                //分解优惠券
                $arr=explode(',', $_POST['coupon_id']);
                $count=count($arr);
                $amount=0;
                $c=0;
                foreach ($arr as $key => $coupon_id) {
                    // 发送优惠券
                    $coupon_info = getRow("SELECT coupon_id,name,discount,date_start,date_end,release_total,get_total,status FROM hb_coupon WHERE coupon_id=".$coupon_id);
                    $amount+=@$coupon_info['discount'];
                    if (@$coupon_info['date_end'] < $now) {
                        // 是否已过期
                        $msg="已经过期";
                        $code=1004;
                    }else if ( 0 >= ($coupon_info['release_total']-$coupon_info['get_total']) ) {
                        // 已领完
                        $msg="已经领完";
                        $code=1003;
                    }else if (($coupon_info['date_end'] > $now) && ($coupon_info['status'] == 3)) {
                        // 已作废
                        $msg="已经作废";
                        $code=1002;
                    }else{  
                        // 防止多次领取
                        if(@getRow("SELECT * FROM hb_coupon_customer WHERE coupon_id=".$coupon_id." and customer_id=".$customer_id)){
                            $msg="您已经领取过了";
                            $code=1001;
                        }else{
                            if(saveData("hb_coupon_customer",array('coupon_id'=>$coupon_id,
                                    'customer_id'=>$customer_id,
                                    'date_added'=>$now,
                                    'status'=>0,
                                    'date_start'=>$coupon_info['date_start'],
                                    'date_end'=>$coupon_info['date_end']))){
                            // 领取成功
                            exeSql("UPDATE hb_coupon SET get_total= get_total+1 WHERE coupon_id=".$coupon_id);
                            }

                            $c++;
                        }
                    }
                }
               if($c>0){
                    $this->res['msg']="领取成功";
                    $this->res['retcode']=0;
               }else{
                    $this->res['msg']=$msg;
                    $this->res['retcode']=$code;
               }
           }
         }else{
            //请求方式错误
            $this->res['retcode']=1001;
            $this->res['msg']="请求方式错误";  
        }

        return $this->res;
    }
}