<?php

return [
    /**
     * 监听地址列表
     * 字符串json格式数组
     */
    'uris' => json_decode(env('HPROSE_URIS', '["tcp://0.0.0.0:8888"]'), true),

    /**
     * 鉴权验证
     *      reject_code 验证未通过标识码
     *      reject_msg  验证未通过提示信息
     *      secret      RPC鉴权密钥
     *      timeout     超时限制（秒）
     */
    'auth'  => [
        'reject_code'   => env('HPROSE_REJECT_CODE',0),
        'reject_msg'    => env('HPROSE_REJECT_MSG',"Server拒绝本次请求"),
        'secret'        => env('HPROSE_SECRET',"123456789"),
        'timeout'       => env('HPROSE_TIMEOUT',60),
    ],

    /**
     * true开启 false关闭，开启后将自动对外发布一个远程调用方法 `demo`
     * $client->demo()
     */
    'demo' => env('HPROSE_DEMO'),
];
