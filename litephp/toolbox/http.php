<?php

namespace litephp\toolbox;

class http
{
    /**
     * 最近一次请求 httpcode
     */
    public static $httpCode;

    /**
     * 请求原生结果
     * 请求失败，则为FALSE
     */
    public static $response;

    /**
     * 请求失败时的错误信息
     */
    public static $errMsg = '';

    /**
     * GET 请求
     */
    public static function get($url, $params = [], $headers = [])
    {
        // 将参数拼接到URL中
        $url .= '?' . http_build_query($params);

        return self::CURL($url, 'GET', [], $headers);
    }

    /**
     * POST 请求
     * 'Content-Type: application/x-www-form-urlencoded'
     * 'Content-Type: application/json'
     */
    public static function post($url, $post_data = [], $headers = [])
    {
        return self::CURL($url, 'POST', $post_data, $headers);
    }

    /**
     * 上传文件 - 传入文件路径
     * @param $post_data 一并提交的参数
     */
    public static function upload($url, $file_path, $post_data = [], $file_name = '', $param_name = 'file')
    {
        if ($file_name) {
            $PostData = [
                $param_name => new \CURLFile($file_path, mime_content_type($file_path), $file_name)
            ];
        } else {
            $PostData = [
                $param_name => new \CURLFile($file_path)
            ];
        }

        $PostData = array_merge($PostData, $post_data);

        $headers = [
            'Content-Type: multipart/form-data'
        ];

        return self::CURL($url, 'POST', $PostData, $headers);
    }

    /**
     * 上传文件 - 传入文件内容
     * @param $post_data 一并提交的参数
     * @param $file_name 上传文件名
     */
    public static function uploadByContent($url, $file_content, $post_data = [], $file_name = '', $param_name = 'file')
    {
        $PostData = [
            $param_name => $file_content,
        ];

        if ($file_name) {
            $PostData['filename'] = $file_name;
        }

        $PostData = array_merge($PostData, $post_data);

        $headers = [
            'Content-Type: multipart/form-data'
        ];

        return self::CURL($url, 'POST', $PostData, $headers);
    }

    /**
     * 统一CURL方法
     */
    public static function CURL($url, $method, $data = [], $headers = [])
    {
        $method = strtoupper($method);
        // 初始化CURL
        $ch = curl_init();
        // 设置CURL选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method == 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method == 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 禁用SSL证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 禁用SSL主机验证

        // 执行CURL请求
        self::$response = curl_exec($ch);

        // 获取HTTP状态码
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 错误信息
        $errMsg = curl_error($ch);
        if (self::$response === false) {
            self::$errMsg = $errMsg;
        }

        // 关闭CURL会话
        curl_close($ch);

        return self::$response;
    }
}
