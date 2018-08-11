<?php
namespace Git;

/**
 * 服务日志记录
 * 
 * @author phpbin
 *
 */
class Log
{
  
    /**
     * 推送消息
     * 规则：上班时间推送钉钉，其他时间推送邮箱
     * 
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public static function send($to, $subject, $body)
    {
        $hour = date('H');
        $week = date('w');
        if (($hour>=9 && $hour<=20) && ($week>0 && $week<6)) {
            self::ding($subject, $body);
        } else {
            self::email($to, $subject, $body);
        }
    }
  
    /**
     * 发送邮件
     * 
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public static function email($to, $subject, $body)
    {
        global $config;
        $mail = new \Lib\Email\Email($config['email']);
        $rest = $mail->send($to, $subject, nl2br($body));
        Log::info("Email -> $to ::: $rest");
    }
    
    /**
     * 钉钉群组消息
     *
     * @param string $subject
     * @param string $body
     */
    public static function ding($subject, $body)
    {
        global $config;
        $mail = new \Lib\Dingtalk($config['ding_token']);
        $rest = $mail->send($subject, $body);
        Log::info("Ding -> dingtalk ::: $rest");
    }
  
    /**
     * 记录日志
     * 
     * @param string $message
     */
    public static function info($message)
    {
        self::save('info', $message);
    }
  
    /**
     * 调试记录
     *
     * @param string $message
     */
    public static function debug($message)
    {
        echo self::save('debug', $message);
    }
  
    /**
     * 警告记录
     *
     * @param string $message
     */
    public static function warning($message)
    {
        echo self::save('warning', $message);
    }
  
    /**
     * 错误消息处理
     * 
     * @param string $message
     */
    public static function error($message)
    {
        echo self::save('error', $message);
    }
  
    /**
     * 保存日志文件
     * 
     * @param string $level [INFO, DEBUG, WARNING, ERROR]
     * @param string $mesg
     */
    public static function save($level, $mesg)
    {
        global $config;
        $logs = $config['logs'].'/'.date('Y-m-d').'.log';
        $mesg = strtoupper($level)." ".date('Y-m-d H:i:s')." ::: ".$mesg."\n";
        error_log($mesg, 3, $logs);
        return $mesg;
    }
  
    /**
     * 记录详细
     *
     * @param  array $params
     */
    public static function dump(...$params)
    {
        global $config;
        ob_start();
        echo "\n";
        echo "-------------------------------------------------------------\n";
        foreach ($params as $param) {
            if (is_string($param) && stripos($param, '<!DOCTYPE') !== false) continue;
            if (is_string($param) && stripos($param, 'JFIF') !== false) continue;
            var_dump($param);
        }
        echo "-------------------------------------------------------------\n";
        $mesg = ob_get_contents();
        ob_end_clean();
        global $config;
        $logs = $config['logs'].'/dump.log';
        error_log($mesg, 3, $logs);
    }
}