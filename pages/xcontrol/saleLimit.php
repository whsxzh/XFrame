<?php
	//面向对象的control 类
include "xcontrol/base.php";
class saleLimit extends base{
	function __construct() 
	{
       parent::__construct();
	   $this->passkey=@$_SESSION["default"]['passkey'];
	   $this->customer_id=@$_SESSION["default"]['customer_id'];
   	}
   	function index(){
   		$this->getMenu();
   		$merchant_id=$_SESSION['merchant_id']; 
	    $page=isset($_GET['page'])?$_GET['page']:1;
	    if($page<1){
	        $page=1;
	    }
        $size=10;
      	$start=($page-1)*$size;
		  //获取到所有的活动
      	$activity=getData("select * from hb_salelimit where  merchant_id=".$merchant_id." order by sort_order ");
      	$count1=count($activity);
      	//获取到所有的活动
      	$activity=getData("select * from hb_salelimit where  merchant_id=".$merchant_id." order by date_added desc limit ".$start.",".$size."");
      	if($activity){
      		foreach($activity as $k=>$v){
      			$product_count=getRow("select count(*) as count from hb_product_sale where sale_id= '".$v["sale_id"]."' and status=1 ");
      			$activity[$k]["count"]=$product_count["count"];
      		}
      	}

	    if($count1<$size){
	    	$this->getPages($page,1);
	    }else{
	        $this->getPages($page,ceil($count1/$size));
	    }
      	$this->res['curpage']=$page;
      	$this->res['activity']=$activity;
	  	return $this->res;
   	}
   	/**
   	 * 封装后台的图片地址 cgl 2017-2-8
   	 */
	function admindefault($imgbefore) {
	    if((strpos($imgbefore, 'http://') === false) || (strpos($imgbefore, 'http://') > 0)) {
	      $imgafter = TEST_IP . '/image/' . $imgbefore;
	    } else {
	      $imgafter = $imgbefore;
	    }
	    return $imgafter;
	}
	/**
	 * 添加限时抢购活动
	 */
	function addLimit(){
		$data=array(
			"sale_name"=>$_POST["name"],
			"merchant_id"=>$_SESSION["merchant_id"],
			"date_start"=>$_POST["date_start"],//$this->timechange(
			"date_end"=>$_POST["date_end"],
			"date_added"=>date("Y-m-d H:i:s"),
			"date_modified"=>date("Y-m-d H:i:s")
			);
		if($_POST["date_start"]>$_POST["date_end"]){
	      	echo "开始时间必须小于结束时间";exit();
	    }
		if($_POST["date_end"]<date("Y-m-d H:i:s")){
	      	echo "结束时间必须大于当前时间";exit();
	    }
    $is_add_sale=getRow("select * from hb_salelimit where status=1 and date_format(date_start, '%Y-%m-%d %H:%i') = '".date("Y-m-d H:i",strtotime($_POST["date_start"]))."'  ");
    if($is_add_sale){
      echo "已有相同的限时活动！请重新选择开始时间";
      die;
    }

		saveData("hb_salelimit",$data);
		$id=getLastId();
		if($id){
			echo "success";	
		}else{
			echo "添加失败";
		}
		die;
	}
	/**
	 * 禁用和启用
	 */
	function updateLimit(){
		$res=getRow("select status from ".DB_PREFIX."salelimit where sale_id='".$_POST['category_id']."'");
	    $status=$res['status'];
	      //状态是0时，修改为1，为1时，修改为0
	    if($status == 1){
	        $data=array('sale_id'=>$_POST['category_id'],'status'=>0);      
	          $status=saveData(DB_PREFIX."salelimit",$data);
	          if($status ){
	              echo "disable";exit();
	          }
	        }elseif($status == 0){
	          $data=array('sale_id'=>$_POST['category_id'],'status'=>1);
	          $status=saveData(DB_PREFIX."salelimit",$data);
	          if($status ){
	                echo "enable";exit();
	            }
	    }
	}
	/**
	 * 编辑  cgl 
	 */
	function editLimit(){
		 //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      if(empty($_POST['name'])){
        //如果存在传递的name
        echo "请输入活动名称";exit();
      }

       if(empty($_POST['category_id'])){
        //如果存在传递的name
        echo "请选择活动";exit();
      }

      $date=getRow("select * from hb_salelimit where sale_id= '".$_POST['category_id']."' ");
      
      $now=time();
      $time=date("Y-m-d H:i:s",$now);
      // $rule  = "/-/";  
      // $result=preg_match($rule,$_POST["date_start"]); 
      // $result1=preg_match($rule,$_POST["date_end"]); 
      // if($result){
      	$date_start=$_POST["date_start"];
      // }else{
      // 	$date_start=isset($_POST["date_start"])?$this->timechange($_POST["date_start"]):$date["date_start"];
      // }
      // if($result1){
      	$date_end=$_POST["date_end"];
      // }else{
      // 	$date_end=isset($_POST["date_end"])?$this->timechange($_POST["date_end"]):$date["date_end"];
      // }
      //$date_start=$this->timechange($date_start);
      
      //$date_end=$this->timechange($date_end);
      $data=array("sale_id"=>$_POST['category_id'],"sale_name"=>$_POST["name"],"date_modified"=>$time,"date_start"=>$date_start,"date_end"=>$date_end);
      if($date_start>$date_end){
      	echo "开始时间必须小于结束时间";exit();
      }
      if($date_end<date("Y-m-d H:i:s")){
      	echo "结束时间必须大于当前时间";exit();
      }

      $res=saveData(DB_PREFIX."saleLimit",$data);
      if($res){
          echo "success"; exit();
      }else{
          echo "修改失败";exit();
      }
	}

	/*
    *  时间处理函数
    */

    function timechange($time){
        $y=substr($time,strrpos($time,'/')+1);
        $m=substr($time,0,strpos($time,'/'));
        $d=substr($time,strpos($time,'/')+1,strrpos($time,'/')-3);
        $time=$y."-".$m."-".$d;
        return($time); 
    }
    function  change($time){
    	$time=str_replace("/", "-", $time);
    	return $time;
    }
     /*
    }
    * 新   活动商品列表
    */
    function getGoodsActivityList(){
        //菜单
      $this->getMenu();    

      if(isset($_GET['name'])){
        $where=$_GET['name'];
        $this->res['search_name']=$_GET['name'];
      }else{
        $where='';
      }
            $page=1;

            if(isset($_GET['page'])){
                $page=$_GET['page'];
                if($page<1){
                $page=1;
              }
            }
            $start=($page-1)*20;
            $sql="SELECT distinct p.`product_id`,
            p.`model`,
            p.`quantity`,
            p.`image`,
            p.`manufacturer_id`,
            p.`price`, 
            p.`points`,
            p.sales,
            p.proxyprice,
            p.sort_order,
            p.`status`,
            p.brand_id,
            p.`date_modified`,
            p.date_added,
            b.name as bname,
            pd.name
          FROM `hb_product` as p,hb_product_description as pd,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id and   p.brand_id=b.manufacturer_id order by  p.sort_order desc,p.date_added desc,p.product_id asc  limit $start,20 ";

        $dt = getData($sql);
        //var_dump(count($dt));exit();
        $this->res['dt']=$dt;
        // $product=new product;
        // $this->res['cat']=$product->getCat();

       $total=getRow("SELECT count(*) as count
      FROM `hb_product` as p,hb_product_description as pd,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id and   p.brand_id=b.manufacturer_id ",60);


        $total=$total['count'];
        $total_page = ceil($total/20);
        $this->res['is_end_page'] = 1;
        if($page == $total_page){
          $this->res['is_end_page'] = 0;
        }

        $this->getPages($page,$total_page);


        //print_r($this->res);
        $this->res['category_id']=$_GET['category_id'];
        //通过获取到的category_id来获取活动的名字
        $sql="select sale_name from ".DB_PREFIX."saleLimit where sale_id='".$_GET['category_id']."'";
        $category_name=getRow($sql);
        if(count($category_name)){
          $category_name=$category_name['sale_name'];
        }else{
          $category_name="";
        }
        $this->res['category_name']=$category_name;
        $this->res["getGoodsSelectList"]=linkurl("saleLimit/getGoodsSelectList"); 
        $this->res["updateGoodsSort"]=linkurl("saleLimit/updateGoodsSort");      
        $this->res["editGoodsToActivity"]=linkurl("saleLimit/editGoodsToActivity");
        $this->res["getGoodsActivityList"]=linkurl("saleLimit/getGoodsActivityList");
        
        return $this->res;
    }
    function getProductPrice(){
      $json=array();
      if(!empty($_POST["shangpin"])){
        $id=implode(",",$_POST["shangpin"]);
        $pro=getData("SELECT a.product_id,FORMAT(a.proxyprice,2) as proxyprice,b.name FROM hb_product as a join hb_product_description as b on a.product_id=b.product_id where a.product_id in (".$id.") and a.status=1 ");
        $json["data"]=$pro;
        $json["retcode"]=0;
      }else{
        $json["msg"]="商品编号不能为空！";
      }
      echo json_encode($json);
      die;
    }

    /**
     * 添加限时抢购活动
     */
    function editGoodsToActivity(){
    	 $merchant_id=$_SESSION['merchant_id'];
       $json=array();
     // var_dump($_GET);//exit();
      //通过ajax传过来的商品编号
      if(isset($_POST['shangpin'])){
          $shangpin=$_POST['shangpin'];
          $category_id=$_POST['category_id'];
          // print_r($shangpin);
          //array_pop($shangpin);
          // var_dump($category_id);exit();
          if(count($shangpin)>=1){
              foreach ($shangpin as $key => $value) {
                $is_on=getRow("select * from hb_product_sale where product_id ='".$value["product_id"]."' and  sale_id = '".$category_id."' and  status=1 ");
                if($is_on){
                  $json["msg"]="你已经添加过了！请勿重复添加";
                }else{
                  $sql="insert into ".DB_PREFIX."product_sale (product_id,sale_id,status,merchant_id) values('".$value["product_id"]."','".$category_id."','1','".$merchant_id."')";
                  exeSql($sql);
                   $id=getLastId();
                   $sql_price="insert into hb_product_sale_price (sale_id,product_sale_id,product_id,sale_price) values ('".$category_id."','".$id."','".$value["product_id"]."','".$value["price"]."') ";
                   exeSql($sql_price);
                    $json["msg"]="添加成功";
                    $json["retcode"]=0;
                }
              }
          }

      }

      echo json_encode($json);
      exit();
    }
    /**
     * 列表 cgl  限时抢购
     */
    function getGoodsSelectList(){
    	$this->getMenu();    

      if(isset($_GET['name'])){
        $where=$_GET['name'];
        $this->res['search_name']=$_GET['name'];
      }else{
        $where='';
      }
      if(empty($_SESSION['shangpin'])){
          $_SESSION['shangpin']="";
      }
      
            $page=1;
           if(isset($_GET['page'])){
                $page=$_GET['page'];
                if($page<1){
                $page=1;
              }
            }
            $start=($page-1)*20;
            //distinct
            $sql="SELECT  p.`product_id`,
            p.`model`,
            p.`quantity`,
            p.`image`,
            p.`manufacturer_id`,
            p.`price`, 
            p.`points`,
            p.sales,
            p.proxyprice,
            p.sort_order,
            p.`status`,
            p.brand_id,
            p.`date_modified`,
            p.date_added,
            pa.sale_id,
            b.name as bname,
            pd.name,
            (select b.sale_price from hb_product_sale as a join hb_product_sale_price b on a.product_id=b.product_id where a.status=1 and b.product_id=p.product_id AND a.product_sale_id=b.`product_sale_id` and a.sale_id=b.sale_id and b.sale_id='".$_GET['category_id']."' ) as sale_price
          FROM `hb_product` as p,hb_product_description as pd,hb_product_sale as pa,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id and  p.product_id=pa.product_id and p.brand_id=b.manufacturer_id and pa.sale_id='".$_GET['category_id']."' and pa.status=1 order by  p.sort_order desc,p.date_added desc,p.product_id asc limit $start,20";

        $dt = getData($sql);
        //var_dump($dt);exit();
        $this->res['dt']=$dt;
        // $product=new product;
        // $this->res['cat']=$product->getCat();

          $total=getRow("SELECT count(*) as count
          FROM `hb_product` as p,hb_product_description as pd,hb_product_sale as pa,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id and  p.product_id=pa.product_id and p.brand_id=b.manufacturer_id and pa.sale_id='".$_GET['category_id']."' and pa.status=1 ",60);
        $total=$total['count'];
        $total_page = ceil($total/20);
        $this->res['is_end_page'] = 1;
        if($page == $total_page){
          $this->res['is_end_page'] = 0;
        }
        $this->getPages($page,$total_page);
        //通过获取到的category_id来获取活动的名字
        $sql="select sale_name from ".DB_PREFIX."saleLimit where sale_id='".$_GET['category_id']."'";
        $category_name=getRow($sql);
        if(count($category_name)){
          $category_name=$category_name['sale_name'];
        }else{
          $category_name="";
        }
        $this->res['category_name']=$category_name;
        $this->res["getGoodsSelectList"]=linkurl("saleLimit/getGoodsSelectList"); 
        $this->res["updateGoodsSort"]=linkurl("saleLimit/updateGoodsSort");      
        $this->res["deleteActivityGoods"]=linkurl("saleLimit/deleteActivityGoods");
        $this->res["getGoodsActivityList"]=linkurl("saleLimit/getGoodsActivityList");
        //var_dump($this->res['getGoodsSelectList']);exit();
        $this->res['category_id']=$_GET['category_id'];
        return $this->res;
    }
    /**
     * 删除  产品
     */
    function deleteActivityGoods(){
    	 $merchant_id=$_SESSION['merchant_id'];
      //通过ajax传过来的商品编号
      if(isset($_POST['product_id'])){
        $category_id=isset($_POST['category_id'])?$_POST['category_id']:0;
          $sql="UPDATE `".DB_PREFIX."product_sale` SET `status`=0 WHERE sale_id='".$category_id."' and product_id='".$_POST['product_id']."'";
          $res=exeSql($sql);
          if($res){
          echo "删除成功";exit();
        }
      } 
    }
    /**
     * 选择产品
     * 查询详情
     */
    function selectSale(){

      $sql="SELECT  p.`product_id`,
            p.`model`,
            p.`quantity`,
            p.`image`,
            p.`manufacturer_id`,
            p.`price`, 
            p.`points`,
            p.sales,
            p.proxyprice,
            p.sort_order,
            p.`status`,
            p.brand_id,
            p.`date_modified`,
            p.date_added,
            pa.sale_id,
            b.name as bname,
            pd.name,
            (select b.sale_price from hb_product_sale as a join hb_product_sale_price b on a.product_id=b.product_id where a.status=1 and b.product_id=p.product_id AND a.product_sale_id=b.`product_sale_id` and a.sale_id=b.sale_id and b.sale_id='".$_POST['category_id']."' ) as sale_price
          FROM `hb_product` as p,hb_product_description as pd,hb_product_sale as pa,hb_manufacturer as b where p.status=1 and  p.product_id=pd.product_id and  p.product_id=pa.product_id and p.brand_id=b.manufacturer_id and pa.sale_id='".$_POST['category_id']."' and p.product_id = '".$_POST["product_id"]."' and pa.status=1 order by  p.sort_order desc,p.date_added desc,p.product_id asc ";
      $data=getRow($sql);

      $data["retcode"]=0;
      echo json_encode($data);

      die;
    }
    /**
     * cgl 2017-6-14   修改价格
     */
    function editPrice(){
      $category_id=@$_POST["category_id"];
      $product_id=@$_POST["product_id"];
      $price=@$_POST["price"];
      if($category_id && $product_id && $price){
        exeSql("UPDATE hb_product_sale_price set sale_price = '".$price."' where sale_id = '".$category_id."' and product_id = '".$product_id."' ");

        $data["retcode"]=0;
      }else{
        $data["msg"]="编辑价格参数错误";
        $data["retcode"]=0;
      }
      
      echo json_encode($data);
      die;
    }


}
?>