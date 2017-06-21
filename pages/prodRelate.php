
<?php
		include "includes/config.php";
		include "includes/connect.php";

		//查询 排除快递补差价
		$oid=file_get_contents("oid.txt");
		if($oid)
			$order_id=$oid;
		else
			$order_id=0;
		//产品关系 同一个客户1个月内买的，关系加1
		$sql="select o.order_id,o.customer_id,o.date_added,op.product_id,op.name,c.card from hb_order as o,hb_order_product as op,hb_customer as c where o.order_id=op.order_id and o.order_status_id in(4,5) and c.customer_id=o.customer_id and o.order_id>$order_id  order by o.order_id ";
		$data=getData($sql);

		echo sizeof($data),"start\n";
		$curYear=date("Y");

		//查询1个月内购买的数据
		$sql1="select o.order_id,o.date_added,op.product_id,op.name from hb_order as o,hb_order_product as op where o.order_id=op.order_id and o.order_status_id in(4,5) and o.date_added>".strtotime(date("Y-m-d")." -1 month")."  order by o.order_id ";
		$data1=getData($sql1);

echo sizeof($data1),"start\n";
		$i=0;
		foreach ($data as $k => $v) 
		{

			echo $i++,$v['product_id'],$v['order_id'],$v['name']," ";

			$prodrt=array();
			if(strpos($v['name'],"补差价"))
				continue;
			echo "	找匹配产品";
			//对于任意一款产品，检查是不是有同一个客户一个月内购买，如果是，建立产品对并+1
			foreach ($data1 as $k1 => $v1) 
			{
				if($v['customer_id']==$v1['customer_id']&&$v['product_id']<>$v1['product_id'] &&(abs($v1['date_added']-$v1['date_added'])/86400<30))
				{
					if(isset($prodrt[$v['product_id']][$v1['product_id']]))
						$prodrt[$v['product_id']][$v1['product_id']]+=5;
					else
						$prodrt[$v['product_id']][$v1['product_id']]=5;

				}
				# code...
			}

			//保存关系
			/*CREATE TABLE `hb_product_relate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `relate_product_id` int(11) DEFAULT NULL,
  `relate` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;*/
			echo " 生成匹配关系";
			$proddt=getData("select * from hb_product_relate where product_id=".$v['product_id']);
			$proddtarr=array();
			foreach ($proddt as $key => $value) {
				# code...
				$proddtarr[$value['product_id']][$value['relate_product_id']]=array("id"=>$value['id'],"relate"=>$value['relate']);
			}
//保存数据
			echo " 保存匹配关系\n";
			foreach ($prodrt as $k3 => $v3) 
			{
				foreach ($v3 as $k4 => $v4) 
				{
					if(isset($proddtarr[$k3][$k4]))
						exeSql("update hb_product_relate set relate=relate+$v4 where id=".$proddtarr[$k3][$k4]['id']);
					else
						exeSql("insert into hb_product_relate (product_id,relate_product_id,relate) values ($k3,$k4,$v4)");
					
				}
				
			}

		//挖掘年龄性别
			echo "年龄挖掘";
			if(strlen($v['card'])==18)
			{
				$cusYear=substr($v['card'],6,4);
				$sex=substr($v['card'],16,1)%2;//奇数为男，偶数为女
				$year1=$curYear-$cusYear;
				$year1=$year1-$year1%5;

				$age_sex=$year1."_".$sex;

				echo "年龄性别：".$age_sex;

				$row=getRow("select id from hb_product_relate_age_sex where age_sex='$age_sex' and product_id=".$v['product_id']);
				if(empty($row))
				{
					exeSql("insert into hb_product_relate_age_sex(age_sex,product_id,relate) values('$age_sex',".$v['product_id'].",5)");
				}
				else
				{
					exeSql("update hb_product_relate_age_sex set relate=relate+5 where id=".$row['id']);
				}

			}
			
			$oid=$v['order_id'];
		}
		file_put_contents("oid.txt", $oid);
		/*CREATE TABLE `hb_product_relate_age_sex` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `age_sex` varchar(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `relate` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

		//月购买 这个月买的，关系加1

		//年龄性别 相同年龄性别买的，关系加1
		//15位身份证号码：第7、8位为出生年份(两位数)，第9、10位为出生月份，第11、12位代表出生日期，第15位代表性别，奇数为男，偶数为女。 18位身份证号码：第7、8、9、10位为出生年份(四位数)，第11、第12位为出生月份，第13、14位代表出生日期，第17位代表性别，奇数为男，偶数为女。

		
		

		
		


