<?php

/**
 * 请求类
 */

namespace litephp;

class Request
{
    use traits\Instance;

    /**
     * 当前请求方法
     */
    private $_method;

    public function __construct()
    {
        $this->_method = strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * 获取请求时间
     */
    public function requestTime($float = false)
    {
        return $float ? $this->SERVER('REQUEST_TIME_FLOAT', 0) : $this->SERVER('REQUEST_TIME', 0);
    }

    /**
     * 是否为GET请求
     */
    public function isGet()
    {
        return $this->_method === 'GET';
    }

    /**
     * 是否为POST请求
     */
    public function isPost()
    {
        return $this->_method === 'POST';
    }

    /**
     * 是否为PUT请求
     */
    public function isPut()
    {
        return $this->_method === 'PUT';
    }

    /**
     * 是否为DELETE请求
     */
    public function isDelete()
    {
        return $this->_method === 'DELETE';
    }

    /**
     * 是否为HEAD请求
     */
    public function isHead()
    {
        return $this->_method === 'HEAD';
    }

    /**
     * 是否为PATCH请求
     */
    public function isPatch()
    {
        return $this->_method === 'PATCH';
    }

    /**
     * 是否为OPTION请求
     */
    public function isOption()
    {
        return $this->_method === 'OPTION';
    }

    /**
     * 是否为cli
     */
    public function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 是否为cgi
     */
    public function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * 当前是否ssl
     */
    public function isSsl()
    {
        if ($this->server('HTTPS') && ('1' == $this->server('HTTPS') || 'on' == strtolower($this->server('HTTPS')))) {
            return true;
        } elseif ('https' == $this->server('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == $this->server('SERVER_PORT')) {
            return true;
        } elseif ('https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        }
        return false;
    }

    /**
     * 当前是否Ajax请求
     */
    public function isAjax()
    {
        $value  = $this->server('HTTP_X_REQUESTED_WITH');
        return ($value && 'xmlhttprequest' == strtolower($value)) ? true : false;
    }

    /**
     * 检测是否使用手机访问
     */
    public function isMobile()
    {
        if ($this->server('HTTP_VIA') && stristr($this->server('HTTP_VIA'), "wap")) {
            return true;
        } elseif ($this->server('HTTP_ACCEPT') && strpos(strtoupper($this->server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
            return true;
        } elseif ($this->server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->server('HTTP_USER_AGENT'))) {
            return true;
        }
        return false;
    }

    /**
     * 获取客户端IP地址
     * $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * $adv 是否进行高级模式获取（有可能被伪装）
     */
    public function ip($type = 0, $adv = true)
    {
        $type   = $type ? 1 : 0;
        static $ip = null;

        if (null !== $ip) {
            return $ip[$type];
        }

        $httpAgentIp = 'HTTP_X_REAL_IP'; //IP代理标识

        if ($this->server($httpAgentIp)) {
            $ip = $this->server($httpAgentIp);
        } elseif ($adv) {
            if ($this->server('HTTP_X_FORWARDED_FOR')) {
                $arr = explode(',', $this->server('HTTP_X_FORWARDED_FOR'));
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif ($this->server('HTTP_CLIENT_IP')) {
                $ip = $this->server('HTTP_CLIENT_IP');
            } elseif ($this->server('REMOTE_ADDR')) {
                $ip = $this->server('REMOTE_ADDR');
            }
        } elseif ($this->server('REMOTE_ADDR')) {
            $ip = $this->server('REMOTE_ADDR');
        }

        // IP地址类型
        $ip_mode = (strpos($ip, ':') === false) ? 'ipv4' : 'ipv6';

        // IP地址合法验证
        if (filter_var($ip, FILTER_VALIDATE_IP) !== $ip) {
            $ip = ('ipv4' === $ip_mode) ? '0.0.0.0' : '::';
        }

        // 如果是ipv4地址，则直接使用ip2long返回int类型ip；如果是ipv6地址，暂时不支持，直接返回0
        $long_ip = ('ipv4' === $ip_mode) ? sprintf("%u", ip2long($ip)) : 0;

        $ip = [$ip, $long_ip];

        return $ip[$type];
    }

    /**
     * 获取GET参数
     */
    public function get($name = null, $default = null, $filter = null)
    {
        return $this->_input('GET', $name, $default, $filter);
    }

    /**
     * 获取POST参数
     */
    public function post($name = null, $default = null, $filter = null)
    {
        return $this->_input('POST', $name, $default, $filter);
    }

    /**
     * 获取REQUEST参数
     */
    public function request($name = null, $default = null, $filter = null)
    {
        return $this->_input('REQUEST', $name, $default, $filter);
    }

    /**
     * 获取SERVER参数
     */
    public function server($name = null, $default = null)
    {
        return $_SERVER[$name] ?? $default;
    }

    /**
     * 返回过滤后的请求参数
     * @param string $request_method
     * @param string|null $name
     * @param null $default
     * @param string $filter
     * @return array|mixed
     */
    private function _input($request_method = '', $name = null, $default = null, $filter = null)
    {
        switch ($request_method) {
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
                $data = $_POST;
                break;
            case 'REQUEST':
                $data = $_REQUEST;
                break;
            default:
                $data = [];
        }
        if ($name === null) {
            return $data;
        }
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                $data = $default;
                break;
            }
        }
        if ($filter) {
            if (is_array($data)) {
                $data = array_map($filter, $data);
            } else {
                $data = \call_user_func($filter, $data);
            }
        }
        return $data;
    }
}
