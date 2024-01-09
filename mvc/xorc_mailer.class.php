<?php
require_once('Mail.php');
require_once('Mail/mime.php');
require_once("xorc/div/util.php");

class Xorc_Mailer{

   var $_data=array();
   var $_charset_in="UTF-8";
   var $_hdrs=array();
   
   function deliver($mail){
      $parms=array();
      
      # bit sloppy for the moment
      if($this->_hdrs['Return-Path']){
         $parms[]="-f".$this->_hdrs['Return-Path'];
      }
      $ma = Mail::factory('mail', $parms);

      $body=$mail->get();
      #print_r($this->_data);die();
      $ma->send($this->_data['to'], 
      $mail->headers(), $body);
   }
    
   function create(){
      $crlf = "\n";
      $reply=$this->_data['reply_to'];
      if(!$reply) $reply=$this->_data['from'];
      
      $hdrs = array(
                     'From'    => $this->_fix_charset($this->_data['from']),
                     'Return-Path'  => $this->_data['return_path'],
                     'Reply-To'  => $reply,
                     'Subject' => $this->_fix_charset($this->_data['subject'])
                     );
      if($this->_data['headers'])               
         $hdrs = array_merge($hdrs, $this->_data['headers']);

      $mime = new Mail_mime($crlf);

      $text=$this->_fix_charset($this->_data['text']);
    	
      $mime->setTXTBody($text);
		if($this->_data['html']){
	      $html=$this->_fix_charset($this->_data['html']);
			$k = $mime->setHTMLBody($html);
		}
      $mime->headers($hdrs);
      
      # for accessing headers in delivery
      $this->_hdrs=$hdrs;
      return $mime;
   }
   
   function _fix_charset($text){
      if($this->_charset_in=="UTF-8") return utf8toisowin($text);
      return $text;
   }
   function _render($view){
      if(!$view) return;
		$file=XorcApp::$inst->base."/mails/".strtolower(get_class($this))."_".$view;
		if (substr($file, -3)=="txt"){
			$filetxt = $file;
			$filehtml = substr($file, 0, -3)."html";
		}else{
			$filetxt = $file.".txt";
			$filehtml = $file.".html";
		}
		if(!file_exists($filetxt)){
			trigger_error("missing MAILER VIEW $file");
			return "";
		}
		log_error("HTMLMAIL");
		log_error($file);
		foreach($this as $key=>$val){
#		   print "VAR: $key\n";
		   if(!isset($$key)) $$key=$val;
		}
#		foreach($params as $key=>$val){$$key=$val;}
		ob_start();
		include($filetxt);
		$out=ob_get_clean();
		$this->text($out);
		if(file_exists($filehtml)){
			ob_start();
			include($filehtml);
			$out=ob_get_clean();
			$this->html($out);
		}
   }
   
   function __call($m, $args){
      if(preg_match("/^(create|deliver)_(.*)/", $m, $mat)){
         if(method_exists($this, $mat[2])){
            $meth=$mat[2];
           # $view=$this->$meth($args);
            $view=call_user_func_array(array($this, $meth), $args);
            if(!$view && $view!==false){
               $view=$meth.".txt";
            }
            $this->_render($view);
            if($mat[1]=="create"){
               return $this->create();
            }else{
               return $this->deliver($this->create());
            }
         }else{
            trigger_error("(CALL) MAILER unknown {$mat[1]} method $m for object ".get_class($this));
         }
	   }else{
	      trigger_error("(CALL) MAILER unknown method $m for object ".get_class($this));
	   }
   }
   
   function from($f){
      $this->_data['from']=$f;
   }
   
   function return_path($r){
      $this->_data['return_path']=$r;
   }
   
   function to($t){
      $this->_data['to']=$t;
   }
   function cc($t){
      $this->_data['headers']['cc']=$t;
   }
   function bcc($t){
      $this->_data['headers']['bcc']=$t;
   }
   function reply_to($t){
      $this->_data['reply_to']=$t;
   }
   
   function subject($s){
      $this->_data['subject']=$s;
   }
   
   function headers($h){
      $this->_data['headers']=$h;
   }
   
   function header($name, $h){
      $this->_data['headers'][$name]=$h;
   }
   
   function text($t){
      $this->_data['text']=$t;
   }
   function html($html){
      $this->_data['html']=$html;
   }
   
   function old_mail($to, $subj, $text, $extrahdrs=array()){
   //        mail($to, $subj, $text, "From: $from");

     $crlf = "\n";
     $hdrs = array(
                   'From'    => $this->from,
                   'Subject' => $subj
                   );
     $hdrs = array_merge($hdrs, $extrahdrs);

     $mime = new Mail_mime($crlf);

     $mime->setTXTBody(utf8toisowin($text));

     $body = $mime->get();
     $hdrs = $mime->headers($hdrs);

     $mail =& Mail::factory('mail');
     $mail->send($to, $hdrs, $body);
   }
}

?>