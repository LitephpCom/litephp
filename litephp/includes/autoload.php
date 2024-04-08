<?php

/**
 * 自动加载
 */

spl_autoload_register('autoloadLiteWeb');
function autoloadLiteWeb($classname)
{
    if (strpos($classname, 'litephp\\') === 0) {
        $classfile = substr(str_replace('\\', '/', $classname), 8) . '.php';
        $classdir = ['/litephp/', '/litephp/lib/', '/litephp/toolbox/', 'litephp/core'];
        foreach ($classdir as $dir) {
            $classpath = __DIR__ . $dir . $classfile;
            if (file_exists($classpath))  require_once $classpath;
        }
    }
}
