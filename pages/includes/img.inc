<?
function imagesmall($filename,$size,$path="" )
	  {  $src="";
		$info=getimagesize($filename);
		$size=$info[0]/$size;
		$dst= imagecreatetruecolor($info[0]/$size,$info[1]/$size) ;
		if($path=="")
			$path.="l".$filename;
			
		if($size<=1)
		{
			copy($filename,$path);
			return;
		}
		
		if($info[2]==2)
		{
			$src=ImageCreateFromJPEG($filename) ;
	  
			 imagecopyresampled($dst,$src,0,0,0,0,$info[0]/$size,$info[1]/$size,$info[0],$info[1]);
			 //$textcolor = imagecolorallocate($im,100,100,100);
			  if($size<4) imagestring ( $dst , 5 , 20 , $info[1]/$size-20 , 'www.9paipai.com',hexdec("FFFFFF") );
			  $src=ImageJPEG($dst,$path) ;
			}
		  elseif($info[2]==3)
			{
			$src=ImageCreateFromPNG($filename) ;
			 imagecopyresampled ($dst,$src,0,0,0,0,$info[0]/$size,$info[1]/$size,$info[0],$info[1]);
			   $src=ImagePNG($dst,$path) ;
			 }
		elseif($info[2]==1)
			{
			$src=ImageCreateFromGIF($filename) ;
			 imagecopyresampled ($dst,$src,0,0,0,0,$info[0]/$size,$info[1]/$size,$info[0],$info[1]);
			   $src=ImageGIF($dst,$path) ;
			 }
	  }

function getPicName($file)
{
	return $fname="pic".strrchr($file,"/");	
}


function image($filename)
{  $src="";

  $info=getimagesize($filename);
  $size=$info[0]/120;

  $dst= imagecreate ($info[0]/$size,$info[1]/$size) ;
  if($info[2]==2)
  {
      $src=ImageCreateFromJPEG($filename) ;

       imagecopyresized ($dst,$src,0,0,0,0,$info[0]/$size,$info[1]/$size,$info[0],$info[1]);
        $src=ImageJPEG($dst,"l".$filename) ;
      }
  /*elseif($info[2]==1)
      {
      $src=ImageCreateFromGIF($filename) ;
       imagecopyresized ($dst,$src,0,0,0,0,$info[0]/$size,$info[1]/$size,$info[0],$info[1]);
         $src=ImageGIF($dst,"l".$filename) ;
       }     */
}

function savePic($from,$to,$size=600)
{
	if(!isset($_FILES[$from]))
		return false;

	echo "upload<br>";
		//$file=$_POST['file1'];
	if(!$to)
		$to=$_FILES[$from]['name'];
		if ($_FILES[$from]["type"] != "image/jpeg" && $_FILES[$from]["type"] != "image/pjpeg")
			 msgback("必须是jpg格式,或体积超过2M，请转化后上传");

	if($_FILES[$from]["size"] > 2500000)
		 msgback("尺寸不能超过2.5M,请缩图后上传");
		 
		 
		 
		//echo $file."<br> 请稍后..../n";
		//if($f= file_get_contents($file))
		
			$f=$_FILES[$from]['tmp_name'];
			//获得文件
			//$fname=uniqid();
			
			
			move_uploaded_file($f,$to);
			//file_put_contents($fname,$f);
			//保存微缩图片
			if($size>0)
			{
				imagesmall($to,$size);
				//删除原图
				unlink($to);
			}
			
			
			echo $fname."已保存<br>/n";


}

?>