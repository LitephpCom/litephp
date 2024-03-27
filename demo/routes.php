<?php

/**
 * 路由规则 - 演示案例
 */
return [
    'index' => [
        'hello' =>  [
            'get|post|*', function () {
                return 'index hello.';
            }
        ],
    ],
    '/hello' =>  [
        [
            'get', function () {
                return 'get hello.';
            }
        ],
        [
            'post', function () {
                return 'post hello.';
            }
        ]
    ],
    '#/index/+#' =>  [
        'get|post|*', function () {
            return 'reg.';
        },
        true
    ],
];
