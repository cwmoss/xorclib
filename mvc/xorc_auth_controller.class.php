<?php
/*
..........................................................................
	xorc auth controller
	GPL applied
	(c) 20sec.net, robert wagner ~ rw@20sec.net
..........................................................................
*/

#[AllowDynamicProperties]
class Xorc_Auth_Session_Data {
	// authentifizierte userid
	public $id = null;

	// 0, keine authentifizierung
	// 1, authentifiziert, komplett
	public $state = 0;
	public $remember;

	public $expire = null;

	// einsprungadresse
	public $referer;

	public $lastlogin;

	function refresh_expire($idle) {
		$this->expire = time() + (60 * $idle);
	}

	function is_expired() {
		return (time() > $this->expire);
	}

	function set_id($id) {
		$this->id = $id;
	}
}

class Xorc_Auth_Controller extends Xorc_Controller {

	// value for rememberme cookie
	public $remember = "";
	public $cookieval = "";
	public $sess = null;
	public $conf = null;
	public $referer;
	public $uname;
	public $id;

	public $_enabled_actions = 'login logout';

	function __construct() {
		/*
       * make shure, we have a session open, it's needed throu every action!
      */
		if (!Xorcapp::$inst->session_started) Xorcapp::$inst->start_session();
		parent::__construct();
	}

	/*
		support for optional authentication 
		opts['optional']=>true
			==> soft => true
	*/
	function start($opts = null) {
		$this->configure(xorc_ini("auth"));
		$this->load_session();
		// log_error("++ start auth +++");
		// log_error($opts);
		#log_error($this->sess);
		$soft = (is_array($opts) && isset($opts['optional']) && $opts['optional']) ? true : false;
		return $this->is_valid($soft);
	}

	function id() {
		return $this->id;
	}

	function load_session() {
		// log_error("LOAD/START DATA FROM SESSION");
		if (!isset($_SESSION['___x_auth'])) {
			$_SESSION['___x_auth'] = new Xorc_Auth_Session_Data;
		}
		$this->sess = $_SESSION['___x_auth'];
		#log_error($_SESSION);
	}

	function configure($conf = array()) {
		$def = array(
			'idle' => 120,
			'allow_rememberme' => false,
			'persistent_cookie_name' => 'default_cookie_name',
			'persistent_cookie_lifetime' => 1,
		);

		// first call
		if (is_null($this->conf)) {
			$this->conf = array_merge($def, $conf);
		} else {
			// any other calls
			$this->conf = array_merge($this->conf, $conf);
		}
	}

	function is_valid($soft = false) {

		#log_error($this->conf);

		// lokale (anonyme) zugriffe erlauben
		if (($this->conf['allow_local_access'] ?? false) && ($_SERVER["SERVER_ADDR"] == $_SERVER["REMOTE_ADDR"])) {
			return true;
		}
		// welche seite wollte ich ursprünglich sehen?
		if ($_SERVER['REQUEST_METHOD'] == 'GET' && !($this->sess->referer ?? null)) {
			$this->sess->referer = $_SERVER['REDIRECT_SCRIPT_URL'] ?? null;
		}

		if ($this->sess->state ?? null) {
			#print_r($this->sess);			
			#			print " state ok";
			if ($this->sess->is_expired()) {

				//				print " timeout";

				if ($soft) return false;
				else {
					if ($this->conf['erase'] ?? null) $this->soft_logout();
					$this->sess->state = 0;
					throw new XorcControllerNeedsAuthException("Authentification required.", 0, $this->conf['mvc'], "relogin");
				}
			} else {

				$ok = $this->_additional_checks();

				if (!$ok) {
					$this->soft_logout();
					throw new XorcControllerNeedsAuthException("Authentification required.", 0, $this->conf['mvc'], "relogin");
				}

				#				log_error("+++ AUTH IDL ".$this->idle);
				#				log_error(date("Y-m-d H:i:s", (time() + (60 * $this->idle))));
				$this->sess->refresh_expire($this->conf['idle']);
			}
		} else {

			//			print " state not ok";
			#log_error($this->conf);
			#log_error($_COOKIE);

			$this->remember = $this->get_rememberme();

			#log_error($this->cookieval);
			$ok = $this->check_login();

			if ($ok) {
				// ursprungsadresse zwischenspeichern und aus session löschen
				$this->referer = $this->sess->referer;

				/*
			   zur sicherheit werden nach erfolgreichem login evtl. bestehende sessiondaten gelöscht
			   */
				$_SESSION = array();
				$this->load_session();
				/*
			   zur sicherheit wird nach erfolgreichem login eine neue sessionId vergeben
			   */
				session_regenerate_id(true);

				$this->sess->state = 1;
				$this->sess->refresh_expire($this->conf['idle']);
				$this->sess->set_id($this->_fetch_auth_id_from_login());
				if ($this->conf['allow_rememberme'] && $this->remember) {
					$this->set_rememberme($this->remember);
					$this->sess->remember = $this->remember;
				}

				$this->after_login();
				$this->_redirect_action();
				return true;
			} else {
				$this->sess->state = 0;
				if ($soft) return false;
				if (($this->conf['erase'] ?? null) && $_SERVER["REQUEST_METHOD"] != "POST") $this->soft_logout();

				#print_r($_SERVER);
				if ($_SERVER["REQUEST_METHOD"] == "POST") {
					$this->_failed_credentials();
				} else {
					$this->_authentification_required();
				}
				//				exit;
				return false; // app terminates here anyway
			}
		}
		return true;
	}

	function _authentification_required() {
		throw new XorcControllerNeedsAuthException("Authentification required.", 0, $this->conf['mvc'], "login");
	}

	function _failed_credentials() {
		// TODO: immer REDIRECT, da sensible informationen
		throw new XorcControllerNeedsAuthException("Authentification required.", 0, $this->conf['mvc'], "login");
	}

	function is_complete() {
		return $this->sess->state;
	}

	function get_rememberme() {
		if ($this->conf['allow_rememberme']) $remember = $_COOKIE[$this->conf['persistent_cookie_name']];
		else $remember = "";
		return $remember;
	}

	function set_rememberme($value) {
		setcookie(
			$this->conf['persistent_cookie_name'],
			$value,
			time() + ($this->conf['persistent_cookie_lifetime'] * 24 * 3600),
			"/"
		);
		#$this->sess->remember = $value;
	}

	function remove_rememberme($value) {
		// log_error("++++ REM cookie remove $value ++++");
		setcookie(
			$this->conf['persistent_cookie_name'],
			$value,
			time() - (24 * 3600),
			"/"
		);
		#$this->sess->remember = null;
	}

	function logout() {
		$this->before_logout();

		$this->soft_logout();
	}

	function soft_logout() {
		$this->configure(xorc_ini("auth"));
		$this->load_session();
		#log_error("+++ logout +++");
		#log_error($this->sess);

		$remember = $this->sess->remember;
		$this->sess->state = 0;
		$this->_additional_destroy();

		# TODO: wo ist sess?
		#		@$this->sess->state = 0;

		$this->uname = "";
		//		print("softlogout!");
		$this->sess = null;
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time() - 42000, '/');
		}

		if ($this->conf['allow_rememberme'] && $remember) {
			$this->remove_rememberme($remember);
		}

		session_destroy();
		//		print_r($_SESSION);
	}

	function before_logout() {
	}
	function after_login() {
	}

	function _redirect_action() {
		// log_error("++++ REDIRECT after login ++++");
		//	right after the correct posting of auth infos
		//	we do an redirect to prevent second postings
		if ($this->conf['redirect_uri']) {
			$redir = $_SERVER['REDIRECT_URL'];
			$params = null;
			if ($this->conf['redirect_get']) {
				$params = $_GET;
			}
			// log_error($redir);
			// log_error($params);
			$this->redirect($redir, $params);
		} elseif ($this->conf['redirect_referer']) {
			$redir = $_SERVER['HTTP_REFERER'];
			$this->redirect($redir);
		} elseif ($this->conf['redirect_server']) {
			$redir = $_SERVER[$this->conf['redirect_server']];
			$this->redirect($redir);
		} else {
			// log_error("AUTH-OK-REDIRECT");
			#log_error($_SESSION);
			$this->redirect("/");
		}
	}

	function check_login() {
		return $this->_check_login();
	}

	function get_userobject() {
		// log_error("+++ get user +++");
		return $this->_fetch_user();
	}

	function _fetch_auth_id_from_login() {
		return $_REQUEST['xorcuser'];
	}

	function _additional_checks() {
		return true;
	}

	function _additional_destroy() {
	}

	/*
      die _init() funktion wird nur aufgerufen,
      wenn der auth_controller in seiner funktion als
      formular, auslog-seitenanzeiger genutzt wird
   */
	function _init() {
		# achtung!
		# wenn im authentifizierten zustand die login-action aufgerufen wird,
		# soll ein redirect erfolgen
		# 
		# TODO: diese implementierung leitet *immer* um, 
		#     wenn die action direkt aufgerufen wird
		// log_error("XXX AUTH _init REDIRECT");
		if (Xorcapp::$inst->act == 'login') {
			# $this->_redirect_action();
			$this->redirect("/");
		}
	}
}
