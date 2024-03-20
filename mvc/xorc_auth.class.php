<?php
/*
..........................................................................
	xorc auth
	GPL applied
	(c) 20sec.net, robert wagner ~ rw@20sec.net
..........................................................................
*/
Class Xorc_Auth {
	
	var $idle = 120;

	var $id, $uname, $email;

	var $prop = array();
	var $perms = array();
	var $noredirect = false;
	var $exp;
	var $conf=array();
	
	// value for rememberme cookie
	var $remember="";

	// 0, keine authentifizierung
	// 1, authentifiziert, komplett
	var $state = 0;
	var $msg = "";
	var $_entry=array();

	function __construct($prefs=array()){	
		$this->msg = "";
#		log_error($prefs);
	}

	function id(){
		return $this->id;
	}
	
	function set_conf($prefs){
		foreach($prefs as $k=>$v) $this->conf[$k]=$v;
		if(@$prefs['allow_rememberme']){
			if(!isset($this->conf['persistent_cookie_name']))
				$this->conf['persistent_cookie_name'] = 'default_cookie_name';
			if(!isset($this->conf['persistent_cookie_lifetime']))
				$this->conf['persistent_cookie_lifetime'] = 1;
		}
		if(@$prefs['idle']) $this->idle=$prefs['idle'];
	}
	
	function is_valid($soft=false) {
		$this->msg="";
		$this->loginblock="";
		log_error("IS VALID?");

		
		if($this->state){
			
//			print " state ok";
			if(time()>$this->exp){

//				print " timeout";

				if($soft) return false;
				else{
				   if($this->conf['erase']) $this->soft_logout();
				   $this->show_relogin();
				}
			}else{
#				log_error("+++ AUTH IDL ".$this->idle);
#				log_error(date("Y-m-d H:i:s", (time() + (60 * $this->idle))));
				$this->exp = time() + (60 * $this->idle);
			}
		}else{
			
//			print " state not ok";
			if(!$this->_entry) $this->_entry = array($_SERVER['SCRIPT_URI'], $_GET);
			
			if($this->conf['allow_rememberme']) $cookieval=$_COOKIE[$this->conf['persistent_cookie_name']];
			else $cookieval="";
			
			
			list($ok, $msg)=$this->chekk_login($cookieval);
			
			$this->msg=$msg;
			if($ok){
				// reset einsprung URL
				$_entry = $this->_entry;
				$this->_entry = array();
				// 
				$this->state=1;
				$this->refresh_exp();
				if($this->conf['allow_rememberme'] && $this->remember){
					$this->set_rememberme($this->remember);
				}
//	right after the correct posting of auth infos
//	we do an redirect to prevent second postings
				if($this->noredirect){
					#log_error("NOOOOOOOO REDIRECT");
					return true;
				}elseif($this->conf['redirect_uri']){
               $redir=$_SERVER['REDIRECT_URL'];
					if($_entry) $redir = $_entry[0];
					$params = null;
					if($this->conf['redirect_get']){
						$params = $_GET;
						if($_entry) $params = $_entry[1];
					}
					XorcApp::$inst->resp->redirect($redir, $params);
				}elseif($this->conf['redirect_referer']){
               #$redir=$GLOBALS['GO_BACK_TO'];
#log_error($_SERVER); die();
               #if(!$redir) $redir=$_SERVER['HTTP_REFERER'];
               #log_error($_SERVER);
               $redir=$_SERVER['HTTP_REFERER'];
					XorcApp::$inst->resp->redirect($redir);
				}elseif($this->conf['redirect_server']){
				   $redir=$_SERVER[$this->conf['redirect_server']];
					XorcApp::$inst->resp->redirect($redir);
				}else{
		#		   log_error("AUTH-OK-REDIRECT");
				   log_error($_SESSION);	
				   if($this->conf['redirect_w_sessionid']){
				      $p=array(session_name()=>session_id());
				   }else{
				      $p=array();
				   }
				   XorcApp::$inst->ctrl->redirect("", $p);
				}
				return true;
			}else{
				$this->state=0;
				
				// hilfreich für ajax-requests
				//  nicht bei optionalem login!
				if(!$soft) header('HTTP/1.0 403 Forbidden');
				
				if($soft) return false;
				if($this->conf['erase'] && $_SERVER["REQUEST_METHOD"]!="POST") $this->soft_logout();
				
				$this->show_login();			// else
//				exit;
				return false; // app terminates here anyway
			}
		}
		return true;
	}

	function is_complete(){
		return $this->state;
	}

	function chekk_perm($need, $soft=false){
		if($this->perms[$need]) return true;
		else {
			if($soft) return false;
			$this->show_refuse("", $need);
		}
	}

	function refresh_exp(){
		$this->exp = time() + (60 * $this->idle);
	}


/*
	customize! should be overwritten by your application
	
		check, if logindata was submitted,
		grep your logindata from environment,
		chekk it against your passwordcontainer,
		on success - set id, uname, email and optional prop-hash with userdata
			return array(true, "YOUR SUCCESS MESSAGE")
		else
			return array(false, "YOUR FAILED MESSAGE")
			
		if you have not overwritten loginscreen(), the default form will submit
			2 vars named $xorcuser and $xorcpass
*/
	function chekk_login($cookieval){
	#	print("KLASSE:".get_class($this));
		$xorcuser=$_POST['xorcuser'];
		$xorcpass=$_POST['xorcpass'];
		if($xorcuser && $xorcpass){
			$this->uname=$xorcuser;
			return array(true, "SOMEONE FORGOT TO CODE THE PASSWORDCHECK");
		}else{
			return array(false, "SOMEONE FORGOT TO CODE THE PASSWORDCHECK");
		}
	}

/*
	helper function
		chekks word1 and word2 by certain methods (none, crypt, md5, dumb)
*/
	function validate_password($pass1, $pass2, $type="dumb"){
		switch ($type) {
			case "crypt" :
				return (($pass2 == "**" . $pass1) ||
					(crypt($pass1, substr($pass2,0,2)) == $pass2));
	    		break;
			case "none" :
				return ($pass1 == $pass2);
				break;
			case "md5" :
				return (md5($pass1) == $pass2);
				break;
			case "dumb" :
			default :
				return ($this->dumb_enc($pass1) == $pass2);
		}
	}

	function set_rememberme($value){
		setcookie($this->conf['persistent_cookie_name'], $value,
			time()+($this->conf['persistent_cookie_lifetime']*24*3600), "/");
	}

	function remove_rememberme($value){
		setcookie($this->conf['persistent_cookie_name'], $value,
			time()-(24*3600), "/");
	}

   function try_mvc_render($action){
      if($this->conf['mvc']){
         log_error("AUTH-VIA-MVC");
         # Xorcapp::$inst->foreward($this->conf['mvc'], $action);
         throw new XorcControllerNeedsAuthException(".. forewarding...", 0, $this->conf['mvc'], $action);
	      # XorcApp::$inst->terminate();
      }
   }
   
	function show_login($screen=""){
		$this->state = 0;
	#	log_error("LOGIN SCREEN");
//		$this->msg="please login for this operation.";
      
#      $this->soft_logout();
      
      $this->try_mvc_render("login");
      
		if(!$screen) $screen=$this->conf['loginscreen'];
		if(!$screen) $screen="/auth_login";
		
	
		if($this->conf['layout_path']){
		   XorcApp::$inst->ctrl->layout_path($this->conf['layout_path']);
		}
		if($this->conf['layout']){
		   # hack an alternative layout in nextbest controller
		   XorcApp::$inst->ctrl->layout($this->conf['layout']);
		}
		XorcApp::$inst->render($screen, array("msg"=>$this->msg));
		XorcApp::$inst->terminate();
   		
	}

	function show_relogin($screen=""){
		$this->state = 0;
		$this->msg="authentification expired. please login again.";
		
#		$this->soft_logout();
		
		$this->try_mvc_render("relogin");
		
		if(!$screen) $screen=$this->conf['reloginscreen'];
		if(!$screen) $screen="/auth_relogin";
		if($this->conf['layout_path']){
		   XorcApp::$inst->ctrl->layout_path($this->conf['layout_path']);
		}
		if($this->conf['layout']){
		   # hack an alternative layout in nextbest controller
		   XorcApp::$inst->ctrl->layout($this->conf['layout']);
		}
		XorcApp::$inst->render($screen, array("msg"=>$this->msg));
		XorcApp::$inst->terminate();
	}

	function show_logout($screen=""){
		$this->state = 0;
		$this->msg="u are logged out. good bye.";
		
		$this->try_mvc_render("logout");
		
		if(!$screen) $screen=$this->conf['logoutscreen'];
		if(!$screen) $screen="/auth_logout";
		if($this->conf['layout_path']){
		   XorcApp::$inst->ctrl->layout_path($this->conf['layout_path']);
		}
		if($this->conf['layout']){
		   # hack an alternative layout in nextbest controller
		   XorcApp::$inst->ctrl->layout($this->conf['layout']);
		}
		XorcApp::$inst->render($screen, array("msg"=>$this->msg));
		$this->uname="";
#		session_destroy();
		XorcApp::$inst->terminate();
	}

	function show_refuse($screen="", $need=""){
	   
	   $this->try_mvc_render("refuse");
	   
		if(!$screen) $screen=$this->conf['refusescreen'];
		if(!$screen) $screen="/auth_restricted";
		if($this->conf['layout_path']){
		   XorcApp::$inst->ctrl->layout_path($this->conf['layout_path']);
		}
		if($this->conf['layout']){
		   # hack an alternative layout in nextbest controller
		   XorcApp::$inst->ctrl->layout($this->conf['layout']);
		}
		XorcApp::$inst->render($screen, array("msg"=>$this->msg));
		XorcApp::$inst->terminate();
	}

	function logout(){
	   $this->before_logout();
	   
		if($this->conf['allow_rememberme'] && $this->remember){
			$this->remove_rememberme($this->remember);
		}
		$this->soft_logout();
		$this->show_logout();
	}

	function soft_logout(){
		$this->state = 0;
		$this->uname="";
//		print("softlogout!");
		$_SESSION=array();
		if (isset($_COOKIE[session_name()])){
 			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
//		print_r($_SESSION);
	}

   function before_logout(){}
   
	function loginscreen(){}

	function refusescreen(){}

	function make_loginblock(){
		$this->loginblock=$this->opt_loginblock();
	}

	function opt_loginblock(){}

	function dumb_enc($phrase){
		$key = "die12Monatskarte";
		$keystr = $key;
		$anz=strlen($phrase) / strlen($keystr);
		for($i=0; $i<$anz;$i++)
			$keystr .= $key;
		$keystr = substr($keystr, 0, strlen($phrase));
		$phrase = base64_encode(($phrase ^ $keystr));
		return $phrase;
	}

	function dumb_dec($phrase){
		$key = "die12Monatskarte";
		$keystr = $key;
		$anz=strlen(base64_decode($phrase)) / strlen($keystr);
		for($i=0; $i<$anz;$i++)
			$keystr .= $key;
		$keystr = substr($keystr, 0, strlen(base64_decode($phrase)));
		$phrase = base64_decode($phrase) ^ $keystr;
		return $phrase;
	}

}
	
?>