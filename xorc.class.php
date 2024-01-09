<?php

/**
 * Xorc - Basisklasse für Anwendungen
 *
 * konfiguration etc.
 *
 * @author Robert Wagner
 * @version $Id: xorc.class.php 569 2024-01-05 19:42:48Z rw $
 * @copyright 20sec.net, 28 January, 2006
 * @package xorc
 **/

define('XORC_LIB_PATH', __DIR__);

class Xorc {
	public $session_started = false;
	public $perm;
	public $classfilesuffix = ".php";	// additional filesuffix for classfiles 
	public $name;
	public $confdir = "";
	public $include_path;
	public $conf;
	public $_inifile;

	function __construct($inifile = "") {
		if ($inifile === null) $init = false;
		else $init = true;

		$this->include_path = dirname((string) $this->include_path);
		$this->name = strtolower(static::class);

		$confvar = strtoupper($this->name) . "_CONF";

		if (!$inifile) $inifile = $_SERVER[$confvar];
		if (!$inifile) $inifile = $this->name . ".ini";
		if (!preg_match("!/!", (string) $inifile)) {
			if ($_SERVER[$confvar]) {
				$inifile = "{$_SERVER[$confvar]}/{$inifile}";
			} else {
				$inifile = "{$this->include_path}/{$inifile}";
			}
		}
		#	print "INI $inifile ++++";
		$this->confdir = dirname((string) $inifile);

		$conf = @parse_ini_file($inifile, true);
		$this->conf = $conf;
		$this->_inifile = $inifile;

		#print "\n\n\n xorc constructor ($init) \n\n\n";

		if ($init) $this->init_from_conf();
	}

	// should be overwritten
	public function load_classes() {
	}

	public function init_from_conf($conf = null) {
		#      log_error("INIT FROM CONF $this->_inifile");

		if (is_null($conf)) $conf = $this->conf;
		else $this->conf = $conf;
		#      log_error($conf);
		#setlocale("LC_ALL", "de_DE");

		if ($conf['general']['locale'] ?? null) setlocale(LC_ALL, $conf['general']['locale']);
		if (isset($conf['general']['timezone'])) {
			$tz = $conf['general']['timezone'];
		} else {
			$tz = @date_default_timezone_get();
		}
		if (!$tz) $tz = "Europe/Berlin";
		date_default_timezone_set($tz);

		if ($conf['general']['use_db']) {
			if ($conf['general']['use_db'] == 1) $dbv = "adodb";
			else $dbv = $conf['general']['use_db'];
			define('XORC_DB_ADODB_VERSION', $dbv);
			$this->use_db();
		}

		#		print "RUNAPP:". memory_get_usage()."\n";
		if (@$conf['general']['use_ldap']) $this->use_ldap();
		if (@$conf['general']['use_core']) $this->use_core();
		if (@$conf['general']['use_form']) $this->use_form();
		if (@$conf['general']['use_template']) $this->use_template();
		if (@$conf['general']['use_session']) $this->use_session();

		if (@$conf['general']['use_gimmicks']) $this->use_gimmicks();
		if (@$conf['general']['use_l10n']) $this->use_l10n();

		if (@$conf['general']['error_reporting'] || @$conf['general']['error_reporting'] === 0) {
			$err_rep = 0;
			@eval('$err_rep = ' . $conf['general']['error_reporting'] . ';');
			error_reporting($err_rep);
		}

		#		print "RUNAPP:". memory_get_usage()."\n";
		$this->load_classes();
	}

	function use_db($dsn = "", $debug = false, $prefix = "", $ac = null) {
		//    if($this->conf['general']['use_db']=='ar'){
		//       require_once XORC_LIB_PATH.'/ar/ActiveRecord.php';
		//       $cfg = ActiveRecord\Config::instance();
		// #     $cfg->set_model_directory('/path/to/your/model_directory');
		//       $cfg->set_connections(array('development' =>
		//         $this->conf['db']['_db']));
		//       return;
		//    }
		define('ADODB_ASSOC_CASE', 0);
		include_once(__DIR__ . "/db/xorcstore_connector.class.php");
		if (!$dsn) {
			$this->connect_db_section('db');
		} else {
			new XorcStore_Connector("_db", ['dsn' => $dsn, 'debug' => $debug, 'prefix' => $prefix, 'after_connect' => $ac]);
		}
	}

	function connect_db_section($section) {




		foreach ($this->conf[$section] as $var => $dsn) {
			if (preg_match("/\.(debug|prefix|persistent|ignore_sequences|use_sequences|after_connect|charset)$/", (string) $var)) continue;
			#		print "connecting to $dsn";

			new XorcStore_Connector($var, [
				'dsn' => $dsn, 'debug' => @$this->conf[$section][$var . '.debug'],
				'prefix' => @$this->conf[$section][$var . '.prefix'],
				'persistent' => @$this->conf[$section][$var . '.persistent'],
				'ignore_sequences' => @$this->conf[$section][$var . '.ignore_sequences'],
				'use_sequences' => @$this->conf[$section][$var . '.use_sequences'],
				'after_connect' => @$this->conf[$section][$var . '.after_connect'], 'charset' => @$this->conf[$section][$var . '.charset']
			]);

			//			$this->dbconnect($dsn, $var, 
			//				$this->conf[$section][$var.'.debug'],
			//				$this->conf[$section][$var.'.prefix'],
			//				$this->conf[$section][$var.'.persistent']);
		}
	}

	function use_ldap($dsn = "", $debug = "") {
		$ldapconnect = null;
		if (!$dsn) {
			$this->connect_ldap_section('ldap');
		} else {
			$ldapconnect($dsn, "_ldap", $debug);
		}
	}

	function connect_ldap_section($section) {
		foreach ($this->conf[$section] as $var => $dsn) {
			$this->ldapconnect($dsn, $var);
		}
	}

	function use_form() {
		#	include_once(XORC_LIB_PATH . "/form/xorcform.class.php");
	}

	function use_core() {
		//		include_once(XORC_LIB_PATH."/core/Attachment.class");
		//		include_once(XORC_LIB_PATH."/core/Linkage.class");
	}

	function use_template() {
		if ($this->conf['general']['use_template'] == 'smarty3') {
			include_once(XORC_LIB_PATH . "/tpl/Smarty3/libs/Smarty.class.php");
		} else {
			include_once(XORC_LIB_PATH . "/tpl/Smarty/libs/Smarty.class.php");
		}
	}

	function use_xpath() {
		include_once(XORC_LIB_PATH . "/xml/XPath.class.php");
	}

	function use_yaml() {
		if (str_starts_with(PHP_VERSION, "5")) {
			#include_once(XORC_LIB_PATH."/text/spyc-0.4.5-svn/spyc.php");
			include_once(XORC_LIB_PATH . "/text/spyc-0.2.5/spyc.php5");
			#include_once(XORC_LIB_PATH."/text/spyc-0.5/spyc.php");
		} else {
			include_once(XORC_LIB_PATH . "/text/spyc-0.2.5/spyc.php");
		}
	}

	function use_session($name = "") {
		#	   if(PHP_SAPI=='cli') return;
		if (!$name) $name = $this->conf['session']['name'];
		if (!$name) $name = "XORC";
		$this->conf['session']['name'] = $name;
		#		log_error("SETTING SESSION NAME TO $name");

		/*
$session_save_path = "tcp://$host:$port?persistent=1&weight=2&timeout=2&retry_interval=10,  ,tcp://$host:$port  ";
ini_set('session.save_handler', 'memcache');
ini_set('session.save_path', $session_save_path);
*/

		session_name($name);
		$ttl = @$this->conf['session']['ttl'] ? $this->conf['session']['ttl'] : 7200;
		if (isset($this->conf['session']['save_handler'])) {
			ini_set('session.save_handler', $this->conf['session']['save_handler']);
		}
		if (isset($this->conf['session']['save_path'])) {
			$nsp = $this->conf['session']['save_path'];
			// relatives verzeichnis
			if ($nsp[0] != '/' && !preg_match("/:/", (string) $nsp)) {
				$osp = session_save_path();
				$nsp = $osp . '/' . $nsp;
			}
			session_save_path($nsp);
		}
		ini_set("session.gc_maxlifetime", $ttl);
		$cpath = $this->conf['session']['cookie_path'] ?? "/";

		session_set_cookie_params(
			0,
			$cpath,
			@$this->conf['session']['cookie_domain'],
			@$this->conf['session']['cookie_secure'],
			true
		);

		if (isset($this->conf['session']['adodbsession_db'])) {
			if (defined('XORC_DB_ADODB_VERSION')) {
				$dbv = XORC_DB_ADODB_VERSION;
			} else {
				$dbv = "adodb";
			}
			include_once(XORC_LIB_PATH . "/db/$dbv/adodb-errorhandler.inc.php");
			include_once(XORC_LIB_PATH . "/db/$dbv/adodb.inc.php");
			[$GLOBALS['ADODB_SESSION_DRIVER'], $GLOBALS['ADODB_SESSION_CONNECT'], $GLOBALS['ADODB_SESSION_USER'], $GLOBALS['ADODB_SESSION_PWD'], $GLOBALS['ADODB_SESSION_DB']] = explode(":", (string) $this->conf['session']['adodbsession_db']);

			$GLOBALS['ADODB_SESSION_TBL'] = $this->conf['session']['adodbsession_table'] ?: "sessions";
			$datafield = $this->conf['adodbsession_table_datafield'] == "clob" ? "-clob" : "";
			include_once(XORC_LIB_PATH . "/db/$dbv/session/adodb-session{$datafield}.php");
			if ($this->conf['adodbsession_crypt'])
				include_once(XORC_LIB_PATH . "/db/$dbv/session/adodb-cryptsession.php");
			if (!$this->conf['session']['adodbsession_connect'] == 'pconnect')
				adodb_sess_open(false, false, false);
		}
	}

	function start_auth($prefs = "") {
		if (!$prefs) $prefs = [];

		include_once(XORC_LIB_PATH . "/auth/Perm.class");
		include_once(XORC_LIB_PATH . "/auth/auth.class.php");
		if ($this->conf['auth']['classname']) {
			$path = dirname((string) $this->conf['auth']['classname']);
			$this->conf['auth']['classname'] = basename((string) $this->conf['auth']['classname']);
			if ($path) $path .= "/";
			include_once("{$this->include_path}/$path{$this->conf['auth']['classname']}.class$this->classfilesuffix");
		}
		$this->perm = new Perm($this->conf['auth']['perms']);

		if ($prefs['optional'] && !$_COOKIE[$this->conf['session']['name']]) {
			$_auth = new $this->conf['auth']['classname']($this->conf['auth']);
			$_auth->optional = true;
		} else {
			if (!$this->session_started) $this->start_session();

			$_auth = $this->_get_session_var("_auth");

			if (!isset($_auth)) {
				$_auth = new $this->conf['auth']['classname']($this->conf['auth']);
				$_SESSION["_auth"] = $_auth;
			}
		}

		// waehrend der lebenszeit der $_auth variable koennen sich konfigurationsdaten aendern
		//		z.b. die login/ logout seiten je nach der zuletzt (zuerst) benutzten seite eines
		//		angebots.

		$_auth->set_conf($this->conf['auth']);

		if ($prefs['optional']) {
			$ok = $_auth->is_valid(true);
			if (!$ok) {
				$_auth->make_loginblock();
			} else {
				$this->start_session();
				$_SESSION["_auth"] = $_auth;
			}
		} else {
			$_auth->is_valid();		// page terminates if not authorized
		}
		return $_auth;
	}

	function _get_session_var($name) {
		if (isset($_SESSION)) {
			$var = &$_SESSION[$name];
		} else {
			$var = &$GLOBALS['HTTP_SESSION_VARS'][$name];
		}
		return $var;
	}

	function use_gimmicks() {
		include_once(XORC_LIB_PATH . "/div/util.php");
		include_once(XORC_LIB_PATH . "/div/Filer.class");
		include_once(XORC_LIB_PATH . "/div/StaticPicture.class");
	}

	function use_l10n($lang = "") {
		#echo "LLLL";
		#print_r($_GLOBALS);
		if (!$lang) $lang = $this->conf['general']['locale'];
		if (function_exists('bindtextdomain')) {
			#echo $this->include_path;
			#echo $lang;
			$ok = setlocale(LC_MESSAGES, $lang);
			#var_dump($ok); 
			$ok = bindtextdomain($this->name, $this->include_path . "/locale");
			#var_dump($ok); 
			$ok = bind_textdomain_codeset($this->name, 'UTF-8');
			#var_dump($ok); 
			$ok = textdomain($this->name);
			#var_dump($ok); 
		} else {
			include_once(XORC_LIB_PATH . "/l10n/php-gettext-1.0.10/streams.php");
			include_once(XORC_LIB_PATH . "/l10n/php-gettext-1.0.10/gettext.php");
			$langfile = $this->include_path . "/locale/{$lang}/LC_MESSAGES/{$this->name}.mo";
			$GLOBALS['l10n'] = new gettext_reader($fr = new FileReader($langfile));
		}
		include_once(XORC_LIB_PATH . "/l10n/l10n.php");
	}

	function ldapconnect($dsn, $gvar, $debug = false) {
		global ${$gvar};
		$dn = $gvar . "_dn";
		global ${$dn};
		global $LDAP_CONNECT_OPTIONS;
		$LDAP_CONNECT_OPTIONS = [["OPTION_NAME" => LDAP_OPT_DEREF, "OPTION_VALUE" => 2], ["OPTION_NAME" => LDAP_OPT_SIZELIMIT, "OPTION_VALUE" => 100], ["OPTION_NAME" => LDAP_OPT_TIMELIMIT, "OPTION_VALUE" => 30], ["OPTION_NAME" => LDAP_OPT_PROTOCOL_VERSION, "OPTION_VALUE" => 3], ["OPTION_NAME" => LDAP_OPT_ERROR_NUMBER, "OPTION_VALUE" => 13], ["OPTION_NAME" => LDAP_OPT_REFERRALS, "OPTION_VALUE" => FALSE], ["OPTION_NAME" => LDAP_OPT_RESTART, "OPTION_VALUE" => FALSE]];
		if (defined('XORC_DB_ADODB_VERSION')) {
			$dbv = XORC_DB_ADODB_VERSION;
		} else {
			$dbv = "adodb";
		}
		include_once(XORC_LIB_PATH . "/db/$dbv/adodb-errorhandler.inc.php");
		include_once(XORC_LIB_PATH . "/db/$dbv/adodb.inc.php");
		[$driver, $host, $user, $pass, $db] = explode(":", (string) $dsn);
		//print ("$driver, $host, $user, $pass, $db");
		//echo $dn;
		${$dn} = $db;
		//echo $db;
		${$gvar} = ldap_connect($host);
		ldap_set_option(${$gvar}, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_bind(${$gvar}, $user, $pass);
		//$$gvar = NewADOConnection($driver);
		//$$gvar->debug = true;
		//$$gvar->Connect($host, $user, $pass, '');

	}

	function start_session() {
		#	   if(PHP_SAPI=='cli') return;
		#	   log_error("??? START SESSION ".session_name());
		#		log_error(ini_get("session.gc_maxlifetime"));
		$integrity = $this->conf['session']['integrity_check'] ?? false;
		if ($integrity) $integrity = array_map('trim', explode(',', (string) $integrity));

		if (!$this->session_started) {
			$this->use_session();
			#				log_error("!!!! START SESSION ".session_name());
			#				log_error(ini_get("session.gc_maxlifetime"));
			#if(!$this->conf['session']['adodbsession_db']){
			#		      log_error("############################## SESSIONSTART ###################################");

			# http://stackoverflow.com/questions/3393674/php-session-problem-in-safari-opera-and-ie
			# header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

			session_start();

			if ($integrity) {
				if (!isset($_SESSION['__xorc_integrity'])) {
					$_SESSION['__xorc_integrity'] = [];
					foreach ($integrity as $v) {
						$_SESSION['__xorc_integrity'][$v] = $_SERVER[$v];
					}
				}

				foreach ($integrity as $v) {
					if ($_SESSION['__xorc_integrity'][$v] != $_SERVER[$v]) {
						# log_error('SESSION-HIJACK ATTEMPT ON ID '.session_id()." /W $v ({$_SESSION['__xorc_integrity'][$v]} VS {$_SERVER[$v]})");
						$_SESSION = ['__xorc_integrity' => []];
						session_regenerate_id(true);
						# log_error('+++ NEW ID '.session_id());
						foreach ($integrity as $v) {
							$_SESSION['__xorc_integrity'][$v] = $_SERVER[$v];
						}
						break;
					}
				}
			}
			#}
		}

		$this->session_started = true;
	}

	function version() {
		return join("", file(XORC_LIB_PATH . "/VERSION"));
	}

	function log($msg, $file = "") {
		if (@$this->conf['general']['nolog']) return;
		if (!$file) {
			if (!@$this->conf['general']['log']) {
				$file = $this->conf['general']['var'] . "/" . $this->name . ".log";
			} else {
				$file = $this->conf['general']['log'];
			}
		}
		#		debug_print_backtrace();
		if (!is_string($msg)) $msg = "DUMP: " . var_export($msg, true);
		error_log(date("Y-m-d H:i:s") . " " . $msg . "\n", 3, $file);
	}

	function flash($set = "", $redirect = null) {
		static $flash, $flashed;
		if (!$flashed) {
			//			$this->start_session();
			$flash = $_SESSION['_flash'];
			unset($_SESSION['_flash']);
			$flashed = true;
		}
		if ($set) {
			$_SESSION['_flash'] = $set;
			if (!is_null($redirect)) {
				$this->redirect($redirect);
			}
		} else {
			return $flash;
		}
	}

	function redirect($to, $msg = null) {
		if (!is_null($msg)) $this->flash($msg);
		if (!preg_match("!^http!", (string) $to)) {
			if (!preg_match("!^/!", (string) $to)) {
				$to = dirname((string) $_SERVER['SCRIPT_NAME']) . "/$to";
			}
			$proto = ($_SERVER['HTTPS'] == 'on') ? "https://" : "http://";
			$to = $proto . $_SERVER['HTTP_HOST'] . $to;
		}
		header("Location: $to");
		exit;
	}
}
