<?php
/**
 * 全局自动加载类
 *
 * @author phpbin
 *
 */
spl_autoload_register(function ($class) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        \Git\Log::error("CLASS: The $file don't exists ~~~");
    }
});