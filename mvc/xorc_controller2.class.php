<?php
/*

like xorc_controller, but better for DI
- no constructor
- no send_file
+ setup_xorc

*/
class xorc_controller2 {
    public $_layout = array("top" => 1, "bottom" => 1, "page" => 1);
    public $_layout_off = 0;
    public $_layoutname = "";
    public $_layoutpath = "";
    public $_theme;

    public $_path;
    public array $r = [];
    public $require_auth = false;
    public $user;

    public $_post = array(); // postfilter
    public $_post_render = "";
    public $base;
    /*
	   funktionen die mit _unterstrich beginnen können nicht als actions aufgerufen werden
	   welche zusätzlichen funktionen dürfen nicht als actions aufgerufen werden?
	   namen mit leerzeichen trennen
	*/
    public $_disabled_actions = null;
    public $_enabled_actions = null;

    public $content;

    public $title = "";
    public $navpoint = "";
    public $layout = null;
    public $before_filter = [];

    public function _setup_xorc() {
        $this->base = XorcApp::$inst->env->httpbase;
        if (!$this->title) $this->title = XorcApp::$inst->ctrl_name;
        if (!$this->navpoint) $this->navpoint = XorcApp::$inst->ctrl_name;
        if ($this->layout) $this->layout($this->layout);
    }

    function _init_before($action) {
        if ($this->before_filter) {
            foreach ($this->before_filter as $f) {
                $this->$f();
            }
        }
        return true;
    }

    function _init() {
        return;        // you could init some forms here
    }

    function link($to, $text, $parms = array()) {
        return sprintf(
            '<a href="%s">%s</a>',
            $this->url($to, $parms),
            $text
        );
    }

    function url($to = "", $parms = array()) {
        if (!$to) {
            $to = XorcApp::$inst->ctrl_name . "/" . XorcApp::$inst->act;
        } else {
            if (is_array($to)) {
                $parms = $to[1];
                $to = $to[0];
            }
            if (!preg_match("!/!", $to)) {
                $to = XorcApp::$inst->ctrl_name . "/$to";
            }
        }
        return XorcApp::$inst->router->url_for($to, $parms);
    }

    function redirect($to = "", $parms = array()) {
        #	return $to;
        if ($to && $to != "/" && $to[0] == "/") $url = $to;
        else $url = $this->url($to, $parms);
        XorcApp::$inst->resp->redirect($url);
    }

    function foreward($to) {
        if (!preg_match("!/!", $to)) {
            $c = XorcApp::$inst->ctrl_name;
            $a = $to;
        } else {
            list($c, $a) = explode("/", $to);
        }
        XorcApp::$inst->foreward($c, $a);
    }

    function render($cv) {
        XorcApp::$inst->render($cv);
    }

    function start_auth($prefs = array()) {
        return XorcApp::$inst->start_auth($prefs);
    }

    function redirect_referer() {
        $to = $_SERVER['HTTP_REFERER'];
        XorcApp::$inst->resp->redirect($to);
    }

    function layout($l = -1) {
        if ($l != -1) $this->_layoutname = $l;
        return $this->_layoutname;
    }

    function layout_path($path = -1) {
        if ($path != -1) {
            #	      log_error("### SETTING layoutpath {$this->_layoutpath} ==> $path ##");
            $this->_layoutpath = $path;
        }
        return $this->_layoutpath;
    }

    function layout_off() {
        $this->_layout_off = 1;
        XorcApp::$inst->nopage = true;
    }

    function auto($layout) {
        if ($this->_layout_off) return false;
        return $this->_layout[$layout];
    }

    function auto_off($layout_part = null) {
        if ($layout_part) $this->_layout[$layout_part] = null;
        else $this->_layout_off = 1;

        // page abschalten???? muss noch überprüft werden!
        XorcApp::$inst->nopage = true;
    }

    function theme($theme = -1) {
        if ($theme != -1) {
            $this->_theme = $theme;
            // log_error("#### THEME SET TO #$theme#");
        }
        return $this->_theme;
    }

    function _post_render(&$outp) {
        if ($this->_post_render) {
            $m = $this->_post_render;
            return $this->$m($outp);
        } else {
            return $outp;
        }
    }

    function require_auth($act) {
        return false;
    }

    function _check_if_action_is_allowed($action) {
        $action = strtolower($action);
        if (!is_null($this->_enabled_actions)) {
            return in_array($action, explode(' ', $this->_enabled_actions));
        } else {
            # return true;
            return !in_array($action, explode(
                ' ',
                'link url redirect foreward render start_auth send_file redirect_referer ' .
                    'layout layout_path layout_off auto auto_off theme require_auth'
            ));
        }
    }
}
