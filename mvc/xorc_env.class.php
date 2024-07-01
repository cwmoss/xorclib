<?php

class Xorc_Env {

	var $httpbase;		# all img, css, js... requests
	var $actionbase;	# all php requests
	var $proto;
	var $server;

	function __construct() {
		$conf = XorcApp::$inst->conf['general'];

		$https_enabled = false;
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $https_enabled = true;
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') $https_enabled = true;

		$this->proto = ($https_enabled) ? "https://" : "http://";
		if (isset($conf['proto'])) {
			$this->proto = XorcApp::$inst->conf['general']['proto'];
		}
		$this->server = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : "";   # via proxy?
		if (!$this->server) $this->server = $_SERVER['HTTP_HOST'] ?? 'localhost';

		if (isset($conf['urlrewrite']) && $conf['urlrewrite']) {
			$this->httpbase = dirname($_SERVER['SCRIPT_NAME']);
		} else {
			$this->httpbase = $_SERVER['SCRIPT_NAME'];
		}
		if ($this->httpbase == "/") $this->httpbase = "";
		$this->actionbase = isset($conf['noprefix']) ? "" : $this->httpbase;
	}

	function base_uri() {
		$b = $this->proto . $this->server . $this->httpbase;
		return $b;
	}

	function base_action_uri() {
		$b = $this->proto . $this->server . $this->actionbase;
		return $b;
	}
}
