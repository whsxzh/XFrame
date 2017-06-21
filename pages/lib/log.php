<?php
	class log
{
	/**
	 * 记录操作日志  cgl 2017-3-30
	 */
	function saveLog($content,$status=0,$remark=""){
		if($_SERVER['REQUEST_METHOD']=="POST"){
			if($content!="user/login"){
				$data=array();
				$data['userid']=$_SESSION["userid"];
				$data['username']=$_SESSION["username"];
				$data['user_group_id']=$_SESSION["user_group_id"];
				$data['merchant_id']=$_SESSION["merchant_id"];
				$permission=getRow("select * from hb_permissionx where controller='".$content."' ");
				$persx=@$permission["name"];

				$content=$_SESSION["username"]." 在 ".date("Y-m-d H:i:s",time())."  操作 ".$persx." ".$content." ,"."请求参数：".json_encode($_REQUEST);

				$data["remark"]=$remark;//备注
				$data['content']=$content;//操作内容
				$data['user_ip']=$_SERVER["REMOTE_ADDR"];//ip
				$data['date_added']=date("Y-m-d H:i:s",time());
				$data['date_modified']=date("Y-m-d H:i:s",time());
				$data['status']=$status;//状态  0,成功，1未成功
				saveData("hb_user_log",$data);
			}
		}
	}
}
?>