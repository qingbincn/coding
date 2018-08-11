### Coding.net 简易自动部署工具1.0
#### 1 为什么要自动部署?
开发过过程中需要将本地代码同步到测试服务器进行调试,常的同步方式有FTP上传,Samba复制,以及服务上直接GIT PULL,如果连续的代码提交将是比较麻烦的过种.所有我写了个简易的同步工具,希望大家用的着.哈哈..

#### 2 测试环境部署方式
![image](http://www.phpbin.cn/wp-content/uploads/2018/08/coding.png)

**教程配置目录**  
1. 同步工具目录:     /mydata/www/coding
2. 服务器仓库目录:   /mydata/res/
3. WEB访问目录:      /mydata/www/
4. 日志目录(可写):   /mydata/logs
5. 备份目录(可写):   /mydata/backup

#### 3 Coding 部署公钥
Coding: 项目 -> 设置 -> 部署公钥    [配置文档](https://coding.net/help/doc/account/ssh-key.html)


#### 4 服务器配置
##### 4.1 安装apache
```shell
#  yum -y install httpd
#  chkconfig –levels 235 httpd on
```
##### 4.2 配置Apache执行权限
```shell
 # Sudo配置 或 超级管理员
 $ chmod u+w /etc/sudoers  //可写权限
 $ vim /etc/sudoers
 -------------------------------------------------------------
 #Defaults  requiretty    //注释掉这行
 apache  ALL=(ALL)  NOPASSWD:ALL  //添加一行
 -------------------------------------------------------------
 $ chmod u-w /etc/sudoers //还原440
 $ /etc/init.d/httpd restart
```

##### 4.3 部署工具配置
```shell
## 安装 GIT
# yum -y install git expect

## 从Github拉取代码
# cd /mydata/www
# git clone -b master git@github.com:phpbin/coding.git coding
```
测试: http://xxx.xxx.xxxx.xxx/coding/ 可以访问

工具配置:config目录
```php
$config = [
    
    // 日志目录
    'logs' => '/mydata/logs',
    
    // 备份目录
    'backup' => '/mydata/backup',

    // 授权TOKEN, coding推送消息的密钥
    'token' => '10db32ebc87929d4e14a0c2933abcdedd',
    
    // 钉钉access_token
    // 默认配置:工作时推送钉钉消息,其他发邮件
    'ding_token' => '10db32ebc87929d4e14a0c2933abcdedd',

     // 邮箱配置
    'email' => [
        'smtp' => 'smtp.phpbin.cn',
        'port' => '25',
        'from' => 'AudoDeploy<phpbin@aliyun.com>',
        'user' => 'phpbin@aliyun.com',
        'pass' => 'abc1234568787'
    ],
];
```
[如何设置钉钉机器人?](https://coding.net/help/doc/practice/dingtalk.html)

###### 4.3.1 部署的项目配置
> 教程部署项目为 **books**, 一定有用SSH方式
1. 初始化项目
```shell
## 拉取初始化代码(一定要用SSH方式)
# cd /mydata/res
# git clone -b master git@git.coding.net:phpbin/books.git books

## 同步代码到WEB访问目录
## 然后WEB目录进行必要的配置:如果数据库,上传目录等
# cp -rf /mydata/res/books /mydata/www/
```
2. 工具同步配置
```shell
## 回到配置目录
# cd /mydata/www/coding/project

## 新建配置文件,格式为: [项目名称.版本名称], 全部要求小写
## books.mast.php 说明:
```
```php
return [
    'source'  => '/mydata/res/books',   // 同步源
    'target'  => '/mydata/www/books',   // 同步目录
    'backup'  => $config['backup'].'/books',  // 备份目录
    'shell'   => 'sudo /etc/init.d/httpd restart',  // 同步后执行脚本(可选)
    'refresh' => [
        'public/h5/index.html'   // 需要同步的文件(一般刷新JS和CSS后缀)
    ],
    'replace'=> [    // 同步文件内容替换
        '?VER' => '?'.date('Ymd').rand(100, 999),   
        'localhost' => 'mysql.phpbin.cn'
    ]
];
```

#### 5 Coding WebHook 配置
项目: 设置 ->  WebHook -> 新建   

URL格式为: [http://xxx.xxx.xxxx.xxx/coding/?token=授权TOKEN]
![image](http://www.phpbin.cn/wp-content/uploads/2018/08/coding-webhook.png)
  
或后ping一下,不用管返回状态:
  
![image](http://www.phpbin.cn/wp-content/uploads/2018/08/coding-ping.png)


> 项目**Books**修改代码, 提交**Coding**后, 几秒后钉钉收到消息,说明同步成功.


#### 6 其他配置
1. 测试WEB服务器使用的是FTP, 可以将FTP挂载到 /mydata/www/ 目录  
[linux通过curlftpfs挂载FTP空间](http://www.phpbin.cn/archives/linux/841.html)

2. 测试WEB服务使用的是Samba,
```shell
# yum -y install cifs-utils
# mount -t cifs -o username=xxx,password=xxx //xxx.xxx.xxx.xxx/share  /mydata/share
```