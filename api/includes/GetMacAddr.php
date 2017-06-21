<?php
class GetMacAddr{

    var $return_array = array(); // 返回带有MAC地址的字串数组
    var $mac_addr = array();

    function GetMacAddr($os_type){
        switch ( strtolower($os_type) ){
            case "linux":
                $this->forLinux();
                break;
            case "solaris":
                break;
            case "unix":
                break;
            case "aix":
                break;
            default:
                $this->forWindows();
                break;
        }


        $temp_array = array();//数组，用来保存匹配正则表达式的mac地址

        //用来匹配mac地址的正则表达式
        $preg_string = "/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]".
            "[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]".
            "[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i";


        foreach ( $this->return_array as $value )
        {

            if (preg_match($preg_string, $value, $temp_array ) )
            {

                array_push( $this->mac_addr, $temp_array[0]);
            }

        }

        unset($temp_array);
        return $this->mac_addr;
    }


    //获取运行Windows操作系统的计算机mac地址
    function forWindows(){
        @exec("ipconfig /all", $this->return_array);
        if ( $this->return_array )
            return $this->return_array;
        else{
            $ipconfig = $_SERVER["WINDIR"]."\system32\ipconfig.exe";

            if ( is_file($ipconfig) )
                @exec($ipconfig." /all", $this->return_array);
            else
                @exec($_SERVER["WINDIR"]."\system\ipconfig.exe /all",
                    $this->return_array);
        }
    }

    //获取运行Linux操作系统的计算机mac地址
    function forLinux(){
        @exec("ifconfig -a", $this->return_array);

    }

}