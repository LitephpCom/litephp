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
 * 获取框架PDO单例
 * @return \litephp\Pdo
 */
function LiteDB($dbAlias = 'default')
{
    $dbConfig = \LiteWeb::instance()->dbConfig($dbAlias);
    if (!$dbConfig) {
        throw new \ErrorException('无法获取数据库配置信息');
    }
    return \litephp\Pdo::instance($dbConfig, $dbConfig['username'] ?? NULL, $dbConfig['password'] ?? NULL, $dbConfig['options'] ?? NULL);
}
