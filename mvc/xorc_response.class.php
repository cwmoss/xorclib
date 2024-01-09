<?php

class Xorc_Response{
	var $headers;
	var $status;

	function __construct(){
		$this->status=200;
	}
	
	function redirect($to, $parms=null){
		if(!preg_match("!^http!", $to)){
			//print_r(Xorc::$inst);
			if(!preg_match("!^/!", $to)){
				$to=XorcApp::$inst->env->httpbase."/$to";
			}
			$to=XorcApp::$inst->env->proto.XorcApp::$inst->env->server.$to;
		}
		# print $to;
		$to=str_replace("&amp;", "&", $to);
		
		if($parms){
		   if(is_array($parms)){
		      $parms = http_build_query($parms);
		   }
		   $to.="?".$parms;
		}
#		log_error("HEADERS SENT?");
#		log_error(headers_sent());
#		log_error(headers_list());
#		header_remove();
#		log_error("header-location: #$to#");
		header("Location: $to");
		// print $to;
		exit;
	}
}

?>