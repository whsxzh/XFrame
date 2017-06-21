<?php
	//面向对象的control 类
	include_once "xcontrol/base.php";
	class home
	{
		var $res=array("retcode"=>0,'msg'=>'success');
		function __construct() 
		{
	       if(isset($_POST["customerid"])){
               $customer_id = $_POST["customerid"];
            }else{
               $customer_id=@$_SESSION["default"]["customer_id"];
            }
            if(isset($_POST["passkey"])){
               $req_key=@$_POST["passkey"];
            }else{
               $req_key=@$_SESSION["default"]["passkey"];
            } 
		   $this->passkey=$req_key;
		   $this->customer_id=$customer_id;
	   	}
	   	
	    /**
	     * cgl 2017-3-16 获取首页内容
	     */
	    function gethomecontent(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		 if(isset($_POST["customerid"])){
	               $customer_id = $_POST["customerid"];
	            }else{
	               $customer_id=@$_SESSION["default"]["customer_id"];
	            }
	            if(isset($_POST["passkey"])){
	               $req_key=@$_POST["passkey"];
	            }else{
	               $req_key=@$_SESSION["default"]["passkey"];
	            } 
	    		// var_dump($customer_id);exit();
		    	$data["bannerlist"]=$this->getBanner(12,1,$customer_id);
		    	$data["bannerlabel"]=$this->getBanner(13,1,$customer_id);
		    	$data["banneractivity"]=$this->getBanner(14,1,$customer_id);
		    	
		    	if(isset($_POST["sex"]) && isset($_POST["birthday"])){
		    		$limit=isset($_POST["limit"])?$_POST["limit"]:"1";
		    		$bannerhobby=$this->getCustomerhobby($_POST["sex"],$_POST["birthday"],1,$customer_id);

		    		$data["reCommandCategory"]['categoryList']=$this->getCustomerCategory($_POST["sex"],$_POST["birthday"],0,$customer_id);
		    		$data["reCommandCategory"]['banner']=$this->getBanner(18,1,$customer_id);
		    		$data["reCommandCategory"]['title']='推荐品类';

		    		//如果存在性别和生日信息返回智能推荐信息
		    		foreach($data["banneractivity"] as $k=>$v){
		    			if($v['title']=="智能推荐" && $v['ban_type']==6){
		    				$data["banneractivity"][$k]["product_list"]=$bannerhobby;
		    				$data["banneractivity"][$k]["ban_type"]=7;
		    			}
		    		}
		    	}else{
		    		//如果不存在性别和生日信息不返回智能推荐信息
		    		foreach($data["banneractivity"] as $k=>$v){
		    			if($v['title']=="智能推荐" && $v['ban_type']==6){
		    				unset($data["banneractivity"][$k]);
		    			}
		    		}
		    	}
		    	$data["banneractivity"]=array_merge($data["banneractivity"]);
		    	$this->res["data"]=$data;
	    	}else{
	    		$this->res['msg']="请求方式错误"; //请求方式错误
	    		$this->res['retcode']=1180; //请求方式错误
	    	}
			return $this->res;
	    	// return $data['banneractivity'];
	    }

	    /*
	    *  zxx  2017-3-17 数据挖掘 
	    */
	    function getCustomerhobby($sex,$birthday,$limit,$customer_id){
	    	//if($_SERVER['REQUEST_METHOD']=="POST"){
	    		//if(isset($_POST["sex"]) && isset($_POST["birthday"])){
	    			// $sex=$_POST["sex"];
	    			// $birthday=$_POST["birthday"];

	    			//$rule=$this->getRule($sortRule);
	    			if($sex==2){	
	    				$sex=1;	//男
	    			}else{
	    				$sex=0;//女
	    			}

	    			//限制条件
	    			//$limit=isset($_POST['limit'])?$_POST['limit']:"1";
	    			if($limit==0){
	    				//获取到前七个
	    				$limit="limit 7";
	    			}else{
	    				//获取所有
	    				$limit="";
	    			}
	    			
	    			$age=$this->getage($birthday);
	    			if($age>40){
	    				//选择的是70后或者60后 分两段
	    				$age_sex="'".($age-5)."_".$sex."','".$age."_".$sex."'";	
	    			}else{
	    				//拼接查询条件
	    				$age_sex="'".$age."_".$sex."'";
	    			}
	    			$sql="SELECT  product_id from hb_product_relate_age_sex where age_sex in (".$age_sex.") order by relate desc";
	    			//return $sql;
	    			$sql2="SELECT distinct p.image as productimg,p.date_added,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		p.quantity
		      		from hb_product as p,hb_product_relate_age_sex as pr,hb_product_description as pd  where pr.product_id=p.product_id and  age_sex in (".$age_sex.") and  p.status=1 and p.product_id=pd.product_id  and p.price>5 and p.product_id in (".$sql.") order by relate desc ".$limit;    		
	    			$products=getData($sql2);
	    			//获取商品库存状态，是否为新品,以及判断是否为会员
	    			$products=$this->getProductData($products,$customer_id);

					// $this->res["retcode"]=0;
	    // 			$this->res["msg"]="success";
	    			//$this->res["data"]=$products;
	    		// }else{
	    		// 	$this->res["retcode"]=1000;
	    		// 	$this->res["msg"]="请求参数错误";	
	    		// }

	    	// }else{
	    	// 	$this->res["retcode"]=1180;
	    	// 	$this->res["msg"]="请求方式错误";
	    	// }
	    	shuffle($products);
	    	$products=array_slice($products,0,7); 
	    	return $products;
	    }



	    function gethobbyGoods(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		if(isset($_POST["sex"]) && isset($_POST["birthday"])){
	    			$sex=$_POST["sex"];
	    			$birthday=$_POST["birthday"];
	    			if($sex==2){	
	    				$sex=1;	//男
	    			}else{
	    				$sex=0;//女
	    			}
	    			$offset=isset($_POST['offset'])?$_POST['offset']:1;
	    			$count=10;
	    			$start=($offset-1)*$count;
	    			$type=isset($_POST['type'])?$_POST['type']:0;
	    			$rule=$this->getRule($type);
	    			//限制条件
	    			//$limit=isset($_POST['limit'])?$_POST['limit']:"1";
	    			// if($limit==0){
	    			// 	//获取到前七个
	    			// 	$limit="limit 7";
	    			// }else{
	    			// 	//获取所有
	    			// 	$limit="";
	    			// }
	    			$customer_id=isset($_POST['customerid'])?$_POST['customerid']:"";
	    			$age=$this->getage($birthday);
	    			if($age>40){
	    				//选择的是70后或者60后 分两段
	    				$age_sex="'".($age-5)."_".$sex."','".$age."_".$sex."'";	
	    			}else{
	    				//拼接查询条件
	    				$age_sex="'".$age."_".$sex."'";
	    			}
	    			$sql="SELECT  product_id from hb_product_relate_age_sex where age_sex in (".$age_sex.") order by relate desc";
	    			//return $sql;
	    			$sql2="SELECT distinct p.image as productimg,p.date_added,relate,p.sales as salenumber,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		p.quantity
		      		from hb_product as p,hb_product_relate_age_sex as pr,hb_product_description as pd  where pr.product_id=p.product_id and  age_sex in (".$age_sex.") and  p.status=1 and p.product_id=pd.product_id and p.price>5 and p.product_id in (".$sql.") order by ".$rule." limit ".$start.",".$count." "; 
		      		//echo $sql2;exit();   		
	    			$products=getData($sql2);
	    			//获取商品库存状态，是否为新品,以及判断是否为会员

	    			$products=$this->getProductData($products,$customer_id);

					$this->res["retcode"]=0;
	    			$this->res["msg"]="success";
	    			$this->res["data"]=$products;
	    		}else{
	    			$this->res["retcode"]=1000;
	    			$this->res["msg"]="请求参数错误";	
	    		}

	    	}else{
	    		$this->res["retcode"]=1180;
	    		$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
	    }

	     /*
	    *  xzz 2017-4-11 获取用户推荐类别
	    */
	    function getCustomerCategory($sex,$birthday,$limit,$customer_id){
	    	//if($_SERVER['REQUEST_METHOD']=="POST"){
	    		//if(isset($_POST["sex"]) && isset($_POST["birthday"])){
	    			// $sex=$_POST["sex"];
	    			// $birthday=$_POST["birthday"];

	    			//$rule=$this->getRule($sortRule);
	    			if($sex==2){	
	    				$sex=1;	//男
	    			}else{
	    				$sex=0;//女
	    			}

	    			//限制条件
	    			//$limit=isset($_POST['limit'])?$_POST['limit']:"1";
	    			if($limit==0){
	    				//获取到前七个
	    				$limit="limit 7";
	    			}else{
	    				//获取所有
	    				$limit="";
	    			}
	    			
	    			$age=$this->getage($birthday);
	    			if($age>40){
	    				//选择的是70后或者60后 分两段
	    				$age_sex="'".($age-5)."_".$sex."','".$age."_".$sex."'";	
	    			}else{
	    				//拼接查询条件
	    				$age_sex="'".$age."_".$sex."'";
	    			}
	    			$sql="SELECT  cr.category_id,c.name,ct.image from hb_category_relate_age_sex as cr,hb_category_description as c,hb_category as ct where cr.age_sex in (".$age_sex.") and cr.category_id=c.category_id and cr.category_id=ct.category_id and ct.display_mode=0 order by cr.relate desc $limit";
	    			 		
	    			$categorys=getData($sql);
	    		
	    	return $categorys;
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
			}elseif($sortRule==0){
				$rule='p.quantity!=0 DESC,p.date_added DESC,p.date_added DESC,p.quantity DESC';
			}else{
				$rule='p.quantity!=0 DESC,p.date_added DESC,p.date_added DESC,p.quantity DESC';
			}
			return $rule;
	    }

	    /*
	    *把获取到的商品结果集进行价格，上新处理
	    */
	    function getProductData($products,$customer_id){
	    	//var_dump($customer_id);exit();
	    	$customer=getRow("select merchant_id from hb_customer where customer_id='".$customer_id."' ");
	    	if(!empty($products)){
				foreach ($products as $key => $value) {
					if(@$customer["merchant_id"]>0){
						$products[$key]['finalprice']=sprintf("%.2f",$value['proxyprice']);//最终价格
						$products[$key]['originalprice']=sprintf("%.2f",$value['marketprice']);//原价
						$products[$key]['proxyprice']=sprintf("%.2f",$value['proxyprice']);//最终价格
                        $products[$key]['price']=sprintf("%.2f",$value['price']);//原价
					}else{
						$products[$key]['finalprice']=sprintf("%.2f",$value['price']);//最终价格
						$products[$key]['originalprice']=sprintf("%.2f",$value['marketprice']);//原价
						$products[$key]['proxyprice']=sprintf("%.2f",$value['proxyprice']);//最终价格
                        $products[$key]['price']=sprintf("%.2f",$value['price']);//原价
					}
					$products[$key]['finalprice']=str_replace(',', "", $products[$key]['finalprice']);
					$products[$key]['originalprice']=str_replace(',', "", $products[$key]['originalprice']);
					if($value["quantity"]==0){
						//卖光了
						$products[$key]["issaled"]="1";
						$products[$key]["isenough"]="2";
					}elseif($value["quantity"]<10){
						//不够
						$products[$key]["issaled"]="0";
						$products[$key]["isenough"]="0";
					}else{
						//充足
						$products[$key]["issaled"]="0";
						$products[$key]["isenough"]="1";
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
					//unset($products[$key]['price']);
					unset($products[$key]['marketprice']);
					//unset($products[$key]['proxyprice']);
				}	
			}
			return $products;	
	    }

	    /*
	    *根据时间戳或者年龄计算年龄区间
	    */
	    function getage($birthday){
	    	list($y2,$m2,$d2)=explode("-",date("Y-m-d",time()));
	    	if($birthday>1000){
	    		//传过来的是时间戳
	    		list($y1,$m1,$d1)=explode("-",date("Y-m-d",$birthday));
	    		$age=$y2-$y1;
		    	if((int)($m2.$d2)<(int)($m1.$d1)){
		    		$age -=1;
		    	}
		    	$age=$y2-$y1;
	    	}else{
	    		//传过来的是年龄
	    		$age=$birthday;
	    	}
	    	//设置年龄区间
	    	$age=$age-$age%5;
	    	if($age<20){
	    		$age=20;
	    	}elseif($age>=55){
	    		$age=55;
	    	}
	    	return $age;
	    }

	    /*
	    *zxx 2017-3-21 智能相关搭配和分类搭配
	    */
		function getRecommendGoods(){
			if($_SERVER['REQUEST_METHOD']=="POST"){
				if(isset($_POST["product_id"])){
					//返回的商品类型
					$type=isset($_POST["type"])?$_POST["type"]:2;
					$page=isset($_POST["page"])?$_POST["page"]:1;
					$count=isset($_POST["count"])?$_POST["count"]:10;
	    			$start=($page-1)*$count;
	    			$customer_id=isset($_POST['customerid'])?$_POST['customerid']:"";
					$sort_type=isset($_POST["sort_type"])?$_POST["sort_type"]:0;
					if($type==0){
						$data["categoryGoods"]=$this->getCategoryGoods($_POST["product_id"],$sort_type,$count,$start,$customer_id);
						$data["categorycount"]=count($data["categoryGoods"]);
					}elseif($type==1){
						$data["relevantGoods"]=$this->getRelevantGoods($_POST["product_id"],$sort_type,$count,$start,$customer_id);
						$data["relevantcount"]=count($data["relevantGoods"]);
					}else{
						$data["relevantGoods"]=$this->getRelevantGoods($_POST["product_id"],$sort_type,$count,$start,$customer_id);
						$data["categoryGoods"]=$this->getCategoryGoods($_POST["product_id"],$sort_type,$count,$start,$customer_id);
						$data["categorycount"]=count($data["categoryGoods"]);
						$data["relevantcount"]=count($data["relevantGoods"]);
					}
					
					$this->res["data"]=$data;
				}else{
	    			$this->res["retcode"]=1000;
	    			$this->res["msg"]="请求参数错误";
	    		}
			}else{
	    		$this->res["retcode"]=1180;
	    		$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
		}


	    /*
	    *zxx 2017-3-17 相关搭配推荐
	    */
	    function getRelevantGoods($product_id,$sort_type,$count,$start,$customer_id){
	    	// if($_SERVER['REQUEST_METHOD']=="POST"){
	    	// 	if(isset($_POST["product_id"])){
	    			//排序规则
	    			//$sortRule=isset($_POST['type'])?$_POST['type']:0;
	    			$rule=$this->getRule($sort_type);
	    			//限制条件
	    			//$limit=isset($_POST['limit'])?$_POST['limit']:"1";
	    			// if($limit==0){
	    			// 	//获取到前七个
	    			// 	$limit="limit 7";
	    			// }else{
	    			// 	//获取所有
	    			// 	$limit="";
	    			// }
					//通过获取到的product_id获取相关搭配的商品
					$sql="SELECT relate_product_id from hb_product_relate where product_id='".$product_id."'";
					$sql2="SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber
,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		p.quantity
		      		from hb_product as p,hb_product_description as pd,hb_product_relate as pr  where  pr.relate_product_id=p.product_id and pr.product_id=".$product_id."  and  p.status=1 and p.price>5 and p.product_id=pd.product_id and p.product_id in (".$sql.") order by relate desc,".$rule." limit ".$start.",".$count;
					$products=getData($sql2);
					//echo $sql2;exit();
					//获取商品库存状态，是否为新品,以及判断是否为会员
					$products=$this->getProductData($products,$customer_id);

					//$this->res["data"]=$products;
	    	// 	}else{
	    	// 		$this->res["retcode"]=1000;
	    	// 		$this->res["msg"]="请求参数错误";
	    	// 	}
	    	// }else{
	    	// 	$this->res["retcode"]=1180;
	    	// 	$this->res["msg"]="请求方式错误";
	    	// }
	    	return $products;
	    }

	    /*
	    *同类热销商品
	    */
	    function getCategoryGoods($product_id,$sort_type,$count,$start,$customer_id){
	    	// if($_SERVER['REQUEST_METHOD']=="POST"){
	    	// 	if(isset($_POST["product_id"])){
	    			//通过获取到的商品id获取商品分类
	    			//$product_id=$_POST["product_id"];
	    			//$limit=isset($_POST['limit'])?$_POST['limit']:"1";
	    			// if($limit==0){
	    			// 	//获取到前七个
	    			// 	$limit="limit 7";
	    			// }else{
	    			// 	//获取所有
	    			// 	$limit="";
	    			// }
	    			$sql="select category_id from hb_product_to_category where product_id='".$product_id."'";
	    			//排序规则
	    			//$sortRule=isset($_POST['type'])?$_POST['type']:0;
	    			$rule=$this->getRule($sort_type);
	    			$sql1="select product_id from hb_product_to_category where category_id in (".$sql.")";
	    			$sql2="SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber
,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		p.quantity
		      		from hb_product as p,hb_product_description as pd where  p.status=1 and p.price>5 and  p.product_id=pd.product_id and p.product_id in (".$sql1.") order by ".$rule." limit ".$start.",".$count;
		      		$products=getData($sql2);
		      		//var_dump($products);exit();
		      		//获取商品库存状态，是否为新品,以及判断是否为会员
					$products=$this->getProductData($products,$customer_id);

					//$this->res["data"]=$products;

	    	// 	}else{
	    	// 		$this->res["retcode"]=1000;
	    	// 		$this->res["msg"]="请求参数错误";
	    	// 	}
	    	// }else{
	    	// 	$this->res["retcode"]=1180;
	    	// 	$this->res["msg"]="请求方式错误";
	    	// }
	    	return $products;
	    }
	    /**
	     * zxx 2017-3-15  广告管理
	     */

	    function getBanner($banner_id,$merchant_id=1,$customer_id=0){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		if(isset($_POST["banner_id"])){
	    			$banner_id=$_POST["banner_id"];
	    		}
	    		if(isset($_SESSION["default"]["customer_id"])){
					$merchant=getRow("SELECT merchant_id from hb_customer where customer_id = '".$this->customer_id."' ");
					if(!empty($merchant["merchant_id"]) && @$merchant["merchant_id"]!=0){
						$merchant_id=$merchant["merchant_id"];	
					}else{
						$merchant_id=1;
					}
				}else{
					$merchant_id=1;
				}
	    	}
	    	// $banner_id=15;　　banner_id,
	    	$data=getData("select type,
	    		image,link,subtype,item_id as itemid,title,height from hb_banner_image where banner_id='".$banner_id."' and  status=1 and merchant_id='".$merchant_id."' order by sort_order desc  ");
	    	if(!empty($data)){

	    		foreach($data as $k=>$v){

	    			if($v["type"]==0){
	    				if($v["subtype"]==0){
	    					$data[$k]["ban_type"]=0; //分类
	    				}elseif ($v["subtype"]==1) {
	    					$data[$k]["ban_type"]=3; //产品
	    				}elseif ($v["subtype"]==3) {
	    					$data[$k]["ban_type"]=4; //团购
	    				}elseif ($v["subtype"]==4) {
	    					$data[$k]["ban_type"]=5; //秒杀
	    				}elseif ($v["subtype"]==5) {
	    					$data[$k]["ban_type"]=6; //活动
	    					//如果是活动则获取到该活动对应的前七个商品　　pa.category_id 
	    				    $sql="SELECT p.image as productimg,
						          		p.price as price,
						          		p.marketprice as marketprice,
						          		p.proxyprice as proxyprice,
						          		pd.name as productname,
						          		p.product_id as productid,
						          		date_added,
						          		p.quantity
						          		from hb_product as p,hb_product_description as pd,hb_product_to_activity as pa  where p.status=1 and p.product_id=pd.product_id and p.product_id=pa.product_id and pa.category_id='".$v['itemid']."' and pa.status=1 order by p.sort_order desc,p.date_added desc limit 7";

			          		$res=getData($sql);
			          		$res=$this->getProductData($res,$customer_id);
							//查询活动的浏览量
							$points=getRow("select points as scannumber from hb_category as c where c.status = '1' AND c.type = '1' and c.category_id='".$v["itemid"]."' ");
							$data[$k]["scannumber"]=@$points["scannumber"];
			          		$data[$k]["product_list"]=$res;
	    				}
	    			}else if($v["type"]==1){
		    				//外部链接
		    				$data[$k]["ban_type"]=1;
		    				if($v["link"]=="http://haiqihuocang.cn/appinlinehtml/index.html"){
		    					$data[$k]["ban_type"]=2;//会员链接
		    				}
		    				$customer_id=isset($_POST['customerid'])?$_POST['customerid']:0;
		    				//如果存在customerid则找出该邀请码id
		    				if($customer_id>0){
		    					$invitecode=getRow("select invitecode_id from hb_invitecode where customer_id=".$customer_id." order by invitecode_id desc");					
		    					if(!empty($invitecode)){
		    						//如果这个用户有邀请码则拼接h5链接
		    						if(strpos($v["link"],'?')){
		    							$data[$k]['link']=$v["link"]."&inviteid=".$invitecode['invitecode_id'];
		    						}else{
		    							$data[$k]['link']=$v["link"]."?inviteid=".$invitecode['invitecode_id'];
		    						}
		    					}
		    				}
	    				}
	    			unset($data[$k]["subtype"]);
	    			unset($data[$k]["type"]);
	    		}
	    	}
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    			if(isset($_POST["banner_id"])){
		    			$this->res['retcode']=0; 
		    			$this->res['msg']="success";
		    			$this->res['data']=$data;
		    			return $this->res;
		    		}else{
		    			return $data;
		    		}
	    	}else{
	    		return $data;
	    	}
	    }

	    /**
	     * zxx 2017-5-8
	     * 搜索获得商品
	     */
	    function getSearchProduct(){
	    	if($_SERVER['REQUEST_METHOD'] == 'POST'){
	    		if(!empty($_POST['keyword'])){
	    			$keyword=$_POST['keyword'];
	    			$customer_id=isset($_POST['customerid'])?$_POST['customerid']:0;
	    			$time=date("Y-m-d",time());
	    			$keyword=str_replace("'", '', $keyword);
	    			$key_arr=explode(' ',$keyword);
	    			//var_dump($key_arr);
	    			//exit;
	    			//写入关键词表
	    			if($customer_id>0){
	    				exeSql("insert into hb_product_search (customer_id,keyword,dt) values ('".$customer_id."','".$keyword."','".$time."') ");
	    			}

	    			//判断关键词是不是中文
	    			//$allcn = preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$keyword);
	    			$allcn = preg_match('/[\x{4e00}-\x{9fa5}]/u', $keyword);
	    			//当搜索的关键词是汉字时插入数据库
	    			if($allcn){
	    				//如果表中关键词不存在，则插入数据，如果存在则 times+1
	    				if(getRow("select * from hb_product_keywordpingyin where keyword='".$keyword."'")){
	    					exeSql("update hb_product_keywordpingyin set times=times+1 where keyword='".$keyword."'");
	    				}else{
	    					$keywordpingyin=$this->Pinyin($keyword,'UTF8');
	    					exeSql("insert into hb_product_keywordpingyin (keyword,keywordpingyin,times) values ('".$keyword."','".$keywordpingyin."','1') ");
	    				}
	    			}else{
	    				//如果是拼音则去找数据库中该中文是否存在，存在则找到并替换关键词
	    				if($k=getRow("select keyword from hb_product_keywordpingyin where keywordpingyin='".$keyword."'")){
	    					$keyword=$k['keyword'];
	    				}else{
	    					//
	    					$keywordpingyin=$this->Pinyin($keyword,'UTF8');
	    					exeSql("insert into hb_product_keywordpingyin (keyword,keywordpingyin,times) values ('".$keyword."','".$keywordpingyin."','1') ");
	    				}
	    			}

	    			//进行分页和排序规则的指定
	    			$count=10;
	    			$page=isset($_POST['page'])?$_POST['page']:1;
	    			$sortRule=isset($_POST['sort_type'])?$_POST['sort_type']:0;
	    			$rule=$this->getRule($sortRule);
	    			$start=($page-1)*$count;
	    			

	    			//一级分类
	    		    $category_info=getData("select category_id from hb_category_description where name like '%".$keyword."%'");
	    		    $str="";

	    		    //如果不空 找出二级分类
	    		    if(!empty($category_info)){
		    		    foreach($category_info as $key => $value) {
		    		    	$str.=$value['category_id'].",";
		    		    }
		    		    $str=trim($str,',');
		    		    $category_ids=getData("select category_id from hb_category where parent_id in (".$str.")");
	    		    }
	    		    
	    		    
	    		    //如果不空，找出三级分类
	    		    if(!empty($category_ids)){
	    		    	$str.=",";
		    		    foreach($category_ids as $key => $value) {
		    		    	$str.=$value['category_id'].",";
		    		    }
		    		    $str=trim($str,',');
		    		    $category_ids=getData("select category_id from hb_category where parent_id in (".$str.")");
	    		    }

	    		    if(!empty($category_ids)){
	    		    	$str.=",";
		    		    foreach($category_ids as $key => $value) {
		    		    	$str.=$value['category_id'].",";
		    		    }
		    		    $str=trim($str,',');
	    		    }

	    		    //搜索分类的条件
	    		    if(empty($str)){
	    		    	$cat="";
	    		    }else{
	    		    	$cat="or ptc.category_id in (".$str.")";
	    		    }
	    		    $str1="";
	    		    if(count($key_arr>1)){
	    				//说明有多个并列关系
	    				$str1.="(";
	    				foreach($key_arr as $k=>$v){
	    					$str1.="pd.name like '%".$v."%' and ";
	    				}
	    				$str1=trim($str1,'and ');
	    				$str1.=") ";
	    				$str1.=" or m.name like '%".$keyword."%' or ";

	    				$str1.="(";
	    				foreach($key_arr as $k=>$v){
	    					$str1.="pd.meta_keyword like '%".$v."%' and ";
	    				}
	    				$str1=trim($str1,'and ');
	    				$str1.=") ";
	    				$str1.=$cat;
	    			}else{
	    				$str1="pd.name like '%".$keyword."%' or m.name like '%".$keyword."%' or pd.meta_keyword like '%".$keyword."%' ".$cat;
	    			}
	    			//var_dump($str1);exit;
	    			//找出标题，关键词，品牌，分类的商品
					$product=getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		p.quantity
		      		from hb_product as p,hb_product_description as pd,hb_manufacturer as m,hb_product_to_category as ptc,hb_category as c,hb_category_description as cd  where cd.category_id=c.category_id and p.product_id=ptc.product_id and c.category_id=ptc.category_id and m.manufacturer_id=p.manufacturer_id  and  p.status=1 and p.product_id=pd.product_id and (".$str1." )order by ".$rule." limit ".$start.",".$count,600);
					//var_dump($product);exit;
					//把获取到的商品进行格式转换
					$product=$this->getProductData($product,$customer_id);
					$this->res['data']=$product;
	    			$this->res['retcode']=0;
	    			$this->res['msg']="请求成功";
	    		}else{
	    			$this->res['retcode']=1001;
	    			$this->res['msg']="请求参数错误";
	    		}
	    	}else{
	    		$this->res['retcode']=1000;
	    		$this->res['msg']="请求方式错误";
	    	}
	    	unset($this->passkey);
	    	unset($this->customer_id);
	    	return $this->res;
	    }

	    /**
	     *搜素联想的词汇
	     * zxx 2017-5-19
	     */
	    function getImagineGoods(){
	    	if($_SERVER['REQUEST_METHOD'] == 'POST'){
	    		$keyword=isset($_POST['keyword'])?$_POST['keyword']:"";
	    		$keyword=str_replace("'", '', $keyword);
	    		if(!empty($keyword)){
	    			$data=getData("select keyword,times from hb_product_keywordpingyin where (keyword like '%".$keyword."%' or keywordpingyin like '%".$keyword."%') order by times desc limit 10 ");
	    		}else{
	    			$data=array();
	    		}
	    		$this->res['data']=$data;
	    		//var_dump($data);exit;
	    	}else{
	    		$this->res['retcode']=1000;
	    		$this->res['msg']="请求方式错误";
	    	}
	    	return $this->res;
	    }

	    /**
	     *显示热搜排行榜的前10位
	     * zxx 2017-5-19
	     */
	    function getHotSearch(){
	    	if($_SERVER['REQUEST_METHOD'] == 'POST'){
	    		$keyword=getData("select keyword from hb_product_keywordpingyin order by times desc  limit 10 ");
	    		$this->res['data']=$keyword;
	    	}else{
	    		$this->res['retcode']=1000;
	    		$this->res['msg']="请求方式错误";
	    	}
	    	return $this->res;
	    }
	    /**
	     *把数据库中的汉字商品转化成拼音
	     *zxx 2017-5-17
	     */
	    function changeWord(){
	    	$product=getData("select id,keyword from hb_product_keywordpingyin order by id limit 3");
	    	$arr=array();
	    	foreach ($product as $key => $value) {
	    		$product[$key]['keyword']=$this->Pinyin($value['keyword'],'UTF8');
	    		$arr[$value['id']]=$product[$key]['keyword'];
	    	}
	    	$ids=implode(',',array_keys($arr));
	    	 //修改数据库中的内容
	    	$sql="update hb_product_keywordpingyin set keywordpingyin= CASE id ";
	    	foreach ($arr as $key => $value) {
	    		$sql.=" WHEN ".$key." THEN '".$value."'";
	    	}
	    	$sql.=" END where id in (".$ids.")";
	    	exeSql($sql);
	    }

	    /**
	     * [Pinyin description]
	     * zxx 2017-5-9
	     * 汉字转拼音
	     */
	    function Pinyin($_String, $_Code='gb2312'){
	        $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha".
	                    "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|".
	                    "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er".
	                    "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui".
	                    "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang".
	                    "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang".
	                    "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue".
	                    "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne".
	                    "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen".
	                    "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang".
	                    "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|".
	                    "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|".
	                    "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu".
	                    "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you".
	                    "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|".
	                    "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
	                     
	        $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990".
	                    "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725".
	                    "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263".
	                    "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003".
	                    "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697".
	                    "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211".
	                    "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922".
	                    "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468".
	                    "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664".
	                    "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407".
	                    "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959".
	                    "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652".
	                    "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369".
	                    "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128".
	                    "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914".
	                    "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645".
	                    "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149".
	                    "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087".
	                    "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658".
	                    "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340".
	                    "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888".
	                    "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585".
	                    "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847".
	                    "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055".
	                    "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780".
	                    "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274".
	                    "|-10270|-10262|-10260|-10256|-10254";
	                     
	        $_TDataKey = explode('|', $_DataKey);
	        $_TDataValue = explode('|', $_DataValue);
	        $_Data = (PHP_VERSION>='5.0') ? array_combine($_TDataKey, $_TDataValue) : $this->Arr_Combine($_TDataKey, $_TDataValue);
	        arsort($_Data);
	        reset($_Data);
	        if($_Code != 'gb2312') $_String = $this->U2_Utf8_Gb($_String);
	        $_Res = '';
	        for($i=0; $i<strlen($_String); $i++){
	            $_P = ord(substr($_String, $i, 1));
	            if($_P>160) { $_Q = ord(substr($_String, ++$i, 1)); $_P = $_P*256 + $_Q - 65536; }
	            $_Res .= $this->Pinyins($_P, $_Data);
	        }
	        return $_Res;
    	}
        /**
         * [Pinyins description]
         * zxx 2017-5-9
         * 汉字转拼音
         */
	    function Pinyins($_Num, $_Data){
	        if ($_Num>0 && $_Num<160 ) return chr($_Num);
	            elseif($_Num<-20319 || $_Num>-10247) return '';
	        else {
	            foreach($_Data as $k=>$v){ if($v<=$_Num) break; }
	            return $k;
	        }
	    }
	    
	    /**
	     * 
	     * zxx 2017-5-9
	     * 汉字转拼音
	     */
	    function U2_Utf8_Gb($_C){
	        $_String = '';
	        if($_C < 0x80){ 
	            $_String .= $_C;
	        }elseif($_C < 0x800){
	            $_String .= chr(0xC0 | $_C>>6);
	            $_String .= chr(0x80 | $_C & 0x3F);
	        }elseif($_C < 0x10000){
	            $_String .= chr(0xE0 | $_C>>12);
	            $_String .= chr(0x80 | $_C>>6 & 0x3F);
	            $_String .= chr(0x80 | $_C & 0x3F);
	        }elseif($_C < 0x200000) {
	            $_String .= chr(0xF0 | $_C>>18);
	            $_String .= chr(0x80 | $_C>>12 & 0x3F);
	            $_String .= chr(0x80 | $_C>>6 & 0x3F);
	            $_String .= chr(0x80 | $_C & 0x3F);
	        }
	            return iconv('UTF-8', 'GB2312', $_String);
	        }

	     /**
	     * 
	     * zxx 2017-5-9
	     * 汉字转拼音
	     */   
	    function Arr_Combine($_Arr1, $_Arr2){
	        for($i=0; $i<count($_Arr1); $i++) $_Res[$_Arr1[$i]] = $_Arr2[$i];
	        return $_Res;
	    }


	    /**
	     *
	     * zxx 2017-5-11
	     * 用户脚印
	     */
	    function getFootGoods(){
	    	if($_SERVER['REQUEST_METHOD'] == 'POST'){
	    		if(isset($_POST['customerid'])){

	    			//进行分页和排序规则的指定
	    			$count=10;
	    			$page=isset($_POST['page'])?$_POST['page']:2;
	    			$sortRule=isset($_POST['sort_type'])?$_POST['sort_type']:0;
	    			$start=($page-1)*$count;
	    			
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
						$rule='pl.id DESC';
					}

	    			//查询商品
	    			$product=getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		p.quantity
		      		from hb_product as p,hb_product_description as pd,hb_product_look as pl  where  pl.product_id=p.product_id and p.status=1 and p.product_id=pd.product_id and p.product_id in (select  product_id from hb_product_look where customer_id='".$_POST['customerid']."') order by ".$rule." limit ".$start.",".$count,600);
		      		$product=$this->getProductData($product,$_POST['customerid']);
	    			$this->res['data']=$product;
	    			$this->res['retcode']=0;
	    			$this->res['msg']="success";
	    		}else{
	    			$this->res['retcode']=1001;
	    			$this->res['msg']="请求参数错误";
	    		}
	    	}else{
	    		$this->res['retcode']=1000;
	    		$this->res['msg']="请求方式错误";
	    	}
	    	return $this->res;
	    }


	    /**
	     * 查询出热销商品
	     * zxx 2017-5-15
	     */
	    function getHotGoods(){
	    	if($_SERVER['REQUEST_METHOD'] == 'POST'){
	    			//进行分页和排序规则的指定
	    			$count=10;
	    			$page=isset($_POST['page'])?$_POST['page']:1;
	    			$sortRule=isset($_POST['sort_type'])?$_POST['sort_type']:1;
	    			$customer_id=isset($_POST['customerid'])?$_POST['customerid']:0;
	    			$start=($page-1)*$count;

	    			if($sortRule==1){
						$rule='p.quantity!=0 DESC,p.date_added DESC,p.quantity DESC';
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

	    			//查询商品
	    			$product=getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		p.quantity
		      		from hb_product as p,hb_product_description as pd,hb_product_look as pl  where  pl.product_id=p.product_id and p.status=1 and p.product_id=pd.product_id  order by ".$rule." limit ".$start.",".$count,600);
		      		$product=$this->getProductData($product,$customer_id);
	    			$this->res['data']=$product;
	    			$this->res['retcode']=0;
	    			$this->res['msg']="success";
	    	}else{
	    		$this->res['retcode']=1000;
	    		$this->res['msg']="请求方式错误";
	    	}
	    	return $this->res;	
	    }

		/**
		 * 查询出分类商品的父类
		 */
		function getParentGoods(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				if(!empty($_POST['category_id'])){

					//进行分页和排序规则的指定
	    			$count=10;
	    			$page=isset($_POST['page'])?$_POST['page']:1;
	    			$sortRule=isset($_POST['sort_type'])?$_POST['sort_type']:1;
	    			$start=($page-1)*$count;
	    			$rule=$this->getRule($sortRule);


	                //找出二级分类
	               $array=array();
	               $array1=getData("select parent_id from hb_category where category_id=".$_POST['category_id']." and status=1");
	              // var_dump($array1);exit;
	               if(!empty($array1)){
	                    foreach ($array1 as $key => $value1) {
	                        //如果该分类id在数组中已经存在则不插入
	                        if(!in_array($value1['parent_id'], $array)){
	                            $array[]=$value1['parent_id'];
	                        }
	                    }
	               }
	               //第二级
		            // if(!empty($array)){
		            //     foreach ($array as $key => $value) {
		            //        $array1=getData("select parent_id from hb_category where category_id=".$value." and status=1");
		            //        if(!empty($array1)){
		            //             foreach ($array1 as $key => $value1) {
		            //                 if(!in_array($value1['parent_id'], $array)){
		            //                     $array[]=$value1['parent_id'];
		            //                 }
		            //             }
		            //        }
		            //     }
		            // }
		            //找出这样分类下面的所有子分类
		            if(!empty($array)){
		            	 foreach ($array as $key => $value) {
		            	 	$array1=getData("select category_id from hb_category where parent_id=".$value." and status=1 ");
		            	 	if(!empty($array1)){
		                        foreach ($array1 as $key => $value1) {
		                            if(!in_array($value1['category_id'], $array)){
		                                $array[]=$value1['category_id'];
		                            }
		                        }
		                   	}
		            	 }
		            }
		            //再次找出底下的分类
		             if(!empty($array)){
		            	 foreach ($array as $key => $value) {
		            	 	$array1=getData("select category_id from hb_category where parent_id=".$value." and status=1 ");
		            	 	if(!empty($array1)){
		                        foreach ($array1 as $key => $value1) {
		                            if(!in_array($value1['category_id'], $array)){
		                                $array[]=$value1['category_id'];
		                            }
		                        }
		                   	}
		            	 }
		            }
		            //去除掉热销中的自己的分类 2017-6-8
		            $key=array_search($_POST['category_id'],$array);
		            array_splice($array,$key,1);


	                $array=implode(',', $array); 
	                if(!empty($array)){
	                	$product_id=getData("select product_id from hb_product_to_category where category_id in(".$array.")");
	                }

	                $product_ids=array();
	                //获取到所有的商品id
	                if(!empty($product_id)){
	                    foreach ($product_id as $key => $value) {
	                         $product_ids[]=$value['product_id'];
	                    }

	                    $product_ids=implode(',', $product_ids);
	                     $sql="select distinct 
	                            p.image as productimg,
	                            p.date_added,
	                            p.sales as salenumber,
	                            p.price as price,
	                            p.marketprice as marketprice,
	                            p.proxyprice as proxyprice,
	                            pd.name as productname,
	                            p.product_id as productid,
	                            p.quantity
	                            from hb_product as p,hb_product_description as pd where p.status=1 and p.product_id=pd.product_id and p.product_id in (".$product_ids.") order by ".$rule." limit ".$start.", ".$count."";   
	                    $products=getData($sql);


	                    if(empty($products)){
	                    	if($sortRule==1){
							$rule='p.quantity!=0 DESC,p.date_added DESC,p.quantity DESC';
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

			    			//查询商品
			    			$products=getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
				      		p.price as price,
				      		p.marketprice as marketprice,
				      		p.proxyprice as proxyprice,
				      		pd.name as productname,
				      		p.product_id as productid,
				      		p.quantity
				      		from hb_product as p,hb_product_description as pd,hb_product_look as pl  where  pl.product_id=p.product_id and p.status=1 and p.product_id=pd.product_id  order by ".$rule." limit ".$start.",".$count,600);
	                    }
	                    $customer_id=isset($_POST['customerid'])?$_POST['customerid']:0;
	                    $products=$this->getProductData($products,$customer_id);
	                }else{
	                    $products=array();
	                }
	                $this->res['data']=$products;
				}else{

					$this->res['retcode']=1001;
		    		$this->res['msg']="请求参数错误";
				}
			}else{

				$this->res['retcode']=1000;
	    		$this->res['msg']="请求方式错误";
			}

			return $this->res;
		}

		/**
		 * 收藏夹
		 * zxx 2017-6-14
		 */
		function collectGoods(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				if(isset($_POST['customerid'])){

					//进行分页和排序规则的指定
					$product=array();
	    			$count=10;
	    			$page=isset($_POST['page'])?$_POST['page']:1;
	    			$start=($page-1)*$count;  

					//查询商品
	    			$products=getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
		      		p.price as price,
		      		p.marketprice as marketprice,
		      		p.proxyprice as proxyprice,
		      		pd.name as productname,
		      		p.product_id as productid,
		      		wh.name as merchantname,
		      		p.quantity
		      		from hb_product as p,hb_product_description as pd ,hb_customer_wishlist as cw ,hb_warehouse as wh where cw.product_id=p.product_id and p.manufacturer_id=wh.warehouse_id and p.status=1 and p.product_id=pd.product_id and cw.customer_id='".$_POST['customerid']."' order by cw.date_added desc limit ".$start.",".$count,600);
		      		$customer_id=isset($_POST['customerid'])?$_POST['customerid']:0;
		      		if(empty($products)){
		      			//搜藏夹没有商品时推荐
		    			$product=getData("SELECT distinct p.image as productimg,p.date_added,p.sales as salenumber,
			      		p.price as price,
			      		p.marketprice as marketprice,
			      		p.proxyprice as proxyprice,
			      		pd.name as productname,
			      		p.product_id as productid,
			      		p.quantity
			      		from hb_product as p,hb_product_description as pd  where  p.status=1 and p.product_id=pd.product_id  order by p.date_added DESC limit ".$start.",".$count,600);
		      		}
		      		$product=$this->getProductData($product,$customer_id);
	                $products=$this->getProductData($products,$customer_id);
	                $this->res['validwishilist']=$products;
	                $this->res['newprdlist']=$product;
				}else{
					$this->res['msg']='参数错误';
					$this->res['retcode']=1000;
				}
			}else{
				$this->res['msg']='请求方式错误';
				$this->res['retcode']=1180;
			}
			return $this->res;
		}

		/**
		 * 优选商品列表
		 * zxx 2017-6-14
		 */
		function getnewprdlist(){

		}

		/**
		 * 邀请码id转化成 邀请码
		 * zxx 2017-6-14
		 */
		function changeInvite(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				if(isset($_POST['inviteid'])){
					$code=getRow("select invitecode from hb_invitecode where invitecode_id=".$_POST['inviteid']);
					$code=@$code['invitecode'];
					$this->res['invitecode']="haiqihuocang-".$code;
				}else{
					$this->res['msg']='参数错误';
					$this->res['retcode']=1000;
				}

			}else{
				$this->res['msg']='请求方式错误';
				$this->res['retcode']=1180;
			}
			return $this->res;
		}

	}
?>