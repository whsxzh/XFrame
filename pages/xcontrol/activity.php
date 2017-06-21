<?php
	//面向对象的control 类
include "xcontrol/base.php";
include "xcontrol/product.php";
class activity extends base
{
	
	/*
  * 活动首页
  */
	function index(){
		//菜单

		$this->getMenu();
    //获取merchant_id
      $merchant_id=$_SESSION['merchant_id']; 
      $page=isset($_GET['page'])?$_GET['page']:1;
      if($page<1){
          $page=1;
      }
      $size=10;
      $start=($page-1)*$size;
		  //获取到所有的活动
      $activity=getRow("select count(c.parent_id) as count from ".DB_PREFIX."category as c , ".DB_PREFIX."category_description as cd where  c.category_id=cd.category_id and c.type=1 and c.parent_id=0 and merchant_id=".$merchant_id." order by c.sort_order ",60);

      $count1=$activity['count'];
      //获取到所有的活动
      $activity=getData("select c.category_id,c.date_start,c.date_end,c.date_added,c.sort_order,c.points,c.merchant_id,c.type,cd.name,c.status,c.image from ".DB_PREFIX."category c , ".DB_PREFIX."category_description cd  where c.category_id=cd.category_id and c.type=1 and c.parent_id=0 and merchant_id=".$merchant_id." order by c.date_added desc limit ".$start.",".$size."");
     
      //如果商品不空，则使用处理照片路径的函数
      if(!empty($activity)){
        foreach ($activity as $key => $value) {
          $activity[$key]["image"]=$this->admindefault($value['image']);   
        }
      }
      foreach ($activity as $key => $value) {
          $count=getRow("select count(ptc.product_id) from ".DB_PREFIX."product_to_activity ptc left join ".DB_PREFIX."product p on(ptc.product_id=p.product_id) where ptc.status=1 and p.status=1 and category_id=".$value['category_id']);
          $count=$count['count(ptc.product_id )'];
          $activity[$key]['count']=$count;
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
    /*
    *修改活动标题的状态
    */
  	function updateStatus(){

       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      $res=getRow("select status from ".DB_PREFIX."category where category_id='".$_POST['category_id']."'");
      $status=$res['status'];
      //状态是0时，修改为1，为1时，修改为0
      if($status == 1){
        $data=array('category_id'=>$_POST['category_id'],'status'=>0);      
          $status=saveData(DB_PREFIX."category",$data);
          if($status ){
              echo "disable";exit();
          }
        }elseif($status == 0){
          $data=array('category_id'=>$_POST['category_id'],'status'=>1);
          $status=saveData(DB_PREFIX."category",$data);
          if($status ){
                echo "enable";exit();
            }
      }

    }

    /*
    *添加活动标题
    */
    function addActivity(){

       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      if(empty($_POST['name'])){
        //如果存在传递的name
        echo "请输入活动名称";exit();
      }

      $now=time();
      $time=date("Y-m-d H:i:s",$now);
      $date_start=isset($_POST["date_start"])?$_POST["date_start"]:"";
      $date_start=$this->timechange($date_start);
      $date_end=isset($_POST["date_end"])?$_POST["date_end"]:"";
      $date_end=$this->timechange($date_end);
      
      $data=array("parent_id"=>0,"top"=>0,"sort_order"=>0,"status"=>1,"date_added"=>$time,"date_modified"=>$time,"points"=>0,"display_mode"=>0,"store_id"=>0,"merchant_id"=>$merchant_id,"type"=>1,'column'=>0,"date_start"=>$date_start,"date_end"=>$date_end,"image"=>"/");
      $data1=array("name"=>$_POST['name']);
      $res=saveData(DB_PREFIX."category",$data);
      $id=getLastId();
      //echo $id;exit();
      $sql="insert into ".DB_PREFIX."category_description (category_id,language_id,name) values ('".$id."',0,'".$_POST['name']."')";
      $res1=exeSql($sql);
      if($res && $res1){
          echo "success"; exit();
      }else{
          echo "插入数据失败";exit();
      }
      
    }


     /*
    *修改活动标题
    */
    function editActivity(){

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

      $now=time();
      $time=date("Y-m-d H:i:s",$now);
      $date_start=isset($_POST["date_start"])?$_POST["date_start"]:"";
      $date_start=$this->timechange($date_start);
      $date_end=isset($_POST["date_end"])?$_POST["date_end"]:"";
      $date_end=$this->timechange($date_end);
      $data=array("category_id"=>$_POST['category_id'],"date_modified"=>$time,"date_start"=>$date_start,"date_end"=>$date_end);
      $res=saveData(DB_PREFIX."category",$data);
      $sql="UPDATE `".DB_PREFIX."category_description` SET `name`='".$_POST['name']."' WHERE category_id='".$_POST['category_id']."'";
      $res1=exeSql($sql);
      if($res && $res1){
          echo "success"; exit();
      }else{
          echo "修改数据失败";exit();
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
        $product=new product;
        $this->res['cat']=$product->getCat();

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
        $sql="select name from ".DB_PREFIX."category_description where category_id='".$_GET['category_id']."'";
        $category_name=getRow($sql);
        if(count($category_name)){
          $category_name=$category_name['name'];
        }else{
          $category_name="";
        }
        $this->res['category_name']=$category_name;
        $this->res["getGoodsSelectList"]=linkurl("activity/getGoodsSelectList"); 
        $this->res["updateGoodsSort"]=linkurl("activity/updateGoodsSort");      
        $this->res["editGoodsToActivity"]=linkurl("activity/editGoodsToActivity");
        $this->res["getGoodsActivityList"]=linkurl("activity/getGoodsActivityList");
        
        return $this->res;
    }

     /*
    * 新   修改商品排序
    */

    function updateGoodsSort(){
        //菜单
      $this->getMenu();      
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      $res=getRow("select sort_order from ".DB_PREFIX."product where product_id='".$_POST['product_id']."'");

      if(isset($res['sort_order'])){
          $sort_order=$res['sort_order'];
      }else{
          exit();
      }
      //type为add时候，使sort_order值变小
      if($_POST['type']=="add"){
          $sort_order=$sort_order+1;
          $data=array('product_id'=>$_POST['product_id'],'sort_order'=>$sort_order);      
          $res=saveData(DB_PREFIX."product",$data);
          if($res){
              echo "yes";exit();
          }
      }elseif($_POST['type']=="reduce"){
          $sort_order=$sort_order-1;
          $data=array('product_id'=>$_POST['product_id'],'sort_order'=>$sort_order);      
          $res=saveData(DB_PREFIX."product",$data);
          if($res){
              echo "yes";exit();
          }
      }
      return $this->res;
    }



   /*
    * 新   选中商品列表
    */

    function getGoodsSelectList(){
        //菜单
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
            pa.category_id,
            b.name as bname,
            pd.name,
            c.name as cname,
            pa.activity_price 
          FROM `hb_product` as p,hb_product_description as pd,hb_product_to_activity as pa,hb_category_description as c,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id and  p.product_id=pa.product_id and pa.category_id=c.category_id and p.brand_id=b.manufacturer_id and pa.category_id='".$_GET['category_id']."' and pa.status=1 order by  p.sort_order desc,p.date_added desc,p.product_id asc limit $start,20";

        $dt = getData($sql);

        //var_dump($dt);exit();
        $this->res['dt']=$dt;
        $product=new product;
        $this->res['cat']=$product->getCat();


        

          $total=getRow("SELECT count(*) as count
          FROM `hb_product` as p,hb_product_description as pd,hb_product_to_activity as pa,hb_category_description as c,hb_manufacturer as b where  pd.name like '%".$where."%'  and p.status=1 and  p.product_id=pd.product_id and  p.product_id=pa.product_id and pa.category_id=c.category_id and p.brand_id=b.manufacturer_id and pa.category_id='".$_GET['category_id']."' and pa.status=1 ",60);
        $total=$total['count'];
        $total_page = ceil($total/20);
        $this->res['is_end_page'] = 1;
        if($page == $total_page){
          $this->res['is_end_page'] = 0;
        }
        $this->getPages($page,$total_page);
        //通过获取到的category_id来获取活动的名字
        $sql="select name from ".DB_PREFIX."category_description where category_id='".$_GET['category_id']."'";
        $category_name=getRow($sql);
        if(count($category_name)){
          $category_name=$category_name['name'];
        }else{
          $category_name="";
        }
        $this->res['changePrice']=linkurl("activity/changePrice");
        $this->res['category_name']=$category_name;
        $this->res['category_id']=$_GET['category_id'];
        $this->res["getGoodsSelectList"]=linkurl("activity/getGoodsSelectList"); 
        $this->res["updateGoodsSort"]=linkurl("activity/updateGoodsSort");      
        $this->res["deleteActivityGoods"]=linkurl("activity/deleteActivityGoods");
        $this->res["getGoodsActivityList"]=linkurl("activity/getGoodsActivityList");
        //var_dump($this->res['getGoodsSelectList']);exit();
        $this->res['category_id']=$_GET['category_id'];
        return $this->res;
    }

    /**
     * 修改活动价格
     * zxx 2017-6-15
     */
    function changePrice(){
       if(isset($_POST['product_id']) && isset($_POST['category_id']) && isset($_POST['activity_price']) ){
          if(exeSql("update hb_product_to_activity set activity_price='".$_POST['activity_price']."' where product_id='".$_POST['product_id']."' and category_id='".$_POST['category_id']."'")){
            $msg="修改成功";
            $code=0;
          }else{
            $msg="修改失败";
            $code=1000;
          }
          
       }else{
          //活动商品不存在
          $msg="参数错误";
          $code=1001;
       }
       echo $code;
       exit;
    }

     /*
    *修改商品所属活动
    */
    function editGoodsToActivity(){
       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
     // var_dump($_GET);//exit();
      //通过ajax传过来的商品编号
      if(isset($_GET['shangpin'])){
          $shangpin=$_GET['shangpin'];
          $category_id=$_GET['category_id'];
          array_pop($shangpin);
          //var_dump($category_id);exit();
          if(count($shangpin)>=1){
              foreach ($shangpin as $key => $value) {

               $sql="SELECT * FROM ".DB_PREFIX."product_to_activity where category_id='".$category_id."' and product_id='".$value."'";
               $products=getData($sql);
               if($products){
                //如果存在则不添加 不存在则添加
                $sql="update ".DB_PREFIX."product_to_activity set status=1 where category_id='".$category_id."' and product_id='".$value."'";
                exeSql($sql);
               }else{
                  $sql="insert into ".DB_PREFIX."product_to_activity (product_id,category_id,status) values('".$value."','".$category_id."','1')";
                   exeSql($sql);
               }
              

            }
          }

      }
        echo "保存成功";exit();
      
    }

    /*
    *删除活动所属商品
    */
    function deleteActivityGoods(){

       //获取merchant_id
      $merchant_id=$_SESSION['merchant_id'];
      //通过ajax传过来的商品编号
      if(isset($_POST['product_id'])){
        $category_id=isset($_POST['category_id'])?$_POST['category_id']:0;
        if(is_array($_POST['product_id'])){
              $product_id=implode(',', $_POST['product_id']);
              $product_id=trim($product_id,',');
              $sql="UPDATE `".DB_PREFIX."product_to_activity` SET `status`=0 WHERE category_id='".$category_id."' and product_id in (".$product_id.")";
              $res=exeSql($sql);
        }else{
           $sql="UPDATE `".DB_PREFIX."product_to_activity` SET `status`=0 WHERE category_id='".$category_id."' and product_id='".$_POST['product_id']."'";
           $res=exeSql($sql);
        }
          if($res){
          echo "删除成功";exit();
        }else{
          echo "删除失败";exit;
        }
      } 
    }
}
?>