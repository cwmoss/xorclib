<?php

class Xorc_Request {
	var $path;	// path
	var $path_with_query;

	var $p;		// request parms

	// is this a xmlhttprequest?
	var $ajax = false;

	function __construct() {
		//		print_r($_SERVER);
		#		log_error("REQ-CLASS: ".$_SERVER['SCRIPT_NAME']."--".$_SERVER['REQUEST_URI']);
		if (xorcapp::$inst->conf['general']['urlrewrite'] ?? null) {
			$path = str_replace(
				dirname($_SERVER['SCRIPT_NAME']) . "/",
				"",
				(string) ($_SERVER['REQUEST_URI'] ?? "")
			);
			if (!$path || $path[0] != "/") $path = "/" . $path;		# always starting with *one* slash
			// alles hinter einem ? loswerden
			if (($ok = strpos($path, '?')) !== false) {
				$path = substr($path, 0, $ok);
			}
			$this->path = urldecode($path);
			#			print $_SERVER['REQUEST_URI']; print "#".$_SERVER['SCRIPT_NAME'];
			# print_r($this);
		} else {
			$this->path = $_SERVER['PATH_INFO'] ?? '';
		}

		//		$this->path_with_query=$this->path;
		//		if($_SERVER['QUERY_STRING']) $this->path_with_query.="?".$_SERVER['QUERY_STRING'];

		$this->path_with_query = $_SERVER["REQUEST_URI"] ?? "";
		#		print_r($this); exit;
		$this->p = $_REQUEST ? $_REQUEST : array();
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->ajax = true;
		}
	}

	function add_path_vars($p) {
		if ($p) {
			//			print_r($p);
			$this->p = array_merge($this->p, $p);
		}
	}
}
