<?php
namespace Lib\Oper;

/**
 * 目录内容替换
 * 
 * @author phpbin
 *
 */
class Replace
{
  
    /**
     * 配置数组
     *
     * @var array
     */
    private $config;
  
    /**
     * 根目录
     * 
     * @var string
     */
    private $base;
  
    /**
     * 允许替换文件
     * 
     * @var array
     */
    private $cans = array('.php','.html', '.htm', '.js', '.css');

    /**  
     * 初始化
     * 
     * @param array $config
     */
    public function __construct(&$config)
    {
        $this->config = $config;
    }
  
    /**
     * 开始执行替换
     * 
     * @param string $dir
     */
    public function run($dir)
    {
        echo "*********** Auto Start Replace ************* \n";
        $this->base = $dir;
        $this->start($dir);
        echo "-------------------------------------------- \n";
        echo "**************** Success******************** \n";
    }
  
    /**
     * 遍历目录递归替换
     * 
     * @param string $dir
     */
    public function start($dir)
    {
        if (!is_dir($dir)) {
        exit ("Error ::: $dir is not exists ::: ");
        echo "----------------- Over ----------------- \n";
        }
    
        $files = scandir($dir);
        foreach ($files as $file) {
        $full = $dir.'/'.$file;
        if ($file == "." || $file == "..") continue;
        if (is_dir($full)) {
            $this->start($full);
        } else {
            $this->doing($full, $file);
        }
        }
    }
  
    /**
     * 单个替换内容
     * 
     * @param string $file 文件名
     */
    public function doing($file)
    {
        echo "-------------------------------------------- \n";
        $name = str_replace($this->base.'/', '', $file);
        echo "Read ::: $name\n";
        $stream = file_get_contents($file);
        $ext = strrchr(strtolower($file), '.');
        if (in_array($ext, $this->cans)) {
            $replaces = $this->config['replace'];
            echo "Repl ::: $name\n";
            foreach ($replaces as $search=>$replace) {
                $stream = str_replace($search, $replace, $stream);
            }
        }
        file_put_contents($file, $stream);
        echo "Save ::: $name\n";
    }
}