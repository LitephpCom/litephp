<?php
/**
 * 演示指令类
 */
class cmd
{
    public static $CmdHelp = [
        'name'  =>  'test指令',
        'description'   =>  '这是一个测试指令',
        'options'   =>  [
            '-t'    =>  '测试参数，返回hello test字符串'
        ],
    ];

    public function execute($cmdArgv)
    {
        if (array_key_exists('-t',$cmdArgv)) {
            echo 'hello test';
        } else {
            echo 'hello cmd';
        }
    }
}