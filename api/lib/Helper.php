<?php

/**
 * User: lcb
 * Date: 2017/6/5
 */
class Helper
{
    /**
     * 获取数据表里面的字段
     * array (size=19)
     * 'user_id' =>
     * array (size=6)
     * 'name' => string 'user_id' (length=7)
     * 'type' => string 'int(11)' (length=7)
     * 'notnull' => boolean false
     * 'default' => null
     * 'primary' => boolean true
     * 'autoinc' => boolean true
     * @param $tableName
     * @param $useCache  是否使用缓存 不使用的话 可以更新缓存
     * @return array
     */
    public static function getFields($tableName, $useCache = true)
    {
        if ($useCache && $fields = getCache('hqhc_' . $tableName . '_fields')) {
            return unserialize($fields);
        }
        $sql    = 'SHOW COLUMNS FROM ' . $tableName;
        $result = getData($sql);
        $info   = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $val                 = array_change_key_case($val);
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => ((bool)('' === $val['null']) || (bool)('no' === strtolower($val['null']))), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                ];
            }
        }
        setCache('hqhc_' . $tableName . '_fields', serialize($info), 36000);

        return $info;
    }


    /**
     * 解析表名
     * @param $tableName
     * @return mixed|string
     */
    public static function parseTable($tableName)
    {
        if (false === strpos($tableName, '`')) {
            if (strpos($tableName, '.')) {
                $tableName = str_replace('.', '`.`', $tableName);
            }
            $tableName = '`' . $tableName . '`';
        }

        return $tableName;
    }

    /**
     * 记录日志函数
     * @param        $message
     * @param string $logName
     */
    public static function writeLog($message, $logName = '')
    {
        if (!$logName) {
            $logName = '/var/log';
            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'){
                $logName = dirname(__DIR__) . '/log';
            }
            if (!is_dir($logName)) {
                mkdir($logName, 0777);
            }
            $logName = $logName. '/api_' . date('Ym') . '.txt';
        }
        @file_put_contents($logName, '[' . date('d H:i:s') . ']' . (is_array($message) ? var_export($message, true) : $message) . "\r\n", FILE_APPEND);
    }

    /**
     * @param $imgbefore
     * @return string
     * @description 处理图片地址
     */
    public static function handleImage($imgbefore)
    {
        //high  修改图片  cgl   11.22

        if (is_null($imgbefore)) {
            $imgafter = '';
        } else if ((strpos($imgbefore, 'http://') === false) || (strpos($imgbefore, 'http://') > 0)) {
            $imgafter = TEST_IP1 . '/image/' . $imgbefore;
        } else {
            $imgafter = $imgbefore;
        }

        return $imgafter;
    }

}