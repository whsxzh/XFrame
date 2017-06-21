<?php
	//面向对象的control 类
	include "xcontrol/base.php";
	include "lib/pagination.php";
	include "xcontrol/sdk.php";
	class productapi extends base
	{
		function __construct() 
		{
	       parent::__construct();
	       //print "In SubClass constructor\n";
			$this->userid=$_SESSION['userid'];
			$this->username=$_SESSION['username'];
	   	}

   		/*
		 *智能商品比价 2017-4-6
		 */
		function compareProduct(){
			//把关键词放入到session中
			if(isset($_SESSION['keyword'])){
				$keyword=$_SESSION['keyword'];
			}else{
				$keyword=getData("select name from hb_product_keyword ");
				$_SESSION['keyword']=$keyword;
			}
			
			$this->getMenu();
			if(!empty($_POST['name'])){
				$sign=$this->haitaole($_POST['name']);
				$haidai=$this->haidai($_POST['name']);
				$haitun=$this->haitun($_POST['name']);
				$sanyecao=$this->sanyecao($_POST['name']);
				$fubang=$this->fubang($_POST['name']);
				//var_dump($haidai);exit;
				$data=array();
				$data=$this->foreachProduct($data,$sign);
				$data=$this->foreachProduct($data,$haidai);
				$data=$this->foreachProduct($data,$haitun);
				$data=$this->foreachProduct($data,$sanyecao);
				$data=$this->foreachProduct($data,$fubang);
				$this->res['orderinfo']=$data;
				$this->res['name']=$_POST['name'];
				//var_dump($data);exit;
			}
			
			$this->res['compareProduct']=linkurl("productapi/compareProduct");
			return $this->res;
		}

		/*
		 *循环获取到的商品信息
		 */
		function foreachProduct($data,$variable){
			if(!empty($variable)){
				foreach ($variable as $key => $value) {
					array_push($data,$value);
				}
			}
			//$data=array(1,2);
			return $data;
		}

		/*
		 *发送curl请求
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



		/*
		 *海淘乐api 2017-4-7 zxx
		 */
		function haitaole($name=''){
			$aliance_code="J8OU6GVHZDXW6QTV";
			$request_time=date("Y-m-d H:i:s");
			//var_dump($request_time);exit;
			$sql='select sku from '.DB_PREFIX.'product_sku where name like "haitaole-%'.$name.'%" limit 3';
			//$skus=getRow($sql);
			// if(!empty($skus)){
			// 	$sku=$skus['sku'];
			// }else{
			// 	return false;
			// }
			//return $sku;exit;
			$skus=getData($sql,600);//var_dump($skus);exit();
			$hai=array();
			if(!empty($skus)){
				foreach ($skus as $key => $value) {
					$sku=$value['sku'];
					$secret_key="ca3de78e07ebf40c4d25223d305bec42";
					$msg=$aliance_code."|"."$request_time"."|".$secret_key;
					$sign=md5($msg);
					$param=array('skus'=>$sku,'sign'=>$sign,'request_time'=>$request_time,'alliance_code'=>$aliance_code);
					$res=$this->curlPost("http://www.haitaole.com/apis/alliances/product/search", $param,'POST');
					$postObj = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
					@$haitaole['name']=$postObj->products_list->product->products_name;
					@$haitaole['price']=$postObj->products_list->product->agent_prices->agent_price->price;
					@$haitaole['quantity']=$postObj->products_list->product->quantity;
					@$haitaole['froms']='海淘乐';
					if(!empty($haitaole)){
						foreach ($haitaole as $key => $value) {
							@$haitaole[$key]=(array)$value;
							@$haitaole[$key]=$haitaole[$key][0];
						}
					}
					$hai[]=$haitaole;
				}
			}
			//$sku='1046004552';
			// $secret_key="ca3de78e07ebf40c4d25223d305bec42";
			// $msg=$aliance_code."|"."$request_time"."|".$secret_key;
			// $sign=md5($msg);
			// $param=array('skus'=>$sku,'sign'=>$sign,'request_time'=>$request_time,'alliance_code'=>$aliance_code);
			// $res=$this->curlPost("http://www.haitaole.com/apis/alliances/product/search", $param,'POST');
			// $postObj = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
			// $haitaole['name']=$postObj->products_list->product->products_name;
			// $haitaole['price']=$postObj->products_list->product->agent_prices->agent_price->price;
			// $haitaole['quantity']=$postObj->products_list->product->quantity;
			// $haitaole['froms']='海淘乐';
			// foreach ($haitaole as $key => $value) {
			// 	$haitaole[$key]=(array)$value;
			// 	$haitaole[$key]=$haitaole[$key][0];

			// }
			//var_dump($hai);
			return $hai;
			exit;
		}

		/*
		 *excel导入到数据库 2017-4-7 zxx
		 */
		function getExcel(){
			 require_once("lib/PHPExcel/PHPExcel.php");
        	 $objPHPExcel = new PHPExcel();
        	 $objSheet = $objPHPExcel->getActiveSheet();        //选取当前的sheet对象
			 //利用php读取excel数据
			 require 'lib/PHPExcel/PHPExcel/IOFactory.php';
			 $filePath ='lib/productExcel/fubang.xlsx';
			 $PHPExcel = new PHPExcel(); 
			/**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/ 
			$PHPReader = new PHPExcel_Reader_Excel2007(); 
			if(!$PHPReader->canRead($filePath)){ 
				$PHPReader = new PHPExcel_Reader_Excel5(); 
				if(!$PHPReader->canRead($filePath)){ 
					echo 'no Excel'; 
					return ; 
				} 
			} 

			$PHPExcel = $PHPReader->load($filePath); 
			/**读取excel文件中的第一个工作表*/ 
			$currentSheet = $PHPExcel->getSheet(0); 
			/**取得最大的列号*/ 
			$allColumn = $currentSheet->getHighestColumn(); 
			/**取得一共有多少行*/ 
			$allRow = $currentSheet->getHighestRow(); 
			/**从第二行开始输出，因为excel表中第一行为列名*/ 
			$data=array();
			for($currentRow = 645;$currentRow <= $allRow;$currentRow++){ 
				/**从第A列开始输出*/ 
				for($currentColumn= 'A';$currentColumn<= $allColumn; $currentColumn++){ 
					$val = $currentSheet->getCellByColumnAndRow(ord($currentColumn) - 65,$currentRow)->getValue();/**ord()将字符转为十进制数*/ 
					if($currentColumn == 'B' || $currentColumn == 'D') 
					{ 
						$data[]=$val;
					}
				} 
			}
			//获取到sku和商品名称对应的数组
			$arr=array();
			$k=0;
			if(!empty($data)){
				foreach ($data as $key => $value) {
					if(!empty($data[$key*2]) && !empty($data[$key*2])){
						$arr[$k]["num"]=@$data[$key*2];
						$arr[$k]["name"]="fubang-".@$data[$key*2+1];
						$k++;
					}
				}
			} 
			$num=0;
			foreach ($arr as $key => $value) {
				$sql1='select * from hb_product_sku where sku="'.$value['num'].'" and name="'.$value['name'].'"';
				$res=getRow($sql1);
				if(empty($res)){
					$sql='insert into hb_product_sku (sku,name,froms) values ("'.$value['num'].'","'.$value['name'].'","富邦")';
					exesql($sql);
					$num++;	
				}
			}
			echo "共插入".$num."条数据";
        	exit();
		}

		/*
		 *海带api
		 */
		function haidai($name='儿童'){
			$goods_sn="HD1100003472";

			//1.先获取用户的token和id
			$time=$this->getTimestamp(13);
			$url="http://test.seatent.com/api/mobile/member!hdLogin.do?";
			$pwd=md5("81902328");
			$param="appkey=93029921&password=".$pwd."&timestamp=".$time."&username=13777817587";
			$param1="username=13777817587&password=".$pwd."&appkey=93029921&timestamp=".$time;
			//&topSign=2e4901845c304bc29fef32376ab366e9
			$topSign="";
			$top="2e4901845c304bc29fef32376ab366e9".$param."2e4901845c304bc29fef32376ab366e9";
			$topSign=strtoupper(SHA1($top));
			$url.=$param1."&topSign=".$topSign;
			$res=$this->curlPost($url,'','GET');
			//var_dump($res);exit;
			$res=json_decode($res);
			@$token=$res->token;
			if(empty($token)){
				return false;
			}
			//$token="c0f7f921db3d7591d82e38a3d0cd8653befc48b0";
		//print_r($token);
		//exit();

			//2.根据商品名称获取商品的sn
			$time=$this->getTimestamp(13);
			$url="http://test.seatent.com/api/mobile/goods!getFenxiaoSigleGoodsList.do?";
			 //$name="澳洲";
			$name=urlencode($name);
			$param="appkey=93029921&member_id=12714&name=".$name."&pageSize=10000&timestamp=".$time."&token=".$token;
			$param1="appkey=93029921&name=".$name."&pageSize=10000&timestamp=".$time."&member_id=12714&token=".$token;
			$topSign="";
			$top="2e4901845c304bc29fef32376ab366e9".$param."2e4901845c304bc29fef32376ab366e9";
			$topSign=strtoupper(SHA1($top));
			$url.=$param1."&topSign=".$topSign;

			$res=$this->curlPost($url,'','GET');
			$res=json_decode($res);
			$sn=$res->data->result;
			//$res1['name']=(array)$res1['name'];
			$data=array();
			if(!empty($sn)){
				foreach($sn as $key=>$value){
					$data[$key]['name']=$value->name;
					$data[$key]['quantity']=$value->enableStore;
					$data[$key]['price']=$value->price;
					$data[$key]['froms']='海带';
				}
			}
			return($data);
			//print_r($data);
			exit();
		}
		/*
		 *海豚api
		 */
		function haitun($name=''){
			$sql='select sku from '.DB_PREFIX.'product_sku where name like "haitun-%'.$name.'%" limit 3';
			$skus=getData($sql,600);
			$hai=array();
			if(!empty($skus)){
				foreach ($skus as $key => $value) {
					$hai[]=$value['sku'];
				}
			}else{
				return false;
			}
		    $str=array("data"=>
		    	 array(
		    		"sku"=>$hai
		    		),
		    	 );
		    $data = openSdk::api('getProductDetail',$str);
		    $haitun=array();
		    if(!empty($data['data'])){
		    	foreach($data['data'] as $k=>$v){
					$haitun[$k]['name']=$v['productName'];
					$haitun[$k]['price']=sprintf("%.2f", $v['price']);
					$haitun[$k]['froms']='海豚';
					$haitun[$k]['quantity']='接口未提供';		
				}
		    }else{
		    	return false;
		    }
		    //var_dump($haitun);exit;
		    return $haitun;
		exit;
	}

		/*
		 *三叶草api
		 */
		function sanyecao($name=''){

			$sql='select sku,name from '.DB_PREFIX.'product_sku where name like "kebilin-%'.$name.'%" limit 3';
			$skus=getData($sql,600);

			$hai=array();
			$name=array();
			if(!empty($skus)){
				foreach ($skus as $key => $value) {
					$hai[]=$value['sku'];
					$skus[$key]['name']=str_replace('kebilin-', '', $value['name']);
					$name[]=$skus[$key]['name'];
				}
			}else{
				return false;
			}
			$hai=implode($hai, ',');
			$params = array();
			$head['userid'] ="1875";
			$head['timestamp'] = date("Y-m-d H:i:s");
			$password="580a4caf16f1c6aef374a2c2bba85b153e1ea5d2";
			$head['sign'] = MD5($head['userid'].$password.$head['timestamp']);
			$url = 'http://api.kebilin.com/';
			$params['head'] = $head;
			$params['head']['f'] ="get_goods_sku";//方法
			$params['body'] = array();
			$params['body']['page_no'] = 1;//页码
			$params['body']['serial'] = $hai;//sku
			$params['body']['page_size'] = 100;//每页数量 最大值500，默认值100
			$params['body']['starttime'] = "";//更新开始时间
			$params['body']['endtime'] = "";//更新结束时间
			//print_r($params);exit();
			$result = $this->curlPost($url,json_encode($params),'POST');
			//$result=$result[]['body'];
			$result=json_decode($result,true);
			//获取到所以的商品信息
			$result=$result['root']['body'];
			/*********将数据插入到数据库**************************/
			//$sanyecao=array();
			// foreach ($result as $key => $value) {
			// 	$sanyecao[$key]['name']="kebilin-".$value['goods_name'];
			// 	$sanyecao[$key]['num']=$value['sn'];
			// }
			// $num=0;
			// foreach ($sanyecao as $key => $value) {
			// 	$sql1='select * from hb_product_sku where sku="'.$value['num'].'" and name="'.$value['name'].'"';
			// 	$res=getRow($sql1);
			// 	if(empty($res)){
			// 		$sql='insert into hb_product_sku (sku,name,froms) values ("'.$value['num'].'","'.$value['name'].'","客比邻")';
			// 		exesql($sql);
			// 		$num++;	
			// 	}
			// }
			// echo "共插入".$num."条数据";
			/*************************************/
			$sanyecao=array();
			if(!empty($result)){
				foreach ($result as $k => $value) {
					$sanyecao[$k]['name']=$name[$k];
					$sanyecao[$k]['price']=$value['price'];
					$sanyecao[$k]['quantity']=$value['stock'];
					$sanyecao[$k]['froms']="客比邻";
				}
			}else{
				return false;
			}
			return $sanyecao;
			exit;
		}

		/*
		 *富邦api
		 */
		function fubang($name=""){
			$sql='select sku,name from '.DB_PREFIX.'product_sku where name like "fubang-%'.$name.'%" limit 3';
			$skus=getData($sql,600);
			$hai=array();
			$name=array();
			if(!empty($skus)){
				foreach ($skus as $key => $value) {
					$hai[]=$value['sku'];
				}
			}else{
				return false;
			}
			$client_key="zy28bbce65bad4bd00";
			$secret_key="16539f0956a942650074ab86b0403baa";
			$product=array();
			//var_dump($hai);exit;
			foreach ($hai as $k => $value) {
				$data=array(
					"product_no"=>$value,
				);
				$data=json_encode($data);
				$sign=md5($secret_key.$data);
				$paramArr=array("client_key"=>$client_key,"data"=>$data,"sign"=>$sign);
				$strParam = '';
				//将里面的元素urlencode
			    foreach ($paramArr as $key => $val) {
			       if ($key != '' && $val !='') {
			           $strParam .= $key.'='.urlencode($val).'&';
			       }
			    }
			    $strParam=trim($strParam,'&');
			    $url="http://erp.ikjtao.com/api/v4/product?";
			    $url.=$strParam;
			    //发送ajax请求
			    $res=$this->curlPost($url,'',"POST");
			    $res=json_decode($res);
			      if($res->ret_num == 0){
			      $product[$k]['name']=$res->ret_data->goods_name;
			      $product[$k]['quantity']=$res->ret_data->available_share_inventory;
			      $product[$k]['froms']="富邦";
			      $product[$k]['price']=@$res->ret_data->price;
			    }
			}
			return $product; exit;
		}
		/*获取时间戳 zxx 2017-4-14*/
		function getTimestamp($digits = false) {  
	        $digits = $digits > 10 ? $digits : 10;  
	        $digits = $digits - 10;  
	        if ((!$digits) || ($digits == 10))  
	        {  
	            return time();  
	        }  
	        else  
	        {  
	            return number_format(microtime(true),$digits,'','');  
	        }  
	    }

	    /*
	    *提取关键词 zxx 2017-4-17
	    */
	   function getKeyword(){
	   		// $a=200;
	   		// if($a%100 == 0){
	   		// 	echo $a;
	   		// }else{
	   		// 	echo $a."-";
	   		// }
	   		// exit;
		   	 set_time_limit(0);
		   	echo "<pre/>";
	   		//对商品名字进行处理
	   		$sql="select name from hb_product_sku limit 10500,500";
		   	$product_name=getData($sql);
		   	//print_r($product_name);exit;
		   	$p_name=array();
		   	$execount=0;
		   	foreach ($product_name as $key => $value) {
		   		$name=substr($value['name'],strpos($value['name'],'-')+1);
		   		$p_name[]=$name;
		   	}
		   	//计算出获取到的商品的数量
		   	$count1=count($p_name);
		   	$array=array();
		   	for($i1=0;$i1<$count1;$i1++){
		   		for($j1=$i1+1;$j1<$count1;$j1++){
		   			//获取到两个名字中的相同部分
		   			$a_array=preg_split('/(?<!^)(?!$)/u',$p_name[$i1]);
				   	@$b_array=preg_split('/(?<!^)(?!$)/u',$p_name[$j1]);
				   	$content="";
				   	foreach ($a_array as $k => $v) {
				   		foreach ($b_array as $k1 => $v1) {
				   			if($v == $v1){
				   				$content.=$v1;
				   			}
				   		}
				   	}
				   	//去除特殊符号，空格，数字
				   	$content=preg_replace('/\d/','', $content);
				   	$content=str_replace("【", "", $content);
				   	$content=str_replace("】", "", $content);
				   	$content=str_replace("'", "", $content);
				   	$content=preg_replace('/\ /','', $content);
				   	//循环获取到的字符串	   	
				   	$c_array=preg_split('/(?<!^)(?!$)/u',$content);
				   	$count=count($c_array);
					//记录有几个数据成为关键词
				   	$ccc=0;
				   	//如果商品名中存在该连续的字符串，则该词为关键词
				   	for($i=-1;$i<$count;$i++){
				   		$str="";
				   		unset($c_array[$i]);
				   		foreach($c_array as $k=>$v){
							$str.=$v;
							if(mb_strlen($str,'UTF8')>1){
								if(strpos($p_name[$i1],$str) && strpos($p_name[$j1],$str) && $str!='的'){
									if(!in_array($str,$array)){
										$array[]=$str;
									}
									
								}
							}
				   		}
				   	} echo "第2重循环的".$j1."个元素<br/>";
				   
		   		} echo "第1重循环的".$i1."个元素<br/>";
		   	}
		   	echo 1;
	   	foreach ($array as $key => $value) {
	   		$getkeyword=getRow("select name from hb_product_keyword where name='".$value."'");
	   		if(empty($getkeyword)){
	   			$execount++;
	   			exeSql("insert into hb_product_keyword (name) values ('".$value."') ");
	   		}
	   	}
	  	 echo "共插入了".$execount."条数据";exit;
	   }
	}