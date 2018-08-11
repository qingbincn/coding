<?php
/**
 * Coding.net 自动部署
 * 
 * 说明：将指定版本pull取服务器，然后同步FTP
 * 
 */
header('Content-Type: text/html; charset=utf-8');
set_time_limit(1000);
error_reporting(E_ALL^E_NOTICE);
defined('APPPATH') or define('APPPATH', __DIR__);
require APPPATH.'/config/loader.php';
require APPPATH.'/config/config.php';

// 参数
$input = file_get_contents('php://input');
$token = $_GET['token'];

// 执行
\Git\Run::Instance($config)
->route($input)
->token($token)
->pull();

// 输出
echo 'complete ~~';