<?php

function getShort($url)//得到短链接
{
	$str=file_get_contents(
"http://api.t.sina.com.cn/short_url/shorten.json?source=1681459862&url_long=".$url);
$arr=json_decode($str);
return $arr[0]->url_short;
//echo $arr[0]->url_short;
}


?>