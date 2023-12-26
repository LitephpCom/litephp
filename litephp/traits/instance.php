<?php

/**
 * 单例trait
 */

namespace litephp\traits;

trait instance
{
    /**
     * @var static
     */
    private static $_instance;

    /**
     * @return static
     */
    public static function instance(...$option)
    {
        !(static::$_instance instanceof static) && (static::$_instance = new static(...$option));
        return static::$_instance;
    }
}
