<?php

function ArrToStr($array)
        {
            $str="";
            foreach ($array as $v) {
                # code...
                if(is_array($v))
                {
                    $str.=ArrToStr($v);
                }
                else
                    $str.=$v;
            }
            return $str;
        }

        

	class wuliu{


	
/**
     * 提供给后台查询信息
     */
    public function sel_admin_ship($orderid){
        //根据快递订单号和用户编号查询
       

        $order=getRow("select ship_id,ship_order_no from hb_order where order_id='".$orderid."'");
        if(empty($order))
        	return array();

        $shipcode=getRow("select cod from hb_shipping where id='".$order["ship_id"]."'");
        
        $typeCom=@$shipcode["cod"];
        $typeNu=@$order["ship_order_no"];
        // $typeCom="ZTO";//STO
        // $typeNu ='533642011285';//229973531902
        if(!isset($typeCom) || !isset($typeNu)){
        	return array();
        }
        $requestData= "{'OrderCode':'','ShipperCode':'$typeCom','LogisticCode':'$typeNu'}";


        $id="1263768";
        $key="36278c0d-8168-4f05-9ef6-b0d749cfc47a";
        $url="http://api.kdniao.cc/api/dist";

        $datas = array(
            'EBusinessID' => $id,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $key);
        $result=$this->sendPost($url, $datas);   
        
        //根据公司业务处理返回的信息......
        $res=json_decode($result,"json");
        // $res["retcode"]=0;
        $sort = array(
        'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
        'field'     => 'AcceptTime',       //排序字段  
        );
        $arrSort = array();
        if(!empty($res["Traces"])){
            foreach($res["Traces"] AS $uniqid => $row){  
                foreach($row AS $key=>$value){  
                    $arrSort[$key][$uniqid] = $value;  
                }  
            }  
            if($sort['direction']){  
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $res["Traces"]);  
            }
        }
        // //快递公司信息
        // $res["company"]=!empty($shippingcompany[0]["com"])?@$shippingcompany[0]["com"]:"";
        // $res["phone"]=!empty($shippingcompany[0]["ship_phone"])?@$shippingcompany[0]["ship_phone"]:"";
        // $res["img"]=!empty($shippingcompany[0]["ship_img"])?HTTP_SERVER.'image/'.$shippingcompany[0]["ship_img"]:"";
        return $res;
    }

    /**
     *  post提交数据 
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据 
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();   
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);      
        }   
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(!isset($url_info['port']))
        {
            $url_info['port']=80;   
        }
        // echo $url_info['port'];
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }

        return $gets;
        fclose($fd);  
        
    }

    /**
     * 电商Sign签名生成
     * @param data 内容   
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }

    }