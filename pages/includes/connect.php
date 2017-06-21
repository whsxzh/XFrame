<?php
//数据库函数封装 by 席 2016.10.11

 $dbh="";
 $data="";

function connectDB()
{
	global $dbh,$data,$pdoInfo, $dbuser, $dbpass;
	try {
	$dbh = new PDO($pdoInfo, $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbh->exec('set names utf8');

	} catch (PDOException $ex) {
	    die("MySQL PDO failed. -- " . $ex->getMessage());
	}

}

//$stmt="";

function getData($sql,$param=array(),$cachetime=0)
{
	if($cachetime>0)
	{
		if($rt=getCache(md5($sql)))
			return $rt;
	}

	$stmt=exeSql($sql,$param);
	$dt=array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	 	$dt[]=$row;
	 } 

	 if($cachetime>0)
	{
		setCache(md5($sql),$dt,$cachetime);
			
	}

	 return $dt;
}


function getRow($sql,$param=array(),$cachetime=0)
{
	if($cachetime>0)
	{
		if($rt=getCache(md5($sql)))
			return $rt;
	}

	$stmt=exeSql($sql,$param);
	$dt=array();
	if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	 	$dt=$row;
	 } 

	if($cachetime>0)
	{
		setCache(md5($sql),$dt,$cachetime);
			
	}

	 return $dt;
}

function getLastId()
{
	global $dbh;
	return $dbh->lastInsertId();
}

function exeSql($sql,$param=array())
{
	global $dbh,$data;

	if($dbh=="")
		connectDB();
	
	// $sql = 'SELECT * FROM `' . $table . '` WHERE mobile = :mobile AND hashed_password = :hashedpassword LIMIT 1;'; //!!
//分析sql，抽出参数
	 $par=array();
	 //$str=$sql;
	 $pos=0;

	 $sql=strtr($sql,array(","=>" ,",")"=>" )"));
	 while($pos=strpos($sql,":",$pos+1))
	 {
	 	
	 	
	 	
	 	if($pos1=strpos($sql," ",$pos+1))
	 		$key=substr($sql,$pos+1,$pos1-$pos-1);
	 	else
	 		$key=substr($sql,$pos+1);
	 	$par[$key]="";
	 }

//准备参数
	$stmt = $dbh->prepare($sql);

	foreach ($par as $key => $value) {
		if(isset($param[$key]))
		{
			$par[$key]=$param[$key];
		 	$stmt->bindParam(':'.$key, $par[$key]);
		}
		/*else
		{
			$data['code']=0;
			$data['msg']="$key 参数必需传入";
			echo json_encode($data);
			exit;
			return false;
		}*/
		 	
		 }	 
	$stmt->execute();
    return $stmt;
}


function saveData($table,$post,$ischeck=false)
{
	//读取表结构 主键 
	//Field       | Type         | Null | Key | Default | Extra 
	$PK="";
	$field=array();
	$table="`$table`";
	$sql = "describe $table"; 
	$muststr="";
	
	$re=exeSql($sql); 
	while( $row=$re->fetch(PDO::FETCH_ASSOC))
	{ 
	if(isset($post[$row['Field']]) && trim($post[$row['Field']]."")<>"")//
		$field[$row['Field']]=$post[$row['Field']];
	elseif($row['Null']=='NO'&&$row['Key']!='PRI')
		$muststr.=" ".$row['Field'];


	if($row['Key']=='PRI')
		{ 
		$PK=$row['Field'];
		//ECHO $row['Field']."是这个表的主键。"; 
		//break; 
		}
	
	}
	if(isset($field[$PK])&&$field[$PK])//update
	{
		$sql="update $table set ";
		foreach($field as $k=>$v)
		{
			if(isset($v)&&$k!=$PK)
				$sql.="`$k`=:".$k.","	;
		}
		$sql=substr($sql,0,-1);
		$sql.=" where `$PK`=:{$PK}";		
	}
	else//insert 
	{
		
		if($muststr<>"" && $ischeck)
		{
			$data['retcode']=1000;
			$data['msg']="$muststr 参数保存时必需传入";
			echo json_encode($data);
			exit;
			return false;
		}
		//拼插入语句
		$sql="insert into $table(";
		$fstr="";
		$vstr="";
		foreach($field as $k=>$v)
		{
			if(isset($v))
			{
				$fstr.="`$k`,";
				$vstr.=":".$k.","	;
			}
		}
		$sql.=substr($fstr,0,-1).") values(".substr($vstr,0,-1).")";
	}
	// echo $sql."<br>\n";
	return exeSql($sql,$post);
}

function saveDataMuti($table,$data) //保存多条数据
{//INSERT INTO table (字段) VALUES (字段值) ,(字段值) ,(字段值) ,(字段值)
//print_r($data);
	$table="`$table`";
	$d1=$data[0];
	$sql="insert into $table(";//(user, answer, itemid) values";
	foreach($d1 as $k=>$v)
	{
		$sql.=$k.",";	
	}
	
	$sql=substr($sql,0,-1).") values";
	
	foreach($data as $d1)
	{
		$sql.="('";
		foreach($d1 as $k=>$v)
		{
			
			$sql.=$v."','";	
		}
		$sql=substr($sql,0,-2)."),";
		//$sql.="('$user', '{$a[1]}',{$a[0]}),";//
	}
	$sql=substr($sql,0,-1);
	//echo $sql;
	return exeSql($sql);	
	//substr();
}

function delData($table,$id)
{
	$sql="delete from $table where id=:id";
	return exeSql($sql,array("id"=>$id));
}

//缓存－－－－－－－－－－－－－－－－
function getCache($key)
{
    

    $memcache = new Memcache;             //创建一个memcache对象
    //$memcache->connect('test.haiqihuocang.com', 11211) or die ("Could not connect"); //连接Memcached服务器
    $memcache->connect('localhost', 11211) or die ("Could not connect"); //连接Memcached服务器

    return $memcache->get($key);
   
        
}

function setCache($key,$dt,$second=60)
{
    
    
    $memcache = new Memcache;             //创建一个memcache对象
    //$memcache->connect('test.haiqihuocang.com', 11211) or die ("Could not connect"); //连接Memcached服务器
     $memcache->connect('localhost', 11211) or die ("Could not connect"); //连接Memcached服务器
    
    return $memcache->set($key, $dt,0,$second);        //设置一个变量到内存中，名称是key 值是test

}

