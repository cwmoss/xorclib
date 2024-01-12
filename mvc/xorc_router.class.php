<?php

class Xorc_Router {
	var $map;
	var $map_default;
	var $map_regex;

	function __construct($f = null) {
		if ($f) {
			$this->load_file($f);
		} elseif (file_exists(XorcApp::$inst->base . "/routes.txt")) {
			$this->load_file(XorcApp::$inst->base . "/routes.txt");
		}
	}

	function load_file($f) {
		#		print "LOADING $f\n";
		foreach (file($f) as $r) {
			$this->parse_map_entry($r);
		}
	}

	function add_route($r) {
		$this->parse_map_entry($r);
	}

	function parse_map_entry($e) {
		if ($e[0] == "#") return;
		$r = preg_split("/\s+/", trim($e));
		$p = array_shift($r);
		$a = array_shift($r);
		$this->map[$p] = $a;
		if ($r) foreach ($r as $ext) {
			if (preg_match('!^(\$\w+)~(.*)$!', $ext, $mat)) {
				$this->map_regex[$p][$mat[1]] = $mat[2];
			} elseif (preg_match('!^(\$\w+)=(.*)$!', $ext, $mat)) {
				$this->map_default[$p][$mat[1]] = $mat[2];
			}
		}
	}

	function route_for($r) {
		$path = $r->path;
		#		log_error("ROUTE FOR:".$r->path);
		$path = preg_replace("!^/!", "", $path);
		$pathL = array_filter(explode("/", $path));
		//		print_r($_GET);
		#		print "#$r->path#~path~$path~";
		//		print_r($this);
		#      log_error($path);
		if (!$path) return array($this->def_route(), array());
		foreach ($this->map as $p => $a) {
			// static routes?
			if ($path == $p) return array($a, array());

			// there are vars for contr and-or action in the route
			$path_r = str_replace(
				array('$controller', '$action'),
				array("([_\d\w]+)", "([_\d\w]+)"),
				$p
			);

			// parameter regex
			if (isset($this->map_regex[$p]))
				$path_r = str_replace(
					array_keys($this->map_regex[$p]),
					array_values($this->map_regex[$p]),
					$path_r
				);

			//			print " ".$path_r." ";
			$parm = array();

			// lohnen sich weitere tests?
			if ($path_r != $p) {

				#			   print("TESTE $path vs. !^{$path_r}!\n-\n");

				if (!preg_match("!^{$path_r}$!", $path, $mat)) {

					//				   print "NO\n";

					// haben wir noch default werten zum anhaengen?
					if (isset($this->map_default[$p])) {

						//						print "ABER:DEFAULT WERTE";

						// wohin damit?						
						$pL = explode("/", $p);
						$path_with_defs = $pathL;
						$total = sizeof($this->map_default[$p]);
						$start = sizeof($pL) - sizeof($path_with_defs);
						//						print "START: $start";
						if ($start > $total || $start <= 0) continue;
						$start = $total - $start;
						$c = 0;
						foreach ($this->map_default[$p] as $k => $v) {
							if ($c >= $start) $path_with_defs[] = $v;
						}

						//						print_r($path_with_defs);

						$path2 = join("/", $path_with_defs);

						//						print "MIT DEFAULTS: $path2 vs. !^{$path_r}!\n-\n";

						if (!preg_match("!^{$path_r}!", $path2, $mat)) continue;
					} else {
						continue;
					}
				}
				//				print "OK\n";
				// passt!					
				//				print "MATCH $p~$path";
				preg_match_all('!\$(\w+)!', $p, $mat2, PREG_SET_ORDER);
				//				print_r($mat2);
				//				print_r($mat);
				$c = 1;
				foreach ($mat2 as $m) {
					//						print_r($m);
					//						$$m[1]=$mat[$c++];						
					if ($m[1] != 'action' && $m[1] != 'controller') {
						if (isset($mat[$c]))
							$parm[$m[1]] = $mat[$c];
						$c++;
					} else {
						$a = str_replace($m[0], $mat[$c++], $a);
					}
				}
				//				print "PARAMETER:"; print_r($parm);
				#            log_error($a);
				#            $a=str_replace("=", "/", $a);
				return array($a, $parm);
			}
		}
	}

	function url_for($action, $params = array()) {
		#log_error("+++ url for: $action");
		#log_error($params);

		$url = "";
		$raute = "";
		$host = "";
		//		print "$action~";
		if (!is_array($params)) $params = array('id' => $params);

		if (isset($params['#'])) {
			$raute = "#" . urlencode($params['#']);
			unset($params['#']);
		}
		if (isset($params['+'])) {
			$proto = $host = "";
			if (preg_match("!^(https?://)(.*?)$!", $params['+'], $phmat)) {
				#            print_r($phmat);
				$proto = $phmat[1];
				$host = $phmat[2];
			} else {
				$host = $params['+'];
			}
			#         print "P: $proto; H: $host\n";
			if (!$host) $host = XorcApp::$inst->env->server;
			if (!$proto) $proto = XorcApp::$inst->env->proto;
			$host = $proto . $host;
			unset($params['+']);
		}
		$caL = explode("/", $action);
		$act = array_pop($caL);
		if (xorcapp::$inst->conf['general']['routingbug'] ?? null) {
			$cont = join("/", $caL);
		} else {
			if (isset($caL[1])) {
				$prefix = $caL[0];
				$cont = $caL[1];
			} else {
				$prefix = null;
				$cont = $caL[0];
			}
		}
		#      log_error("URL FOR:");
		#      log_error($cont);
		#      log_error($act);

		//   if($action[0]=="/"){
		//       $url=$action;
		//   }else{
		foreach ($this->map as $p => $a) {
			#    	       log_error("#### MAP ENTRY $p - $a");
			$a = str_replace(array('$controller', '$action'), array($cont, $act), $a ?: "");

			# $a=preg_replace("!(\w+)=\\1!", "$1", $a);
			#    			log_error("VGL {$a} vs. $action");
			if ($a == $action) {
				$url = str_replace(
					array('$controller', '$action'),
					array($cont, $act),
					$p
				);
				break;
			}
		}
		//    }
		if (!$url) {
			log_error("+++ FAILED url for: $action");
			log_error($params);
			$url = "(URL KONNTE NICHT AUFGELOEST WERDEN)";
		} else {
			$http = array();
			foreach ($params as $k => $v) {
				$url2 = str_replace('$' . $k, $v, $url);
				if ($url2 == $url) $http[] = urlencode($k) . "=" . urlencode($v);
				else $url = $url2;
			}
			if ($http) $http = "?" . join("&amp;", $http) . $raute;
			else $http = $raute;
		}



		// falls noch $vars uebrig sind loeschen wir die einfach raus, 
		//    in der hoffnung, dass default werte definiert wurden
		//    diesen test sparen wir uns :)
		$url = preg_replace('!(/\$.*)!', "", $url);
		if ($url == "/") $url = "";
		$url = $host . XorcApp::$inst->env->actionbase . "/$url" . $http;

		#		log_error("#### RESULT URL: $url");

		return $url;
	}

	function def_route() {
		// var_dump ($this->map);
		// return split("/", $this->map["/"]);
		return $this->map["/"];
	}
}
