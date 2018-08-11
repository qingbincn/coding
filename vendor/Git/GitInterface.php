<?php
namespace Git;

/**
 * GIT处理接口
 * 说明：所有版本分支类都必须实现这个接口
 * 
 * @author phpbin
 *
 */
Interface GitInterface
{
  
    /**
     * 替换内容
     * 
     * @param string $file
     */
    public function replace($file);
  
    /**
     * 压缩
     * 
     * @param string $stream
     * @param string $ext
     */
    public function compress($stream, $ext);
}