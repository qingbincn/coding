<?php
/**
 * 部署文件自动替换
 * 
 * @author phpbin
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL^E_NOTICE);
defined('APPPATH') or define('APPPATH', __DIR__);
require APPPATH.'/config/loader.php';

// 替换内容
$config['replace'] = [
    '?VER'    => '?'.date('Ymd'),
    '/ji1/'   => '/service/',
    '/ji2/'   => '/ji5/',
    'ttcdemo' => 'tiantiance',
    'www.ji.com/sxk-ji1' => 'www.suixingkang.com/service',
    'www.ji.com/sxk-ji2' => 'www.suixingkang.com/ji5',
    'www.ji.com/sxk-srv' => 'www.suixingkang.com/service/sxk-srv',
    'www.ji.com/tiantiance' => 'www.suixingkang.com/tiantiance',
    'www.ji.com' => 'www.suixingkang.com'
];

// 执行替换
(new Lib\Oper\Replace($config))->run($argv[1]);
echo 'success~~';