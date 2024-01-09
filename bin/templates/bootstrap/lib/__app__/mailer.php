<?php
namespace xorc;

class mailer{
 
   static $conf;
   static $vars;
   static $hdrs;
   
   static $t;
   
	function __construct(){
	#	$this->set_defaults();
	}
	
	static function conf($c=array()){
	   $conf=$c?:xorc_ini('mail');
	   $conf['basepath'] = $conf['basepath']?:\xorc\app::$inst->mails;
	   self::$conf = array_intersect_key($conf, ['transport'=>1, 'basepath'=>1, 'pretend'=>false]);
	   self::$vars = array_intersect_key($conf, ['sitename'=>1, 'monitor'=>1]);
	   self::$hdrs = array_diff_key($conf, self::$vars, self::$conf);
	}
	
	/*
	   http://swiftmailer.org/docs/messages.html
	   
	   transport:
	      smtp://rw@20sec.net:geheim@mail.20sec.de:25
	      sendmail://localhost/usr/bin/sendmail
	*/
	static function transport(){
	   if(self::$t) return self::$t;
	   if(!self::$conf) self::conf();
	   $cred = parse_url(self::$conf['transport']);
	   log_debug($cred);
	   if($cred['scheme']=='smtp'){
   	   $transport = \Swift_SmtpTransport::newInstance($cred['host'], $cred['port']?:25)
   	      ->setUsername($cred['user'])
   	      ->setPassword($cred['pass'])
   	      ->setPort(465)
            ->setEncryption('ssl')
   	      ;
      }elseif($cred['scheme']=='sendmail'){
         $transport = \Swift_SendmailTransport::newInstance($cred['path'].' -t'); # .' -bs'
      }elseif($cred['scheme']=='php'){
	      $transport = \Swift_MailTransport::newInstance();
	   }
	   self::$t = \Swift_Mailer::newInstance($transport);
	   return self::$t;
	}
	
	static function find_views($view){
	   $t = array();
      $i = pathinfo($view);
      $base = self::$conf['basepath'];
      if(!$i['extension']){
         $t['txt'] = $base.'/'.$view.'.txt';
         $t['html'] = $base.'/'.$view.'.html';
      }else{
         $t[$i['extension']] = $base.'/'.$view;
      }
      return $t;
	}
	
	static function send($views, $headers, $data=array()){
	   if(!self::$conf) self::conf();
	   $views = self::find_views($views);
	   $hdrs = is_string($headers)?['to'=>$headers]:$headers;
		# var_dump($hdrs);
	   $data = array_merge(self::$vars, $data);
	   $ok = 2;

	   $m=\Swift_Message::newInstance();
	   foreach($views as $type=>$view){
	      $body = self::render($view, $data);
	      if($body===false){
	         $ok--;
	         continue;
	      }
	      if($type=='txt'){
	         list($body, $h) = self::strip_headers($body);
	         $hdrs = array_merge($hdrs, $h);
	         $m->setBody($body, 'text/plain');
	      }elseif($type=='html'){
				if(!$data['nolayout']){
					$data['content'] = $body;
					$layout = self::render(self::$conf['basepath'].'/_layout.html', $data);
					if($layout!==false){
						$body = $layout;
					}
				}
	         $m->addPart($body, 'text/html');
	      }
	   }
	   #var_dump($ok);
	   if(!$ok) return array('body not found');
	   
	   self::set_headers($m, $hdrs);
	   
	   if(self::$conf['pretend']){
	      log_info("[mail:pretend]\n". $m->toString());
	      return true;
	   }
	   $trans = self::transport();
	   $ok = $trans->send($m, $failures);
	   log_debug($trans);
	   if(!$ok){
	      log_warning("[mail] failures\n". $m->getHeaders()->toString());
	      log_warning($failures);
	      return $failures;
	   }else{
	      return true;
	   }
	}
	
   static function render($view, $params){
      return \xorc\view::render_file($view, $params);
   }
   
   /*
      aus reply-to wird Reply-To usw.
   */
   static function set_headers(&$message, $hdrs){
      static $addr_keys;
      if(!$addr_keys) $addr_keys = explode(' ', 'from to reply-to errors-to cc bcc sender return-path');
      $hdrs = array_merge(self::$hdrs, $hdrs);
      $headers = $message->getHeaders();
      # print $headers->toString();
      foreach($hdrs as $key => $h){
         $h = str_replace('#monitor', self::$vars['monitor'], $h);
         $hmail = join('-', array_map('ucfirst', explode('-', $key)));
         if(in_array($key, $addr_keys)){
            $h = self::addr_line_simple_parse($h);
            // kein namensanteil erlaubt
            if($key=='return-path'){
               if($h[0]) $h=$h[0];
               else $h=key($h);
               $message->setReturnPath($h);
            }elseif($key=='from'){
               $message->setFrom($h);
            }else{
               $headers->addMailboxHeader($hmail, $h);
            }
         }else{
            $headers->addTextHeader($hmail, $h);
         }
      }
   }
   
   /*
      "walter white" ww@meth.org, bounce@meth.org, dea <office@dea.gov>, chicken wings <chicks@eat-fresh.com>
   */
   static function addr_line_simple_parse($addr_line){
      $addrs=array();
      foreach(explode(',', $addr_line) as $line){
         if(preg_match("/^(.*?)([^ ]+@[^ ]+)?$/", trim($line), $mat)){
            $name = trim(str_replace('"', '', $mat[1]));
            $email = trim($mat[2], '<>');
            if($name) $addrs[$email] = $name;
            else $addrs[] = $email; 
         }
      }
      return $addrs;
   }
      
	static function strip_headers($txt){	
		$h=array();
		list($hdr, $body) = explode("\n\n", $txt, 2);
	   if(preg_match_all("/^\s*([-A-z]+)\s*:(.*?)$/m", $hdr, $mat, PREG_SET_ORDER)){
	      foreach($mat as $set){
	          $name=strtolower(trim($set[1]));
	          $val=trim($set[2]);
	          $h[$name]=$val;
	       }
	   }
		/*
		   haben wir einen sinnvollen header gefunden?
		   wir testen das erste element
		*/
		if($h){
			if(in_array(key($h), explode(' ', 'subject from to reply_to cc bcc errors_to return_path'))){
			   return array($body, $h);
			}
		}
		return array($txt, []);
	}

}

?>