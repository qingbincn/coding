<?php
/**
 * Coding.net自动部署
 * 仓库：http://coding.net
 * 
 * @author phpbin
 * 
 */
$config = [
    
    // 日志目录
    'logs' => '/mydata/logs',
    
    // 备份目录
    'backup' => '/mydata/backup',

    // 授权TOKEN
    'token' => '10db32ebc87929d4e14a0c2933abcdedd',
    
    // 钉钉Token
    'ding_token' => '10db32ebc87929d4e14a0c2933abcdedd',

     // 邮箱配置
    'email' => [
        'smtp' => 'smtp.phpbin.cn',
        'port' => '25',
        'from' => 'AudoDeploy<phpbin@aliyun.com>',
        'user' => 'phpbin@aliyun.com',
        'pass' => 'abc1234568787'
    ],
];