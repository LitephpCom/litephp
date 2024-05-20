#!/usr/local/bin/php -q
<?php
/**
 * 命令行执行脚本
 */
error_reporting(E_ALL);
ignore_user_abort(true);
set_time_limit(0);

class LiteCmd
{
    /**
     * 版本号
     */
    public static $version = '1.0.0';

    /**
     * 原始参数
     */
    public static $argv = [];

    /**
     * 原始参数数量
     */
    public static $argc = 1;

    
}