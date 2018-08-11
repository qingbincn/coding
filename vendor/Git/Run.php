<?php
namespace Git;

/**
 * Coding.net自动部署
 *
 * @author phpbin
 *
 */
class Run
{
  
    /**
     * 单例实例
     *
     * @var Git\Run
     */
    static private $instance = null;
  
    /**
     * 全局配置
     *
     * @var array
     */
    private $config = array();
  
    /**
     * Coding消息
     * 
     * @var array
     */
    private $coding = array();
  
    /**
     * 初始化
     *
     * @param array $config
     */
    private function __construct(&$config)
    {
        $this->config = $config;
    }
  
    /**
     * 初始化单例
     *
     * @param array $config
     * @return Git\Run
     */
    static public function Instance(&$config)
    {
        if (self::$instance == null) {
            self::$instance = new Run($config);
        }
        return self::$instance;
    }
  
    /**
     * 分析推送消息
     * 
     * @param string $input
     * @return \Git\Run
     */
    public function route($input)
    {
        //Log::dump($input);
        $json = json_decode($input, true);
        $this->coding['name']    = $json['repository']['name'];
        $this->coding['branch']  = str_replace('refs/heads/', '', $json['ref']);
        $this->coding['email']   = $json['pusher']['email'];
        //$this->coding['pusher']  = $json['pusher']['name'];
        $this->coding['pusher']  = $json['head_commit']['committer']['name'];
        //$this->coding['email'] = 'phpbin@dingtalk.com';
        $this->coding['message'] = $json['head_commit']['message'];
        $this->coding['time']    = date('Y-m-d H:i:s', $json['head_commit']['timestamp']);
        $this->coding['project'] = $this->coding['name'].'/'.$this->coding['branch'];
        $this->config['subject'] = $this->title();
        $this->config['commits'] = $json['commits'];
    
        // 判断数据是不是完整
        foreach ($this->coding as $val) {
            if (!$val) {
                Log::error($this->coding['project'].' ::: 读取Coding消息失败~');
                exit('over');
            }
        }
    
        return $this;
    }
  
    /**
     * Token验证码
     * 
     * @param string $token
     */
    public function token($token)
    {
        if (empty($token) || $token != $this->config['token']) {
            $errmsg = $this->coding['project']." ::: Token [$token] 验证失败！";
            Log::error($errmsg);
            Log::send($this->coding['email'], $this->config['subject'], $errmsg);
            exit();
        }
        return $this;
    }
  
    /**
     * 拉取&同步
     * 
     */
    public function pull()
    {
        $name   = $this->coding['name'];
        $branch = $this->coding['branch'];
        $file   = strtolower($name.'.'.$branch);
        $config = require_once APPPATH.'/project/'.$file.'.php';
        $config['commits'] = $this->config['commits'];
        $config['branch'] = $branch;
        $config['name']  = $name;
    
        // 扩展类判断
        $filename = APPPATH.'/Git/'.ucfirst($name).'/'.ucfirst($branch).'.php';
        $filename = str_replace('-', '', $filename);
        if (file_exists($filename)) {
            require_once $filename;
            $fcname = "\\Git\\".ucfirst($name)."\\".ucfirst($branch);  
            $fcname = str_replace('-', '', $fcname);
        } else {
            $fcname = "\\Git\\GitBase";
        }
        $result = (new $fcname($config))->pull();
        $body   = $this->body($result);
        Log::send($this->coding['email'], $this->config['subject'], $body);
    }
  
    /**
     * 生成标题
     * 
     * @return string
     */
    private function title()
    {
        return "@".$this->coding['pusher']."：".$this->coding['message'];
    }
  
    /**
     * 生成通知内容 
     * 
     * @param array $result
     */
    private function body($result)
    {
        $body .= "### ".$this->config['subject']."\n";
        $body .= "-------------\n";
        $body .= "#### git pull ".$this->coding['name']." ".$this->coding['branch']."\n";
        $body .= "###### -----------------------------------------------------------------\n";
        $body .= "".$result['output']."\n";
        $body .= "###### -----------------------------------------------------------------\n";
        $body .= implode("\n", $result['sync'])."\n";
        if ($result['shell']) {
            $body .= implode("\n", $result['shell'])."\n";
        }
        return str_replace("\n", "    \n", $body);
    }
    
}