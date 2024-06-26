<?php
/**
 * 命令行执行脚本
 */
error_reporting(E_ALL);
ignore_user_abort(true);
set_time_limit(0);

include __DIR__ . '/litephp/core/Lite.php';
include __DIR__ . '/litephp/traits/instance.php';

class LiteCmd
{
    use \litephp\traits\instance;

    /**
     * 版本号
     */
    public static $version = '1.0.0';

    /**
     * 运行模式
     * DEBUG: 开发调试模式，输出错误信息，记录错误+异常日志
     * ONLINE: 日志（线上）模式，不输出任何错误异常信息，但记录到日志
     */
    private $MODE = 'ONLINE';

    /**
     * 项目目录 - 非网站根目录,绝对路径
     */
    private $PROJECT_DIR;

    /**
     * 应用（目录）名
     * 同文件夹名称
     */
    private $APP_NAME = '';

    /**
     * 日志目录 - 绝对路径
     * 存放错误、异常等生成的日志
     */
    private $LOG_PATH;

    /**
     * 框架配置参数
     * mode: 运行模式
     * timezone: 时区
     * log_path: 日志目录路径，默认项目目录下的logs文件夹
     * include_files: 自定义载入文件列表
     * func_exception: 自定义异常处理函数 参数：($e, MODE)
     * func_404: 自定义404回调函数
     * command: 命令行模式，指令与类名映射
     */
    private $CONFIG = [
        'mode'          =>  'ONLINE',
        'timezone'      =>  'Asia/Shanghai',
        'log_path'      =>  null,
        'include_files' =>  [],
        'func_exception'    =>  null,
        'func_404'      =>  null,
        'command'       =>  [],
    ];

    /**
     * 原始参数
     */
    private $argvRaw = [];

    /**
     * 解析后的参数
     */
    private $argv;

    /**
     * 注册后的指令帮助信息
     * 格式：
     * cmd => ['name'=>'','description'=>'','options'=>['-t'=>'xxx','-r'=>'xxx']]
     */
    private $CmdHelp = [];

    /**
     * 启动
     * 默认$argv[1]是指令名称
     * 参数格式：-key=value
     * 框架固定参数：
     * -h|--help: 打印帮助
     * -P|--project_dir：项目目录，如 -P=/www/,--project_dir=/www/
     * -A|--app_name: 应用目录，如 -A=index
     */
    public function start($argv,$argc)
    {
        # 解析参数
        $this->argv = self::parseArgv($argv,$argc);
        if (empty($this->argv['-P']) && empty($this->argv['--project_dir'])) {
            $PROJECT_DIR = getcwd();
        } else {
            $PROJECT_DIR = $this->argv['-P'] ?: $this->argv['--project_dir'];
        }
        $APP_NAME = '';
        if (!empty($this->argv['-A']) || !empty($this->argv['--app_name'])) {
            $APP_NAME = !empty($this->argv['-A']) ? $this->argv['-A'] : $this->argv['--app_name'];
        }
        
        \litephp\core\Lite::init($PROJECT_DIR, $APP_NAME);
        $this->PROJECT_DIR = \litephp\core\Lite::PROJECT_DIR();
        $this->APP_NAME = \litephp\core\Lite::APP_NAME();
        $this->CONFIG = \litephp\core\Lite::CONFIG();
        $this->MODE = \litephp\core\Lite::MODE();
        $this->LOG_PATH = \litephp\core\Lite::LOG_PATH();

        // 初始化日志类
        \litephp\Log::instance()->config($this->LOG_PATH);

        // 错误异常处理 - 务必在初始化日志类后面
        $this->MODE == 'DEBUG' ? ini_set('display_errors', '1') : ini_set('display_errors', '0');
        set_exception_handler([$this, 'exception']);
        set_error_handler([$this, 'error']);

        try {
            if (!isset($this->CONFIG['command']) || !is_array($this->CONFIG['command'])){
                throw new \Exception('项目 command 配置项错误');
            }
            if ($this->argv[1] && !array_key_exists($this->argv[1], $this->CONFIG['command'])){
                throw new \Exception($this->argv[1].'指令不存在');
            }
            // 注册指令，形成帮助文档
            foreach($this->CONFIG['command'] as $command => $className) {
                if (!class_exists($className)) {
                    throw new \Exception($command.' 指令对应的'.$className.'类不存在');
                }
                if (!method_exists($className, 'execute')) {
                    throw new \Exception($command.' 指令对应的'.$className.'类方法execute不存在');
                }
                if (property_exists($className, 'CmdHelp')) {
                    $this->CmdHelp = array_merge($this->CmdHelp, [$command=>$className::$CmdHelp]);
                }
            }
            if (array_key_exists('-h',$this->argv) || array_key_exists('--help',$this->argv)) {
                // 输出帮助文档
                if ($this->argv[1]) {
                    exit($this->printHelp($this->argv[1]));
                }
                exit($this->printHelp());
            } else {
                // 执行指令
                if ($this->argv[1]) {
                    call_user_func([(new $this->CONFIG['command'][$this->argv[1]]),'execute'], $this->argv);
                }
            }
        } catch (\litephp\ExitException $e) {
            if ($this->MODE === 'DEBUG') {
                //输出打印错误信息
                echo $e->getMessage();
            }
        }
    }

    /**
     * 框架 - 自定义异常处理函数
     */
    public function exception($e)
    {
        // 记录日志
        $time = time();
        $filename = date('Ymd', $time) . '.log';
        $content = "Date: " . date('Y/m/d H:i:s', $time) . "\n";
        $content .= "Msg: " . $e->getMessage() . "\n";
        $content .= "File: " . $e->getFile() . "(" . $e->getLine() . ")\r\n=====详情如下=====\r\n";
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
        \litephp\Log::instance()->writeLn($filename, $content);
        // 异常处理
        $func = $this->CONFIG['func_exception'] ?? null;
        if ($func && LiteCheckFunction($func)) {
            call_user_func($func, $e, $this->MODE);
        } elseif ($this->MODE === 'DEBUG') {
            //输出打印错误信息
            echo $e->getMessage();
        }
    }

    /**
     * 框架 - 自定义错误处理函数
     */
    public function error($severity, $message, $file, $line)
    {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * 解析命令行参数
     * php LiteCmd command -abc
     * 默认$argv[1]是指令名称
     * 参数格式：
     * 1. -k=value
     * 2. --key=value
     * 3. k=value
     * 4. key
     */
    public function parseArgv($argv,$argc)
    {
        $this->argvRaw = $argv;
        $_argv = [0=>$argv[0],1=>null];
        # php LiteCmd command -abc
        if ($argc === 0) {
            return $_argv;
        }
        foreach($argv as $k => $v){
            if ($k == 0) {
                # LiteCmd 脚本文件
                continue;
            }
            if ($k === 1 && strpos($v,'-') === false) {
                # command
                $_argv[1] = $v;
                continue;
            }
            if (strpos($v,'-') !== false) {
                $tmpV = ltrim($v,'-');
                if (!$tmpV) {
                    continue;
                }
            }
            $key = '';
            $value = null;
            if (strpos($v,'=') !== false && strpos($v,'=') > 0) {
                list($key,$value) = explode('=',$v,2);
            } else {
                $key = $v;
            }
            # 防止后面参数覆盖脚本和指令名称
            if ($key === 0 || $key === '0' || $key === 1 || $key === '1'){
                continue;
            }
            $_argv[$key] = $value;
        }
        return $_argv;
    }

    /**
     * 输出指令帮助信息
     */
    public function printHelp($cmd = null)
    {
        if (!$cmd) {
            $helpStr = "This is a command line LiteCmd script.\r\nUsage:\r\n    php LiteCmd command [-P=Project_Dir] [-A=App_Name] [options] [-h]\r\nComands:\r\n";
            foreach($this->CmdHelp as $cmd => $config) {
                $helpStr .= "    {$cmd}\t{$config['name']}\t{$config['description']}\r\n";
            }
            return $helpStr;
        } else {
            if (!isset($this->CmdHelp[$cmd])) {
                return 'No help information found.';
            } else {
                $command = $this->CmdHelp[$cmd];
                $helpStr = "This is a {$cmd} command-line script.\r\nName:\t{$command['name']}\r\nDescription:\t{$command['description']}\r\nUsage:\r\n    php LiteCmd {$cmd} [options] [-h]\r\nOptions:\r\n";
                foreach($command['options'] as $option => $description){
                    $helpStr .= "    {$option}\t{$description}\r\n";
                }
                return $helpStr;
            }
        }
    }
}