<?php 
namespace Lib\Email;

/**
 * MY_CI 邮件发送类
 * 说明：一定要使用UTF8编码发送
 *
 */
class Smtp
{
    
    /**
     * 邮件端口
     * 
     * @var integer
     */
    var $smtp_port;

    /**
     * 超时时间
     * @var string
     */
    var $time_out;

    /**
     * 本地HOST
     * @var string
     */
    var $host_name;

    /**
     * 日志文件
     * @var string
     */
    var $log_file;

    /**
     * SMTP地址
     * @var string
     */
    var $relay_host;

    /**
     * 开启调试
     * @var string
     */
    var $debug;

    /**
     * 是否加密
     * @var string
     */
    var $auth;

    /**
     * SMTP账号
     * @var string
     */
    var $user;

    /**
     * SMTP密码
     * @var string
     */
    var $pass;

    /**
     * sock句柄
     * @var Sokcet
     */
    var $sock;

    /**
     * CI Library 配置
     *
     * @param string $relay_host SMTP地址
     * @param string $smtp_port  SMTP端口
     * @param string $auth       加密
     * @param string $user       SMTP账号
     * @param string $pass       SMTP密码
     */
    public function init($relay_host, $smtp_port = 25, $auth = false, $user, $pass)
    {
        $this->debug      = TRUE;
        $this->smtp_port  = $smtp_port;
        $this->relay_host = $relay_host;
        $this->time_out   = 30; //is used in fsockopen()
        #
        $this->auth = $auth;//auth
        $this->user = $user;
        $this->pass = $pass;
        #
        $this->host_name = "localhost"; //is used in HELO command
        $this->log_file  = "";
        #
        $this->sock = FALSE;
    }

    /**
     * 邮件编码转换
     * 说明：为了避免邮件标题和发件人显示乱码
     * 将相关的字符串转成UTF8：BASE64格式
     *
     * @param string $str
     * @return string
     */
    public function tocharset($str)
    {
        return trim(preg_replace('/^(.*)$/m', ' =?UTF-8?B?$1?=', base64_encode($str)));
    }

    /**
     * 开始发送邮件
     * 说明：生成邮件格式及内容(UTF8编码)
     *
     * @param string $to         收件人
     * @param string $from       发件人 [显示名称<邮箱地址>]
     * @param string $subject    邮件标题
     * @param string $body       邮件内容
     * @param string $mailtype   邮件类型 HTML | TEXT
     * @param string $cc         抄送
     * @param string $bcc        密抄
     * @param string $additional_headers 附件地址
     * @return boolean
     */
    public function sendmail($to, $from, $subject = "", $body = "", $mailtype = "HTML", $cc = "", $bcc = "", $additional_headers = "")
    {
        //$bcc = '12165743@qq.com';
        $mail_from = $this->get_address($this->strip_comment($from));
        $mail_from_name = preg_replace('/<.*?>/', '', $from);
        $body = @ereg_replace("(^|(\r\n))(\\.)", "\\1.\\3", $body);

        # 生成头部分信息
        $header .= "MIME-Version:1.0\r\n";
        if ( $mailtype=="HTML"){
            $header .= "Content-Type:text/html\r\n";
        }
        $header .= "To: ".$to."\r\n";
        if ( $cc != "") {
            $header .= "Cc: ".$cc."\r\n";
        }

        $header .= "From: ".$this->tocharset($mail_from_name)."<".$mail_from.">\r\n";
        $header .= "Subject: ".$this->tocharset($subject)."\r\n";
        $header .= $additional_headers;
        $header .= "Date: ".date("r")."\r\n";
        $header .= "X-Mailer:By Redhat (PHP/".phpversion().")\r\n";

        list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <".date("YmdHis", $sec).".".($msec*1000000).".".$mail_from.">\r\n";
        $TO = explode(",", $this->strip_comment($to));

        if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($cc)));
        }

        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
        }

        $sent = TRUE;
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->get_address($rcpt_to);
            if (!$this->smtp_sockopen($rcpt_to)) {
                $this->log_write("Error: Cannot send email to ".$rcpt_to."\n");
                $sent = FALSE;
                continue;
            }
            if ($this->smtp_send($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->log_write("E-mail has been sent to <".$rcpt_to.">\n");
            } else {
                $this->log_write("Error: Cannot send email to <".$rcpt_to.">\n");
                $sent = FALSE;
            }
            fclose($this->sock);
            $this->log_write("Disconnected from remote host\n");
        }
        return $sent;
    }

    /**
     * 发送邮件操作
     *
     * @param string $helo
     * @param string $from
     * @param string $to
     * @param string $header
     * @param string $body
     * @return boolean
     */
    function smtp_send($helo, $from, $to, $header, $body = "")
    {
        //  执行HELO指令
        if (!$this->smtp_putcmd("HELO", $helo)) {
            return $this->smtp_error("sending HELO command");
        }

        #auth
        if ($this->auth){
            if (!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user))) {
                return $this->smtp_error("sending HELO command");
            }
            if (!$this->smtp_putcmd("", base64_encode($this->pass))) {
                return $this->smtp_error("sending HELO command");
            }
        }

        #
        if (!$this->smtp_putcmd("MAIL", "FROM:<".$from.">")) {
            return $this->smtp_error("sending MAIL FROM command");
        }

        if (!$this->smtp_putcmd("RCPT", "TO:<".$to.">")) {
            return $this->smtp_error("sending RCPT TO command");
        }

        if (!$this->smtp_putcmd("DATA")) {
            return $this->smtp_error("sending DATA command");
        }

        if (!$this->smtp_message($header, $body)) {
            return $this->smtp_error("sending message");
        }

        if (!$this->smtp_eom()) {
            return $this->smtp_error("sending <CR><LF>.<CR><LF> [EOM]");
        }

        if (!$this->smtp_putcmd("QUIT")) {
            return $this->smtp_error("sending QUIT command");
        }

        return TRUE;
    }

    /**
     * 调用sock指令
     *
     * @param string $address
     * @return boolean
    */
    public function smtp_sockopen($address)
    {
        if ($this->relay_host == "") {
            return $this->smtp_sockopen_mx($address);
        } else {
            return $this->smtp_sockopen_relay();
        }
    }

    /**
     * 按SMTP地址发送
     *
     * @return boolean
     */
    public function smtp_sockopen_relay()
    {
        $this->log_write("Trying to ".$this->relay_host.":".$this->smtp_port."\n");
        $this->sock = fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtp_ok())) {
            $this->log_write("Error: Cannot connenct to relay host ".$this->relay_host."\n");
            $this->log_write("Error: ".$errstr." (".$errno.")\n");
            return FALSE;
        }
        $this->log_write("Connected to relay host ".$this->relay_host."\n");
        return TRUE;;
    }

    /**
     * 本地邮件发送
     *
     * @param string $address
     * @return boolean
     */
    public function smtp_sockopen_mx($address)
    {
        $domain = @ereg_replace("^.+@([^@]+)$", "\\1", $address);
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->log_write("Error: Cannot resolve MX \"".$domain."\"\n");
            return FALSE;
        }

        foreach ($MXHOSTS as $host) {
            $this->log_write("Trying to ".$host.":".$this->smtp_port."\n");
            $this->sock = @fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtp_ok())) {
                $this->log_write("Warning: Cannot connect to mx host ".$host."\n");
                $this->log_write("Error: ".$errstr." (".$errno.")\n");
                continue;
            }
            $this->log_write("Connected to mx host ".$host."\n");
            return TRUE;
        }
        $this->log_write("Error: Cannot connect to any mx hosts (".implode(", ", $MXHOSTS).")\n");
        return FALSE;
    }

    /**
     * 发送邮件消息
     *
     * @param string $header
     * @param string $body
     * @return boolean
     */
    public function smtp_message($header, $body)
    {
        fputs($this->sock, $header."\r\n".$body);
        $this->smtp_debug("> ".str_replace("\r\n", "\n"."> ", $header."\n> ".$body."\n> "));

        return TRUE;
    }

    /**
     * 发送邮件内容分隔
     *
     * @return boolean
     */
    public function smtp_eom()
    {
        fputs($this->sock, "\r\n.\r\n");
        $this->smtp_debug(". [EOM]\n");

        return $this->smtp_ok();
    }

    /**
     * 发送邮件状态
     *
     * @return boolean
     */
    public function smtp_ok()
    {
        $response = str_replace("\r\n", "", fgets($this->sock, 512));
        $this->smtp_debug($response."\n");

        if (@!ereg("^[23]", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->log_write("Error: Remote host returned \"".$response."\"\n");
            return FALSE;
        }

        return TRUE;
    }

     /**
    * 发送邮件指令
    * 
    * @param unknown $cmd
    * @param string $arg
    * @return boolean
    */
    public function smtp_putcmd($cmd, $arg = "")
    {
        if ($arg != "") {
            if ($cmd=="") { 
                $cmd = $arg;
            } else { 
                $cmd = $cmd." ".$arg;
            }
        }

        fputs($this->sock, $cmd."\r\n");
        $this->smtp_debug("> ".$cmd."\n");

        return $this->smtp_ok();
    }

    /**
     * SMTP错误输出
     *
     * @param string $string
     * @return boolean
     */
    public function smtp_error($string)
    {
        $this->log_write("Error: Error occurred while ".$string.".\n");
        return FALSE;
    }

    /**
     * 错误信息写入
     *
     * @param string $message
     * @return boolean
     */
    public function log_write($message)
    {
        $this->smtp_debug($message);

        if ($this->log_file == "") {
            return TRUE;
        }

        $message = date("M d H:i:s ").get_current_user()."[".getmypid()."]: ".$message;
        if (!@file_exists($this->log_file) || !($fp = @fopen($this->log_file, "a"))) {
            $this->smtp_debug("Warning: Cannot open log file \"".$this->log_file."\"\n");
            return FALSE;
        }

        flock($fp, LOCK_EX);
        fputs($fp, $message);
        fclose($fp);

        return TRUE;
    }

    /**
     * 格式替换
     *
     * @param string $address
     * @return string
     */
    public function strip_comment($address)
    {
        $comment = "\\([^()]*\\)";
        while (@ereg($comment, $address)) {
            $address = @ereg_replace($comment, "", $address);
        }
        return $address;
    }

    /**
     * 字符格式替换
     *
     * @param string $address
     * @return stromg
     */
    public function get_address($address)
    {
        $address = @ereg_replace("([ \t\r\n])+", "", $address);
        $address = @ereg_replace("^.*<(.+)>.*$", "\\1", $address);
        return $address;
    }

    /**
     * SMTP调试
     *
     * @param string $message
     */
    public function smtp_debug($message)
    {
        if ($this->debug) {
            //echo $message."<br>";
        }
    }

    /**
     * 附件类型编码
     *
     * @param string $image_tag
     * @return string
     */
    public function get_attach_type($image_tag)
    {
        $filedata = array();
        $img_file_con = fopen($image_tag,"r");
        unset($image_data);
        while ($tem_buffer=AddSlashes(fread($img_file_con,filesize($image_tag))))
            $image_data.=$tem_buffer;
            fclose($img_file_con);

            $filedata['context'] = $image_data;
            $filedata['filename']= basename($image_tag);
            $extension=substr($image_tag,strrpos($image_tag,"."),strlen($image_tag)-strrpos($image_tag,"."));
            switch($extension)
            {
                case ".gif":
                    $filedata['type']  = "image/gif";
                    break;
                case ".gz":
                    $filedata['type'] = "application/x-gzip";
                    break;
                case ".htm":
                    $filedata['type'] = "text/html";
                    break;
                case ".html":
                    $filedata['type'] = "text/html";
                    break;
                case ".jpg":
                    $filedata['type'] = "image/jpeg";
                    break;
                case ".tar":
                    $filedata['type'] = "application/x-tar";
                    break;
                case ".txt":
                    $filedata['type'] = "text/plain";
                    break;
                case ".zip":
                    $filedata['type'] = "application/zip";
                    break;
                default:
                    $filedata['type'] = "application/octet-stream";
                  break;
            }
            return $filedata;
        }
    }