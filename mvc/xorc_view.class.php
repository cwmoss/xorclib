<?php
class Xorc_View {
	public $auto = array("top", "bottom");

	public array $render_log = [];
	public array $blocks = [];
	public array $open_blocks = [];

	public $basedir;

	public function __construct($basedir = null) {
		if (!$basedir) $basedir = xorcapp::$inst->base;
	}

	function render($view = "", $params = []) {
		$view = strtolower((string) $view);
		// XorcApp::$inst->log("[VIEW] $view");
		if (!$view) $view = strtolower(XorcApp::$inst->ctrl_name . "_" . XorcApp::$inst->act);
		elseif ($view[0] == "/") $view = substr($view, 1);
		else $view = strtolower(XorcApp::$inst->ctrl_name . "_" . $view);
		if (preg_match("!\.(\w+)$!", $view, $m)) {
			if ($m[1] == 'xml') {
				XorcApp::$inst->nopage = true;
				header("Content-Type: text/xml; charset=UTF-8");
			} elseif ($m[1] == 'js') {
				XorcApp::$inst->log("JS VIEW");
				XorcApp::$inst->nopage = true;
				#            header("Content-Type: text/javascript");
			} elseif ($m[1] == 'pjs') {
				XorcApp::$inst->log("PJS VIEW");
				XorcApp::$inst->nopage = true;
				include_once("xorc/mvc/prototype_helper.class.php");

				XorcApp::$inst->ctrl->_page = new Prototype_helper;
			}
		} else {
			$view .= ".html";
		}
		$out = $this->_include($view, $params, 1);
		if (XorcApp::$inst->ctrl->_page ?? null) $out = XorcApp::$inst->ctrl->_page->return_string();

		XorcApp::$inst->log("[VIEW] {$view} with PARTIALS " . (join(", ", $this->render_log) ?: '-'));
		foreach (XorcApp::$inst->ctrl->_post as $p) {
			$out = $p($out);
		}
		return $out;
	}

	function render_part($view = "", $params = array()) {
		// XorcApp::$inst->log("RND PART $view");
		$inc = $this->find_partial($view);
		$this->render_log[] = $view . " => " . ($inc ? 'OK' : 'NOT FOUND');
		if (!$inc) return "NOT FOUND: $view";
		$out = $this->_include($inc, $params, 0);
		return $out;
	}

	function render_block(string $blockname, string $view, array $params = []) {
		$this->blocks[$blockname] = $this->render_part($view, $params);
	}

	function start_block(string $blockname) {
		$this->open_blocks[] = $blockname;
		ob_start();
	}

	function end_block() {
		$content = ob_get_clean();
		$name = array_pop($this->open_blocks);
		$this->blocks[$name] = $content;
	}

	function get_block(string $name): string {
		return trim($this->blocks[$name] ?? "");
	}

	function find_partial($view) {
		$file = basename($view);
		# evtl. enthält der view ein unterverzeichnis, 
		#     dann muss der dateiname neu gebaut werden
		#     in diesem fall geschieht die adressierung
		#     *immmer* relativ zum view/[theme] verzeichnis
		#     controller paths werden dann *nicht* mehr 
		#     berücksichtigt
		$direct = ($file != $view);
		if ($direct) {
			$viewfile = dirname($view) . "/_" . $file . ".html";
			// log_error("--- file != view");
		} else {
			$viewfile = "_" . $file . ".html";
		}

		# controllerpath (enthält "/" zb. "admin/")
		$path = XorcApp::$inst->ctrl->_path;
		# theme
		if (XorcApp::$inst->ctrl)
			$theme = XorcApp::$inst->ctrl->theme();
		$found = false;

		if ($theme) {
			$base = $this->basedir . "/themes/$theme";

			if ($direct) {
				$check = "$base/view/$viewfile";
			} else {
				$check = "$base/view/$path/$viewfile";
			}

			if (file_exists($check)) {
				$found = $check;
			} elseif (!$direct && $path) {
				# ohne pfad ein verzeichnis nach oben testen
				$check = "$base/view/$viewfile";
				if (file_exists($check)) {
					$found = $check;
				}
			}
		}

		if (!$found) {
			$base = $this->basedir;
			if ($direct) {
				$check = "$base/view/$viewfile";
			} else {
				$check = "$base/view/$path/$viewfile";
			}

			if (file_exists($check)) {
				$found = $check;
			} elseif (!$direct && $path) {
				# ohne pfad ein verzeichnis nach oben testen
				$check = "$base/view/$viewfile";
				if (file_exists($check)) {
					$found = $check;
				}
			}
		}

		return $found;
	}

	function render_page() {
		#	   log_error("BASE:".XorcApp::$inst->base);
		$c = XorcApp::$inst->ctrl;
		$c->content = &XorcApp::$inst->out;
		$charset = isset(XorcApp::$inst->conf['general']['charset']) ? XorcApp::$inst->conf['general']['charset'] : "";
		if (!$charset) $charset = "UTF-8";
		header("Content-type: text/html; charset=$charset");
		// print "RENDER PAGE "; print_r($c);
		$layout = $c->layout();
		if ($layout) $layout = "_$layout";
		$path = $c->layout_path();
		$base = $this->basedir;
		$theme = $c->theme();
		if ($theme) $base .= "/themes/$theme/view";
		else $base .= "/view";
		if ($path) {
			$path = $base . "/" . $path;
		} else {
			$path = $base;
		}

		log_error("[VIEW] LAYOUT/THEME $layout/$theme");

		$buffer = array();
		foreach ($this->auto as $auto) {
			if ($c->auto($auto) && file_exists($path . "/_layout{$layout}.$auto.html")) {
				#	   log_error("AUTOLAYOUT: $auto >> ".$path."/_layout{$layout}.$auto.html");
				$buffer[$auto] = $this->_include($path . "/_layout{$layout}.$auto.html");
				#		log_error("RESULT:");
				#		log_error($buffer[$auto]);
			}
		}

		$c->layout = $buffer;

		if ($c->auto('page') && file_exists($path . "/_layout{$layout}.page.html")) {
			#  log_error($c->layout);
			return $this->_include($path . "/_layout{$layout}.page.html");
		} else {
			return ($c->layout['top'] ?? '') . $c->content . ($c->layout['bottom'] ?? '');
		}
	}

	function _include($file, $_original_params = array(), $rel = 0) {
		if (XorcApp::$inst->ctrl)
			$theme = XorcApp::$inst->ctrl->theme();
		if ($theme && ($rel || $file[0] != "/")) {
			$file0 = $this->basedir . "/themes/$theme/view/$file";
			// log_error("theme view $file0 ?");
			if (file_exists($file0)) {
				// log_error("OK.");
				$file = $file0;
			} else {
				// log_error("FAILED.");
				$theme = null;
			}
		}
		if (!$theme) {
			if ($rel || $file[0] != "/") $file = $this->basedir . "/view/$file";
		}
		// log_error("view $file");
		if (!file_exists($file)) {
			log_error("!!! missing VIEW $file");
			return "";
		}
		foreach (XorcApp::$inst->ctrl as $key => $val) {
			if (!isset($$key)) $$key = $val;
		}
		foreach ($_original_params as $key => $val) {
			$$key = $val;
		}
		// log_error("view_include: $file");
		ob_start();
		include($file);
		$out = ob_get_clean();
		//		print $out;
		// log_error("OK");
		return $out;
	}
}
