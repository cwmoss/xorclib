<?php

/**
 * XorcApp - Basisklasse für MVC Anwendungen
 *
 * konfiguration etc.
 *
 * @author Robert Wagner
 * @version $Id$
 * @copyright 20sec.net, 28 January, 2006
 * @package xorc
 **/

require_once(__DIR__ . "/mvc/xorc_request.class.php");
require_once(__DIR__ . "/mvc/xorc_response.class.php");
require_once(__DIR__ . "/mvc/xorc_router.class.php");
require_once(__DIR__ . "/mvc/xorc_env.class.php");
require_once(__DIR__ . "/mvc/xorc_controller.class.php");
require_once(__DIR__ . "/mvc/xorc_view.class.php");
require_once(__DIR__ . "/mvc/helper.php");
// require_once("mvc/error.php");
require_once(__DIR__ . "/mvc/xorc_exception.class.php");
require_once(__DIR__ . "/xorc.class.php");
if (!defined('XORCAPP_NODISPATCH')) define('XORCAPP_NODISPATCH', false);
# print memory_get_usage()."\n";

class xorcapp extends Xorc {
	public static $inst;

	public $conf;
	public $router;
	public $req;
	public $resp;
	public $env;
	public $ctrl;
	public $act;
	public $view;

	public $original_action;

	public $ctrl_path;
	public $ctrl_name;

	public $current_action;

	public $auth;
	public $user;

	public $flash;
	public $_flashed = false;

	public $flash_vars;
	public $_flashed_vars = false;

	public $cL = [];
	public $oL = [];
	public $out;
	public $outv = [];
	public $_used_controller = [];
	public $firstaction = true;

	public $autoauth = false;
	public $sessionstart = false;

	public $pre = [];
	public $post = [];

	public $nopage = false;
	public $location;

	static function run($klas = "", $conf = null): xorcapp {

		if (!isset(self::$inst)) {
			//			print __CLASS__;
			//			print self::KLAS;
			//			print get_class(self);

			if (!$klas) $klas = self::class;
			self::$inst = new $klas(null, $conf);

			#   vorgezogen: ab base() kann dann korrekt geloggt werden.
			// self::$inst->base();
			##self::$inst->init_from_conf($conf);
		}
		self::$inst->env();
		self::$inst->req();
		self::$inst->resp();

		self::$inst->view(new Xorc_View);
		self::$inst->router();

		if (PHP_SAPI == 'cli') return self::$inst;
		if (XORCAPP_NODISPATCH === true) return self::$inst;

		self::$inst->dispatch();

		//		print_r(self::$inst);
		return self::$inst;
	}

	function dispatch() {

		//list($cont, $act, $parms)=$this->router->get_route($this->req);
		[$ca, $params] = $this->router->route_for($this->req);
		//list($cont, $act)=@split("/", $ca);
		$this->original_action = $ca;

		$caL = @explode("/", (string) $ca);
		// print_r($caL);
		if (sizeof($caL) > 2) {
			$act = array_pop($caL);
			$cont = join("/", $caL);
		} else {
			$act = $caL[1];
			$cont = $caL[0];
		}

		$this->log("[APP] REQ " . $_SERVER['REQUEST_METHOD'] . " $cont/$act " .
			($this->req->ajax ? "AJAX" : "NORMAL") . " ~ " . ($_SERVER['QUERY_STRING'] ?? "") .
			" ~ " . $_SERVER["REQUEST_URI"]);

		// $this->log($_GET);

		# try {
		$this->location = $this->req->path_with_query;
		$this->req->add_path_vars($params);

		if ($this->autoauth) $this->auto_auth($cont, $act);

		foreach ($this->pre as $ca) {
			[$pc, $pa] = explode("/", (string) $ca);
			$this->action($pc, $pa);
		}

		$this->log("[APP] SESSION STARTS (IF NOT BEFORE)");
		if ($this->sessionstart) $this->start_session();

		$this->action($cont, $act);

		foreach ($this->post as $ca) {
			[$pc, $pa] = explode("/", (string) $ca);
			$this->action($pc, $pa);
		}
		# }  catch (xxException $e) {
		#   $this->crtl->message=$e->getMessage();
		#	$this->out.=$this->view->render("/errors/500.html");
		# }
		$this->render_page();
	}

	function action($cont, $act, $parms = []) {
		try {
			$this->log("[APP] ACT $cont/$act " . ($this->req->ajax ? "AJAX" : "NORMAL") . " " . ($_SERVER['QUERY_STRING'] ?? null));
			$this->ctrl = $this->load_controller($cont, $act);

			// $c=new $cont;
			if ($this->ctrl) {
				$this->log("[APP] CTRL " . $this->ctrl::class . " LOADED OK");

				# evtl. wird ein anderer controller geladen
				#     z.b. auth exception
				$cont = $this->ctrl_name;
				$act = $this->act;

				if (
					$act[0] != '_' && $this->ctrl->_check_if_action_is_allowed($act) &&
					method_exists($this->ctrl, $act) &&

					// eindeutige URLs /action != /Action
					// d.h. wir vergleichen auch noch mit der 
					// originalschreibweise der methode
					in_array($act, get_class_methods($this->ctrl))
				) {

					//   if($this->ctrl->require_auth($act)){     


					//					$this->ctrl->user=$this->user;
					//					print_r($this->user);
					$this->cL[] = [-1, $cont, $act];
					$pos = sizeof($this->cL) - 1;

					//		ob_start();
					$this->current_action = "$cont/$act";
					if (!$parms) $parms = [];
					$ok = call_user_func_array([$this->ctrl, $act], $parms);
					//		$this->out.=ob_get_clean();
					if ($this->cL[$pos][0] === -1) {
						$this->cL[$pos][0] = $ok;
					}
				} else {
					throw new XorcRuntimeException("action for $cont / $act not found.");
				}
			} else {
				throw new XorcRuntimeException("controller for $cont not found.");
			}
		} catch (XorcControllerForewardException $e) {
			#   print "ERROR";
			$this->out .= $this->view->render("/errors/default.html", ["message" => $e->getMessage()]);
			#$this->error($e);
		}
	}

	//	no stack
	//	no delay
	// 	render right in place	
	function subaction($cont, $act, $parms = []) {

		try {
			$this->log("[APP] SUB $cont/$act " . ($this->req->ajax ? "AJAX" : "NORMAL") . " " . $_SERVER['QUERY_STRING']);
			$c = $this->load_controller($cont, $act, true);
			// $c=new $cont;
			if ($c) {
				if (method_exists($c, $act)) {
					$safe = [$c, $this->ctrl_name, $act];

					$this->ctrl = $c;	// set the controllerobject
					$this->act = $act;
					$this->ctrl_name = $cont;

					ob_start();
					$ok = call_user_func_array([$c, $act], $parms);
					print ob_get_clean();
					if ($ok === null || $ok !== false) {
						print $this->view->render($ok);
					}

					// reset
					[$this->ctrl, $this->ctrl_name, $this->act] = $safe;
				} else {
					throw new XorcRuntimeException("action for $cont / $act not found.");
				}
			} else {
				throw new XorcRuntimeException("controller for $cont not found.");
			}
		} catch (Exception $e) {
			$this->error($e);
		}
	}

	function foreward($cont, $act) {
		$this->log("[APP] FWD $cont/$act " . ($this->req->ajax ? "AJAX" : "NORMAL") . " " . $_SERVER['QUERY_STRING']);
		$last = sizeof($this->cL) - 1;
		$this->cL[$last][0] = false;
		$this->action($cont, $act);
	}

	function render($cv, $params = []) {
		#		$this->log("RND SINGLE $cv");
		$this->out .= $this->view->render($cv, $params);
		//		print $this->out;
	}

	function render_page($skip_actions = false) {
		#log_error("render-page: skip_actions?".$skip_actions);
		// post rendering?
		$post = null;
		// $this->log($this->cL);
		if (!$skip_actions) foreach ($this->cL as $c) {
			$ok = $c[0];	// returncode of controller
			if ($ok !== -1 && ($ok === null || $ok !== false)) {
				$this->ctrl = $this->load_controller($c[1]);	// set the controllerobject
				$this->act = $c[2];
				$this->ctrl_name = $c[1];

				if ($ok === true) {
					$this->outv[$c[1] . "_" . $c[2]] = $this->view->render("");
				} else {
					$this->out .= $this->view->render($ok);
				}
			} else {
				//				print "NORENDERING";
			}
			// postrendering *immer* beachten
			if ($this->ctrl->_post_render) {
				log_error("[APP] post-render {$c[1]} // {$this->ctrl->_post_render}");
				$post = $this->ctrl;
			}
		}

		if (!$this->req->ajax && !self::$inst->nopage) {
			// $this->log("RND PAGE");
			$outp = $this->view->render_page();
		} else {
			# $this->log("RND NOPAGE");
			$outp = $this->out;
		}

		if ($post) print $post->_post_render($outp);
		else print $outp;
	}

	function flash($set = "", $redirect = null) {
		if (!$this->_flashed) {
			$this->start_session();
			$this->flash = @$_SESSION['_flash'];
			unset($_SESSION['_flash']);
			$this->_flashed = true;
		}
		if ($set) {
			@$_SESSION['_flash'] = $set;
		} else {
			return $this->flash;
		}
	}

	function flash_var($key, $val = null) {
		if (!$this->_flashed_vars) {
			$this->start_session();
			$this->flash_vars = @$_SESSION['_flash_vars'];
			unset($_SESSION['_flash_vars']);
			$this->_flashed_vars = true;
		}
		if (!is_null($val)) {
			if (!is_array(@$_SESSION['_flash_vars'])) $_SESSION['_flash_vars'] = [];
			$_SESSION['_flash_vars'][$key] = $val;
		} else {
			return $this->flash_vars[$key] ?? null;
		}
	}

	function env() {
		$this->env = new Xorc_Env;
	}

	function req() {
		$this->req = new Xorc_Request;
	}

	function resp() {
		$this->resp = new Xorc_Response;
	}

	function view($set = null) {
		if ($set) $this->view = $set;
		#	else $this->view=new Xorc_View;
		return $this->view;
	}

	function router() {
		$this->router = new Xorc_Router;
	}

	function error($e) {
		print "es ist ein fehler aufgetreten: " . $e->getMessage() . "\n";
	}

	function terminate($layout = true) {
		#		$this->log("TERMINATE $layout");
		if ($layout) $this->render_page();
		else print $this->out;
		exit;
	}

	function name($name = "") {
		if (!$name) return self::$inst->name;
		else self::$inst->name = $name;
	}

	function auto_auth($cont, $act) {
		# 		log_error("AUTOAUTH $cont/$act");
		$this->ctrl = $this->load_controller($cont, $act);
		//		print_r($this->ctrl);		
		$this->auth = $this->start_auth();
		$this->user = $this->auth->get_userobject();
	}

	function &start_auth($prefs = null) {
		if (!$prefs) $prefs = [];

		$this->log("[APP] **LEGACY AUTH START");
		if ($this->auth) return $this->auth;
		#		$this->log("AUTH START CONTINUED");

		include_once(__DIR__ . "/mvc/xorc_auth.class.php");
		if ($this->conf['auth']['controller']) {
			$path = dirname((string) $this->conf['auth']['controller']);
			#			$this->log("CUSTOM CONTROLLER ".$this->conf['auth']['controller']);
			$this->conf['auth']['classname'] = basename((string) $this->conf['auth']['controller']);
			if ($path) $path .= "/";
			if ($this->conf['auth']['classfile'])
				include_once($this->conf['auth']['classfile']);
			#   include_once("{$this->include_path}/$path{$this->conf['auth']['controller']}.class.php");
			$authn = $this->conf['auth']['controller'];
		} else {
			$authn = "Xorc_Auth";
		}

		$this->log($authn);

		if ($prefs['optional'] && !$_COOKIE[$this->conf['session']['name']]) {
			$_auth = new $authn($this->conf['auth']);
			$_auth->optional = true;
		} else {
			if (!$this->session_started) $this->start_session();

			$_auth = &$this->_get_session_var("_auth");

			if (!isset($_auth)) {
				#			   log_error("SETZE AUTH");
				#			   log_error($this->conf);
				#			   log_error("ENDE SETZE AUTH");
				$_auth = new $authn($this->conf['auth']);
				$_SESSION["_auth"] = $_auth;
			}
		}

		#		$this->log($_auth);

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
			#		   log_error("### AUTH - IS_VALID?");
			#		   log_error($_auth);
			$_auth->is_valid();		// page terminates if not authorized
		}

		// evtl. ausloggen?
		if ($this->req->p['logout']) $_auth->logout();
		$this->auth = $_auth;
		#print_r($conf['general']['use_l10n']);
		if ($this->conf['general']['use_l10n'] == "afterauth") {
			$this->use_l10n($_SESSION['xorclang']);
		}
		return $_auth;
	}


	function &load_controller($cname, $action = "", $is_sub = false) {
		$klasn = str_replace("/", "_", strtolower((string) $cname)) . "_c";
		$path = strtolower(dirname((string) $cname));
		if ($path) $path .= "/";
		#		log_error("CTRL-LOAD: trial $path - $klasn - $action");

		if (isset($this->_used_controller[$klasn])) {
			$this->ctrl_name = $cname;
			$this->act = $action;
			log_error("[APP] CTRL-LOAD: OK. USED BEFORE.");
			return $this->_used_controller[$klasn];
		}



		if (!class_exists($klasn, false)) {
			$klasfile = $this->base . "/controller/{$path}{$klasn}.class.php";
			log_error("[APP] CTRL-LOAD: INCLUDING FILE - $klasfile.");
			if (file_exists($klasfile))
				$ok = include($klasfile);
		}

		if (class_exists($klasn, false)) {
			#			log_error("CTRL-LOAD: OK. loaded.");	
			$this->ctrl_name = $cname;
			$this->act = $action;
			$this->ctrl_path = $path;
			$c = new $klasn;

			$c->_path = $path;		# path mitgeben

			// wegen _init
			$this->ctrl = $c;

			$c->r = &$this->req->p;

			if ($c->require_auth && $is_sub) {
				log_error("[APP] NEED AUTH FOR SUB");
				if ($this->auth) $this->user = $this->auth->get_userobject();
			} elseif ($c->require_auth) {
				log_error("[APP] NEED AUTH");
				$authp = "";
				if ($c->require_auth !== true) $authp = $c->require_auth;
				//   print_r($this);
				try {
					log_error("[APP] try AUTH");
					#					log_error($authp);
					if ($this->conf['auth']['mvcplus']) {
						log_error("[APP] new gen / AUTH CONTROLLER");
						$this->auth = $this->load_controller("auth");
						$this->auth->start($authp);

						// reset original request parms 
						$this->ctrl_name = $cname;
						$this->act = $action;
						$this->ctrl_path = $path;
					} else {
						$this->auth = $this->start_auth($authp);
					}
					// log_error("OK");
				} catch (XorcControllerNeedsAuthException $e) {
					// log_error("NEED-AUTH-Exception: $e->cont / $e->act");
					return $this->load_controller($e->cont, $e->act);
				}

				#log_error("AUTH-OBJ");
				#  log_error($this->auth);
				$this->user = $this->auth->get_userobject();
			}

			$c->user = $this->user;

			$c->_init_before($action);
			$c->_init($action);

			$this->_used_controller[$klasn] = &$c;

			return $this->_used_controller[$klasn];
		}

		#		log_error("CTRL-LOAD: FAILED");	
		return false;
	}

	public function register_autoload() {
		spl_autoload_register([$this, 'lib_ctrl_autoload']);
	}

	public function lib_ctrl_autoload($clas) {
		$clas = strtolower((string) $clas);
		$path = $this->include_path;
		$try = "$path/" . $clas . ".class.php";
		#   print "TRY $try";
		if (file_exists($try)) {
			include_once($try);
			return true;
		} elseif (strpos($clas, "_controller")) {
			$path = $this->base . "/controller";
			include_once("$path/" . $clas . ".class.php");
			return true;
		}
		return false;
	}
}
