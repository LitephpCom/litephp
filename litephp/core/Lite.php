<?php

/**
 * 核心类
 */

namespace litephp\core;

class Lite
{
    /**
     * 运行模式
     * DEBUG: 开发调试模式，输出错误信息，记录错误+异常日志
     * ONLINE: 日志（线上）模式，不输出任何错误异常信息，但记录到日志
     */
    private static $MODE = 'ONLINE';

    /**
     * 项目目录 - 非网站根目录,绝对路径
     */
    private static $PROJECT_DIR;

    /**
     * 应用（目录）名
     * 同文件夹名称
     */
    private static $APP_NAME = '';

    /**
     * 日志目录 - 绝对路径
     * 存放错误、异常等生成的日志
     */
    private static $LOG_PATH;

    /**
     * 框架配置参数
     * mode: 运行模式
     * timezone: 时区
     * log_path: 日志目录路径，默认项目目录下的logs文件夹
     * include_files: 自定义载入文件列表
     * func_exception: 自定义异常处理函数 参数：($e, MODE)
     * func_404: 自定义404回调函数
     */
    private static $CONFIG = [
        'mode'          =>  'ONLINE',
        'timezone'      =>  'Asia/Shanghai',
        'log_path'      =>  null,
        'include_files' =>  [],
        'func_exception'    =>  null,
        'func_404'      =>  null,
    ];

    /**
     * 框架公共初始化
     * 1. 配置项目路径
     * 2. 加载必需文件
     * 3. 加载配置内容
     */
    public static function init($PROJECT_DIR, $APP_NAME = '')
    {
        spl_autoload_register(['\litephp\core\Lite', 'autoload']);

        // 载入框架函数库
        require_once dirname(__DIR__) . '/includes/functions.php';

        // 项目目录
        if (!is_dir($PROJECT_DIR)) {
            throw new \ErrorException('错误：项目目录配置错误。');
        }
        self::$PROJECT_DIR = realpath($PROJECT_DIR);

        // 应用目录
        if ($APP_NAME) {
            if (!is_dir(self::$PROJECT_DIR . '/' . $APP_NAME)) {
                throw new \ErrorException('错误：应用目录配置错误。');
            }
            self::$APP_NAME = $APP_NAME;
        }

        // 加载项目配置
        $CONFIG_FILE = self::$PROJECT_DIR . '/config.php';
        if (file_exists($CONFIG_FILE) && ($CONFIG = require_once $CONFIG_FILE) && is_array($CONFIG)) {
            self::$CONFIG = array_merge(self::$CONFIG, $CONFIG);
        }
        // 加载应用配置
        if (self::$APP_NAME) {
            $CONFIG_FILE = self::$PROJECT_DIR . '/' . self::$APP_NAME . '/config.php';
            if (file_exists($CONFIG_FILE) && ($CONFIG = require_once $CONFIG_FILE) && is_array($CONFIG)) {
                self::$CONFIG = array_merge(self::$CONFIG, $CONFIG);
            }
        }

        //获取运行模式
        self::$MODE = strtoupper(self::$CONFIG['mode'] ?? 'ONLINE');

        // 设置时区
        $timeZone = self::$CONFIG['timezone'] ?? 'Asia/Shanghai' and date_default_timezone_set($timeZone);

        // 创建运行时目录
        $LOG_PATH = !empty(self::$CONFIG['log_path']) && file_exists(self::$CONFIG['log_path']) ? self::$CONFIG['log_path'] : self::$PROJECT_DIR . '/logs';
        if (!is_dir($LOG_PATH) && (!mkdir($LOG_PATH, 0775, true) || !chmod($LOG_PATH, 0775))) {
            throw new \ErrorException('错误：日志目录不存在或无权创建。');
        }
        self::$LOG_PATH = realpath($LOG_PATH);

        // 初始化日志类
        \litephp\Log::instance()->config($LOG_PATH);

        // 加载项目函数库文件
        $FUNCTIONS_FILE = self::$PROJECT_DIR . '/functions.php';
        if (file_exists($FUNCTIONS_FILE)) {
            require_once $FUNCTIONS_FILE;
        }
        // 加载应用函数库文件
        if (self::$APP_NAME) {
            $FUNCTIONS_FILE = self::$PROJECT_DIR . '/' . self::$APP_NAME . '/functions.php';
            if (file_exists($FUNCTIONS_FILE)) {
                require_once $FUNCTIONS_FILE;
            }
        }

        // 自定义加载文件
        if (!empty(self::$CONFIG['include_files'])) {
            if (!is_array(self::$CONFIG['include_files'])) {
                throw new \ErrorException('错误：无法载入自定义文件列表。');
            }
            foreach (self::$CONFIG['include_files'] as $includeFile) {
                if (!file_exists($includeFile)) {
                    throw new \ErrorException("错误：自定义加载文件 (" . basename($includeFile) . ") 不存在。");
                }
                require_once $includeFile;
            }
        }
        # 主动释放变量
        unset($CONFIG_FILE, $CONFIG, $timeZone, $LOG_PATH, $FUNCTIONS_FILE, $includeFile);
    }

    /**
     * 框架文件的自动加载机制
     */
    public static function autoload($classname)
    {
        if (strpos($classname, 'litephp\\') === 0) {
            $classfile = substr(str_replace('\\', '/', $classname), 8) . '.php';
            $classdir = ['/', '/lib/', '/toolbox/', '/core/'];
            foreach ($classdir as $dir) {
                $classpath = dirname(__DIR__) . $dir . $classfile;
                if (file_exists($classpath))  require_once $classpath;
            }
        } else {
            $classpath = self::$PROJECT_DIR . '/' . str_replace('\\', '/', $classname) . '.php';
            if (file_exists($classpath))  require_once $classpath;
        }
    }

    public static function __callStatic($name, $arguments)
    {
        $name = strtoupper($name);
        return self::$$name;
    }
}
