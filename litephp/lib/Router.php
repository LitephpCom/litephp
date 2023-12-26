<?php

/**
 * 路由类
 */

namespace litephp;

class Router
{
    use traits\instance;

    /**
     * 入口文件
     */
    private $indexfile = 'index.php';

    /**
     * 路由表
     * 格式: 请求方法 => [路由规则:严格匹配或正则, 正则模式:是或否, 执行方法: 回调函数]
     * 'GET|POST|ANY' => [rule:'url|reg',reg:true:false,callback: func]
     */
    private $maps = [];

    /**
     * 当前请求方法
     */
    public $method;

    /**
     * 当前请求URI信息
     */
    public $uri;

    /**
     * 解析后的pathinfo信息
     */
    public $path;

    /**
     * 路由解析结果
     */
    public $matched = false;

    /**
     * 解析成功的路由规则
     */
    public $route;

    /**
     * 当前解析执行结果
     */
    public $response;

    /**
     * 解析失败时的处理函数
     */
    private $func404;

    /**
     * 初始化
     */
    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->uri = '/' . ltrim($_SERVER['REQUEST_URI'], '/');
    }

    /**
     * 配置
     * @return self
     */
    public function init($indexfile = 'index.php', $func404 = null)
    {
        $this->indexfile = $indexfile;
        if ($func404 && !LiteCheckFunction($func404)) {
            throw new \Exception('路由错误: 配置404函数错误。');
        }
        $this->func404 = $func404;
        //处理pathinfo
        $pos = strpos($this->uri, '?');
        $path = $pos === FALSE ? strtolower($this->uri) : strtolower(substr($this->uri, 0, $pos));
        if (stripos($this->uri, '/' . strtolower($this->indexfile)) === 0) {
            $this->path = substr($path, strlen('/' . $this->indexfile));
        } else {
            $this->path = $path;
        }
        return $this;
    }

    /**
     * 单独设置404函数
     * @return self
     */
    public function set404($func404)
    {
        if (!LiteCheckFunction($func404)) {
            throw new \Exception('路由错误: 404函数不可用。');
        }
        $this->func404 = $func404;
        return $this;
    }

    /**
     * 单独新增1条路由
     * @return self
     */
    public function push($method, $rule, $func, $reg = false, $route = [])
    {
        if (!LiteCheckFunction($func)) {
            throw new \Exception('路由错误: 新增处理函数不可用。[' . $method . ' => ' . $rule . ']');
        }
        $method = strtoupper($method);
        if (!$this->checkMethod($method)) {
            throw new \Exception('路由错误: 方法非GET,POST或*。[' . $method . ' => ' . $rule . ']');
        }
        if (!isset($this->maps[$method])) $this->maps[$method] = [];
        array_push($this->maps[$method], ['rule' => $rule, 'reg' => $reg ? true : false, 'function' => $func, 'self' => $route]);
        return $this;
    }

    /**
     * 批量增加路由规则
     * @return self
     * 格式：[method=>rule,function,reg],...
     */
    public function pushAll($maps = [])
    {
        foreach ($maps as $route) {
            if (!is_array($route) || count($route) < 2) {
                throw new \Exception('路由错误: 配置规则格式错误。');
            }
            // 兼容 php7.3 以前版本
            if (!function_exists('array_key_first')) {
                function array_key_first(array $arr)
                {
                    foreach ($arr as $key => $unused) {
                        return $key;
                    }
                    return NULL;
                }
            }
            $method = array_key_first($route);
            $rule = $route[$method];
            $function = next($route);
            if ($function === false || !LiteCheckFunction($function)) {
                throw new \Exception('路由错误: 执行函数(或方法)无法调用。');
            }
            $reg = next($route) ? true : false;
            $this->push($method, $rule, $function, $reg, $route);
        }
        return $this;
    }

    /**
     * 路由解析
     * 1. 优先解析GET,POST; 再尝试*
     * 2. 优先解析严格模式，后正则模式
     * 3. 匹配 * 方法的规则
     * 4. 匹配 * 方法的 * 规则
     * 5. 以上无法匹配，则解析失败 404
     */
    public function parse()
    {
        ob_clean();
        ob_start();
        // 匹配具体的请求方法
        if (array_key_exists($this->method, $this->maps)) {
            foreach ($this->maps[$this->method] as $route) {
                //严格模式
                if (!$route['reg'] && $route['rule'] === $this->path) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
                //正则模式
                if ($route['reg'] && preg_match($route['rule'], $this->path, $matches)) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
            }
        }

        // 匹配 任何* 方法
        $any = [];
        if (!$this->matched && array_key_exists('*', $this->maps)) {
            foreach ($this->maps['*'] as $route) {
                //跳过，放最后匹配
                if ($route['rule'] === '*') {
                    $any = $route;
                    continue;
                }
                //严格模式
                if (!$route['reg'] && $route['rule'] === $this->path) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
                //正则模式
                if ($route['reg'] && preg_match($route['rule'], $this->path, $matches)) {
                    $this->matched = true;
                    $this->route = $route;
                    if (is_array($route['function'])) {
                        $this->response = call_user_func([new $route['function'][0], $route['function'][1]]);
                        break;
                    } else {
                        $this->response = call_user_func($route['function']);
                        break;
                    }
                }
            }
        }

        // 匹配 * 方法的 * 规则
        if (!$this->matched && $any) {
            $this->matched = true;
            $this->route = $any;
            if (is_array($any['function'])) {
                $this->response = call_user_func([new $any['function'][0], $any['function'][1]]);
            } else {
                $this->response = call_user_func($any['function']);
            }
        }

        // 解析失败，404
        if (!$this->matched && LiteCheckFunction($this->func404)) {
            $this->response = call_user_func($this->func404);
        }
        $flushContent = ob_get_contents();
        ob_end_clean();
        return ($flushContent ?: '') . (string)$this->response;
    }

    /**
     * 检测支持的方法
     * @return bool
     */
    private function checkMethod($method)
    {
        $arr = ['GET', 'POST', '*'];
        return in_array(strtoupper($method), $arr);
    }
}
