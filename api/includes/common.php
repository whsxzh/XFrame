<?php


/* $_POST['user_id']=$id;
                     $_POST['last_money']=$acout['money'];
                     $_POST['last_real_money']=$acout['real_money'];
                     $_POST['rel_user_id']=$rel_user_id;*/



function isVip($id,$refresh=false)
{
    global $data;
      //如果用户的财务账户不存在 创建用户财务账户

            $dt=getRow("select merchant_id from hb_customer where customer_id=$id",600,$refresh);
            if(empty($dt))
            {
                return false;
            }
            elseif($dt['merchant_id']>0)
                return true;
            else
                return false;

}

function getUser()
{
   $table = 'users';
   $sql = 'SELECT * FROM `' . $table . '` WHERE mobile = :mobile AND hashed_password = :hashedpassword LIMIT 1;'; //!!
   if ($row =getRow($sql,$_POST)) {
       

        $dt=array( 
        	'id' => $row['id'],
 			'login_ip' => $_SERVER["REMOTE_ADDR"],
        	'login_date' => time()
        	);
  
        saveData($table,$dt);

        return $row;
	}

	return false;
}


function isDate( $dateString ) {
    return strtotime( date('Y-m-d', strtotime($dateString)) ) === strtotime( $dateString );
}

function checkPost($str)
{
     global $data;
    $keys=explode(",", $str);
    foreach ($keys as $key) {
        # code...
        if(!isset($_POST[$key]) || empty($_POST[$key]))
         {
            $data['code']=0;
            $data['msg']=$key."参数必需输入";
            echo json_encode($data);
            exit;
            return false;
         }   
    }
    return true;
}

//实名认证
function juhecurl($url,$params=false,$ispost=0){
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
    return $response;
}

/*
 * 发送验证码
 * 王志超 17.4.10
 * content 短信内容
 * telephone 电话
 * rand 验证码
 * typ验证码类型
 */
function sendMsg($telephone,$content,$rand,$typ){
    include_once '../iwantcdm/lib/sms.php';
    $sms=new Sms();

    if(!preg_match("/^13[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|14[0-9]{9}$/",$telephone)){
        //你的手机号不正确
        return array('retcode'=>8000,'msg'=>'手机号不正确');
    }else{
        //发送
        $return=$sms->sendSingleMt($telephone,$content);
        $res=json_decode($return,"json");

        //获取发送到底是否成功
        if(!$res || @$res["retcode"]!=0){
            return array('retcode'=>8002,'msg'=>'发送失败');
        }

        /**
         * 检测这个手机号码是否已经发送过一次
         */
        $is_find=getRow("select * from hb_customer_validate where mobile = '" .$telephone. "' and typ='" .$typ. "' and statu=0");
        if($is_find){
            exeSql("update hb_customer_validate set validate='" .$rand. "',statu=0,dat=now() where mobile = '" .$telephone. "' and typ='" .$typ. "'");
        }else{
            exeSql("insert into hb_customer_validate set mobile = '" .$telephone. "',validate='" .$rand. "',typ='" .$typ. "',statu=0,dat=now()");
        }
        return array('retcode'=>0,'msg'=>'success');
    }
}
