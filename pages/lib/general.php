<?php
	
	$domain="";
	$ssl="";
	$rewrite = array();

	function __construct($domain, $ssl = '') {
		$this->domain = $domain;
		$this->ssl = $ssl;
	}
	function addRewrite($rewrite) {
		$this->rewrite[] = $rewrite;
	}

	function token($length = 32) {
		// Create token to login with
		$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$token = '';
		for ($i = 0; $i < $length; $i++) {
			$token .= $string[mt_rand(0, strlen($string) - 1)];
		}
		return $token;
	}
	/**
	 * 12.22   ws   编码,编码后为小写   筛选特殊字符串 
	 */

	function escape($value) {
		return str_replace(array("\\", "\0", "\n", "\r", "\x1a", "'", '"'), array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"'), $value);
	}

	/**
	 * 12.28   cgl   封装跳转路径
	 */

	function redirect($url, $status = 302) {
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
		exit();
	}
	/**
	 * 12.28  cgl   封装路径   限制条件  || isset($route[2])     后面加上
	 * 使用   linkurl("user/asd")   结果：http://localhost/iwantcdm/xindex.php?m=user&act=asd
	 */
	function linkurl($route, $args = '') {
		$route=explode("/", $route);
		if(!isset($route[0]) || !isset($route[1]) ){
			echo "No Path Defined, Please Redefine！";
			die;
		}
		if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
			$url = 'https://' .str_replace('www.', '', $_SERVER['HTTP_HOST']).rtrim(dirname($_SERVER['PHP_SELF']), '/.\\').'/';
		} else {
			$url = 'http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\').'/';
		}
		$url .= 'xindex.php?m='. $route[0].'&act='. $route[1];
		if ($args) {
			if (is_array($args)) {
				$url .= '&amp;' . http_build_query($args);
			} else {
				$url .= str_replace('&', '&amp;', '&' . ltrim($args, '&'));
			}
		}
		return $url;
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



