<?php
require_once("xorc/mvc/xorc_auth_controller.class.php");

class Auth_C extends Xorc_Auth_Controller{
  
   public $login; // login objekt
   
   public $is_firstlogin=false;
   public $failed=0;
   public $lastlogin;
   public $bob_session;

   public $uid;
   public $hash;
   
   public $_enabled_actions='login relogin logout security_logout restricted';
   
   function _init(){
      log_error("### AUTH INIT ####");
      parent::_init();
      # anti-clickjacking header
      $c = $_SERVER['<app-name-uc>_ID'];
      
      $theme = $_SERVER['<app-name-uc>_THEME'];
      if($theme) $this->theme($theme);
      
    #  $this->contest = contest::lookup($c);
		$theme = $_SERVER['<app-name-uc><_THEME'];
		if($theme) $this->theme($theme);
		
		#log_error("+++ c {$c} // c-ID {$this->contest->id} THEME {$theme} +++");
		
		header("X-Frame-Options: SAMEORIGIN");
   }
   
   /*
     wir können uns nicht auf die _init funktion verlassen, weil
     der auth controller auch ein pre-filter controller ist
     es laufen quasi 2 instanzen. TODO: ändern in xorc
   */
   function _init_login(){
      log_error("++++++++++++++++ AUTH-INIT-SESSION");
      log_error($_SESSION);
      #log_error($this->login);
      
      $this->login = new app_login;
      
      // sonst mehrfache fehlermeldungen
      $this->login->errors->clear();
      log_error("+++ AUTH-LOGIN _INIT");
      #log_error($this->login);
      
      if(isset($_SESSION['failed'])) $this->failed = $_SESSION['failed'];
      $this->title = 'Anmelden';
   }
   
   function login(){
#		log_error("GLEICH CHECK LOGIN ".$_SERVER['REQUEST_METHOD']);
#		log_error($_SESSION);
     if($_SERVER['REQUEST_METHOD']!='POST') $this->_init_login();
      
   #     $this->captcha=new CM_Captcha;
   #     $this->captcha->generate_code();

      return "login";
   } 

  function relogin(){
     $this->title = 'Anmelden';
     return "relogin";
  }

  function before_logout(){
     // session: userID explizit holen und setzen!
#     $this->load_session();

     #$this->_additional_destroy();
	  $this->title = 'Abmelden';
  }

	function security_logout(){
		$this->logout();
		$this->title = 'Abmelden';
	}
	
  function restricted(){
     $this->title = '--';
     return "restricted";
  }
    
  function _check_captcha(){
      $cap=new CM_Captcha;
      if(!$cap->validate($this->r)){
         #$this->antrag->errors->add_to_base("Der eingegebene Code war nicht richtig.");
         #flash($this->antrag->errors->all_as_string());
			$this->captchainvalid = true;#return $this->$step();
			return false;
      }else{
			#$this->antrag->humanverification = true;
			return true;
		}
	
  }
  
   function captcha(){
      $seed=$this->r['id'];
	   $cap=new CM_Captcha(array('font'=>'times_new_yorker.ttf'));
	   $cap->image($seed);
      exit;
   }
  	
  	function _fetch_user(){
  	   log_error("FETCH ");

  	   $uid = $this->sess->id;
  	   log_error("FETCH $uid");
      return user::i()->find_first(array('conditions'=>array('id'=>$uid)));
  	}
  	
  	function _fetch_auth_id_from_login(){
  	   return $this->uid;
  	}
  	
  	function _check_login(){
  	   log_error($this->r);
  	   log_error("::::::::::::::::::: CHECK LOGIN :::::::::::::::::");
  	   $this->_init_login();
  	   log_error("::::::::::::::::::: CHECK LOGIN2 :::::::::::::::::");
      $email = $this->r['login']['usr'];
      $pwd = $this->r['login']['pwd'];
      
 		if($email && $pwd){
         $this->login->usr = $id;
       	$u = user::i()->find_first(array('conditions'=>array('email'=>$email, 'contest_id'=>$this->contest->id)));
       	
         if($u && $u->password_match($pwd)){
				
				if(!$u->is_activated()){
					$this->login->errors->add('Du bist noch nicht freigeschaltet. Bitte klick auf den Link, den wir dir per E-Mail zugeschickt haben. Bitte schau auch in den Spam Ordner deines E-Mail Programms.');
					return false;
				}
				
				if($u->is_blocked()){
					$this->login->errors->add('Dieser Login ist gesperrt.');
					return false;
				}
				
            $this->sess->lastlogin = $u->login_at;
            
            $u->after_login();
            // userid kurz zwischenspeichern, siehe: _fetch_auth_id_from_login
            $this->usw = $u;
            $this->uid = $u->id; 
            return true;
         }else{
            $this->login->errors->add('login falsch');
            #$this->failed = $_SESSION['failed'] = 1;
         }
      }elseif($_SERVER['REQUEST_METHOD']=='POST'){
         log_error("::::::: LOGIN ERRORS -- $id -- $pin --");
         log_error($_GET);
         if(!$id) $this->login->errors->add('Bitte gib E-Mail-Adresse und Passwort ein.');
      }
      return false;
   }
   
   function after_login(){	
      $strong=null;
      $this->sess->_toktok = bin2hex(openssl_random_pseudo_bytes(32, $strong));
      $this->sess->_toktok_GET = bin2hex(openssl_random_pseudo_bytes(32, $strong));
            
      // schalter für DEMO modus (parallele logins) vorhanden?
      if(!xorc_ini('auth.allow_concurrent_sessions')){
      #   BOB_open_session::start($this->sess->id, session_id());
      }
   }
   
   function _redirect_action(){
      //	right after the correct posting of auth infos
      //	we do an redirect to prevent second postings
      if($this->usw->role=='usr'){
         $action = 'dashboard/image/index';
      }elseif($this->usw->role=='adm'){
         $action = 'admin/user/index';
      }elseif($this->usw->role=='jur'){
         $action = 'contest/jury';
      }else{
			// == usr
			$action = 'dashboard/image/index';
		}
      $this->redirect($action);
	   # $this->redirect("/", csrf_token_array()); 	
   }

}

