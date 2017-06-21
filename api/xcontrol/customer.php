<?php
// @session_start();
	// print_r($_SESSION);
	//面向对象的control 类
	include "xcontrol/base.php";
	include "lib/idcard.php";
	class customer
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
	     * cgl  2017-3-13  数据分析  首页分析  推荐的商品
	     */
	    function newupmyinfo(){
	      if($_SERVER['REQUEST_METHOD']=="POST"){
	        if(isset($_POST['sex']) && isset($_POST['birthday'])){
	        	//如果存在年龄和性别
	        	$_POST['customer_id']=$this->customer_id;
	        	//saveData("hb_customer",$_POST);
	        	$sql="update hb_customer set sex='".$_POST['sex']."',birthday='".$_POST['birthday']."' where customer_id='".$_POST['customer_id']."'";
	        	exeSql($sql);
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
	    /**
	     * cgl 2017-3-24  根据手机号码登录
	     */
	    function sendToPhone(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		include_once '../iwantcdm/lib/sms.php';
		    	$sms=new Sms();
		    	$telephone=isset($_POST["telephone"])?$_POST["telephone"]:null;
		    	//类型   1为注册  2为登录
		    	// $type=isset($_POST["type"])?$_POST["type"]:1;

		    	if(!$telephone){
		    		$this->res["retcode"]= 1000;
		    		$this->res["msg"]="参数错误";
		    	}
		    	else{
		            if(!preg_match("/^13[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|14[0-9]{9}$/",$telephone)){
		                //你的手机号不正确
		                $this->res["retcode"]=1208;
		                $this->res["msg"]="手机格式不正确";
		            }else{
		    			//注册
		    			//查询是否存在该手机
		    			$is_exist=getRow("select * from hb_customer where telephone = '".$telephone."' and status=1 ");
		    			if($is_exist){
		    				//已注册
		    				$type="用户登录";
		    			}else{
		    				//没注册
		    				$type="用户注册";
		    			}
		    			$rand=mt_rand(1111,9999);
				    	$return=$sms->sendSingleMt($telephone,"【嗨企货仓】"."您的验证码是：".$rand);
				    	$res=json_decode($return,"json");
				    	if(isset($res["retcode"]) && @$res["retcode"]==0){
				    		//发送成功  插入数据
				    		$this->delete_validate($telephone,$type,$rand);
				    	}else{
				    		//发送失败
				    		$this->res["retcode"]=1;
				    		$this->res["msg"]="发送失败";
				    	}
			    	}
		        }
	    	}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
	    }

	    /**
	     * cgl 2017-3-24 供上面使用  删除短信验证
	     */
	    function delete_validate($telephone,$where,$rand){
	    	//判断是否有该信息
			$is_send=getRow("SELECT * FROM hb_customer_validate WHERE mobile = '" .$telephone. "' and typ = '".$where."' ");
			if($is_send){
				exeSql("DELETE FROM `hb_customer_validate` WHERE mobile = '" . $telephone . "' and typ = '".$where."' ");
			}
			//插入信息
			$data=array(
				"mobile"=>$telephone,
				"validate"=>$rand,
				"typ"=>$where,
				"dat"=>date("Y-m-d H:i:s",time())
				);
			saveData("hb_customer_validate",$data);
	    }

	    /*
	    *2017-3-28 zxx 实名认证
	    */
	    function realNameAuthentication(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		$customerid=isset($this->customer_id)?$this->customer_id:"";
	    		$idcard=isset($_POST['idcard'])?$_POST['idcard']:"";
	    		$name=isset($_POST['name'])?$_POST['name']:"";
	    		if($customerid && $idcard && $name){
	    			
	    			$data=$this->getRealName($customerid,$idcard,$name);

                    // lcb 6-8
	    			if(isset($data['retcode']) && $data['retcode'] == 0){
                        $backUrl = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '';
                        if(!$backUrl){
                            $backUrl = isset($_SESSION['default']['shop_url']) ? $_SESSION['default']['shop_url'] : '';
                        }else if($backUrl && $intPos = stripos($backUrl, 'route=share/name')){
                            if(isset($_REQUEST['backUrl']) && $_REQUEST['backUrl']){
                                $backUrl = $_REQUEST['backUrl'];
                            }else{
                                $backUrl = substr($backUrl,0, $intPos).'route=share/confirm';
                            }
                        }
                        $data['url'] = str_ireplace('&amp;', '&', urldecode($backUrl));
                    }
	    			$this->res=$data;
	    		}else{
			       	$this->res["retcode"]=1000;
			       	$this->res["msg"]="请求参数错误";
	    		}
	    	}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	//return $this->res;
            exit(json_encode($this->res));
	    	
	    }

	/*
	*3.28 zxx 实名认证
	*/
	function getRealName($customerid,$idcard,$name){
		header('Content-type:text/html;charset=utf-8');
		//配置您申请的appkey

        $appkey = "39e7639dbabaf5a849579cd2b5029348";
        //************1.真实姓名和身份证号码判断是否一致************
        $url = "http://op.juhe.cn/idcard/query";
        $is_login = getRow("SELECT cl.* FROM `hb_customer_login` cl left join `hb_customer` c on c.customer_id = cl.email WHERE cl.email = '" . $customerid . "' and c.status = 1");
        if($is_login){
        	//获取用户信息 
        	$query =getrow("SELECT * FROM hb_customer WHERE customer_id = '" . (int)$customerid . "'");
        	$time = strtotime(@$customer['realname_error_time'])+600;//错误5次就10分钟后才能重新提交
        	 if(@$customer['realname_error_num'] < 5 || time()>$time){
        	 	// $params = array(
           //          "idcard" => $idcard,//身份证号码
           //          "realname" => $name,//真实姓名
           //          "key" => $appkey,//应用APPKEY(应用详细页查询)
           //      );
           //      $paramstring = http_build_query($params);
           //      $content = $this->juhecurl($url,$paramstring);
           //      $result = json_decode($content,true);
                // print_r($result);
                // die;
          //       if(@isCreditNo($idcard))
		        // {
		        //     $result['error_code']=0;
		        //     $result['result']['res']=1;
		                           
		        // }
		        // else
		        // {
		        //     $result['error_code']=-1;
		        //     $result['result']['res']=-1;

		        // }


		        $url = 'https://v.apistore.cn/api/a1';
				$param=array(
				'key' => '42350b7fda95e7e4bbabb03a1328d595',
				'cardNo' => $idcard,
				'realName' => $name,
				'information' => '',
				);

				//发送远程请求;
				$result =$this->APIStore($url, $param, true);
				if ($result['error_code'] == 0) {
				    //处理成功
				    $result['error_code']=0;
		            $result['result']['res']=1;

				} else {
				    //处理失败
				    $result['error_code']=-1;
		            $result['result']['res']=-1;
				}
				//var_dump($result);EXIT;
                if($result){
                    if($result['error_code']=='0'){
                        if($result['result']['res'] == '1'){
                            //验证正确，修改customer表中的真实姓名和身份证号以及错误次数置0
                            $data = array(
                                'customerid'=>$customerid,
                                'lastname'=>$name,
                                'card'=>$idcard
                            );
                            if(@$result['is_realname']!=1){
                             	exeSql("insert into hb_card (name,card) values ('".$name."','".$idcard."')");
                            }

                            $this->editRealname($data);//修改customer关于验证姓名错误的字段
                            $this->res['msg']="信息修改成功"; 
                            $this->res['retcode']=0;
                        }else{
                            //验证错误，修改customer表中错误次数和时间
                            if(time()>$time){
                                $realname_error_num = 1;
                            }else{
                                $realname_error_num = $customer['realname_error_num']+1;
                            }

                            $data = array(
                                'customerid'=>$customerid,
                                'realname_error_num'=>$realname_error_num
                            );
                           $this->res['msg']='身份信息不匹配'; 
                           $this->res['retcode']=1100;
                            //$data = array('retcode'=>0,'code'=>1,'msg'=>'身份证号码和真实姓名不一致');
                        }
                    }else{
                        //验证错误，修改customer表中错误次数和时间
                        if(time()>$time){
                            $realname_error_num = 1;
                        }else{
                            $realname_error_num = $customer['realname_error_num']+1;
                        }

                        $data = array(
                            'customerid'=>$customerid,
                            'realname_error_num'=>$realname_error_num
                        );
                        // $this->editRealname($data);//修改customer关于验证姓名错误的字段
                       $this->res['msg']='身份信息不匹配'; 
                    	$this->res['retcode']=1100;
                    }
                }else{
                	$this->res['msg']='接口未返回数据'; 
                    $this->res['retcode']=1102;
                   
                }
            }else{
               // $this->res['msg']='错误请求超过五次';
               $this->res['msg'] = '错误次数超过5次，请十分钟后再试';
               $this->res['retcode']=1103;
            }
        }else{
           $this->res['msg']='请先登录'; 
            $this->res['retcode']=1101;
        }

       return $this->res;
	}


	/**
	 * 实名认证 （新） 
	 * zxx 2017-6-14 
	 */

	function APIStore($url, $param = null, $ispost = false)
	{	
		//https协议是否需要验证证书
		define('SSL_VERIFYPEER', false);
	    
	    $sql="select * from hb_card where name='".$param['realName']."' and card='".$param['cardNo']."'";
	    $data=getRow($sql);
		if(empty($data)){
			//echo 1;
			//说明没有实名认证过
			$curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, $ispost ? $url : $url . '?' . http_build_query($param));
		    //如果是https协议
		    if (stripos($url, "https://") !== FALSE) {
		        /**
		         * 如果需要验证证书
		         */
		        if (SSL_VERIFYPEER) {
		            //验证交换证书
		            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		            //检查SSL证书公用名是否存在，并且是否与提供的主机名匹配
		            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		            //设置实现协议为TLS1.0版本
		            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		            //根证书文件路径,相对路径和绝对路径均可,
		            //推荐使用绝对路径;为了安全证书文件最好不要和应用代码放在一起;
		            //用户请保持更新 使用https接口需要设置该证书文件为可信任根证书，
		            //以最大限度满足安全性（使用信任任何证书的方式并不安全）。
		            curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
		        } /**
		         * 如果不需要验证证书
		         */
		        else {
		            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		            //CURL_SSLVERSION_TLSv1
		            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
		        }
		    }

		    //USERAGENT
		    curl_setopt($curl, CURLOPT_USERAGENT, 'APIStore');
		    //超时时间
		    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
		    curl_setopt($curl, CURLOPT_TIMEOUT, 120);
		    //通过POST方式提交
		    if ($ispost) {
		        curl_setopt($curl, CURLOPT_POST, true);
		        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param));
		    }
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    //返回内容
		    $callbcak = curl_exec($curl);
		    //关闭,释放资源
		    curl_close($curl);
		    //返回内容JSON_DECODE
		    return json_decode($callbcak, true);
		}else{
			//echo 2;
			//实名认证了
			$result['error_code']=0;
			$result['is_realname']=1;
			return $result;
		}

	}


	/*
	*实名认证 zxx 2017-3-28 
	*/
   function juhecurl($url,$params=false,$ispost=0){


   	$response['error_code']=0;
      $response['result']['res']=1;
      $response['result']['res1']=1;
      $response=json_encode($response);
return $response;
      

    $response['error_code']=0;
    $data=explode('&', $params);

    $data[1]=str_replace('realname=',"",$data[1]);
    $data[1]=urldecode($data[1]);
    $data[0]=str_replace('idcard=',"",$data[0]);
    $sql="select * from hb_card where name='".$data[1]."' and card='".$data[0]."'";
    $data1=getRow($sql);
    //var_dump($sql);exit;
    if(!empty($data1)){
      $response['error_code']=0;
      $response['result']['res']=1;
      $response['result']['res1']=1;
      $response=json_encode($response);
    }else{
      $httpInfo = array();
          $ch = curl_init();

          curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
          curl_setopt( $ch, CURLOPT_USERAGENT , 'JuheData' );
          curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
          curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
          curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
          if( $ispost )
          {
              curl_setopt( $ch , CURLOPT_POST , true );
              curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
              curl_setopt( $ch , CURLOPT_URL , $url );
          }
          else
          {
              if($params){
                  curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
              }else{
                  curl_setopt( $ch , CURLOPT_URL , $url);
              }
          }
          $response = curl_exec( $ch );
          if ($response === FALSE) {
              //echo "cURL Error: " . curl_error($ch);
              return false;
          }
          $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
          $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
          curl_close( $ch );
    }
    //var_dump($data[1]);exit;
        return $response;
    }
    /*
    *zxx 2017-3-28 修改真实信息
    */
	 function editRealname($data){
		$date=date('Y-m-d H:i:s');
		if(isset($data['lastname']) && isset($data['card']) && $data['lastname'] != '' && $data['card'] != ''){
			$sql = "UPDATE hb_customer SET lastname = '" . $data['lastname'] . "', card = '" . $data['card'] . "',realname_error_num = '0' WHERE `customer_id` = '" . $data['customerid'] . "'";
		}else{
			$sql = "UPDATE hb_customer SET realname_error_num = '" . $data['realname_error_num'] . "', realname_error_time = '" . $date . "' WHERE `customer_id` = '" . $data['customerid'] . "'";
		}

		exeSql($sql);
	}

	/*
	*zxx 2017-3-28 新增我的收货地址 
	*/
	function addmyaddr(){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			if(empty($_POST['firstname']) || empty($_POST['country_id']) || empty($_POST['zone_id']) || empty($_POST['city']) || empty($_POST['address_1'])  ){
				$this->res["retcode"]=1000;	
				$this->res["msg"]="请求参数错误";
			}else{
				
				$_POST['lastname']=isset($_POST['lastname'])?$_POST['lastname']:"/";
				$_POST['company']=isset($_POST['company'])?$_POST['company']:"/";
				$_POST['address_2']=isset($_POST['address_2'])?$_POST['address_2']:"/";
				$_POST['postcode']=isset($_POST['postcode'])?$_POST['postcode']:"/";
				$_POST['customer_id']=isset($_POST['customerid'])?$_POST['customerid']:"";
				$_POST['custom_field']=isset($_POST['custom_field'])?$_POST['custom_field']:"/";
				$_POST['custom_field']=array("1"=>$_POST['custom_field']);
				$_POST['custom_field']=json_encode($_POST['custom_field']);
				if(isset($_POST['idcard'])){
					//判断身份证是否存在
					//配置您申请的appkey
			        $appkey = "39e7639dbabaf5a849579cd2b5029348";
			        //************1.真实姓名和身份证号码判断是否一致************
			        $url = "http://op.juhe.cn/idcard/query";
			        $params = array(
	                    "idcard" => $_POST['idcard'],//身份证号码
	                    "realname" => $_POST['firstname'],//真实姓名
	                    "key" => $appkey,//应用APPKEY(应用详细页查询)
	                );
	                // $paramstring = http_build_query($params);
	                // $content = $this->juhecurl($url,$paramstring);
	                // $result = json_decode($content,true);


	                /*修改 zxx 2017-6-12*/
                    // if(@isCreditNo($_POST['idcard']))
                    // {
                    //     $result['error_code']=0;
                    //     $result['result']['res']=1;
                    //     $result['result']['res1']=1;
                                       
                    // }
                    // else
                    // {
                    //     $result['error_code']=-1;
                    //     $result['result']['res']=-1;
                    //     $result['result']['res1']=1;

                    // }

			        $url = 'https://v.apistore.cn/api/a1';
					$param=array(
					'key' => '42350b7fda95e7e4bbabb03a1328d595',
					'cardNo' => $_POST['idcard'],
					'realName' => $_POST['firstname'],
					'information' => '',
					);

					//发送远程请求;
					$result =$this->APIStore($url, $param, true);
					if ($result['error_code'] == 0) {
					    //处理成功
					    $result['error_code']=0;
			            $result['result']['res']=1;

					} else {
					    //处理失败
					    $result['error_code']=-1;
			            $result['result']['res']=-1;
					}

	                // if(@$result['result']['res']==1 && $result['result']['res']!=1){
                 // 		 exeSql("insert into hb_card (name,card) values ('".$_POST['firstname']."','".$_POST['idcard']."')");
                	// } 

	                if(empty($result) || @$result['result']['res']!=1){
	                	$this->res["retcode"]=1103;
		       			$this->res["msg"]="身份证信息不符合";
	                }else{
						//判断用户是否登录
						$is_login = getRow("SELECT cl.* FROM `hb_customer_login` cl left join `hb_customer` c on c.customer_id = cl.email WHERE cl.email = '" . $_POST['customer_id'] . "' and c.status = 1");
						if($is_login){
							if(@$result['is_realname']!=1){
		                     	exeSql("insert into hb_card (name,card) values ('".$_POST['firstname']."','".$_POST['idcard']."')");
		                    }
							//登陆了
							saveData("hb_address",$_POST);
							$address_id=getLastId();
							//判断是否存在def
							if(isset($_POST['def']) && @$_POST['def']==1){
								//修改默认地址
								exeSql("UPDATE hb_customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . $_POST['customer_id'] . "'");
							}

							//判断是否有过实名认证，如果没有则修改用户表信息
							$sql="select card from hb_customer where customer_id=".$_POST['customer_id'];
							$a=getRow($sql);
							if(empty($a['card'])){
								//修改用户表信息
								  $data = array(
                                'customerid'=>$_POST['customer_id'],
                                'lastname'=> $_POST['firstname'],
                                'card'=>$_POST['idcard']
	                            );
	                            $this->editRealname($data);//修改customer关于验证姓名错误的字段
							}

							$this->res["retcode"]=0;
			       			$this->res["msg"]="添加成功";
						}else{
							$this->res["retcode"]=1101;
			       			$this->res["msg"]="请先登录";
						}
	                }
				}else{
					//判断用户是否登录
					$is_login = getRow("SELECT cl.* FROM `hb_customer_login` cl left join `hb_customer` c on c.customer_id = cl.email WHERE cl.email = '" . $_POST['customer_id'] . "' and c.status = 1");
					if($is_login){
						//登陆了
						saveData("hb_address",$_POST);
						$this->res["retcode"]=0;
						$address_id=getLastId();
						//判断是否存在def
						if(isset($_POST['def']) && @$_POST['def']==1){
							//修改默认地址
							exeSql("UPDATE hb_customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . $_POST['customer_id'] . "'");
						}
		       			$this->res["msg"]="添加成功";
					}else{
						$this->res["retcode"]=1101;
		       			$this->res["msg"]="请先登录";
					}
				}
			}
		}else{
	       	$this->res["retcode"]=1180;
	       	$this->res["msg"]="请求方式错误";
    	}
    	return $this->res;
	}

	/*
	*zxx 2017-3-28 修改我的收货地址
	*/
		function updateaddress(){
			if($_SERVER['REQUEST_METHOD']=="POST"){
				if(empty($_POST['firstname']) || empty($_POST['country_id']) || empty($_POST['zone_id']) || empty($_POST['city']) || empty($_POST['address_1']) ){
					$this->res["retcode"]=1000;	
					$this->res["msg"]="请求参数错误";
				}else{
					//var_dump($_POST);exit;
					$_POST['lastname']=isset($_POST['lastname'])?$_POST['lastname']:"/";
					$_POST['company']=isset($_POST['company'])?$_POST['company']:"/";
					$_POST['address_2']=isset($_POST['address_2'])?$_POST['address_2']:"/";
					$_POST['postcode']=isset($_POST['postcode'])?$_POST['postcode']:"/";
					$_POST['customer_id']=isset($_POST['customerid'])?$_POST['customerid']:"";
					$_POST['address_id']=isset($_POST['address_id'])?$_POST['address_id']:"";
					$_POST['custom_field']=isset($_POST['custom_field'])?$_POST['custom_field']:"/";
					$_POST['custom_field']=array("1"=>$_POST['custom_field']);
					$_POST['custom_field']=json_encode($_POST['custom_field']);
					
					if(isset($_POST['idcard'])){

						//通过address_id获取改地址的信息
						$customer_info=getRow("select firstname,idcard from hb_address where address_id='".$_POST['address_id']."'");
						if(!empty($customer_info)){
							//如果两项都符合，则无需调用身份证验证接口
							if($customer_info['firstname']==$_POST['firstname'] && $customer_info['idcard']==$_POST['idcard']){
								$result=array("result"=>array("res"=>1),"is_realname"=>1);
							}else{
								// 判断身份证是否存在
								// 配置您申请的appkey
						        //$appkey = "39e7639dbabaf5a849579cd2b5029348";
						        // //************1.真实姓名和身份证号码判断是否一致************
						        // $url = "http://op.juhe.cn/idcard/query";
						        // $params = array(
				          //           "idcard" => $_POST['idcard'],//身份证号码
				          //           "realname" => $_POST['firstname'],//真实姓名
				          //           "key" => $appkey,//应用APPKEY(应用详细页查询)
				          //       );
				                // $paramstring = http_build_query($params);
				                // $content = $this->juhecurl($url,$paramstring);
				                // $result = json_decode($content,true);
				                 

				                 /*修改 zxx 2017-6-12*/
			                    // if(@isCreditNo($_POST['idcard']))
			                    // {
			                    //     $result['error_code']=0;
			                    //     $result['result']['res']=1;
			                    //     $result['result']['res1']=1;
			                                       
			                    // }
			                    // else
			                    // {
			                    //     $result['error_code']=-1;
			                    //     $result['result']['res']=-1;
			                    //     $result['result']['res1']=1;

			                    // }
			                    $url = 'https://v.apistore.cn/api/a1';
								$param=array(
								'key' => '42350b7fda95e7e4bbabb03a1328d595',
								'cardNo' => $_POST['idcard'],
								'realName' => $_POST['firstname'],
								'information' => '',
								);

								//发送远程请求;
								$result =$this->APIStore($url, $param, true);
								if ($result['error_code'] == 0) {
								    //处理成功
								    $result['error_code']=0;
						            $result['result']['res']=1;

								} else {
								    //处理失败
								    $result['error_code']=-1;
						            $result['result']['res']=-1;
								}
			                    //var_dump($result);exit;
				                // if(@$result['result']['res']==1 && @$result['result']['res1']!=1){
                    //          		 exeSql("insert into hb_card (name,card) values ('".$name."','".$idcard."')");
                    //         	} 
							}
						}
		                if(@empty($result) || @$result['result']['res']!=1){
						 //if(false){
		                	$this->res["retcode"]=1103;
			       			$this->res["msg"]="身份证信息不符合";
		                }else{
							//判断用户是否登录
							$is_login = getRow("SELECT cl.* FROM `hb_customer_login` cl left join `hb_customer` c on c.customer_id = cl.email WHERE cl.email = '" . $_POST['customer_id'] . "' and c.status = 1");
							if($is_login){
								//var_dump($result);exit;
								//添加身份证到数据库
								if(@$result['is_realname']!=1){
			                     	exeSql("insert into hb_card (name,card) values ('".$_POST['firstname']."','".$_POST['idcard']."')");
			                    }
								//登陆了
								
								saveData("hb_address",$_POST);
								//判断是否存在def
								if(isset($_POST['def']) && @$_POST['def']==1){
									//修改默认地址
									exeSql("UPDATE hb_customer SET address_id = '" . $_POST['address_id'] . "' WHERE customer_id = '" . $_POST['customer_id'] . "'");
								}
								$this->res["retcode"]=0;
				       			$this->res["msg"]="修改成功";
							}else{
								$this->res["retcode"]=1101;
				       			$this->res["msg"]="请先登录";
							}
		                }
					}else{
					//判断用户是否登录
						$is_login = getRow("SELECT cl.* FROM `hb_customer_login` cl left join `hb_customer` c on c.customer_id = cl.email WHERE cl.email = '" . $_POST['customer_id'] . "' and c.status = 1");
						if($is_login){
							//登陆了
							saveData("hb_address",$_POST);

							if(isset($_POST['def']) && @$_POST['def']==1){
								//修改默认地址
								exeSql("UPDATE hb_customer SET address_id = '" .  $_POST['address_id'] . "' WHERE customer_id = '" . $_POST['customer_id'] . "'");
							}

							$this->res["retcode"]=0;
			       			$this->res["msg"]="修改成功";
						}else{
							$this->res["retcode"]=1101;
			       			$this->res["msg"]="请先登录";
						}
					}
				}
			}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
		}

	    /**
	     * cgl 2017-3-24  验证无密登录
	     */
	    function noPwdLogin(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		$telephone=isset($_POST["telephone"])?$_POST["telephone"]:null;
	    		$code=isset($_POST["code"])?$_POST["code"]:null;
	    		//头像
	    		$headurl=isset($_POST["headurl"])?$_POST["headurl"]:'http://iwant-u.cn/image/placeholder_circle.png';
	    		//openid unionid
	    		$openid=isset($_POST["openid"])?$_POST["openid"]:"NULL";
	    		$unionid=isset($_POST["unionid"])?$_POST["unionid"]:"NULL";

	    		if(!$telephone){
		    		$this->res["retcode"]= 1000;
		    		$this->res["msg"]="参数错误";
		    	}
		    	else{
		            if(!preg_match("/^13[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|14[0-9]{9}$/",$telephone)){
		                //你的手机号不正确
		                $this->res["retcode"]=1208;
		                $this->res["msg"]="手机格式不正确";
		            }else{
		            	//检查手机号码和验证是否正确   
		            	$is_right=getRow("select * from hb_customer_validate where mobile='".$telephone."' and validate= '".$code."' and (typ='用户登录' or typ='用户注册') ");
		            	if($is_right){
		            		//查询用户
		            		$user=getRow("select * from hb_customer where telephone = '".$telephone."' ");
		            		if($user){
		            			$money=getRow("select * from hb_balance where customer_id = '".$user["customer_id"]."' ");
		            			if($user["status"]==1){

		            				if(!empty($_COOKIE["unionid"]) && @$_COOKIE["unionid"]!="undefined" ){
		            					$unionid=$_COOKIE["unionid"];
		            				}

		            				//查询是否绑定过
		            				if($user["unionid"]=="NULL" || $user["unionid"]==""){
		            					exeSql("update hb_customer set unionid ='".$unionid."' where customer_id = '".$user["customer_id"]."' ");
		            				}
		            				if($user["wechat_openid"]=="NULL" || $user["wechat_openid"]==""){
		            					exeSql("update hb_customer set wechat_openid ='".$openid."' where customer_id = '".$user["customer_id"]."' ");
		            				}
		            				//微信公众号的unionid
		            				if($user["wechant_union_id"]=="NULL" || $user["wechant_union_id"]=="" || $user["wechant_union_id"]=="undefined"  ){
		            					exeSql("update hb_customer set wechant_union_id ='".$unionid."' where customer_id = '".$user["customer_id"]."' ");	
		            				}

		            				//判断是否是代理
		                            if($user["merchant_id"]==0){
		                                //不是代理
		                                $is_agent=0;
		                            }else if($user["merchant_id"]==1){
		                                //是平台代理
		                                $is_agent=1;
		                            }else if($user["merchant_id"]!=1 && $user["merchant_id"]!=0){
		                                //是商户代理
		                                $is_agent=2;
		                            }
		                            //判断是否进行了实名认证
		                            if($user["lastname"]!=null && $user["card"]!=null){  
		                                //认证了的
		                                $is_dentify=1;
		                            }else{
		                                 $is_dentify=0;
		                            }
		                            //资金密码
		                            $is_money_pwd=0;
		                            if($user["money_pwd"]!=null){
		                                //已设置资金密码
		                                $is_money_pwd=1;
		                            }
		                            //第三方标识  默认是有的
		                            $qq_sign=1;
		                            $wechat=1;
		                            if($user["qq_openid"]==null){
		                                $qq_sign=0;
		                            }
		                            if($user["wechat_openid"]==null && $user["unionid"]==null){
		                                $wechat=0;
		                            }
		                            //判断是否为企业用户  cgl  2017-2-23  新增
		                            if($user["proxy_status"]==1){
		                                $company_status=1;
		                            }else{
		                                $company_status=0;//不是企业用户
		                            }
		                            $_SESSION["default"]['passkey']=sha1(md5($user["customer_id"].$user["password"].$user["salt"]));
		                            $_SESSION["default"]['customer_id']=$user["customer_id"];
		                            $is_phone=0;//没有验证
		                            if(@$user["telephone"]){
		                            	$is_phone=1;//进行过手机验证
		                            }
							    	$json = array(
		                                'customerid'=>$user['customer_id'],
		                                'headurl'=>empty($user['headurl'])?null:$user['headurl'],
		                                'nickname'=>@$user['firstname'],
		                                'qq_sign'=>$qq_sign,
		                                'wechat'=>$wechat,
		                                "is_money_pwd"=>$is_money_pwd,
		                                "left_money"=>empty($money["balance"])?'0.00':@$money["balance"],
		                                "go_money"=>empty($money["availabe_balance"])?'0.00':@$money["availabe_balance"],
		                                "phone"=>@$user["telephone"],
		                                "card"=>@$user["card"],
		                                "realname"=>@$user["lastname"],
		                                "is_agent"=>$is_agent,
		                                "is_dentify"=>$is_dentify,
		                                "passkey"=>sha1(md5($user["customer_id"].$user["password"].$user["salt"])),     //加密的值
		                                "is_company"=>$company_status,
		                                "birthday"=>$user['birthday'],
		                                "sex"=>$user['sex'],
		                                "rctoken"=>$user['rctoken'],
		                                "is_phone"=>$is_phone
		                            );
		                            setCookie("passkey",sha1(md5($user["customer_id"].$user["password"].$user["salt"])),time()+3600*24*7,"/");
		                            setCookie("customer_id",$user["customer_id"],time()+3600*24*7,"/");

		                            // $_COOKIE["customer_id"]=$user["customer_id"];
		                            // $_COOKIE["passkey"]=sha1(md5($user["customer_id"].$user["password"].$user["salt"]));
		                            // // print_r($_SESSION);
		                            // print_r($_COOKIE);
		                            // setcookie("user",$json,time()+3600*24*7);
		                            // setCache('user'.$openid,$json,3600*24*7);
		                            $this->res["data"]=$json;

		            			}else{
		            				$this->res["retcode"]=4004;
		       						$this->res["msg"]="该用户账号异常,请联系客服";
		            			}
		            		}else{
		            			//$this->res["retcode"]=1102;
		       					// $this->res["msg"]="该用户不存在";
		       					
		       					//注册
		            			$data=array(
		            				"customer_group_id"=>1,
		            				"store_id"=>0,
		            				"firstname"=>"NULL",
		            				"lastname"=>"NULL",
		            				"email"=>"NULL",
		            				"telephone"=>$telephone,
		            				"fax"=>"NULL",
		            				"password"=>"NULL",//密码
		            				"salt"=>token(9),//salt值
		            				"address_id"=>0,
		            				"custom_field"=>"NULL",
		            				"ip"=>$_SERVER["REMOTE_ADDR"],
		            				"status"=>1,
		            				"approved"=>1,
		            				"token"=>"NULL",
		            				"safe"=>0,
		            				"qq_openid"=>"NULL",
		            				"qq_openid_share"=>"NULL",
		            				"rctoken"=>"NULL",//融云的token
		            				"unionid"=>$unionid,//共同使用登录和注册
		            				"wechat_openid"=>"NULL",
		            				"wechat_openid_share"=>$openid,//web端openid
		            				"headurl"=>$headurl,//头像
		            				"sex"=>1,
		            				"card"=>"NULL",//身份证
		            				"merchant_id"=>0,
		            				"clientid"=>"NULL",
		            				"isdisturb"=>0,
		            				"sharetimes"=>0,
		            				"remark"=>"NULL",
		            				"realname_error_num"=>0,
		            				"realname_error_time"=>"NULL",
		            				"proxy_status"=>0,
		            				"is_set_pwd"=>0,//未设置密码
		            				"newsletter"=>"NULL",
		            				"date_added"=>date("Y-m-d H:i:s",time()),
		            				"wechant_union_id"=>$unionid
		            				);
		            			saveData("hb_customer",$data);
		            			$customer_id=getLastId();
		            			if($customer_id){
		            				//修改融云的token
		            				$user1=getRow("select * from hb_customer where telephone= '".$telephone."' ");
		            				$customer_uu=getRow("SELECT MAX(customer_id) AS customer_id from `hb_customer` limit 1");
		            				$customer_uuid=@$customer_uu["customer_id"]+2;
		            				require_once 'lib/Rongyun.php';
		            				$r=new Rongyun();
		            				$rongyun_token=$r->getRctoken($customer_uuid,"张三",$headurl);

		            				//修改
		            				exeSql("UPDATE `hb_customer` SET customer_id=".$customer_uuid.",rctoken='".$rongyun_token."' WHERE customer_uuid = '" . (int)$user1["customer_uuid"] . "' ");
		            				//增加资金记录
		            				$balance = array(
		                               'customer_id'=>$customer_uuid,
		                               'balance'=>'0.00',
		                               'availabe_balance'=>'0.00',
		                               "date_added"=>date("Y-m-d H:i:s",time()),
		                               "date_modified"=>date("Y-m-d H:i:s",time())
		                            );
		            				saveData("hb_balance",$balance);

		            				//加入登录记录表
		            				$is_login=getRow("SELECT * FROM hb_customer_login WHERE email = '" . $customer_uuid . "' ");
		            				if($is_login){
		            					exeSql("UPDATE hb_customer_login SET total = (total + 1), date_modified = '" . date('Y-m-d H:i:s') . "' WHERE customer_login_id = '" . (int)$is_login['customer_login_id'] . "'");
		            				}else{
		            					//插入记录
		            					$login_record=array(
		            						"email"=>$customer_uuid,
		            						"ip"=>$_SERVER["REMOTE_ADDR"],//$_SERVER["REMOTE_ADDR"]
		            						"total"=>1,
		            						"date_added"=>date("Y-m-d H:i:s",time()),
		            						"date_modified"=>date("Y-m-d H:i:s",time())
		            						);
		            					saveData("hb_customer_login",$login_record);
		            				}
		            				$user=getRow("select * from hb_customer where telephone = '".$telephone."' ");
		            				$money=getRow("select * from hb_balance where customer_id = '".$user["customer_id"]."' ");
		            				//判断是否是代理
		                            if($user["merchant_id"]==0){
		                                //不是代理
		                                $is_agent=0;
		                            }else if($user["merchant_id"]==1){
		                                //是平台代理
		                                $is_agent=1;
		                            }else if($user["merchant_id"]!=1 && $user["merchant_id"]!=0){
		                                //是商户代理
		                                $is_agent=2;
		                            }
		                            //判断是否进行了实名认证
		                            if($user["lastname"]!=null && $user["card"]!=null){  
		                                //认证了的
		                                $is_dentify=1;
		                            }else{
		                                 $is_dentify=0;
		                            }
		                            //资金密码
		                            $is_money_pwd=0;
		                            if($user["money_pwd"]!=null){
		                                //已设置资金密码
		                                $is_money_pwd=1;
		                            }
		                            //第三方标识  默认是有的
		                            $qq_sign=1;
		                            $wechat=1;
		                            if($user["qq_openid"]==null){
		                                $qq_sign=0;
		                            }
		                            if($user["wechat_openid"]==null && $user["unionid"]==null){
		                                $wechat=0;
		                            }
		                            //判断是否为企业用户  cgl  2017-2-23  新增
		                            if($user["proxy_status"]==1){
		                                $company_status=1;
		                            }else{
		                                $company_status=0;//不是企业用户
		                            }
		                            $_SESSION["default"]['passkey']=sha1(md5($user["customer_id"].$user["password"].$user["salt"]));
		                            $_SESSION["default"]['customer_id']=$user["customer_id"];
		                            $is_phone=0;//没有验证
		                            if(@$user["telephone"]){
		                            	$is_phone=1;//进行过手机验证
		                            }
							    	$json = array(
		                                'customerid'=>$user['customer_id'],
		                                'headurl'=>empty($user['headurl'])?null:$user['headurl'],
		                                'nickname'=>@$user['firstname'],
		                                'qq_sign'=>$qq_sign,
		                                'wechat'=>$wechat,
		                                "is_money_pwd"=>$is_money_pwd,
		                                "left_money"=>empty($money["balance"])?'0.00':@$money["balance"],
		                                "go_money"=>empty($money["availabe_balance"])?'0.00':@$money["availabe_balance"],
		                                "phone"=>@$user["telephone"],
		                                "card"=>@$user["card"],
		                                "realname"=>@$user["lastname"],
		                                "is_agent"=>$is_agent,
		                                "is_dentify"=>$is_dentify,
		                                "passkey"=>sha1(md5($user["customer_id"].$user["password"].$user["salt"])),     //加密的值
		                                "is_company"=>$company_status,
		                                "birthday"=>$user['birthday'],
		                                "sex"=>$user['sex'],
		                                "rctoken"=>$user["rctoken"],
		                                "is_phone"=>$is_phone
		                            );
		                            setCookie("passkey",sha1(md5($user["customer_id"].$user["password"].$user["salt"])),time()+3600*24*7);
		                            setCookie("customer_id",$user["customer_id"],time()+3600*24*7);
									// setcookie("user",$json,time()+3600*24*7);
		                            // setCache('user'.$openid,$json,3600*24*7);
		                            $this->res["data"]=$json;

		            			}else{
		            				//失败 注册
		            				$this->res["retcode"]=1;
				    				$this->res["msg"]="注册会员失败";
		            			}
		            		}
		            	}else{
		            		$this->res["retcode"]=1100;
		       				$this->res["msg"]="验证码不正确";
		            	}
		            }
		        }
	    	}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;

	    }
	    /**
	     * 修改密码  cgl  2017-4-3 
	     */
	    function updatePwd(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		$customer_id=$this->customer_id;
	    		$old_password=isset($_POST["old_password"])?$_POST["old_password"]:null;//现在密码
	    		$new_password=isset($_POST["new_password"])?$_POST["new_password"]:null;//新密码
	    		$ok_password=isset($_POST["ok_password"])?$_POST["ok_password"]:null;//确认密码
	    		$customer=getRow("select * from hb_customer where customer_id= '".$customer_id."' ");
	    		if($customer){
	    			if(!empty($customer["password"]) && $customer["password"]!="NULL" ){
	    				//验证密码
	    				$old_password1=sha1($customer['salt'] . sha1($customer['salt'] . sha1($old_password)));
	    				if($old_password1!=$customer["password"]){
	    					//密码不正确
	    					$this->res["retcode"]=1100;
		       				$this->res["msg"]="该用户密码不正确";
		       				return $this->res;
	    				}
	    			}
	    			//判断密码和确认密码是否一致
	    			if($new_password==$ok_password){
	    				//修改密码
	    				$change_pwd=sha1($customer['salt'] . sha1($customer['salt'] . sha1($new_password)));
	    				exeSql("UPDATE hb_customer SET password = '" . $change_pwd . "' WHERE `customer_id` = '" . $customer_id . "'");
	    				$this->res["retcode"]=0;
		       			$this->res["msg"]="修改密码成功";
	    			}else{
	    				$this->res["retcode"]=4005;
		       			$this->res["msg"]="新密码和确认密码不一致";
	    			}
	    		}else{
	    			$this->res["retcode"]=1102;
		       		$this->res["msg"]="该用户不存在";
	    		}
	    	}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
	    }
	    /**
	     * cgl 2017-4-6  增加会员申请的条件
	     */
	    function applyMember(){
	    	if($_SERVER['REQUEST_METHOD']=="POST"){
	    		include_once "home.php";
				$home=new home();
				$reviewing=isset($_POST["reviewing"])?$_POST["reviewing"]:2;
				$this->customer_id=isset($_POST["customerid"])?$_POST["customerid"]:null;
				// var_dump($this->customer_id);
				// var_dump($reviewing);
					
				// if( $this->customer_id=="1732" && $reviewing==2 ){
				// 	$this->res["data"]=null;
				// }else{
					$banner=$home->getBanner(22,1);
		    		$this->res["data"]["member_banner"]=$banner;
		    		$this->res["data"]["member_condition"]="1.邀请码（永久享受）\n2.条件申请\n    累计购买50单\n    累计购买8888元";
		    		$this->res["data"]["pay_condition"]="188";
		    		$this->res["data"]["member_privilege"]="1.全场商品享受VIP专属优惠\n2.会员低价商品,设置差价分享,轻松赚钱\n3.会员能够获得专属优惠\n4.会员新品优先购";	
				// }
				
	    	}else{
		       	$this->res["retcode"]=1180;
		       	$this->res["msg"]="请求方式错误";
	    	}
	    	return $this->res;
	    }

		/*
		 * 发送手机短信验证码
		 * 王志超 17.4.10
		 * type 发送验证码的类型，1注册登录，2修改密码，3设置资金密码，4轮盘抽奖
		 */
		function sendValidate(){
			$telephone = isset($_POST['telephone'])?$_POST['telephone']:'';
			$type = isset($_POST['type'])?$_POST['type']:'';
			$rand=mt_rand(1111,9999);
			$content = "【嗨企货仓】验证码：" .$rand. "，欢迎来到嗨企货仓，如非本人操作，请忽略";

			if(empty($type) || empty($telephone)){
				return array('retcode'=>1000,'msg'=>'参数错误');
			}

			$customer = getRow("select * from hb_customer where telephone = '" .$telephone. "'");

			if(empty($customer) && ($type == 2 || $type == 3)){
				return array('retcode'=>9000,'msg'=>'账号不存在');
			}

			if($type == '1'){
				if($customer){
					$typ = "用户登录";
				}else{
					$typ = "用户注册";
				}

			}else if($type == '2'){
				$typ = "找回密码";
			}else if($type == '3'){
				$typ = "设置资金密码";
			}else{
				$typ = "轮盘抽奖";
			}

			$return = sendMsg($telephone,$content,$rand,$typ);
			return $return;
		}

		/*
		 * 验证手机短信验证码
		 * 王志超 17.4.10
		 * type 发送验证码的类型，1注册，2登录，3修改密码，4设置资金密码，5轮盘抽奖
		 */
		function checkValidate(){
			$telephone = isset($_POST['telephone'])?$_POST['telephone']:'';
			$type = isset($_POST['type'])?$_POST['type']:'';
			$validate = isset($_POST['validate'])?$_POST['validate']:'';

			if(empty($telephone) || empty($type) || empty($validate)){
				return array('retcode'=>1000,'msg'=>'参数错误');
			}
			$customer = getRow("select * from hb_customer where telephone = '" .$telephone. "'");
			if($type == '1'){
				$typ = "用户注册";
				if($customer){
					return array('retcode'=>9000,'msg'=>'账号已被注册');
				}

			}else if($type == '2'){
				$typ = "用户登录";
				if(empty($customer)){
					return array('retcode'=>9001,'msg'=>'账号不存在');
				}
			}else if($type == '3'){
				$typ = "找回密码";
				if(empty($customer)){
					return array('retcode'=>9002,'msg'=>'账号不存在');
				}
			}else if($type == '4'){
				$typ = "设置资金密码";
				if(empty($customer)){
					return array('retcode'=>9003,'msg'=>'账号不存在');
				}
			}else{
				$typ = "轮盘抽奖";
			}

			$is_right=getRow("select * from hb_customer_validate where mobile='".$telephone."' and validate= '".$validate."' and typ='" .$typ. "' ");
			if(!$is_right){
				return array('retcode'=>9004,'msg'=>'请输入正确验证码');
			}

			return $this->res;
		}

		/*
		 *验证h5端领取优惠券
		 *zxx 2017-4-19
		 * 
		 */
		function checkValidate1(){
			//接受到传递过来的参数
			$salt = token(9);
			$telephone = isset($_POST['telephone'])?$_POST['telephone']:'';
			$validate = isset($_POST['validate'])?$_POST['validate']:'';
			$inviteid = isset($_POST['inviteid'])?$_POST['inviteid']:'';
			$post['telephone']=$telephone;
			$post['telephone']=$telephone;
			//进行判断
			if(empty($telephone)  || empty($validate)){
				return array('retcode'=>1000,'msg'=>'参数错误');
			}

			//进行验证码检测
			$is_right=getRow("select * from hb_customer_validate where mobile='".$telephone."' and validate= '".$validate."'");
			if(!$is_right){
				return array('retcode'=>9004,'msg'=>'请输入正确验证码');
			}

			//查询数据库中的数据
			$customer = getRow("select * from hb_customer where telephone = " .$telephone. "");
			if($customer){
				//判断之前是不是会员，如果是则无需更改，如果不是则成为会员
				$merchant_id=getRow("select merchant_id from  hb_customer where telephone=".$telephone);

				if(@$merchant_id['merchant_id'] == 0){
					//如果包含inviteid
					if($inviteid){
						//不是会员，查出对应的parent_id,merchant_id
						$invite_msg=getRow("select * from hb_invitecode where invitecode_id=".$inviteid);
						if(!empty($invite_msg)){
							//修改他的会员状态  cgl 2017-6-10  增加成为会员的时间
							$sql="update hb_customer set merchant_id=1,parent_id=".@$invite_msg['customer_id'].",invitecode_id=".@$inviteid.",proxy_time=NOW() where telephone='".@$telephone."'";
							exeSql($sql );
						}	
					}
				}
				//如果存在则返回customerid
				$customer_id=$customer['customer_id'];
			}else{
				//如果不存在则插入数据,并返回customerid
				exeSql("INSERT INTO hb_customer SET customer_id=default,customer_group_id = '1',store_id = '1',sex='1',firstname = '".$telephone."',lastname = '',card = '',telephone = '".$telephone."',custom_field = '',salt = '',password = '',ip = '',qq = '',qq_openid = '',wechat = '',unionid = '',wechat_openid = '',newsletter = '0',sharetimes = '0',realname_error_num = '0',status = '1',approved = '0',birthday = '631123200',date_added = NOW(),headurl = '',is_set_pwd = '0',proxy_time = NOW() ");
           		 $customer_uuid = getLastId();
				if(!$customer_uuid){
					return $this->res = array(
							'retcode'	=>9003,
							'msg'		=>"注册失败，请重新操作"
					);
				}

				//修改融云的token
				$customer_last=getRow("SELECT MAX(customer_id) AS customer_id from `hb_customer` limit 1");
				$customer_id=@$customer_last['customer_id']+2;
				require_once 'lib/Rongyun.php';
				$r=new Rongyun();
				$rongyun_token=$r->getRctoken($customer_id,"张三",isset($post['headurl'])?$post['headurl']:"http://iwant-u.cn/image/placeholder_circle.png");

				//修改
				$customer_update = exeSql("UPDATE `hb_customer` SET customer_id=".$customer_id.",rctoken='".$rongyun_token."' WHERE customer_uuid = '" . (int)$customer_uuid . "' ");
				if($customer_update->rowCount() == 0){
					$this->res = array(
							'retcode'	=>9004,
							'msg'		=>"注册失败，请重新操作"
					);
				}

				//如果包含inviteid
				if($inviteid){
					//不是会员，查出对应的parent_id,merchant_id
					$invite_msg=getRow("select * from hb_invitecode where invitecode_id=".$inviteid);
					if(!empty($invite_msg)){
						//修改他的会员状态
						exeSql("update hb_customer set merchant_id=1,parent_id=".@$invite_msg['customer_id'].",invitecode_id=".@$inviteid." where telephone=".@$telephone );
					}	
				}
			}
			$num=0;
			$money=0;
			//如果优惠券id存在，则插入数据
			if(!empty($_POST['coupon_id'])){
				$coupon_ids=explode(',',$_POST['coupon_id']);
				foreach ($coupon_ids as $key => $value) {
					if(getRow("select * from hb_coupon_customer where customer_id=".$customer_id." and coupon_id=".$value)){
						//有就不插入
					}else{
						//没有就插入
						$date_added=time();
						//判断优惠券是否过期和已经领完
						$info=getRow("select * from hb_coupon where coupon_id=".$value);
						if($info['status']==3 || $info['date_end']<$date_added || $info['get_total']>=$info['release_total']){
							
						}else{
							exeSql("insert into hb_coupon_customer (coupon_id,customer_id,date_added) values (".$value.",".$customer_id.",".$date_added.")");
							exeSql("update  hb_coupon set get_total=get_total+1 where coupon_id=".$value);
							$num++;
							$money+=$info['discount'];
						}
					}
				}
				if($num==0){
					return array("retcode"=>1,'msg'=>"您已经领取过了");
				}
				if(count($coupon_ids)>1){
					//大礼包
					$coupon_info=getRow("select * from hb_coupon where coupon_id=".$coupon_ids[$num-1]);
					$msg1=$coupon_info['discount_desn'];
					$msg2="￥".$money;
					$msg3="大礼包";
					$msg4=$msg3."领取成功";
					$msg5=$msg3."已存入账户中，可购买所适应的商品";
				}else{
					$coupon_info=getRow("select * from hb_coupon where coupon_id=".$_POST['coupon_id']);
					$msg1=$coupon_info['discount_desn'];
					if($coupon_info['discount']==$coupon_info['min_limit_amount'] && $coupon_info['discount']>15){
						//抵用券
						$msg2="商品";
						$msg3="兑换券";
						$msg4=$msg2."兑换券领取成功";
					}else{
						//优惠券
						$msg2="￥".$coupon_info['discount'];
						$msg3="优惠券";
						$msg4=$msg2."优惠券领取成功";
					}
					$msg5=$msg3."已存入账户中，可购买所适用的商品";
				}
				$msg=array(
					"coupon_id"=>$_POST['coupon_id']
				);
				$_SESSION['msg1']=$msg1;
				$_SESSION['msg2']=$msg2;
				$_SESSION['msg3']=$msg3;
				$_SESSION['msg4']=$msg4;
				$_SESSION['msg5']=$msg5;
				return array("retcode"=>0,'msg'=>$msg);
			}

			return array('retcode'=>0,'msg'=>$customer_id,'num'=>$num);
		}

		function getSessionData(){
			$msg=array();
			$msg["msg1"]=@$_SESSION['msg1'];
			$msg["msg2"]=@$_SESSION['msg2'];
			$msg["msg3"]=@$_SESSION['msg3'];
			$msg["msg4"]=@$_SESSION['msg4'];
			$msg["msg5"]=@$_SESSION['msg5'];
			$this->res['data']=$msg;
			return $this->res;
		}
		function loginApp($customer,$openid=array("qq"=>"NULL","qq_openid"=>"NULL","wechat"=>"NULL","unionid"=>"NULL","wechat_openid"=>"NULL")){

			//判断是否是代理
			if($customer["merchant_id"] > 1){
				//是商户代理
				$is_agent=2;
			}else if($customer["merchant_id"]==1){
				//是平台代理
				$is_agent=1;
			}else{
				//不是代理
				$is_agent=0;
			}

			//判断是否进行了实名认证
			if(!empty($customer["lastname"]) && !empty($customer["card"]) && $customer["lastname"] != 'NULL' && $customer["card"] != 'NULL'){
				//认证了的
				$is_dentify=1;
			}else{
				$is_dentify=0;
			}

			//资金密码
			$is_money_pwd=0;
			if(!empty($customer["money_pwd"]) && $customer["qq_openid"] != 'NULL'){
				//已设置资金密码
				$is_money_pwd=1;
			}
			//第三方标识  默认是有的
			$qq_sign=1;
			$wechat=1;
			if(empty($customer["qq_openid"]) || $customer["qq_openid"] == 'NULL'){
				$qq_sign=0;
			}
			if((empty($customer["wechat_openid"])|| $customer["wechat_openid"] == 'NULL') && (empty($customer["unionid"])|| $customer["unionid"] == 'NULL')){
				$wechat=0;
			}

			//存在微信/qq的openid，用于微信绑定电话号码，同步信息
			if(!empty($openid['qq_openid']) && $openid["qq_openid"] != 'NULL'){
				exeSql("update hb_customer set qq=:qq,qq_openid=:qq_openid where customer_id = '" .$customer['customer_id']. "'",$openid);
				$qq_sign=1;
			}
			if(!empty($openid['unionid']) && $openid["unionid"] != 'NULL'){
				exeSql("update hb_customer set wechat=:wechat,unionid=:unionid where customer_id = '" .$customer['customer_id']. "'",$openid);
				$wechat=1;
			}

			if(!empty($openid['qq_openid']) && $openid["qq_openid"] != 'NULL'){
				exeSql("update hb_customer set wechat=:wechat,wechat_openid=:wechat_openid where customer_id = '" .$customer['customer_id']. "'",$openid);
				$wechat=1;
			}

			//判断是否为企业用户  cgl  2017-2-23  新增
			if($customer["proxy_status"]==1){
				$company_status=1;
			}else{
				$company_status=0;//不是企业用户
			}
			$_SESSION["default"]['passkey']=sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"]));
			$_SESSION["default"]['customer_id']=$customer["customer_id"];
			$json = array(
					'customerid'=>$customer['customer_id'],
					'headurl'=>empty($customer['headurl'])?null:$customer['headurl'],
					'nickname'=>@$customer['firstname'],
					'qq_sign'=>$qq_sign,
					'wechat'=>$wechat,
					"is_money_pwd"=>$is_money_pwd,
					"left_money"=>empty($money["balance"])?'0.00':@$money["balance"],
					"go_money"=>empty($money["availabe_balance"])?'0.00':@$money["availabe_balance"],
					"phone"=>@$customer["telephone"],
					"card"=>@$customer["card"],
					"realname"=>@$customer["lastname"],
					"is_agent"=>$is_agent,
					"is_dentify"=>$is_dentify,
					"passkey"=>sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"])),     //加密的值
					"is_company"=>$company_status,
					"birthday"=>$customer['birthday'],
					"sex"=>$customer['sex'],
					"rctoken"=>$customer['rctoken']
			);
			return $json;
		}

		function registerApp($telephone,$headurl,$openid=array("qq"=>"NULL","qq_openid"=>"NULL","wechat"=>"NULL","unionid"=>"NULL","wechat_openid"=>"NULL")){
			//注册
			beginTransaction();

			$data = array(
					"customer_group_id"=>1,
					"store_id"=>0,
					"firstname"=>"NULL",
					"lastname"=>"NULL",
					"email"=>"NULL",
					"telephone"=>$telephone,
					"fax"=>"NULL",
					"password"=>"NULL",//密码
					"salt"=>token(9),//salt值
					"address_id"=>0,
					"custom_field"=>"NULL",
					"ip"=>$_SERVER["REMOTE_ADDR"],
					"status"=>1,
					"approved"=>1,
					"token"=>"NULL",
					"safe"=>0,
					"qq"=>$openid['qq'],
					"qq_openid"=>$openid['qq_openid'],
					"qq_openid_share"=>"NULL",
					"rctoken"=>"NULL",//融云的token
					"wechat"=>$openid['wechat'],
					"unionid"=>$openid['unionid'],//共同使用登录和注册
					"wechat_openid"=>$openid['wechat_openid'],//openid
					"wechat_openid_share"=>"NULL",
					"headurl"=>$headurl,//头像
					"sex"=>1,
					"card"=>"NULL",//身份证
					"merchant_id"=>0,
					"clientid"=>"NULL",
					"isdisturb"=>0,
					"sharetimes"=>0,
					"remark"=>"NULL",
					"realname_error_num"=>0,
					"realname_error_time"=>"NULL",
					"proxy_status"=>0,
					"is_set_pwd"=>0,//未设置密码
					"newsletter"=>"NULL",
					"date_added"=>date("Y-m-d H:i:s",time())
			);

			saveData("hb_customer",$data);
			$customer_uuid = getLastId();//customer表的主键是customer_uuid
			if(!$customer_uuid){
				//失败 注册
				return $this->res =  array("retcode"=>5000,"msg"=>"注册失败");
			}

			//修改融云的token
			$customer_last = getRow("SELECT MAX(customer_id) AS customer_id from `hb_customer` limit 1");
			$customer_id = @$customer_last["customer_id"]+2;

			require_once 'lib/Rongyun.php';
			$r=new Rongyun();
			$rongyun_token = $r->getRctoken($customer_id,"张三",$headurl);

			//修改
			$customer_result = exeSql("UPDATE `hb_customer` SET customer_id=".$customer_id.",rctoken='".$rongyun_token."' WHERE customer_uuid = '" . (int)$customer_uuid . "' ");
			if($customer_result->rowCount() == 0){
				$this->res = array('retcode'=>5001,'msg'=>'注册失败');
			}

			//增加资金记录
			$balance = array(
					'customer_id'=>$customer_id,
					'balance'=>'0.00',
					'availabe_balance'=>'0.00',
					"date_added"=>date("Y-m-d H:i:s",time()),
					"date_modified"=>date("Y-m-d H:i:s",time())
			);
			saveData("hb_balance",$balance);
			$balance_id = getLastId();
			if(!$balance_id){
				$this->res =  array("retcode"=>5002,"msg"=>"注册失败");
			}


			//加入登录记录表
			$login_record=array(
					"email"=>$customer_uuid,
					"ip"=>$_SERVER["REMOTE_ADDR"],//$_SERVER["REMOTE_ADDR"]
					"total"=>1,
					"date_added"=>date("Y-m-d H:i:s",time()),
					"date_modified"=>date("Y-m-d H:i:s",time())
			);
			saveData("hb_customer_login",$login_record);
			$customer_login_id = getLastId();
			if(!$customer_login_id){
				$this->res =  array("retcode"=>5003,"msg"=>"注册失败");
			}

			if($this->res['retcode'] == 0){
				commit();

				$customer = getRow("select * from hb_customer where telephone = '".$telephone."' ");

				$is_agent=0;//不是代理
				$is_dentify=0;//未实名认证
				$is_money_pwd=0;//未设置资金密码
				//第三方标识
				$qq_sign=0;
				$wechat=0;
				//存在微信/qq的openid，用于微信、qq注册
				if(!empty($openid['qq_openid'])){
					$qq_sign=1;
				}
				if(!empty($openid['unionid']) || !empty($openid['qq_openid'])){
					$wechat=1;
				}

				$company_status=0;//不是企业用户

				$_SESSION["default"]['passkey']=sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"]));
				$_SESSION["default"]['customer_id']=$customer["customer_id"];
				$json = array(
						'customerid'=>$customer['customer_id'],
						'headurl'=>empty($customer['headurl'])?null:$customer['headurl'],
						'nickname'=>@$customer['firstname'],
						'qq_sign'=>$qq_sign,
						'wechat'=>$wechat,
						"is_money_pwd"=>$is_money_pwd,
						"left_money"=>empty($money["balance"])?'0.00':@$money["balance"],
						"go_money"=>empty($money["availabe_balance"])?'0.00':@$money["availabe_balance"],
						"phone"=>@$customer["telephone"],
						"card"=>@$customer["card"],
						"realname"=>@$customer["lastname"],
						"is_agent"=>$is_agent,
						"is_dentify"=>$is_dentify,
						"passkey"=>sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"])),     //加密的值
						"is_company"=>$company_status,
						"birthday"=>$customer['birthday'],
						"sex"=>$customer['sex'],
						"rctoken"=>$customer["rctoken"]
				);
				$this->res["data"]=$json;
			}else{
				rollBack();
			}
			return $this->res;
		}

		function pwdLogin(){
			$post = $_POST;
			if(!isset($post['telephone']) || empty($post['telephone']) || !isset($post['password']) || empty($post['password'])){
				$this->res = array(
					'retcode'	=>9000,
					'msg'		=>'参数错误'
				);
				return $this->res;
			}


			$customer = getRow("select * from hb_customer where telephone = '" .$post['telephone']. "'");

			if(empty($customer)){
				$this->res = array(
						'retcode'	=>9001,
						'msg'		=>'该账号不存在'
				);
				return $this->res;
			}

			if($customer['status'] != 1){
				$this->res = array(
						'retcode'	=>9002,
						'msg'		=>'该用户账号异常,请联系客服'
				);
				return $this->res;
			}

			$password_sha1 = sha1($customer['salt'] . sha1($customer['salt'] . sha1($post['password'])));

			if($password_sha1 == $customer['password']){
				//判断是否是代理
				if($customer["merchant_id"] > 1){
					//是商户代理
					$is_agent=2;
				}else if($customer["merchant_id"] == 1){
					//是平台代理
					$is_agent=1;
				}else{
					//不是代理
					$is_agent=0;
				}

				//app登录，剪切板存在邀请码，成为邀请码会员，并返回大转盘链接
				$invitecode_url = "";
				if(isset($post['invitecode']) && !empty($post['invitecode']) && $is_agent == 0){
					$invite = getRow("select * from hb_invitecode where invitecode = '" .$post['invitecode']. "'");

					if($invite){
						$is_agent = 1;//是平台代理
						if(empty($invite['merchant_id'])){
							$invite['merchant_id'] = 1;
						}

						if($invite['merchant_id'] > 1){
							$is_agent = 2;//是商户代理
						}

						exeSql("update hb_customer set merchant_id = '" .$invite['merchant_id']. "',parent_id = '" .$invite['customer_id']. "',invitecode_id = '" .$invite['invitecode_id']. "' where customer_id = '" .$customer['customer_id']. "' and telephone = '" .$customer['telephone']. "'");
						$invitecode_url = $invite['url'];
					}
				}

				//判断是否进行了实名认证
				if(!empty($customer["lastname"]) && $customer["lastname"] != null && !empty($customer["card"]) && $customer["card"] != null){
					//认证了的
					$is_dentify=1;
				}else{
					$is_dentify=0;
				}

				//资金密码
				$is_money_pwd=0;
				if(!empty($customer["money_pwd"]) && $customer["money_pwd"] != null){
					//已设置资金密码
					$is_money_pwd=1;
				}
				//第三方标识  默认是有的
				$qq_sign=1;
				$wechat=1;
				if(empty($customer["qq_openid"]) || $customer["qq_openid"] == null){
					$qq_sign=0;
				}
				if((empty($customer["wechat_openid"]) || $customer["wechat_openid"] == null) && (empty($customer["unionid"]) || $customer["unionid"] == null)){
					$wechat=0;
				}
				//判断是否为企业用户  cgl  2017-2-23  新增
				if($customer["proxy_status"]==1){
					$company_status=1;
				}else{
					$company_status=0;//不是企业用户
				}
				$_SESSION["default"]['passkey']=sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"]));
				$_SESSION["default"]['customer_id']=$customer["customer_id"];

				$login_data = array(
						'ip'			=>$_SERVER["REMOTE_ADDR"],
						'add_time'		=>date('Y-m-d H:i:s')
				);
				$is_login = getRow("SELECT * FROM hb_customer_login WHERE email = '" . $customer["customer_id"] . "' AND ip = :ip",$login_data);
				if(empty($is_login)){
					exeSql("INSERT INTO hb_customer_login SET email = '" . $customer["customer_id"] . "', ip = :ip, total = 1, date_added = :add_time, date_modified = :add_time",$login_data);
				}else{
					exeSql("UPDATE hb_customer_login SET total = (total + 1), date_modified = :add_time WHERE customer_login_id = '" . (int)$is_login['customer_login_id'] . "'",$login_data);
				}

				$json = array(
						'customerid'=>$customer['customer_id'],
						'headurl'=>empty($customer['headurl'])?'http://iwant-u.cn/image/placeholder_circle.png':$customer['headurl'],
						'nickname'=>@$customer['firstname'],
						'qq_sign'=>$qq_sign,
						'wechat'=>$wechat,
						"is_money_pwd"=>$is_money_pwd,
						"left_money"=>empty($money["balance"])?'0.00':@$money["balance"],
						"go_money"=>empty($money["availabe_balance"])?'0.00':@$money["availabe_balance"],
						"phone"=>@$customer["telephone"],
						"card"=>@$customer["card"],
						"realname"=>@$customer["lastname"],
						"is_agent"=>$is_agent,
						"is_dentify"=>$is_dentify,
						"passkey"=>sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"])),     //加密的值
						"is_company"=>$company_status,
						"birthday"=>$customer['birthday'],
						"sex"=>$customer['sex'],
						"rctoken"=>$customer['rctoken'],
						"invitecode_url"=>$invitecode_url
				);
				$this->res["data"]=$json;
			}else{
				$this->res = array(
						'retcode'	=>9003,
						'msg'		=>'密码错误'
				);
				return $this->res;
			}
			return $this->res;
		}

		function noPwdLoginForApp(){
			$post = $_POST;
			$telephone=isset($_POST["telephone"])?$_POST["telephone"]:null;
			$code=isset($_POST["code"])?$_POST["code"]:null;

			if(!isset($post["telephone"]) || empty($post["telephone"]) || !isset($post["code"]) || empty($post["code"])){
				$this->res = array(
					'retcode'	=>1000,
					'msg'		=>"参数错误"
				);
				return $this->res;
			}

			if(!preg_match("/^13[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|14[0-9]{9}$/",$telephone)){
				//你的手机号不正确
				$this->res = array(
						'retcode'	=>9001,
						'msg'		=>"手机格式不正确"
				);
				return $this->res;
			}

			//检查手机号码和验证是否正确
			$is_right=getRow("select * from hb_customer_validate where mobile='".$telephone."' and validate= '".$code."' and (typ='用户登录' or typ='用户注册') ");
			if(!$is_right){
				$this->res = array(
						'retcode'	=>9002,
						'msg'		=>"验证码不正确"
				);
				return $this->res;
			}

			$customer_check = getRow("select * from hb_customer where telephone = '".$telephone."' ");

			//$is_register=1新注册且post没有邀请码；为0时登录或post没有邀请码，移动端不用弹出填写邀请码的输入框
			$is_register = 0;

			$qq = isset($post['qq'])?$post['qq']:"";
			$qq_openid = isset($post['qq_openid'])?$post['qq_openid']:"";
			$wechat = isset($post['wechat'])?$post['wechat']:"";
			$unionid = isset($post['unionid'])?$post['unionid']:"";
			$wechat_openid = isset($post['wechat_openid'])?$post['wechat_openid']:"";
			$headurl = isset($post['headurl'])?$post['headurl']:"http://iwant-u.cn/image/placeholder_circle.png";

			if(!$customer_check){
				$is_register = 1;
				beginTransaction();
				//注册
				$firstname = $telephone;
				if(!empty($post['qq'])){
					$firstname = $post['qq'];
				}
				if(!empty($post['wechat'])){
					$firstname = $post['wechat'];
				}
//				$data=array(
//						"customer_group_id"=>1,
//						"store_id"=>0,
//						"firstname"=>$firstname,
//						"lastname"=>"NULL",
//						"email"=>"NULL",
//						"telephone"=>$telephone,
//						"fax"=>"NULL",
//						"password"=>"NULL",//密码
//						"salt"=>token(9),//salt值
//						"address_id"=>0,
//						"custom_field"=>"NULL",
//						"ip"=>$_SERVER["REMOTE_ADDR"],
//						"status"=>1,
//						"approved"=>1,
//						"token"=>"NULL",
//						"safe"=>0,
//						"qq"=>isset($post['qq'])?$post['qq']:"NULL",
//						"qq_openid"=>isset($post['qq_openid'])?$post['qq_openid']:"NULL",
//						"qq_openid_share"=>"NULL",
//						"rctoken"=>"NULL",//融云的token
//						"wechat"=>isset($post['wechat'])?$post['wechat']:"NULL",
//						"unionid"=>isset($post['unionid'])?$post['unionid']:"NULL",//共同使用登录和注册
//						"wechat_openid"=>isset($post['wechat_openid'])?$post['wechat_openid']:"NULL",
//						"wechat_openid_share"=>"NULL",//web端openid
//						"headurl"=>isset($post['headurl'])?$post['headurl']:"http://iwant-u.cn/image/placeholder_circle.png",//头像
//						"sex"=>1,
//						"card"=>"NULL",//身份证
//						"merchant_id"=>0,
//						"clientid"=>"NULL",
//						"isdisturb"=>0,
//						"sharetimes"=>0,
//						"remark"=>"NULL",
//						"realname_error_num"=>0,
//						"realname_error_time"=>"NULL",
//						"proxy_status"=>0,
//						"is_set_pwd"=>0,//未设置密码
//						"newsletter"=>"NULL",
//						"date_added"=>date("Y-m-d H:i:s",time())
//				);
//				saveData("hb_customer",$data);
				$salt = token(9);

				$ip = $_SERVER["REMOTE_ADDR"];

				$customer_data = array(
                    'firstname'=>$firstname,
                    'telephone'=>$post['telephone'],
                    'salt'=>$salt,
                    'ip'		=>$ip,
                    'qq'=>$qq,
                    'qq_openid'=>$qq_openid,
                    'wechat'=>$wechat,
                    'unionid'=>$unionid,
                    'wechat_openid'=>$wechat_openid,
					'headurl'	=>$headurl
				);
				exeSql("INSERT INTO hb_customer SET customer_group_id = '1',
													store_id = '1',
													sex='1',
													firstname = :firstname,
													lastname = '',
													card = '',
													telephone = :telephone,
													custom_field = '',
													salt = salt,
													password = '',
													ip = :ip,
													qq = :qq,
													qq_openid = :qq_openid,
													wechat = :wechat,
													unionid = :unionid,
													wechat_openid = :wechat_openid,
													newsletter = '0',
													sharetimes = '0',
													realname_error_num = '0',
													status = '1',
													approved = '0',
													birthday = '631123200',
													date_added = NOW(),
													headurl = :headurl,
													is_set_pwd = '0',
													proxy_time = ''",$customer_data);
				$customer_uuid=getLastId();
				if(!$customer_uuid){
					return $this->res = array(
							'retcode'	=>9003,
							'msg'		=>"注册失败，请重新操作"
					);
				}

				//修改融云的token
				$customer_last=getRow("SELECT MAX(customer_id) AS customer_id from `hb_customer` limit 1");
				$customer_id=@$customer_last['customer_id']+2;
				require_once 'lib/Rongyun.php';
				$r=new Rongyun();
				$rongyun_token=$r->getRctoken($customer_id,"张三",isset($post['headurl'])?$post['headurl']:"http://iwant-u.cn/image/placeholder_circle.png");

				//修改
				$customer_update = exeSql("UPDATE `hb_customer` SET customer_id=".$customer_id.",rctoken='".$rongyun_token."' WHERE customer_uuid = '" . (int)$customer_uuid . "' ");
				if($customer_update->rowCount() == 0){
                    rollBack();
					return  $this->res = array(
							'retcode'	=>9004,
							'msg'		=>"注册失败，请重新操作"
					);
				}

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
                    rollBack();
					return $this->res = array(
							'retcode'	=>9005,
							'msg'		=>"注册失败，请重新操作"
					);
				}

				if($this->res['retcode'] == 0){
					commit();
				}else{
					rollBack();
					return $this->res;
				}
			}else{
				if(!empty($qq_openid)){
					exeSql("update hb_customer set qq_openid = '" .$qq_openid. "',qq = '" .$qq. "' where customer_id = '" .$customer_check['customer_id']. "' and telephone = '" .$customer_check['telephone']. "'");
				}

				if(!empty($unionid)){
					exeSql("update hb_customer set unionid = '" .$unionid. "',wechat = '" .$wechat. "' where customer_id = '" .$customer_check['customer_id']. "' and telephone = '" .$customer_check['telephone']. "'");
				}

				if(!empty($wechat_openid)){
					exeSql("update hb_customer set wechat_openid = '" .$wechat_openid. "',wechat = '" .$wechat. "' where customer_id = '" .$customer_check['customer_id']. "' and telephone = '" .$customer_check['telephone']. "'");
				}
			}


			$customer = getRow("select * from hb_customer where telephone = '".$telephone."' ");

			//账号被禁用
			if($customer['status'] != 1){
				$this->res = array(
						'retcode'	=>9006,
						'msg'		=>'该用户账号异常,请联系客服'
				);
				return $this->res;
			}

			$money=getRow("select * from hb_balance where customer_id = '".$customer["customer_id"]."' ");
			//判断是否是代理
			if($customer["merchant_id"] > 1){
				//是商户代理
				$is_agent=2;
			}else if($customer["merchant_id"] == 1){
				//是平台代理
				$is_agent=1;
			}else{
				//不是代理
				$is_agent=0;
			}

			//app登录，剪切板存在邀请码，成为邀请码会员，并返回大转盘链接
			$invitecode_url = "";
			if(isset($post['invitecode']) && !empty($post['invitecode']) && $is_agent == 0){
				$is_register = 0;
				$invite = getRow("select * from hb_invitecode where invitecode = '" .$post['invitecode']. "'");

				if($invite){
					$is_agent=1;
					if(empty($invite['merchant_id'])){
						$invite['merchant_id'] = 1;
					}

					if($invite['merchant_id'] > 1){
						$is_agent=2;
					}
					exeSql("update hb_customer set merchant_id = '" .$invite['merchant_id']. "',parent_id = '" .$invite['customer_id']. "',invitecode_id = '" .$invite['invitecode_id']. "' where customer_id = '" .$customer['customer_id']. "' and telephone = '" .$customer['telephone']. "'");
					$invitecode_url = $invite['url'];
				}
			}

			//判断是否进行了实名认证
			if(!empty($customer["lastname"]) && $customer["lastname"] != null && !empty($customer["card"]) && $customer["card"] != null){
				//认证了的
				$is_dentify=1;
			}else{
				$is_dentify=0;
			}

			//资金密码
			$is_money_pwd=0;
			if(!empty($customer["money_pwd"]) && $customer["money_pwd"] != null){
				//已设置资金密码
				$is_money_pwd=1;
			}

			//第三方标识  默认是有的
			$qq_sign=1;
			$wechat=1;
			if(empty($customer["qq_openid"]) || $customer["qq_openid"] == null){
				$qq_sign=0;
			}
			if((empty($customer["wechat_openid"]) || $customer["wechat_openid"] == null) && (empty($customer["unionid"]) || $customer["unionid"] == null)){
				$wechat=0;
			}

			//判断是否为企业用户  cgl  2017-2-23  新增
			if($customer["proxy_status"]==1){
				$company_status=1;
			}else{
				$company_status=0;//不是企业用户
			}
			$_SESSION["default"]['passkey']=sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"]));
			$_SESSION["default"]['customer_id']=$customer["customer_id"];

			$login_data = array(
				'ip'	=>$_SERVER["REMOTE_ADDR"],
				'add_time'	=>date('Y-m-d H:i:s')
			);
			$is_login = getRow("SELECT * FROM hb_customer_login WHERE email = '" . $customer["customer_id"] . "' AND ip = :ip",$login_data);
			if(empty($is_login)){
				exeSql("INSERT INTO hb_customer_login SET email = '" . $customer["customer_id"] . "', ip = :ip, total = 1, date_added = :add_time, date_modified = :add_time",$login_data);
			}else{
				exeSql("UPDATE hb_customer_login SET total = (total + 1), date_modified = :add_time WHERE customer_login_id = '" . (int)$is_login['customer_login_id'] . "'",$login_data);
			}

			$json = array(
					'customerid'=>$customer['customer_id'],
					'headurl'=>empty($customer['headurl'])?'http://iwant-u.cn/image/placeholder_circle.png':$customer['headurl'],
					'nickname'=>@$customer['firstname'],
					'qq_sign'=>$qq_sign,
					'wechat'=>$wechat,
					"is_money_pwd"=>$is_money_pwd,
					"left_money"=>empty($money["balance"])?'0.00':@$money["balance"],
					"go_money"=>empty($money["availabe_balance"])?'0.00':@$money["availabe_balance"],
					"phone"=>@$customer["telephone"],
					"card"=>@$customer["card"],
					"realname"=>@$customer["lastname"],
					"is_agent"=>$is_agent,
					"is_dentify"=>$is_dentify,
					"passkey"=>sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"])),     //加密的值
					"is_company"=>$company_status,
					"birthday"=>$customer['birthday'],
					"sex"=>$customer['sex'],
					"rctoken"=>$customer['rctoken'],
					"invitecode_url"=>$invitecode_url,
					"is_register"=>$is_register
			);
			$this->res["data"]=$json;

			return $this->res;

		}


		/** 
		 *  @desctiption 用户领取优惠券活动
		 *  @param      string  coupon_id  优惠券id
		 *  @param      string  customerid  登录的用户id
		 *  @return     string  data  接口调用成功与否的json字符串
		 *  @author     godloveein@yeah.net
		 *  @d/t        2017-04-14
		 */
		public function sendCoupon(){
			$now = time();
			$data = array();
			// 验证参数
			if(!isset($_POST['customer_id']) || !isset($_POST['coupon_id'])){
				$data = array('code'=>1,'msg'=>'缺少参数','data'=>array());
				echo json_encode($data);exit;
			}
			$customer_id = htmlspecialchars($_POST['customer_id']);
			$coupon_id = htmlspecialchars($_POST['coupon_id']);
			$cdcode = isset($_POST['cdcode'])?$_POST['cdcode']:"";

			//判断是多张优惠券还是单张
			if(strpos($_POST['coupon_id'],',')){
				$arr=explode(',', $_POST['coupon_id']);
				$c=0;
				foreach ($arr as $key => $coupon_id) {
					// 发送优惠券
					$coupon_info = getRow("SELECT coupon_id,name,discount,date_start,date_end,release_total,get_total,status,code FROM hb_coupon WHERE coupon_id=".$coupon_id);
					if ($coupon_info['date_end'] < $now) {
						// 是否已过期
					}else if ( 0 >= ($coupon_info['release_total']-$coupon_info['get_total']) ) {
						// 已领完
					}else if (($coupon_info['date_end'] > $now) && ($coupon_info['status'] == 3)) {
						// 已作废
					}else{	
						// 防止多次领取
						if(@getRow("SELECT * FROM hb_coupon_customer WHERE coupon_id=".$coupon_id." and customer_id=".$customer_id)){
						}
						else{
							if(saveData("hb_coupon_customer",array('coupon_id'=>$coupon_id,
									'customer_id'=>$customer_id,
									'date_added'=>$now,
									'status'=>0,
									'date_start'=>$coupon_info['date_start'],
									'date_end'=>$coupon_info['date_end']))){
							// 领取成功
							exeSql("UPDATE hb_coupon SET get_total= get_total+1 WHERE coupon_id=".$coupon_id);
							}
							$code = 0;
							$c++;
						}
					}
				}
				$code = 0;
				if($c == 0){
					$msg = "您已经领过大礼包咯"; //return $msg;
				}else{
					$msg = "一共成功领取到".$c."张优惠券"; //return $msg;
				}
			}else{

				// 发送优惠券
				$coupon_info = getRow("SELECT coupon_id,name,discount,date_start,date_end,release_total,get_total,status,code FROM hb_coupon WHERE coupon_id=".$coupon_id);
				if ($coupon_info['date_end'] < $now) {
					// 是否已过期
					$code = 1;
					$msg = '已过期';
				}else if ( 0 >= ($coupon_info['release_total']-$coupon_info['get_total']) ) {
					// 已领完
					$code = 1;
					$msg = '已领完';
				}else if (($coupon_info['date_end'] > $now) && ($coupon_info['status'] == 3)) {
					// 已作废
					$code = 1;
					$msg = '已作废';
				}else{	

					// 防止多次领取
					if(@getRow("SELECT * FROM hb_coupon_customer WHERE coupon_id=".$coupon_id." and customer_id=".$customer_id)){
						$code = 1;
						$msg = "您已经领取过了";
					}
					else{
						//h5端判断是否需要兑换码
						if(isset($_POST['type']) && @$_POST['type']=='h5'){
							if($coupon_info['code']){
								// 需要输入兑换码才可以领取的优惠券
								$code = 5;
								$msg = '请输入兑换码';
							}else{
								//不需要兑换码直接领取成功
								if(saveData("hb_coupon_customer",array('coupon_id'=>$coupon_id,
										'customer_id'=>$customer_id,
										'date_added'=>$now,
										'status'=>0,
										'date_start'=>$coupon_info['date_start'],
										'date_end'=>$coupon_info['date_end']))){
									// 领取成功
									exeSql("UPDATE hb_coupon SET get_total= get_total+1 WHERE coupon_id=".$coupon_id);
									}
									$code = 0;
									$msg = "领取成功";
							}
						}else{
							//如果存在cdcode，则是通过兑换码进入的
							if(isset($_POST['cdcode']) ){
								//判断该code是否存在于该优惠券中
								//把code根据 ','分割
								$code_arr=explode(',', $coupon_info['code']);
								foreach ($code_arr as $key => $value) {
									$code_arr[$key]=trim($value," ");
								}
								if(in_array($_POST['cdcode'],$code_arr)){
									//存在，不处理
									if(saveData("hb_coupon_customer",array('coupon_id'=>$coupon_id,
										'customer_id'=>$customer_id,
										'date_added'=>$now,
										'status'=>0,
										'date_start'=>$coupon_info['date_start'],
										'date_end'=>$coupon_info['date_end']))){
									// 领取成功
									exeSql("UPDATE hb_coupon SET get_total= get_total+1 WHERE coupon_id=".$coupon_id); 
									}
									$code = 0;
									$msg = "领取成功";
								}else{
									//不存在，返回错误
									$code = 6;
									$msg = "兑换劵错误";
								}	
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
								$code = 0;
								$msg = "领取成功";
							}
							
						}
					}
				}	
			}
			
			$data = array('code'=>$code,'msg'=>$msg,'data'=>array());
			echo json_encode($data);exit;
		}


		/**
		 *  @decription 根据优惠券id获取优惠券详情
		 *  @param      string  coupon_id  优惠券id
		 *  @return     string  coupon_info  优惠券详情json字符串
		 *  @author     godloveein@yeah.net
		 *  @d/t        2017-04-14
		 */
		public function getCouponById(){
			$data = array('code'=>0,'msg'=>'success','data'=>array());
			if(empty($_POST['coupon_id'])){
				$data = array('code'=>1,'msg'=>'缺少参数','data'=>array());
			}else{
				if(strpos($_POST['coupon_id'],',')){
					//说明有多张优惠券
					$arr=explode(',', $_POST['coupon_id']);
					$coupon_info=array();
					$coupon_info['discount']=0;
					foreach($arr as $k=>$v){
						$info = getRow("SELECT * FROM hb_coupon WHERE coupon_id=".$v);
						$coupon_info['discount']+=@(int)$info['discount'];
					}
					$coupon_info['name']=@$info['name'];
				}else{
					$coupon_id = htmlspecialchars($_POST['coupon_id']);
					$coupon_info = getRow("SELECT * FROM hb_coupon WHERE coupon_id=".$coupon_id);
					$coupon_info['discount'] = @(int)$coupon_info['discount'];
					
				}
				$data = array('code'=>0,'msg'=>'success','data'=>$coupon_info);
				
			}
			echo json_encode($data);exit;
		}



		/*
		 * 微信、qq登录
		 * wangzhichao 17.4.14
		 */
		function thirdLogin(){
			$post = $_POST;
			$customer = array();
			if(!empty($post['qq_openid'])){
				$customer = getRow("select * from hb_customer where qq_openid = '" .$post['qq_openid']. "'");
			}else if(!empty($post['unionid'])){
				$customer = getRow("select * from hb_customer where unionid = '" .$post['unionid']. "'");
			}else if(!empty($post['wechat_openid'])){
				$customer = getRow("select * from hb_customer where wechat_openid = '" .$post['wechat_openid']. "'");
			}

			if(empty($customer)){
				$data = array('is_register'=>1);
				return $this->res = array(
					'retcode'	=>0,
					'msg'		=>'success',
					'data'		=>$data
				);
			}


			if($customer['status'] != 1){
				return $this->res = array(
					'retcode'	=>4004,
					'msg'		=>'该用户账号异常,请联系客服'
				);
			}

			$money=getRow("select * from hb_balance where customer_id = '".$customer["customer_id"]."' ");
			//判断是否是代理
			if($customer["merchant_id"] > 1){
				//是商户代理
				$is_agent=2;
			}else if($customer["merchant_id"] == 1){
				//是平台代理
				$is_agent=1;
			}else{
				//不是代理
				$is_agent=0;
			}

			//app登录，剪切板存在邀请码，成为邀请码会员，并返回大转盘链接
			$invitecode_url = "";
			if(isset($post['invitecode']) && !empty($post['invitecode']) && $is_agent == 0){
				$invite = getRow("select * from hb_invitecode where invitecode = '" .$post['invitecode']. "'");

				if($invite){
					$is_agent = 1;//是平台代理
					if(empty($invite['merchant_id'])){
						$invite['merchant_id'] = 1;
					}

					if($invite['merchant_id'] > 1){
						$is_agent = 2;//是商户代理
					}
					exeSql("update hb_customer set merchant_id = '" .$invite['merchant_id']. "',parent_id = '" .$invite['customer_id']. "',invitecode_id = '" .$invite['invitecode_id']. "' where customer_id = '" .$customer['customer_id']. "' and telephone = '" .$customer['telephone']. "'");
					$invitecode_url = $invite['url'];
				}
			}

			//判断是否进行了实名认证
			if(!empty($customer["lastname"]) && $customer["lastname"] != 'NULL' && !empty($customer["card"]) && $customer["card"] != 'NULL'){
				//认证了的
				$is_dentify=1;
			}else{
				$is_dentify=0;
			}

			//资金密码
			$is_money_pwd=0;
			if(!empty($customer["money_pwd"]) && $customer["money_pwd"] != 'NULL'){
				//已设置资金密码
				$is_money_pwd=1;
			}

			//第三方标识  默认是有的
			$qq_sign=1;
			$wechat=1;
			if(empty($customer["qq_openid"]) || $customer["qq_openid"] == 'NULL'){
				$qq_sign=0;
			}
			if((empty($customer["wechat_openid"]) || $customer["wechat_openid"] == 'NULL') && (empty($customer["unionid"]) || $customer["unionid"] == 'NULL')){
				$wechat=0;
			}

			//判断是否为企业用户  cgl  2017-2-23  新增
			if($customer["proxy_status"]==1){
				$company_status=1;
			}else{
				$company_status=0;//不是企业用户
			}
			$_SESSION["default"]['passkey']=sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"]));
			$_SESSION["default"]['customer_id']=$customer["customer_id"];

			$login_data = array(
					'ip'		=>$_SERVER["REMOTE_ADDR"],
					'add_time'		=>date('Y-m-d H:i:s')
			);
			$is_login = getRow("SELECT * FROM hb_customer_login WHERE email = '" . $customer["customer_id"] . "' AND ip = :ip",$login_data);
			if(empty($is_login)){
				exeSql("INSERT INTO hb_customer_login SET email = '" . $customer["customer_id"] . "', ip = :ip, total = 1, date_added = :add_time, date_modified = :add_time",$login_data);
			}else{
				exeSql("UPDATE hb_customer_login SET total = (total + 1), date_modified = :add_time WHERE customer_login_id = '" . (int)$is_login['customer_login_id'] . "'",$login_data);
			}

			$json = array(
					'customerid'=>$customer['customer_id'],
					'headurl'=>empty($customer['headurl'])?'http://iwant-u.cn/image/placeholder_circle.png':$customer['headurl'],
					'nickname'=>@$customer['firstname'],
					'qq_sign'=>$qq_sign,
					'wechat'=>$wechat,
					"is_money_pwd"=>$is_money_pwd,
					"left_money"=>empty($money["balance"])?'0.00':@$money["balance"],
					"go_money"=>empty($money["availabe_balance"])?'0.00':@$money["availabe_balance"],
					"phone"=>@$customer["telephone"],
					"card"=>@$customer["card"],
					"realname"=>@$customer["lastname"],
					"is_agent"=>$is_agent,
					"is_dentify"=>$is_dentify,
					"passkey"=>sha1(md5($customer["customer_id"].$customer["password"].$customer["salt"])),     //加密的值
					"is_company"=>$company_status,
					"birthday"=>$customer['birthday'],
					"sex"=>$customer['sex'],
					"rctoken"=>$customer['rctoken'],
					"invitecode_url"=>$invitecode_url,
					'is_register'=>0
			);
			$this->res["data"]=$json;

			return $this->res;
		}

		/*
		 *找回密码
		 * 王志超 17.4.14
		 */
		function findPwd(){
			$post = $_POST;
			if(empty($post['telephone']) || empty($post['password'])){
				$this->res = array(
						'retcode'	=>1000,
						'msg'		=>'参数错误'
				);
				return $this->res;
			}

			$customer=getRow("select * from hb_customer where telephone= '".$post['telephone']."' ");
			if(empty($customer)){
				$this->res = array(
						'retcode'	=>9000,
						'msg'		=>'账号不存在'
				);
				return $this->res;
			}

			$change_pwd=sha1($customer['salt'] . sha1($customer['salt'] . sha1($post['password'])));
			exeSql("UPDATE hb_customer SET password = '" . $change_pwd . "' WHERE `customer_id` = '" . $customer['customer_id'] . "'");

			return $this->res;
		}

		/*
		 *设置密码
		 * 王志超 17.4.14
		 */
		function resetPwd(){
			//需要验证customerid和passkey
			$base = (array)new base();
			if($base['res']['retcode'] > 0){
				return $base['res'];
			}

			$post = $_POST;
			if(!isset($post['new_password']) || empty($post['new_password']) || !isset($post['type']) || empty($post['type'])){
				$this->res = array(
						'retcode'	=>1000,
						'msg'		=>'参数错误'
				);
				return $this->res;
			}

			$customer=getRow("select * from hb_customer where customer_id= '".$post['customerid']."' ");
			if(empty($customer)){
				$this->res = array(
						'retcode'	=>9000,
						'msg'		=>'账号不存在'
				);
				return $this->res;
			}

			//1设置密码，2重置密码
			if($post['type'] == 1){
				if(empty($customer['password']) || $customer['password'] == 'NULL'){
					$change_pwd=sha1($customer['salt'] . sha1($customer['salt'] . sha1($post['new_password'])));
					exeSql("UPDATE hb_customer SET password = '" . $change_pwd . "' WHERE `customer_id` = '" . $customer['customer_id'] . "'");
				}else{
					$this->res = array(
							'retcode'	=>9001,
							'msg'		=>'账号存在密码，不能设置密码'
					);
					return $this->res;
				}
			}else{
				if(!isset($post['old_password']) || empty($post['old_password'])){
					$this->res = array(
							'retcode'	=>1000,
							'msg'		=>'参数错误'
					);
					return $this->res;
				}
				$old_change_pwd = sha1($customer['salt'] . sha1($customer['salt'] . sha1($post['old_password'])));
				if($old_change_pwd != $customer['password']){
					$this->res = array(
							'retcode'	=>9000,
							'msg'		=>'当前密码错误'
					);
					return $this->res;
				}

				$change_pwd=sha1($customer['salt'] . sha1($customer['salt'] . sha1($post['new_password'])));
				exeSql("UPDATE hb_customer SET password = '" . $change_pwd . "' WHERE `customer_id` = '" . $customer['customer_id'] . "'");
			}

			$this->res['data']['passkey'] = sha1(md5($customer["customer_id"].$change_pwd.$customer["salt"]));
			return $this->res;
		}

		/*
		 *掌门人模块
		 * zxx 2017-5-2 
		 */
		function getHeadmanInfo(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				if(!empty($_POST['customerid'])){
					//获取用户信息
					$customerinfo=getData("select type,invitecode_id from hb_invitecode where customer_id=".$_POST['customerid']);
					$invitecode="";
					if(!empty($customerinfo)){
						foreach($customerinfo as $k=>$v){
							$invitecode.=$v['invitecode_id'].",";
						}
						$invitecode=trim($invitecode,',');
					}
					if(!empty($customerinfo)){
						if(false){
							//不是掌门人
							$this->res['msg']='没有粉丝';
							$this->res['retcode']='0';
						}else{
							//分页
							$page=isset($_POST['page'])?$_POST['page']:1;
							$start=($page-1)*10;

							//查出掌门人粉丝的广告
							$banner_id=25;
							$banner_info=getRow("select image,title,item_id,type,subtype,link from hb_banner_image where status=1 and banner_id=".$banner_id);
							if(!empty($banner_info)){
					    			if($banner_info["type"]==0){
					    				if($banner_info["subtype"]==0){
					    					$banner_info["ban_type"]=0; //分类
					    				}elseif ($v["subtype"]==1) {
					    					$banner_info["ban_type"]=3; //产品
					    				}elseif ($v["subtype"]==3) {
					    					$banner_info["ban_type"]=4; //团购
					    				}elseif ($v["subtype"]==4) {
					    					$banner_info["ban_type"]=5; //秒杀
					    				}
					    			}else if($banner_info["type"]==1){
					    				//外部链接
					    				$banner_info["ban_type"]=1;
					    				if($banner_info["link"]=="http://iwant-u.cn/appinlinehtml/index.html"){
					    					$banner_info["ban_type"]=2;//会员链接
					    				}
					    			}
					    			unset($banner_info["subtype"]);
					    			unset($banner_info["type"]);
					    		}

							// $info=getData("select 
							// 	c.firstname,
							// 	ct.amount,
							// 	count(c.customer_id) as sum,
							// 	sum(ct.amount) as amount
							// 	from hb_customer as c,hb_order as o ,hb_customer_transaction as ct where o.order_status_id=5 and o.customer_id=c.customer_id and ct.order_id=o.order_id and  ct.type=3 and invitecode_id=".$customerinfo["invitecode_id"]." group by c.customer_id limit ".$start." , 10");
							$info=getData("select c.firstname,count(o.order_id) as sum ,FORMAT(sum(ct.amount),2) as amount from hb_customer as c 
										left join hb_order as o on c.customer_id=o.customer_id and o.order_status_id in (2,3,4,5,11) 
										left join hb_customer_transaction as ct on o.order_id=ct.order_id and ct.type in (3,7) where invitecode_id in (".$invitecode.")  group by c.customer_id order by sum desc ");
							$info1=array_slice($info, $start,10);
							if(empty($info)){
								//没有粉丝
								$this->res['msg']='没有粉丝';
								$this->res['retcode']='0';
							}else{

								foreach ($info1 as $key => $value) {
									if(preg_match("/^1[34578]\d{9}$/", $value['firstname'])){
										$info1[$key]['firstname']=substr_replace($value['firstname'], '****', 3,4);
									}
									if($value['amount']==''){
										$info1[$key]['amount']="0.00";
									}
								}
								//算出总收入
								$money=0;
								foreach ($info as $key => $value) {
									$money+=$value['amount'];
								}
								$this->res['msg']='查询成功';
								$this->res['retcode']='0';
								$this->res['data']['customerlist']=$info1;
								$this->res['data']['customernum']=count($info);
								$this->res['data']['amount_all']=$money;
								//var_dump($banner_info);exit;
								if(!empty($banner_info)){
									foreach($banner_info as $k=> $v){
										$this->res['data']['banner'][$k]=$v;
									}
								}
							}
						}
					}else{
						//不是会员
						$this->res['msg']='没有粉丝';
						$this->res['retcode']='0';
					}
				}else{
					$this->res['msg']='请求参数错误';
					$this->res['retcode']='1000';	
				}

			}else{
				$this->res['msg']='请求方式错误';
				$this->res['retcode']='1180';				
			}

			$this->res['data']['h5url']="http://haiqihuocang.cn/web/links/zhangmenren/index.html";
			return $this->res;
		}
		/**
		 * 是否实名认证 cgl 2017-5-3
		 * @return boolean [description]
		 */
		function is_dentify(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				$user=getRow("select * from hb_customer where customer_id = '".$this->customer_id."' ");

				 //判断是否进行了实名认证
	            if(@$user["lastname"]!=null && @$user["lastname"]!="NULL"  && @$user["card"]!=null && @$user["card"]!="NULL"){  
	                //认证了的
	                $is_dentify=1;
	            }else{
	                 $is_dentify=0;
	            }
	            $this->res['code']=$is_dentify;
                $this->res['lastname'] = isset($user["lastname"]) ? $user["lastname"] : '';
            }else{
				$this->res['msg']='请求方式错误';
				$this->res['retcode']='1180';				
			}
			return $this->res;
		}
		/**
		 * 判断用户是不是在活动范围内
		 * zxx 2017-5-27
		 */
		function checkRedPacket(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				if(!empty($_POST['customerid'])){
					//判断用户有没有资格领取
                    $customer_info=getRow("select date_added from hb_customer where customer_id= ".$_POST['customerid']);
                    $redpacket_info=getRow("select * from hb_redpacket where   status=1");
                    $redpacket_customer_info=getRow("select id from hb_redpacket_customer where (customer_id=".$_POST['customerid'].")");
                    if(!empty($redpacket_info)){
	                    //比对用户的注册时间
	                    if(@$customer_info['date_added']>=$redpacket_info['date_start'] && $customer_info['date_added']<=$redpacket_info['date_end']){

	                    	//检查现金红包是否还有
	                    	if($redpacket_info['relase_times']-$redpacket_info['get_times']>0){
	                    		
	                    		//检查用户是否已经领过
	                    		if(empty($redpacket_customer_info)){
	                    			$this->res['msg']='可以领取';
	                    			//$url=str_replace('new', $redpacket_info['url']);  
	                    			$this->res['data']['url']=$redpacket_info['url'];
									$this->res['retcode']=0;
	                    		}else{
	                    			$this->res['msg']='红包已经领过';
									$this->res['retcode']=1002;
	                    		}
	                    		
	                    	}else{
								$this->res['msg']='红包已经领完';
								$this->res['retcode']=1004;
	                    	}
	                    }else{
	                    	$this->res['msg']='您已经是老用户咯，不能在参加了';
							$this->res['retcode']=1005;
	                    }
                    }else{
                    	$this->res['msg']='现金红包已经失效或抢光了';
							$this->res['retcode']=1001;
                    }
				}else{
					$this->res['msg']='请求参数错误';
					$this->res['retcode']=1000;
				}
			}else{
				$this->res['msg']='请求方式错误';
				$this->res['retcode']=1180;
			}
			return $this->res;
		}

		/**
		 * 客户领取支付宝红包列表
		 * zxx 207-5-31
		 */
		
		function getRedpacketLIst(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				if(isset($_POST['customerid'])){
					$money=getRow("select get_amount from hb_redpacket_customer where customer_id=".$_POST['customerid']);
					$money=@$money['get_amount'];
				}else{
					$money=0;
				}
				$redpacket_info=getData("select get_amount,c.firstname,c.headurl from hb_redpacket_customer as rc ,hb_redpacket as r,hb_customer as c where rc.customer_id=c.customer_id and r.redpacket_id=rc.redpacket_id and r.status=1 and rc.status=1 order by get_amount desc limit 10");
				$arr=array();
				foreach ($redpacket_info as $k => $v){
					if(preg_match("/^1[34578]\d{9}$/", $v['firstname'])){
						$redpacket_info[$k]['firstname']=substr_replace($v['firstname'], '****', 3,4);
					}else{
						 $len = mb_strlen($redpacket_info[$k]['firstname'],'utf-8');
						 $redpacket_info[$k]['firstname'] =mb_substr($redpacket_info[$k]['firstname'],1,$len,'utf-8');
						 $redpacket_info[$k]['firstname']="*".$redpacket_info[$k]['firstname'];
					}
				}
				$this->res['data']=$redpacket_info;
				$this->res['money']=$money;
			}else{
				$this->res['msg']='请求方式错误';
				$this->res['retcode']=1180;
			}
			return $this->res;
		}

		/**
		 * app内嗨友打开链接成为会员
		 * zxx 2017-6-6
		 */
		function becomeInvite(){
			if($_SERVER['REQUEST_METHOD']=='POST'){
				if(isset($_POST['customerid'])){

					$merchant_id=getRow("select merchant_id from hb_customer where customer_id='".$_POST['customerid']."' ");
					if(@$merchant_id['merchant_id']<1){
						//非会员
						$proxyid=isset($_POST['proxyid'])?$_POST['proxyid']:0;
						$msg=getRow("select invitecode_id from hb_invitecode where customer_id='".$proxyid."' order by invitecode_id desc ");
						$invitecode_id=@$msg['invitecode_id'];
						if($invitecode_id>0){
							//说明有邀请码
							exeSql("update hb_customer set merchant_id=1,parent_id='".$proxyid."', invitecode_id='".$invitecode_id."' where customer_id=".$_POST['customerid']);
							//没有邀请码
							$this->res['msg']='成为会员成功';
							$this->res['retcode']=0;
						}else{
							//没有邀请码
							$this->res['msg']='分享人不是会员';
							$this->res['retcode']=0;
						}
					}else{
						$this->res['msg']='已经是会员了';
						$this->res['retcode']=0;
					}
					
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
