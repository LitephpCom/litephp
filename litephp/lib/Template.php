<?php

/**
 * 模板类
 */

namespace litephp;

class Template
{
    use traits\Instance;

    /**
     * 所有模板
     * @var array 'tplName' => ['FILE'=>'','CONTENT'=>'']
     */
    private $_allTpl = [];

    /**
     * 基础模板别名
     * @var string
     */
    private $_baseTpl = '';

    /**
     * 所有模板变量
     * @var array 'var' => $val
     */
    private $_allVars = [];

    /**
     * 设置基础模板
     * @return self $this
     */
    public function setBaseTpl($baseTpl = '')
    {
        if ($baseTpl) {
            $this->_baseTpl = $baseTpl;
        }
        return $this;
    }

    /**
     * 添加文件模板
     * @return self $this
     */
    public function addTpl($tplName, $tplFile)
    {
        if (!file_exists($tplFile)) {
            throw new \ErrorException("{$tplName} file does not exist.");
        }
        $this->_allTpl[$tplName] = ['FILE' => $tplFile, 'CONTENT' => ''];
        return $this;
    }

    /**
     * 添加内容模板
     * @return self $this
     */
    public function addContent($tplName, $tplContent = '')
    {
        $this->_allTpl[$tplName] = ['FILE' => '', 'CONTENT' => $tplContent];
        return $this;
    }

    /**
     * 设置模板变量
     * @return self $this
     */
    public function setVar($varName, $var = NULL)
    {
        $this->_allVars[$varName] = $var;
        return $this;
    }

    /**
     * 获取模板变量
     */
    public function getVar($varName, $default = NULL)
    {
        return $this->_allVars[$varName] ?? $default;
    }

    /**
     * 根据模板名渲染模板
     */
    private function loadTpl($tplName = '', $defaultContent = '')
    {
        if (isset($this->_allTpl[$tplName])) {
            if (isset($this->_allTpl[$tplName]['FILE']) && file_exists($this->_allTpl[$tplName]['FILE'])) {
                include $this->_allTpl[$tplName]['FILE'];
            } elseif (isset($this->_allTpl[$tplName]['CONTENT'])) {
                echo $this->_allTpl[$tplName]['CONTENT'];
            }
        } else {
            echo $defaultContent;
        }
    }

    /**
     * 渲染并输出模板
     */
    public function display()
    {
        if (!$this->_baseTpl) {
            throw new \ErrorException('Base template is NULL.');
        }
        $this->loadTpl($this->_baseTpl);
    }

    /**
     * 获取模板渲染后的内容
     * @return string
     */
    public function fetch()
    {
        ob_start();
        $this->display();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
