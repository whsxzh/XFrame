<?php
   class tree{
            //数组，pid, 等级.递归方法找子孙树
           function getTreeArray($arr,$pid,$lev=0){
            //$tree=array();
            static $tree=array();
            foreach ($arr as $key => $value) {
                if($value['pid']==$pid){
                    $value['lev']=$lev;
                    $tree[]=$value;
                    //$tree=array_merge($tree,getTreeArray($arr,$value['id'],++$lev));
                    $this->getTreeArray($arr,$value['id'],$lev+1);
                }
            }
            return $tree;
        }
    }
?>