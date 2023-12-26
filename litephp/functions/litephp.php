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
