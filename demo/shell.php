<?php
/**
 * 命令行模式 - 演示案例
 */

// 载入shell框架核心类
include dirname(__DIR__).'/LiteCmd.php';

// 单例模式，调用启动方法。参数：(传参数组-PHP的全局变量,传参数量-PHP的全局变量)
(LiteCmd::instance()->start($argv,$argc));