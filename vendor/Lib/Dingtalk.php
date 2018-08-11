<?php
namespace Lib;

/**
 * 钉钉消息
 * 
 * @author phpbin
 *
 */
class Dingtalk
{
    
    /**
     * 消息API
     * 
     * @var string
     */
    private $dingapi = 'https://oapi.dingtalk.com/robot/send';
  
    /**
     * ACCESS
     * 
     * @var string
     */
    private $access_token = '';
  
    /**
     * 初始化
     * 
     * @param string $access_token
     */
    public function __construct(&$access_token)
    {
        $this->access_token = $access_token;
    }
  
    /**
     * 发送消息
     * 
     * @param string $title 标题
     * @param string $body 内容
     * @return mixed
     */
    public function send($title, $body)
    {
        $url = $this->dingapi.'?access_token='.$this->access_token;
        $post = '{
            "msgtype": "markdown",
            "markdown": {"title":"'.$title.'",
                "text":"'.$body.'"
            }
        }';
        $json = $this->post($url, $post);
        $json = json_decode($json, true);
        return $json['errmsg'] == 'ok' ? '钉钉消息推送成功~' : '钉钉消息推送失败~';
    }
  
    /**
     * http请求
     * 
     * @param string $url
     * @param string $body
     * @return mixed
     */
    private function post($url, $body)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json;charset=utf-8"));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }
}