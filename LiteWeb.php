<?php

/**
 * 自动加载
 */

spl_autoload_register('autoloadLiteWeb');
function autoloadLiteWeb($classname)
{
    if (strpos($classname, 'litephp\\') === 0) {
        if (($classpath = __DIR__ . '/litephp/' . substr(str_replace('\\', '/', $classname), 8) . '.php') && file_exists($classpath))  require_once $classpath;
        if (($classpath = __DIR__ . '/litephp/lib/' . substr(str_replace('\\', '/', $classname), 8) . '.php') && file_exists($classpath))  require_once $classpath;
    }
}

/**
 * 框架类
 */
class LiteWeb
{
    use \litephp\traits\instance;

    /**
     * 版本号
     */
    public static $version = '1.0.0';

    /**
     * 运行模式
     * Dev: 开发模式，输出错误信息，记录错误+异常日志
     * Log: 日志（线上）模式，不输出任何错误异常信息，但记录到日志
     */
    private $MODE = 'Log';

    /**
     * 入口文件
     * ==相对于网站根目录的完整路径,非绝对路径,举例如下==
     * 网站根目录: /www
     * 文件所在完整路径: /www/admin/index.php
     * 则该配置为: admin/index.php
     */
    private $INDEX_FILE = 'index.php';

    /**
     * 项目目录 - 非网站根目录,绝对路径
     */
    private $PROJECT_DIR;

    /**
     * 应用（目录）名
     * 同文件夹名称
     */
    private $APP_NAME = 'index';

    /**
     * 运行时目录 - 绝对路径
     * 存放错误异常生成的日志等
     */
    private $RUNTIME_DIR = __DIR__ . '/Application/runtime';

    /**
     * 404 处理函数
     * 未匹配到路由时触发
     */
    private $FUNC_404;

    /**
     * 框架配置参数
     * mode: 运行模式
     * timezone: 时区
     * runtime: 运行时目录，默认项目目录下的runtime文件夹
     * functions: 官方函数库文件名列表
     * includes: 自定义载入文件列表
     * exception: 自定义异常处理函数 参数：($e, MODE)
     * function404: 自定义404回调函数
     */
    private $CONFIG = [
        'mode'          =>  'Log',
        'timezone'      =>  'Asia/Shanghai',
        'runtime'       =>  '',
        'functions'     =>  [],
        'includes'      =>  [],
        'exception'     =>  null,
        'function404'   =>  null,
    ];

    /**
     * 启动
     * $INDEX_FILE: 入口文件路径，相对于网站根目录，如 admin/index.php
     * $PROJECT_DIR: 项目目录，如 __DIR__.'/ApplicationName'
     * $APP_NAME: 应用（目录）名，如 index - 分别表示 $PROJECT_DIR/index 文件夹
     */
    public function start($INDEX_FILE, $PROJECT_DIR, $APP_NAME = '')
    {
        // 载入框架函数库
        require_once __DIR__ . '/litephp/functions/litephp.php';

        // 项目的类自动加载
        spl_autoload_register([$this, '_autoload']);

        // 解析入口文件
        $this->INDEX_FILE = $INDEX_FILE ? ltrim($INDEX_FILE, '/') : basename(get_included_files()[0]);

        // 项目目录
        if (!is_dir($PROJECT_DIR)) {
            exit('配置项错误：项目目录不存在。');
        }
        $this->PROJECT_DIR = realpath($PROJECT_DIR);

        // 应用目录
        if ($APP_NAME) {
            if (!is_dir($this->PROJECT_DIR . '/' . $APP_NAME)) {
                exit('配置项错误：应用目录不存在。');
            }
            $this->APP_NAME = $APP_NAME;
        }

        // 加载项目配置
        $CONFIG_FILE = $this->PROJECT_DIR . '/config.php';
        if (file_exists($CONFIG_FILE) && ($CONFIG = require_once $CONFIG_FILE) && is_array($CONFIG)) {
            $this->CONFIG = array_merge($this->CONFIG, $CONFIG);
        }
        // 加载应用配置
        if ($this->APP_NAME) {
            $CONFIG_FILE = $this->PROJECT_DIR . '/' . $this->APP_NAME . '/config.php';
            if (file_exists($CONFIG_FILE) && ($CONFIG = require_once $CONFIG_FILE) && is_array($CONFIG)) {
                $this->CONFIG = array_merge($this->CONFIG, $CONFIG);
            }
        }

        //获取运行模式
        $this->MODE = strtoupper($this->CONFIG['mode'] ?? 'Log');

        // 设置时区
        $timeZone = $this->CONFIG['timezone'] ?? 'Asia/Shanghai' and date_default_timezone_set($timeZone);

        // 创建运行时目录
        $RUNTIME_DIR = empty($CONFIG['runtime']) ? $this->PROJECT_DIR . '/runtime' : $CONFIG['runtime'];
        if (!is_dir($RUNTIME_DIR) && (!mkdir($RUNTIME_DIR, 0775, true) || !chmod($RUNTIME_DIR, 0775))) {
            exit('错误：运行时目录不存在或无权创建。');
        }
        $this->RUNTIME_DIR = realpath($RUNTIME_DIR);

        // 创建日志文件夹 - runtime目录下
        $LOG_PATH = $this->RUNTIME_DIR . ($this->APP_NAME ? '/' . $this->APP_NAME : '') . '/logs';
        if (!is_dir($LOG_PATH) && (!mkdir($LOG_PATH, 0775, true) || !chmod($LOG_PATH, 0775))) {
            exit('错误：日志文件夹不存在或无权创建。');
        }

        // 初始化日志类
        \litephp\Log::instance()->config($LOG_PATH);

        // 错误异常处理 - 务必在初始化日志类后面
        $this->MODE == 'LOG' ? ini_set('display_errors', '1') : ini_set('display_errors', '0');
        set_exception_handler([$this, 'exception']);
        set_error_handler([$this, 'error']);

        // 加载配置项 - functions
        if (!empty($this->CONFIG['functions'])) {
            if (!is_array($this->CONFIG['functions'])) {
                exit('配置项错误：函数库配置错误。');
            }
            foreach ($this->CONFIG['functions'] as $func) {
                if (!file_exists(__DIR__ . '/functions/' . $func . '.php')) {
                    exit("配置项错误：函数 ({$func}) 文件不存在。");
                }
                require_once __DIR__ . '/functions/' . $func . '.php';
            }
        }

        // 自定义加载文件
        if (!empty($this->CONFIG['includes'])) {
            if (!is_array($this->CONFIG['includes'])) {
                exit('配置项错误：自定义加载文件配置错误。');
            }
            foreach ($this->CONFIG['includes'] as $include) {
                if (!file_exists($include)) {
                    exit("配置项错误：自定义加载文件 (" . basename($path) . ") 不存在。");
                }
                require_once $include;
            }
        }

        // 载入路由配置
        $Router = \litephp\Router::instance()->init($this->INDEX_FILE, $this->CONFIG['function404'] ?? null);
        if (($ROUTE_FILE = $this->PROJECT_DIR . '/routes.php') && file_exists($ROUTE_FILE) && ($ROUTE_MAPS = require_once $ROUTE_FILE)) {
            $Router->pushAll($ROUTE_MAPS);
        }
        if ($this->APP_NAME && ($ROUTE_FILE = $this->PROJECT_DIR . '/' . $this->APP_NAME . '/routes.php') && file_exists($ROUTE_FILE) && ($ROUTE_MAPS = require_once $ROUTE_FILE)) {
            $Router->pushAll($ROUTE_MAPS);
        }
        // 路由解析
        try {
            $Response = \litephp\Response::instance();
            $res = $Router->parse();
            if (!$Router->matched) {
                $Response->clearHeader();
                $Response->setHeader404();
                ob_start();
                switch ($this->httpAccept()) {
                    case 'JSON':
                        echo \json_encode([
                            'code'  =>  404,
                            'message'   =>  'The Request is illegal.',
                            'data'  =>  [],
                        ], JSON_FORCE_OBJECT);
                        break;
                    case 'TEXT':
                        echo 'This page not found.';
                        break;
                    default:
                        include __DIR__ . '/litephp/tpl/404.html.php';
                }
                $res = ob_get_contents();
                ob_end_clean();
            }
        } catch (\litephp\ExitException $e) {
            $res = $e->getMessage();
        }
        // 输出
        $Response->send($res);
    }

    /**
     * 项目的类自动加载函数
     */
    private function _autoload($classname)
    {
        $classpath = $this->PROJECT_DIR . '/' . str_replace('\\', '/', $classname) . '.php';
        file_exists($classpath) && (require_once $classpath);
    }

    /**
     * 框架异常处理
     */
    public function exception($e)
    {
        // 记录日志
        $time = time();
        $filename = date('Ymd', $time) . '.log';
        $content = "\nDATE: " . date('Y/m/d H:i:s', $time) . "\n";
        $content .= "MSG: " . $e->getMessage() . "\n";
        $content .= "FILE: " . $e->getFile() . "(" . $e->getLine() . ")\r\n=====详情如下=====\r\n";
        $traceList = $e->getTrace();
        foreach ($traceList as $trace) {
            $fn = $trace['function'];
            if (isset($trace['type'])) {
                $fn = $trace['type'] . $fn;
            }
            if (isset($trace['class'])) {
                $fn = $trace['class'] . $fn;
            }
            if ($fn == get_class($this) . '->exception' || $fn == get_class($this) . '->error') {
                //跳过 异常处理函数
                continue;
            }
            $content .= isset($trace['file']) ? "File: " . $trace['file'] . "(" . $trace['line'] . ")\n" : '';
            $content .= "Func: " . $fn . "\n";
            if ($trace['args'] ?? '') {
                $content .= "---Args Json Start---\n";
                $content .= \json_encode($trace['args']) . "\n";
                $content .= "---Args Json End---\n";
            }
        }
        \litephp\Log::instance()->write($filename, $content);
        // 异常处理
        $func = $this->CONFIG['exception'];
        if (LiteCheckFunction($func)) {
            call_user_func($func, $e, $this->MODE);
        } elseif ($this->MODE !== 'LOG') {
            //输出打印错误信息
            ob_clean();
            ob_start();
            switch ($this->httpAccept()) {
                case 'JSON':
                    echo \json_encode([
                        'code'  =>  $e->getCode(),
                        'message'   =>  $e->getMessage(),
                        'data'  =>  [],
                    ], JSON_FORCE_OBJECT);
                    break;
                case 'TEXT':
                    echo $e->getMessage();
                    break;
                default:
                    include __DIR__ . '/litephp/tpl/exception.html.php';
            }
            ob_end_flush();
        }
    }

    /**
     * 框架错误处理
     */
    public function error($severity, $message, $file, $line)
    {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * 根据请求头返回不同类型
     * 优先级：JSON > HTML > TEXT
     */
    protected function httpAccept()
    {
        if ($_SERVER['HTTP_ACCEPT']) {
            if (stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== FALSE) {
                return 'JSON';
            }
            if (stripos($_SERVER['HTTP_ACCEPT'], 'text/html') !== FALSE) {
                return 'HTML';
            }
            if (stripos($_SERVER['HTTP_ACCEPT'], 'text/plain') !== FALSE) {
                return 'TEXT';
            }
        }
        return 'HTML';
    }
}
