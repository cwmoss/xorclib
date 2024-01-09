<?php

class Register_C extends <appname>_controller{
      
	function index(){
		if(false) $this->redirect("closed");
		
#        $this->_invite_token_check();
      	$this->registration = new user;
		$this->registration->country = 'de';
		#$p = $this->contest->syspage('register/index');
		if($p) $this->title = $p->title;
		else $this->title = 'Registrierung';
		$this->bodyclass = "page-registration";
		$this->captcha = xorc_ini('captcha');
	}
	
	function save(){
		#if(!$this->contest->is_open) $this->redirect("closed");
		
		#$p = $this->contest->syspage('register/index');
		if($p) $this->title = $p->title;
		else $this->title = 'Registrierung';
		
	   $this->registration = new user;
	   $this->registration->set($this->r['reg']);
		#$this->registration->contest_id = $this->contest->id;
		
		$ok1 = $this->registration->is_valid();
		
		$this->captcha = xorc_ini('captcha');
		$recaptcha = new \ReCaptcha\ReCaptcha($this->captcha['secret']);
		$resp = $recaptcha->verify($this->r['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
		if ($resp->isSuccess()) {
			// verified!
			$ok2 = true;
		}else{
			$ok2 = false;
			$errors = $resp->getErrorCodes();
			$tr = array(
				'missing-input-response' => txt('missing-input-response'),
				'invalid-input-response' => txt('invalid-input-response'),
				'missing-input-secret' => 'Captcha Verbindung fehlgeschlagen (kein Secret)',
				'invalid-input-secret' => 'Captcha Verbindung fehlgeschlagen (ungültiges Secret)'
			);
			$errors = array_map(function($msg)use($tr){
				return $tr[$msg];
			}, $errors);
			$this->registration->errors->add('captcha', join('\n', $errors));
		}
		
	   if($ok1 && $ok2){
			$ok3 = $this->registration->save();
			if(!$ok3){
				$this->registration->errors->add_to_base("Unbekannter Fehler");
				return "index";
			}
			$this->registration->password_update($this->r['reg']['passwd']);
			mail_setup();
			xorc\mailer::send('welcome', $this->registration->email, 
				array('u'=>$this->registration));
			
	      flash(txt("thank-you-register"));
	      $this->redirect("contest/index");
	   }else{
	      return "index";
	   }
	}
		
	function confirm(){
	   $this->registration = new user;
	   // via GET?
	   if($_SERVER['REQUEST_METHOD']=='GET'){
	      $tok = $this->r['t'];
			$email = $this->r['e'];
	   }else{
	      $tok = $this->r['reg']['token'];
			$email = $this->r['req']['email'];
	   }
	   $this->registration->token = $tok;
		$this->registration->email = $email;
		#$this->registration->contest_id = $this->contest->id;
	   if($this->registration->is_valid('confirm')){
			$u = $this->registration->find_first(array('conditions'=>array('email'=>$this->registration->email)));
			if($u){
				$u->confirmed();
				flash("Die Registrierung wurde erfolgreich abgeschlossen!");
		      $this->redirect("dashboard/image/index");
			}
	   }
	}
	
	function confirm_ok(){
	   
	}
	
	function remind_password(){
#        $this->_invite_token_check();
      $this->registration = new user;
	}

	function remind_password_save(){
	   $this->registration = new user;
	   $this->registration->set($this->r['reg']);
		#$this->registration->contest_id = $this->contest->id;
      if($this->registration->is_valid('remind_password')){
			$u = $this->registration->find_first(array('conditions'=>array('email'=>$this->registration->reminder_email)));
         mail_setup();
			xorc\mailer::send('password_reset', $u->email, 
				array('u'=>$u, 
				'title'=>'Passwort zurücksetzen'));
	      flash(txt("email-sent"));
	      $this->redirect("contest/index");
	   }else{
	      return "remind_password";
	   }
	}
   	
	function reset_password(){
	   $this->registration = $this->_check_reset_token();
	   $this->registration->passwd="";
	}
	
	function reset_password_save(){
	   $this->registration = $this->_check_reset_token();
	   $this->registration->set($this->r['reg']);
		#$this->registration->contest_id = $this->contest->id;
	   if($this->registration->is_valid('reset_password')){
			$u = $this->registration->find_first(array('conditions'=>array('email'=>$this->registration->email)));
	      $u->password_update($this->registration->passwd);
	      flash(txt('pwd-changed'));
  	      $this->redirect("dashboard/image/index");
	   }
	   return "reset_password";
	}
	
	function closed(){
		#$this->page = $this->contest->syspage('register/closed');
	}
	
	function _check_reset_token(){
	   $fail = 'Ungültiger Zugriff';
	   if($_SERVER['REQUEST_METHOD']=='GET'){
	      $tok = $this->r['t'];
	      $email = $this->r['e'];
	   }else{
	      $tok = $this->r['reg']['reset_token'];
	      $email = $this->r['reg']['email'];
	   }
	   if($tok && $email){
         $u = user::i()->find_first(array('conditions'=>array('email'=>$email)));
         if($u){
            $u->reset_token = $tok;
            $ok = $u->check_reset_token();
				if(!$ok){
					flash($fail);
			      $this->redirect("/");
				}
            return $u;
         }
      }
      # TODO: abgelaufene token?
      flash($fail);
      $this->redirect("/");
	}
	

	# TODO: registration by invite
}

?>