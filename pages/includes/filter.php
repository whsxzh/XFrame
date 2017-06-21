<?php


if(isset($_REQUST))
foreach($_REQUST as $r)
{
	if(preg_match('/select|insert|update|delete|script|\'|\\*|\*|\.\.\/|\.\/|<|>|union|into|load_file|outfile/i',$r))
	msgback("非法输入词，请检查");
}

// by gordon xi 过滤敏感词及非法注入





/*$filterWords="刷单,操,艹,SB,sb";
$words=explode(",", $filterWords);


$badword1 = array_combine($words,array_fill(0,count($badword),'*'));


/*
$blacklist="/'".implode("'|'",$words)."'/i";
*/

/*foreach ($_POST as $key => $value) {
	//$str=$value;
	$_POST[$key] = strtr($value, $badword1);
	/*if(preg_match($blacklist, $str, $matches)){
	    print "found:". $matches[0];
	  }*/ 

//}