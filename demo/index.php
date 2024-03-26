<?php
/**
 * 这是一个入口文件 - 演示案例
 */

// 载入web框架核心类
include '../LiteWeb.php';

// 单例模式，调用启动方法。参数：(入口文件-相对于网站根目录,项目绝对路径,应用名)
(LiteWeb::instance()->start('index.php', __DIR__));