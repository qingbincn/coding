<?php
// Coding部署
return [
    'source'  => '/mydata/res/coding',
    'target'  => '/mydata/ftp/coding',
    'backup'  => $config['backup'].'/coding',
    'shell'   => 'sudo /etc/init.d/httpd restart',
    'refresh' => [
        'public/h5/index.html'
    ],
    'replace'=> [
        '?VER' => '?'.date('Ymd').rand(100, 999),
        'localhost' => 'mysql.phpbin.cn'
    ]
];