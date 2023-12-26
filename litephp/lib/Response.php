<?php
/**
 * 响应输出
 */
namespace litephp;

class Response
{
    use traits\instance;

    /**
     * header 头
     */
    private $headers = [];

    /**
     * CONTENT-TYPE 类型
     */
    private const CONTENT_TYPE = [
        'html'  =>  'text/html',
        'text'  =>  'text/plain',
        'jpeg'  =>  'image/jpeg',
        'zip'   =>  'application/zip',
        'pdf'   =>  'application/pdf',
        'mpeg'  =>  'audio/mpeg',
        'css'   =>  'text/css',
        'js'    =>  'text/javascript',
        'json'  =>  'application/json',
        'xml'   =>  'text/xml',
    ];

    /**
     * 添加 header 头
     * @return self
     */
    public function setHeader($header, $replace = true, $response_code = NULL)
    {
        array_push($this->headers, [$header, $replace, (int)$response_code]);
        return $this;
    }

    /**
     * 删除 header 头
     * @return self
     */
    public function delHeader($header)
    {
        foreach($this->headers as $k => $v) {
            if ($v['header'] == $header) {
                unset($this->headers[$k]);
            }
        }
        header_remove($header);
        return $this;
    }

    /**
     * 清楚所有 header 头
     */
    public function clearHeader()
    {
        $this->headers = [];
        header_remove();
        return $this;
    }

    /**
     * 准备 Header 头
     * @return self
     */
    public function sendHeader()
    {
        if ($this->headers) {
            foreach ($this->headers as $header) {
                header(...$header);
            }
        }
        return $this;
    }

    /**
     * 设置 404 header头
     * @return self
     */
    public function setHeader404()
    {
        $this->setHeader('Status: 404 Not Found');
        return $this;
    }

    /**
     * 重定向
     * @return self
     */
    public function redirect($url)
    {
        $this->setHeader('Location: ' . $url);
        return $this;
    }

    /**
     * 设置 content-type 类型
     * @return self
     */
    public function setHeaderContent($typeAlias = 'html')
    {
        if (self::CONTENT_TYPE[$typeAlias]) {
            $this->setHeader('Content-Type: ' . self::CONTENT_TYPE[$typeAlias]);
        }
        return $this;
    }

    /**
     * 输出
     */
    public function send($response = NULL)
    {
        $this->sendHeader();
        if (is_null($response)) return NULL;
        echo (string)$response;
    }


}