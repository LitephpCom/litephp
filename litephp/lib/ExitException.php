<?php
/**
 * 异常形式输出到页面
 * 场景：类的构造函数直接抛出信息，框架输出到页面
 */
namespace litephp;

class ExitException extends \Exception {}
