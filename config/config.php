<?php
/**
 * Coding.net自动部署
 * 仓库：http://coding.net
 * 
 * @author phpbin
 * 
 */
$config = array();

// 日志目录
$config['logs'] = '/mydata/logs';

// 备份目录
$config['backup'] = '/mydata/backup';

// TOKEN
$config['token'] = '10db32ebc87929d4e14a0c2933abcdedd';

// 钉钉Token
$config['ding_token'] = '26e5981f1711a2985fe9f43d8c40f948fbf82d6ee3aafa64290e92431533cb0c';

// 邮箱配置
$config['email'] = array(
    'smtp' => 'smtp.phpbin.cn',
    'port' => '25',
    'from' => 'AudoDeploy<phpbin@aliyun.com>',
    'user' => 'phpbin@aliyun.com',
    'pass' => 'abc1234568787'
);