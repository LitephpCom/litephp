<?php

/**
 * 框架函数库
 */

/**
 * 检测函数或方法是否可调用
 */
function LiteCheckFunction($function)
{
    if (is_array($function)) {
        return count($function) >= 2 ? method_exists($function[0], $function[1]) : false;
    } else {
        return is_callable($function);
    }
}

/**
 * array_key_first
 * 兼容 php7.3 以前版本
 */
function LiteArrayKeyFirst($array = [])
{
    // 兼容 php7.3 以前版本
    if (!function_exists('array_key_first')) {
        function array_key_first(array $arr)
        {
            foreach ($arr as $key => $unused) {
                return $key;
            }
            return NULL;
        }
    }
    return array_key_first($array);
}

/**
 * 获取框架PDO - 变相单例模式
 * @param $dbAlias 数据库别名
 * @param $closeOption PDO连接选项[关闭/创建]: 0创建 1关闭该别名数据库连接 -1关闭所有
 * @return \litephp\Pdo
 */
function LiteDB($dbAlias = 'default', $closeOption = 0)
{
    static $DB = [];
    switch($closeOption) {
        case 0:
            if (!isset($DB[$dbAlias])) {
                $dbConfig = \LiteWeb::instance()->dbConfig($dbAlias);
                if (!$dbConfig) {
                    throw new \ErrorException('无法获取数据库配置信息');
                }
                $DB[$dbAlias] = new \litephp\Pdo($dbConfig, $dbConfig['username'] ?? NULL, $dbConfig['password'] ?? NULL, $dbConfig['options'] ?? NULL);
            }
            return  $DB[$dbAlias];
        case 1:
            if (isset($DB[$dbAlias])) {
                call_user_func([$DB[$dbAlias],'close']);
                unset($DB[$dbAlias]);
            }
        case -1:
            array_walk($DB[$dbAlias],function($db){
                call_user_func([$db,'close']);
            });
            $DB= [];
        default:
            throw new \ErrorException('参数错误：PDO连接选项');
    }
}
