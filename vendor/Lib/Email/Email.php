<?php
namespace Lib\Email;

/**
 * 邮件发送
 * 
 * @author phpbin
 *
 */
class Email
{
  
    /**
     * 配置
     *
     * @var array
     */
    private $config;
  
    /**
     * 初始化类
     *
     */
    public function __construct(&$config)
    {
        $this->config = $config;
    }
  
    /**
     * 发送邮件
     * 
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return boolean
     */
    public function send($to, $subject, $body)
    {
        $smtp = new Smtp();
        $smtp->init(
            $this->config['smtp'], 
            $this->config['port'], 
            true, 
            $this->config['user'], 
            $this->config['pass']
        );
        $result = $smtp->sendmail($to, $this->config['from'], $subject, $body);
        if ($result == '1') {
            return '邮件发送成功';
        } else {
            return $result;
        }
    }
}
