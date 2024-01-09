<?php
namespace xorc;

class mailer{
 
   static $conf;
   static $vars;
   static $hdrs;
   static $version;

   static $t;
   
	function __construct(){
	#	$this->set_defaults();
	}
	
	static function conf($c=array()){
	   #print "conf-start\n";
	   $conf=$c?$c:xorc_ini('mail');
	   $conf['basepath'] = $conf['basepath']?$conf['basepath']: \xorcapp::$inst->approot."/src/mails";
	   self::$conf = array_intersect_key($conf, array('transport'=>1, 'basepath'=>1, 'pretend'=>false));
	   self::$vars = array_intersect_key($conf, array('sitename'=>1, 'monitor'=>1));
	   self::$hdrs = array_diff_key($conf, self::$vars, self::$conf);
	   #print "conf-end\n";
	}
	
	/*
	   http://swiftmailer.org/docs/messages.html
	   
	   transport:
	      smtp://rw@20sec.net:geheim@mail.20sec.de:25
	      sendmail://localhost/usr/bin/sendmail
	      php://localhost
	*/
	static function transport(){
	   if(self::$t) return self::$t;
	   if(!self::$conf) self::conf();

	   $version = (method_exists('\Swift_Mailer', 'newInstance'))?'old':'new';

	   
	   #var_dump(self::$conf);
	   $cred = parse_url(self::$conf['transport']);
	   #var_dump($cred);
	   log_debug($cred);
	   if($cred['scheme']=='smtp'){
	   	if($version=='old'){
   	   	$transport = \Swift_SmtpTransport::newInstance($cred['host'], $cred['port']?$cred['port']:25);
   	   }else{
   	   	$transport = new \Swift_SmtpTransport($cred['host'], $cred['port']?$cred['port']:25);
   	   }

	if($cred['user']) $transport->setUsername($cred['user'])
   	      ->setPassword($cred['pass'])
   	      // ->setPort(465)
            ->setEncryption('ssl')
   	      ;

   	   if(self::$conf['ssl']){
   	   	$transport->setEncryption('ssl');
   	   }

   	   if(self::$conf['ssl_nocert']){
   	   	$transport->setStreamOptions(array('ssl' => array('allow_self_signed' => true, 'verify_peer' => false)));
   	   }else{
   	   	$sopts = [];
   	   	foreach(self::$conf as $skey=>$val){
   	   		if(preg_match("/^ssl_(.*)$/", $skey, $mat)){
   	   			$sopts[$mat[1]]=$val;
   	   		}
   	   	}
   	   	if($sopts) $transport->setStreamOptions(['ssl' => $sopts]);
	   	}

      }elseif($cred['scheme']=='sendmail'){
      	if($version=='old'){
   	   	$transport = \Swift_SendmailTransport::newInstance($cred['path'].' -t'); # .' -bs'
   	   }else{
   	   	$transport = new \Swift_SendmailTransport($cred['path'].' -t');
   	   }
         
      }elseif($cred['scheme']=='php'){
      	/*
				achtung! php:// gibt es nicht mehr bei neueren versionen
      	*/
      	if($version=='old'){
   	   	$transport = \Swift_MailTransport::newInstance();
   	   }else{
   	   	$transport = new \Swift_MailTransport();
   	   }
	      
	   }
	   #var_dump($transport);
	   if($version=='old'){
   	  	self::$t = \Swift_Mailer::newInstance($transport);
   	}else{
   	  	self::$t = new \Swift_Mailer($transport);
   	}
	   
	   #var_dump(self::$t);
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
	   $hdrs = is_string($headers)?array('to'=>$headers):$headers;
		# var_dump($hdrs);
	   $data = array_merge(self::$vars, $data);
	   $ok = 2;
	   $version = (method_exists('\Swift_Mailer', 'newInstance'))?'old':'new';

	   if($version=='old'){
	   	$m=\Swift_Message::newInstance();
	   }else{
	   	$m=new \Swift_Message;
	   }

	   // zur sicherheit löschen
	   #self::var();
	   $txtvars = $htmlvars = array();

	   foreach($views as $type=>$view){
	   	$data = array_merge($data, self::var());
	      $body = self::render($view, $data);
	      if($body===false){
	         $ok--;
	         continue;
	      }
	      if($type=='txt'){
	         list($body, $h, $txtvars) = self::strip_headers($body);
	         #print_r($vars);
	         #print_r($h);
	         #exit;
	         $hdrs = array_merge($hdrs, $h);

	         $body = self::render_layout('_layout.txt', $body, $hdrs, $txtvars, $data);
	         $m->addPart($body, 'text/plain');
	      }elseif($type=='html'){
	      	list($body, $h, $htmlvars) = self::strip_headers($body);
	      	$hdrs = array_merge($hdrs, $h);
	      	$htmlvars = array_merge($txtvars, $htmlvars);
				$body = self::render_layout('_layout.html', $body, $hdrs, $htmlvars, $data);
	        
				foreach(self::embed() as $cid => $embed){
	   			$m->attach($embed);
	   		}
	         $m->addPart($body, 'text/html');
	      }
	   }

	   #var_dump($ok);
	   if(!$ok) return array('body not found');
	   
	   self::set_headers($m, $hdrs);
	   #print "headers OK\n";
	   if(self::$conf['pretend']){
	      log_info("[mail:pretend]\n". $m->toString());
	      return true;
	   }
	   $trans = self::transport();
	   #var_dump($trans);
	   $ok = $trans->send($m, $failures);
	   #var_dump($ok);
	   #var_dump($trans);
	   log_debug($trans);
	   if(!$ok){
	      log_warning("[mail] failures\n". $m->getHeaders()->toString());
	      log_warning($failures);
	      return $failures;
	   }else{
	      return true;
	   }
	}
	
	static function render_layout($l, $body, $hdr=[], $data=[], $more_data=[]){
		$l = self::$conf['basepath']."/$l";
		if(file_exists($l)){
			$data = array_merge($data, $more_data);
			#print_r($data);
			#exit;
			// nolayout
			if($data['layout']=='off' || $data['layout']==='0') return $body;

			$data['content'] = $body;
			$data['headers'] = $hdr;

			$layout = self::render($l, $data);
			if($layout!==false){
				$body = $layout;
			}
		}
		return $body;
	}

	# $cid = $message->embed(Swift_Image::fromPath('image.png'));
	static function embed($file=null){
		static $embeds=[];
		// clear list
		if(is_null($file)){
			$e = $embeds;
			$embeds = [];
			return $e;
		}
		$img = \Swift_Image::fromPath(self::$conf['basepath'].'/'.$file);
		$id = 'cid:'.$img->getId();
		$embeds[$id] = $img;
		return $id;
	}

	static function var($name=null, $value=null){
		static $vars=[];
		if(is_null($name)){
			$v = $vars;
			$vars = [];
			return $v;
		}
		$vars[$name] = $value;
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
       #print $headers->toString();
		#print_r($hdrs);
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
				if($key=='subject'){
					$message->setSubject($h);
				}else{
            	$headers->addTextHeader($hmail, $h);
				}
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
		$vars = array();
		list($hdr, $body) = explode("\n\n", $txt, 2);
	   if(preg_match_all("/^\s*([-\$A-z]+)\s*:(.*?)$/m", $hdr, $mat, PREG_SET_ORDER)){
	      foreach($mat as $set){
	          $name=strtolower(trim($set[1]));
	          $val=trim($set[2]);

	          if($name && $name[0]=='$'){
	          	$vars[trim($name, '$')] = $val;
	          }else{
	          	$h[$name]=$val;
	          }
	       }
	   }
		/*
		   haben wir einen sinnvollen header gefunden?
		   wir testen das 1) auf $vars 2) erstes element
		*/
		if($vars){
			return array($body, $h, $vars);
		}
		if($h){
			if(in_array(key($h), explode(' ', 'subject from to reply_to cc bcc errors_to return_path'))){
			   return array($body, $h, $vars);
			}
		}
		return array($txt, array(), array());
	}

}

?>
