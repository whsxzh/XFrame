<?php
include "xcontrol/base.php";
require_once( '../system/thirdlib/' . 'RMServerAPI.php');
class msg extends base{
    /*
     * 获取群通知
     * wangzhichao      17.3.15
     * type             2为申请，3邀请
     * date_added       通知时间
     *
     */
    function getGroupNotice(){
        $group_notice_list = getData("SELECT p.push_id,
                                      p.type,
                                      p.date_added,
                                      p.item_id,
                                      p.customer_id,
                                      p.item_id,
                                      p.applyfrom,
                                      p.invitor,
                                      p.common_status,
                                      r.rcgroup_id AS groupid,
                                      r.`groupname`,
                                      c.firstname,
                                      c.telephone,
                                      c.headurl
                                      FROM hb_push as p,hb_rcgroup as r,hb_customer as c
                                      WHERE r.rcgroup_id = p.item_id and c.customer_id = p.customer_id
                                      and p.`target_id` = '". (int)$this->customer_id ."' AND (p.`type` = '2' OR p.`type` = '3') AND (p.`status` = '0' or p.`status` = '1') ORDER BY p.date_added DESC limit 0,10");

        if(empty($group_notice_list)){
            $this->res["retcode"]=0;
            $this->res["group_notice"]=array();
            return $this->res;
        }

        //2为邀请通知，3申请通知
        foreach($group_notice_list as $key=>$val){
            if($val['type'] == 3 && $val['applyfrom'] == 1){
                $customer = getRow("select firstname,telephone from `" .DB_PREFIX. "customer` where customer_id = '" .$val['invitor']. "'");
                $group_notice_list[$key]['invitor_name'] = $customer['firstname'];
                $group_notice_list[$key]['invitor_telephone'] = $customer['telephone'];
            }
        }
        $this->res["retcode"]=0;
        $this->res["group_notice"]=$group_notice_list;
        return $this->res;
    }

    /*
     * 群主处理入群申请
     * wangzhichao  17.3.16
     * customer_id  群主id
     * target_id    加入群的人id
     * group_id     群id
     * type         0表示申请，1表示邀请
     * status       0未处理，1同意，2拒绝
     */
    function handleGroupApply(){
        $post = $_POST;

        if(@$post['target_id'] == '' || @$post['target_id'] < 1 || @$post['group_id'] == '' || @$post['group_id'] < 1 || !in_array(@$post['type'],array('0','1'))){
            $this->res["retcode"] = 1000;
            $this->res["msg"]="参数错误";
            return $this->res;
        }

        //拒绝申请
        if(isset($post['status']) && $post['status'] == 2){
            exeSql("update `" .DB_PREFIX. "push` set common_status = 2 where item_id = '" .$post['group_id']. "' and customer_id = '" .$post['target_id']. "' and target_id = '" .$this->customer_id. "' and type = 3 and applyfrom = '" .$post['type']. "'");
            $this->res["retcode"] = 0;
            return $this->res;
        }

        //同意申请
        //判断用户是否已经是群成员
        $group_member = getRow("select * from `" .DB_PREFIX. "rcgroupmember` where customer_id = '" .(int)$post['target_id']. "' and rcgroup_id = '" .(int)$post['group_id']. "'");
        if(!empty($group_member) && $group_member['status'] != 1){
            $this->res["retcode"] = 1100;
            $this->res["msg"]="此人已经是群成员了";
            return $this->res;
        }

        //获取申请的记录,判断记录是否存在
        $apply_info = getRow("select * from `" .DB_PREFIX. "push` where item_id = '" .$post['group_id']. "' and customer_id = '" .$post['target_id']. "' and target_id = '" .$this->customer_id. "' and type = 3 and applyfrom = '" .$post['type']. "' and common_status = 0");
        if(empty($apply_info)){
            $this->res["retcode"] = 1110;
            $this->res["msg"]="参数错误";
            return $this->res;
        }


        //获取群组信息
        $groupinfo = getRow("select groupname from `" .DB_PREFIX. "rcgroup` where rcgroup_id = '" .(int)$post['group_id']. "'");

        //更新融云群组成员信息
        $rongcloud = new RMServerAPI();
        $return = json_decode($rongcloud->groupJoin($post['target_id'], $post['group_id'], @$groupinfo['groupname']),true);

        if($return['code'] == 200){
            //成员曾经加入本群就修改表，否则就添加记录
            if(!empty($group_member) && $group_member['status'] == 1){
                exeSql("update " . DB_PREFIX . "rcgroupmember SET status = '0' where rcgroup_id = '" . (int)$post['group_id'] . "' and customer_id = '" . (int)$post['target_id'] . "'");
            }else{
                exeSql("INSERT INTO " . DB_PREFIX . "rcgroupmember SET customer_id = '" . (int)$post['target_id'] . "', rcgroup_id = '" . (int)$post['group_id'] . "', date_added = UNIX_TIMESTAMP()");
            }

            //修改群的成员数
            exeSql("update `" .DB_PREFIX. "rcgroup` set membercnt = membercnt+1 where rcgroup_id = '" .(int)$post['group_id']. "'");

            //修改通知记录状态
            exeSql("update `" .DB_PREFIX. "push` set common_status = 1 where item_id = '" .$post['group_id']. "' and customer_id = '" .$post['target_id']. "' and target_id = '" .$this->customer_id. "' and type = 3 and applyfrom = '" .$post['type']. "'");
            $this->res["retcode"] = 0;
            return $this->res;
        }else{
            $this->res["retcode"] = 1110;
            $this->res["msg"]="操作失败，请重新操作";
            return $this->res;
        }
    }

    function getOrderNotice(){
        $order_notice_list = getData("select pu.push_id,
                                      pu.type,
                                      pu.date_added,
                                      pu.item_id,
                                      pu.target_id,
                                      pu.common_status,
                                      o.order_status_id,
                                      o.ship_order_no,
                                      s.com as ship_name,
                                      op.name,
                                      p.image
                                      from `" .DB_PREFIX. "push` as pu
                                      inner join `" .DB_PREFIX. "order` as o on o.order_id = pu.item_id
                                      inner join `" .DB_PREFIX. "order_product` as op on op.order_id = o.order_id
                                      inner join `" .DB_PREFIX. "product` as p on p.product_id = op.product_id
                                      left join `" .DB_PREFIX. "shipping` s on o.ship_id = s.id
                                      where pu.target_id = '" .(int)$this->customer_id. "' and (pu.type = 0 or pu.type = 1) and (pu.status = 0 or pu.status = 1) ORDER BY pu.date_added DESC limit 0,10");


        $this->res["retcode"] = 0;
        $this->res["order_notice"]=$order_notice_list;
        return $this->res;
    }


}