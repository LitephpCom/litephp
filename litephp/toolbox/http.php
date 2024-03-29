<?php

namespace litephp\toolbox;

use Exception;

class http
{
    /**
     * 最近一次请求 httpcode
     */
    public static $httpCode;

    /**
     * 最近一次请求 执行结果
     */
    public static $response;

    /**
     * GET请求
     * 自定义 header 头
     */
    public static function get($url, $params = [], $headers = [])
    {
        $ch = curl_init();

        // 将参数拼接到URL中
        $url .= '?' . http_build_query($params);

        // 设置curl选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // 执行CURL请求
        self::$response = curl_exec($ch);
        // 获取HTTP状态码
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 检查是否有错误发生
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        // 关闭CURL会话
        curl_close($ch);
        if ($errno) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }

    /**
     * CURL POST提交 - 推荐
     * $headers = array(
     *      'Content-Type: application/json',
     *      'Authorization: Bearer your_access_token'
     *  );
     */
    public static function curl($url, $data = [], $type = 'json', $headers = [])
    {
        // 初始化CURL
        $ch = curl_init();
        // 设置CURL选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($type == 'json') {
            $headers[] = 'Content-Type: application/json';
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            $headers[] = 'application/x-www-form-urlencoded';
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // 执行CURL请求
        self::$response = curl_exec($ch);
        // 获取HTTP状态码
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 检查是否有错误发生
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        // 关闭CURL会话
        curl_close($ch);
        if ($errno) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }

    /**
     * file_get_contents函数 POST提交
     */
    public static function fileGetContents($url, $data = [], $type = 'json', $header = [])
    {
        $http = ['http' => [
            'method'    =>  'POST'
        ]];
        if ($type == 'json') {
            $header[] = 'Content-Type: application/json';
            if ($data) $http['http']['content'] = json_encode($data);
        } else {
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            if ($data) $http['http']['content'] = http_build_query($data);
        }
        if ($header) $http['http']['header'] = implode("\r\n", $header);
        // 创建上下文流
        $context = stream_context_create($http);
        // 发起POST请求
        self::$response = file_get_contents($url, false, $context);
        // 获取HTTP状态码
        $errmsg = '';
        if (isset($http_response_header)) {
            $httpStatus = explode(' ', $http_response_header[0]);
            self::$httpCode = isset($httpStatus[1]) ? $httpStatus[1] : null;
            // 获取错误信息
            // if (self::$httpCode >= 400) {
            $errmsg = $http_response_header[0];
            // }
        }
        if (self::$response === false) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }

    /**
     * fopen方式 POST请求
     */
    public static function fopen($url, $data = [], $type = 'json', $header = [])
    {
        $http = ['http' => [
            'method'    =>  'POST'
        ]];
        if ($type == 'json') {
            $header[] = 'Content-Type: application/json';
            if ($data) $http['http']['content'] = json_encode($data);
        } else {
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            if ($data) $http['http']['content'] = http_build_query($data);
        }

        // 创建上下文流
        $context = stream_context_create($http);

        // 打开URL并获取句柄
        $handle = fopen($url, 'r', false, $context);

        // 获取HTTP状态码
        $metaData = stream_get_meta_data($handle);
        $httpStatus = $metaData['wrapper_data'][0];
        preg_match('/HTTP\/1\.\d (\d+)/', $httpStatus, $matches);
        self::$httpCode = isset($matches[1]) ? $matches[1] : null;
        $errmsg = $httpStatus;

        // 获取结果
        self::$response = stream_get_contents($handle);

        // 关闭句柄
        fclose($handle);

        if (self::$response === false) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }

    /**
     * 通过文件路径上传
     * 利用 CURL
     */
    public static function uploadByCURL($url, $file_path, $file_name = null, $paramName = 'file')
    {
        // 构建POST请求数据
        if ($file_name) {
            $post_data = [
                $paramName => new \CURLFile($file_path, mime_content_type($file_path), $file_name)
            ];
        } else {
            $post_data = [
                $paramName => new \CURLFile($file_path)
            ];
        }

        // 初始化cURL会话
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行cURL请求
        self::$response = curl_exec($ch);

        // 获取HTTP状态码
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 关闭cURL会话
        curl_close($ch);

        // 检查是否有错误发生
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        // 关闭CURL会话
        curl_close($ch);
        if ($errno) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }

    /**
     * 通过文件路径上传
     * 利用 file_get_contents 函数
     */
    public static function uploadByfileGetContents($url, $file_path, $file_name = null, $paramName = 'file')
    {
        if (!$file_name) {
            $file_name = basename($file_path);
        }

        // 构建请求内容
        $boundary = uniqid();
        $content = "--$boundary\r\n";
        $content .= "Content-Disposition: form-data; name=\"$paramName\"; filename=\"$file_name\"\r\n";
        $content .= "Content-Type: " . mime_content_type($file_path) . "\r\n\r\n";
        $content .= file_get_contents($file_path) . "\r\n";
        $content .= "--$boundary--\r\n";

        $stream = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: multipart/form-data; boundary=$boundary",
                'content' => $content
            )
        ));

        // 发起请求
        self::$response = file_get_contents($url, false, $stream);

        // 获取HTTP状态码
        $errmsg = '';
        if (isset($http_response_header)) {
            $httpStatus = explode(' ', $http_response_header[0]);
            self::$httpCode = isset($httpStatus[1]) ? $httpStatus[1] : null;
            // 获取错误信息
            // if (self::$httpCode >= 400) {
            $errmsg = $http_response_header[0];
            // }
        }
        if (self::$response === false) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }

    /**
     * 通过文件内容上传
     * 利用 CURL
     */
    public static function uploadByCURLFromContent($url, $file_content, $file_name = null, $paramName = 'file')
    {
        // 构建POST请求数据
        $post_data = [
            $paramName => $file_content,
        ];
        if ($file_name) {
            $post_data['filename'] = $file_name;
        }

        // 初始化cURL会话
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行cURL请求
        self::$response = curl_exec($ch);

        // 获取HTTP状态码
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 关闭cURL会话
        curl_close($ch);

        // 检查是否有错误发生
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        // 关闭CURL会话
        curl_close($ch);
        if ($errno) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }

    /**
     * 上传文件内容
     * 通过 file_get_contents 函数
     */
    public static function uploadByfileGetContentsFromContent($url, $file_content, $file_name = null, $paramName = 'file')
    {
        if (!$file_name) {
            $file_name = 'unnamed';
        }

        // 构建请求内容
        $boundary = uniqid();
        $content = "--$boundary\r\n";
        $content .= "Content-Disposition: form-data; name=\"$paramName\"; filename=\"$file_name\"\r\n";
        $content .= $file_content . "\r\n";
        $content .= "--$boundary--\r\n";

        $stream = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: multipart/form-data; boundary=$boundary",
                'content' => $content
            )
        ));

        // 发起请求
        self::$response = file_get_contents($url, false, $stream);

        // 获取HTTP状态码
        $errmsg = '';
        if (isset($http_response_header)) {
            $httpStatus = explode(' ', $http_response_header[0]);
            self::$httpCode = isset($httpStatus[1]) ? $httpStatus[1] : null;
            // 获取错误信息
            // if (self::$httpCode >= 400) {
            $errmsg = $http_response_header[0];
            // }
        }
        if (self::$response === false) {
            throw new Exception($errmsg);
        }
        return self::$response;
    }
}
