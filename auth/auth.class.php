<?php
/*
..........................................................................
	xorc auth
	GPL applied
	(c) 20sec.net, robert wagner ~ rw@20sec.net
..........................................................................
*/
Class Auth {
	
	var $idle = 120;

	var $id, $uname, $email;

	var $prop = array();
	var $perms = array();

	var $exp;
	var $conf=array();
	
	// value for rememberme cookie
	var $remember="";

	// 0, keine authentifizierung
	// 1, authentifiziert, komplett
	var $state = 0;
	var $msg = "";
    var $msg_logout="u are logged out. good bye.";

	function Auth($prefs=array()){
		$this->set_conf($prefs);		
		$this->msg = "";
	}

	function id(){
		return $this->id;
	}
	
	function set_conf($prefs){
		foreach($prefs as $k=>$v) $this->conf[$k]=$v;
		if(@$prefs['idle']) $this->idle=$prefs['idle'];
	}
	
	function is_valid($soft=false) {
		$this->msg="";
		$this->loginblock="";
		if($this->state){
//			print " state ok";
			if(time()>$this->exp){

//				print " timeout";

				if($soft) return false;
				else $this->show_relogin();
			}else{
				$this->exp = time() + (60 * $this->idle);
			}
		}else{
//			print " state not ok";
			if($this->conf['allow_rememberme']) $cookieval=$_COOKIE[$this->conf['persistent_cookie_name']];
			else $cookieval="";
			list($ok, $msg)=$this->chekk_login($cookieval);
			$this->msg=$msg;
			if($ok){
				$this->state=1;
				$this->refresh_exp();
				if($this->conf['allow_rememberme'] && $this->remember){
					$this->set_rememberme($this->remember);
				}
				if($this->conf['redirect']){
					$dest=$this->redirect_on_login();
					Xorc::redirect($dest);
				}
				return true;
			}else{
				$this->state=0;
				if($soft) return false;
				$this->show_login();			// else
				exit;
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
#	   print "set #{$this->remember}#";
	   #print "COOKIE ".$this->conf['persistent_cookie_name'].
	    #  date("Y-m-d H:i:s", time()+($this->conf['persistent_cookie_lifetime']*24*3600));
	      
		setcookie($this->conf['persistent_cookie_name'], $value,
			time()+($this->conf['persistent_cookie_lifetime']*24*3600), "/");
	}

	function remove_rememberme($value){
		setcookie($this->conf['persistent_cookie_name'], $value,
			time()-(24*3600), "/");
			
		// avoid setting a cookie that was just deleted
		$this->remember=false;
		unset($_COOKIE[$this->conf['persistent_cookie_name']]);
	}

	function show_login($screen=""){
//		global $PHP_SELF, $QUERY_STRING;
//		global $tree, $navpath, $navpath_rev, $breaks;
//		extract($GLOBALS);
		foreach($GLOBALS as $k=>$v){if($k!="GLOBALS") global $$k;}
		$this->state = 0;
//		$this->msg="please login for this operation.";
		if(!$screen) $screen=$this->conf['loginscreen'];
		if(!$screen) $this->loginscreen();
		else include $screen;
		exit;
	}

	function show_relogin($screen=""){
//		global $PHP_SELF, $QUERY_STRING;
		foreach($GLOBALS as $k=>$v){if($k!="GLOBALS") global $$k;}
		$this->state = 0;
		$this->msg="authentification expired. please login again.";
		if(!$screen) $screen=$this->conf['reloginscreen'];
		if(!$screen) $this->loginscreen();
		else include $screen;
		exit;
	}

	function show_logout($screen=""){
//		global $PHP_SELF, $QUERY_STRING;
		foreach($GLOBALS as $k=>$v){if($k!="GLOBALS") global $$k;}
		$this->state = 0;
		$this->msg=$this->msg_logout;
		
		
		$this->uname="";
		
		
		// Löschen aller Session-Variablen.
		$_SESSION = array();

		// Falls die Session gelöscht werden soll, löschen Sie auch das
		// Session-Cookie.
		// Achtung: Damit wird die Session gelöscht, nicht nur die Session-Daten!
		if(isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}

        if(!$screen) $screen=$this->conf['logoutscreen'];
		if(!$screen) $this->loginscreen();
		else include $screen;
		
		// Zum Schluß, löschen der Session.
		session_destroy();
		exit;
	}

	function logout(){
		if($this->conf['allow_rememberme'] && $this->remember){
			$this->remove_rememberme($this->remember);
		}
		$this->show_logout();
	}

	function soft_logout(){
		$this->state = 0;
		$this->uname="";
//		print("softlogout!");

      if($this->conf['allow_rememberme'] && $this->remember){
			$this->remove_rememberme($this->remember);
		}

		$_SESSION=array();
		if (isset($_COOKIE[session_name()])){
 			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
//		print_r($_SESSION);
	}

	function loginscreen(){
		$self=$_SERVER['PHP_SELF'];
		$query=$_SERVER['QUERY_STRING'];
		$js=$onsubmit="";
		if($this->conf['md5js']){
			$js=<<<EJS
<script type="text/javascript" src="{$this->conf['md5js']}"></script>
EJS;
			$onsubmit=' onsubmit="this.xorcpassmd5.value=hex_md5(this.xorcpass.value);this.xorcpass.value=\'\'"';
		}
$form=<<<EDOC
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>login</title>$js
</head>
<body>
<form action="$self" method="post"$onsubmit><table border="0" align="center">
<tr><td colspan="2"><b>$this->msg</b></td></tr>
<tr>
  <td align="right"><i>Username:</i></td><td><input type="Text" name="xorcuser" size="15" maxlength="24" value=""></td>
</tr>
<tr>
	<td align="right"><i>Password:</i></td><td><input type="password" name="xorcpass" size="15" maxlength="32" value="">
<input type="hidden" name="xorcpassmd5" value="">
	</td>
</tr>
EDOC;

	if($this->conf['allow_rememberme']){
$form.=<<<EDOC
<tr><td></td><td><input type="checkbox" name="xorcrememberme"><i>Remember me?</i></td></tr>
EDOC;
	}

$form.=<<<EDOC
<tr>
	<td></td>
	<td><input type="Submit" name="post" value="Login"></td>
</tr>
</table>
</form>
</body>
</html>
EDOC;
		print $form;
	}

	function show_refuse($screen="", $need){
//		global $PHP_SELF, $QUERY_STRING;
		if(!$screen) $screen=$this->conf['refusescreen'];
		if(!$screen) $this->refusescreen();
		else include $screen;
		exit;
	}

	function refusescreen(){
//		global $PHP_SELF, $QUERY_STRING;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head><title>access denied</title></head>
<body>
<p><b><?=$this->msg?></b></p>
<p><b>u dont't have the needed permissions to access this page</b></p>
</body>
</html>
<?
	}

	function make_loginblock(){
		$this->loginblock=$this->opt_loginblock();
	}

	function opt_loginblock(){
		$self=$_SERVER['PHP_SELF'];
		$query=$_SERVER['QUERY_STRING'];
$form=<<<EDOC
<form action="$self" method="post"><table border="0" align="center">
<tr><td colspan="2"><b>$this->msg</b></td></tr>
<tr>
  <td align="right"><i>Username:</i></td><td><input type="Text" name="xorcuser" size="15" maxlength="24" value=""></td>
</tr>
<tr>
	<td align="right"><i>Password:</i></td><td><input type="password" name="xorcpass" size="15" maxlength="15" value=""></td>
</tr>
EDOC;

	if($this->conf['allow_rememberme']){
$form.=<<<EDOC
<tr><td></td><td><input type="checkbox" name="xorcrememberme"><i>Remember me?</i></td></tr>
EDOC;
	}

$form.=<<<EDOC
<tr>
	<td></td>
	<td><input type="Submit" name="post" value="Login"></td>
</tr>
</table>
</form>
EDOC;
		return $form;
	}

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

	function redirect($url){
        // Redirect
        header("Location: $url");
        exit;
	}
	
	function redirect_on_login(){}
}
	
?>