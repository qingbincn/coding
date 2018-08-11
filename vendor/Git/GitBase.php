<?php
namespace Git;

/**
 * GIT处理基类
 *
 * @author phpbin
 *
 */
class GitBase implements GitInterface
{
    
    /**
     * 项目配置
     * 
     * @var array
     */
    private $config = array();

    /**
     * 项目日志
     * 
     * @var array
     */
    private $logs = array();
  
    /**
     * 项目名称
     * 
     * @var string
     */
    private $name;
  
    /**
     * 初始化配置
     * 
     * @param array $config
     */
    public function __construct(&$config)
    {
        $this->config = $config;
        $this->name = strtolower($config['name']);
    }
  
    /**
     * 执行拉取操作
     * 
     * @return array
     */
    public function pull()
    {
        $source= $this->config['source'];
        $branch  = strtolower($this->config['branch']);
        $command = 'cd '.$source.' && sudo git pull origin '.$branch;
        $output  = shell_exec($command);
        $this->logs['output'] = $output;
        Log::info($command);
        Log::info($output);
        if (isset($this->config['target'])) {
            $this->sync($output);
        }
        if ($this->config['shell']) {
            $this->shell();
        }
        // 返回修改数据
        return $this->logs;
    }
  
    /**
     * 执行扩展Shell
     * 
     */
    public function shell()
    {
        $command = $this->config['shell'];
        $output = shell_exec($command);
        Log::info($this->name." --> $command");
        Log::info($output);
        $this->logs['shell'][] = $command;
        $this->logs['shell'][] = $output;
    }
  
    /**
     * 拉取数据同步到FTP
     * 说明：核心类
     * 
     * @param string $output
     */
    public function sync($output)
    {
        // 这里解析数据
        $commits = $this->commits($output);
        if (false == $commits) return ;
    
        // 1.新增文件
        foreach ($commits['added'] as $file) {
            $this->replace($file);
        }
        
        // 2.修改的文件
        foreach ($commits['modified'] as $file) {
            // 去除重复的文件
            if (in_array($file, $commits['added'])) continue;
            if (in_array($file, $commits['removed'])) continue;
            // 备份&替换
            $this->backup($file);
            $this->replace($file);
        }
        
        // 3.删除文件
        foreach ($commits['removed'] as $file) {
            $this->backup($file);
            $this->unlink($file);
        }
    }

    /**
     * 文件替换
     * 
     * {@inheritDoc}
     * @see \Git\GitInterface::replace()
     */
    public function replace($file)
    {
        $source = $this->config['source'].'/'.$file;
        $target = $this->config['target'].'/'.$file;
        $stream = file_get_contents($source);
    
        // 根据扩展名判断是不是需要替换内容
        $ext = strrchr(strtolower($file), '.');
        $cans = array('.php','.html', '.htm', '.js', '.css');
        if (in_array($ext, $cans)) {
            $replaces = $this->config['replace'];
            foreach ($replaces as $search=>$replace) {
                $stream = str_replace($search, $replace, $stream);
            }
            $stream = $this->compress($stream, $ext);
        }
        $this->dirs(dirname($target));
        file_put_contents($target, $stream);
        $logmsg= "Update ::: $source --> $target";
        Log::info($logmsg);
        $this->logs['sync'][] = $logmsg;
    }
    
    /**
     * 替换前备份
     * 
     * @param string $file
     */
    public function backup($file)
    {
        $target = $this->config['target'].'/'.$file;
        $backup = $this->config['backup'].'/'.date('Ymd').'/'.preg_replace("/\.\S{2,5}$/is", '_'.date('YmdHis')."$0", $file);
        $this->dirs(dirname($backup));
        if (!file_exists($target)) return ;
    
        $stream = file_get_contents($target);
        file_put_contents($backup, $stream);
        $logmsg = "Backup ::: $target --> $backup";
        Log::info($logmsg);
        $this->logs['sync'][] = $logmsg;
    }
  
    /**
     * 删除文件
     * 
     * @param string $file
     */
    public function unlink($file)
    {
        // 文件删除
        $target = $this->config['target'].'/'.$file;
        unlink($target);
        $logmsg = "Delete ::: --> $target";
        Log::info($logmsg);
        $this->logs['sync'][] = $logmsg;
        // 删除空目录
        $this->clear(dirname($target));
    }
    
    /**
     * 清空目录
     *
     * @param string $dir
     */
    public function clear($dir)
    {
        $files = scandir($dir);
        if (count($files) == 0) {
            rmdir($dir);
            $this->clear(dirname($dir));
            $logmsg = "Delete ::: --> $dir";
            Log::info($logmsg);
            $this->logs['sync'][] = $logmsg;
        }
    }
  
    /**
     * 深层目录处理
     * 
     * @param string $dir
     */
    private function dirs($dir)
    {
        $parent = dirname($dir);
        if (!is_dir($parent)) {
            if ($this->dirs($parent) && !is_dir($dir)) {
                mkdir($dir, 0777);
            };
        } else {
            if (!is_dir($dir)) {
                mkdir($dir, 0777);
                return true;
            }
        }
    }
  
    /**
     * 压缩操作
     * 说明：扩展使用
     * 
     * {@inheritDoc}
     * @see \Git\GitInterface::compress()
     */
    public function compress($stream, $ext)
    {
        return $stream;
    }
  
    /**
     * 分析pull结果
     * 
     * @param string $output
     * @return array
     */
    public function commits($output)
    {
        $returns['added'] = $returns['modified'] = $returns['removed'] = [];
        // 强制刷新的文件
        if ($this->config['refresh']) {
            $returns['modified'] = $this->config['refresh'];
        }
        $commits = $this->config['commits'];
        foreach ($commits as $commit) {
            $returns['added']    = array_merge($returns['added'], $commit['added']);
            $returns['modified'] = array_merge($returns['modified'], $commit['modified']);
            $returns['removed']  = array_merge($returns['removed'], $commit['removed']);
        }
        return $returns;
    }
}