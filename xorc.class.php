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

	public $approot;
	public $base;

	public $session_started = false;
	public $perm;
	public $classfilesuffix = ".php";	// additional filesuffix for classfiles 
	public $name;
	public $confdir = "";
	public $include_path;
	public $conf;
	public $_inifile;

	function __construct($inifile = "", ?array $config = null) {
		$this->include_path = dirname((string) $this->include_path);
		$this->name = strtolower(static::class);

		$this->approot = dirname(realpath($this->include_path . "/../"));
		$this->base = $this->approot . "/src";

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
		$this->confdir = dirname((string) $inifile);

		// wir brauchen eine gültige ini datei
		if (is_null($config)) {
			if (!is_readable($inifile)) {
				throw new Exception("missing or unreadable ini file. please check for environment variable {$confvar} or default {$this->name}_prod.ini");
			}
			$conf = parse_ini_file($inifile, true);
			if ($conf === false) {
				throw new Exception("ini file parse error. please check " . basename($inifile));
			}
			$this->conf = $conf;
			$this->_inifile = $inifile;
		}
		$this->init_from_conf($config);
	}

	// should be overwritten
	public function load_classes() {
	}

	public function init_from_conf($conf = null) {
		#      log_error("INIT FROM CONF $this->_inifile");

		if (is_null($conf)) $conf = $this->conf;
		else $this->conf = $conf;

		if (($this->conf['general']['var'][0] ?? '') != "/") {
			$this->conf['general']['var'] = $this->approot . "/" . ($this->conf['general']['var'] ?? 'var');
		}

		if ($conf['general']['locale'] ?? null) setlocale(LC_ALL, $conf['general']['locale']);
		if (isset($conf['general']['timezone'])) {
			$tz = $conf['general']['timezone'];
		} else {
			$tz = @date_default_timezone_get();
		}
		if (!$tz) $tz = "Europe/Berlin";
		date_default_timezone_set($tz);

		if ($conf['general']['use_db'] ?? null) {
			if ($conf['general']['use_db'] == 1) $dbv = "adodb";
			else $dbv = $conf['general']['use_db'];
			define('XORC_DB_ADODB_VERSION', $dbv);
			$this->use_db();
		}

		if (@$conf['general']['use_session']) $this->use_session();

		if ($conf['general']['error_reporting'] || @$conf['general']['error_reporting'] === 0) {
			error_reporting($conf['general']['error_reporting']);
		}

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

	function _get_session_var($name) {
		if (isset($_SESSION)) {
			$var = &$_SESSION[$name];
		} else {
			$var = &$GLOBALS['HTTP_SESSION_VARS'][$name];
		}
		return $var;
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
