<?php
require_once(__DIR__ . "/formtag_helper.php");

function url($to = "", $parms = array()) {
   //	print ("URL:$to"); print_r(XorcApp::$inst);
   if (!XorcApp::$inst->ctrl) return XorcApp::$inst->router->url_for($to, $parms);
   return XorcApp::$inst->ctrl->url($to, $parms);
}

function url_for($to = "", $parms = array()) {
   return XorcApp::$inst->ctrl->url($to, $parms);
}

// unencoded htmlentities, hmm alles falsch
function uurl($to = "", $parms = array()) {
   $u = url($to, $parms);
   return str_replace('&amp;', '&', $u);
}

function selfurl() {
   return XorcApp::$inst->location;
}

function image($url, $parms = array()) {
   //    $url=url(XorcApp::$inst->ctrl->base."/$url", $parms);
   $url = XorcApp::$inst->ctrl->base . "/$url";
   $opts = array_merge(array("src" => $url, "border" => 0), $parms);
   return sprintf('<img %s />', opts_to_html($opts));
}

function image_path($url, $opts = null) {
   //    $url=url(XorcApp::$inst->ctrl->base."/$url", $parms);
   if (!$opt) $opts = array();
   if ($opts == "+") $opts = array("+" => "");
   if (isset($opts['+'])) {
      $ph = proto_host($opts['+']);
   } else {
      $ph = "";
   }
   log_error("#### BASE  " . XorcApp::$inst->ctrl->base);
   $url = $ph . XorcApp::$inst->ctrl->base . "/$url";
   return $url;
}

function proto_host($ph = "") {
   $proto = $host = "";
   if (preg_match("!^(https?://)(.*?)$!", $ph, $phmat)) {
      $proto = $phmat[1];
      $host = $phmat[2];
   } else {
      $host = $ph;
   }
   if (!$host) $host = XorcApp::$inst->env->server;
   if (!$proto) $proto = XorcApp::$inst->env->proto;
   $host = $proto . $host;
   return $host;
}

function kill_chache($anticache = null) {
   if ($anticache === null) {
      if ((@$_ENV["XORC_ENV"] && $_ENV["XORC_ENV"] == "development") || (@$_SERVER["XORC_ENV"] && $_SERVER["XORC_ENV"] == "development")) {
         #if(($_ENV["XORC_ENV"] && $_ENV["XORC_ENV"]=="production") || ($_SERVER["XORC_ENV"] && $_SERVER["XORC_ENV"]=="production")){
         # $nocache="";
         $nocache = "?" . time();
      } else {
         # $nocache="?".time();
         $nocache = "";
      }
   } else {
      if ($anticache) {
         // versionsstrings
         if (is_string($anticache)) {
            $nocache = "?$anticache";
         } else {
            $nocache = "?" . time();
         }
      } else $nocache = "";
   }
   return $nocache;
}

function js_tag($url, $anticache = null) {
   $nocache = kill_chache($anticache);
   $url = XorcApp::$inst->ctrl->base . "/$url" . $nocache;
   return sprintf('<script type="text/javascript" src="%s"></script>' . "\n", $url);
}

function css_tag($url, $anticache = null) {
   $nocache = kill_chache($anticache);
   $url = XorcApp::$inst->ctrl->base . "/$url" . $nocache;
   return sprintf('<link rel="stylesheet" href="%s" type="text/css" />' . "\n", $url);
}

function themed_js_tag($url, $anticache = null) {
   $nocache = kill_chache($anticache);
   $th = XorcApp::$inst->ctrl->theme();
   $url = XorcApp::$inst->ctrl->base . "/themes/$th/$url" . $nocache;
   return sprintf('<script type="text/javascript" src="%s"></script>' . "\n", $url);
}

function themed_css_tag($url, $anticache = null) {
   $nocache = kill_chache($anticache);
   $th = XorcApp::$inst->ctrl->theme();
   $url = XorcApp::$inst->ctrl->base . "/themes/$th/$url" . $nocache;
   return sprintf('<link rel="stylesheet" href="%s" type="text/css" />' . "\n", $url);
}

function themed_asset($url = null) {
   $th = XorcApp::$inst->ctrl->theme();
   $url = XorcApp::$inst->ctrl->base . ($th ? "/themes/$th/$url" : "/$url");    //ae 100106 (so muss nicht jedes standard-asset in alle themes kopiert werden)
   //$url=XorcApp::$inst->ctrl->base."/themes/$th/$url";  							//original
   return $url;
}

function is_ajax() {
   return Xorcapp::$inst->req->ajax ? true : false;
}
function render_part($tpl, $parms = array()) {
   return XorcApp::$inst->view->render_part($tpl, $parms);
}

function render_classes($cf) {
   XorcApp::$inst->view = new $cf;
}

function button_to($to, $text = "", $parms = array(), $htmlparms = array()) {
   if (!$text) $text = $to;
   if (is_null($parms)) $parms = array();

   $method = $htmlparms['method'] ?? "";
   if (!$method) $method = "post";
   //   if($htmlparms['confirm']) $onsubmit="onsubmit=\"return(confirm('{$htmlparms['confirm']}'))\"";
   if ($htmlparms['confirm'] ?? "") {
      $onsubmit = 'onsubmit="' . htmlspecialchars("return(confirm('{$htmlparms['confirm']}'))") . '"';
   } elseif ($htmlparms['jq_confirm'] ?? "") {
      $onsubmit = 'data-confirm="' . htmlspecialchars($htmlparms['jq_confirm']) . '"';
   } else {
      $onsubmit = "";
   }

   $btn_class = $htmlparms['class'];
   if (!$btn_class) $btn_class = "btn";
   $btn_title = $htmlparms['title'] ?? "";

   $type = isset($htmlparms['type']) ? $htmlparms['type'] : 'input';
   if ($type == 'button') {
      $button = sprintf(
         '<button type="submit" class="%s" value="%s">%s</button>',
         $btn_class,
         htmlspecialchars($text),
         $btn_title
      );
   } else {
      $button = sprintf(
         '<input type="submit" class="%s" value="%s" title="%s">',
         $btn_class,
         htmlspecialchars((string)$text),
         htmlspecialchars((string)$btn_title)
      );
   }


   $html = sprintf(
      '<form method="%s" action="%s" %s class="button-to">%s',
      $method,
      XorcApp::$inst->ctrl->url($to),
      $onsubmit,
      $button
   );
   if (!is_array($parms) && $parms) {
      $parms = array("id" => $parms);
   }
   foreach ($parms as $p => $val) {
      $html .= hidden_field_tag($p, $val);
   }
   return $html . "</form>";
}

# $this->r['back']||$this->r['back_x']||$this->r['back_y']
function button_pressed($btn) {
   $r = XorcApp::$inst->ctrl->r;
   return (isset($r[$btn]) || isset($r["{$btn}_x"]) || isset($r["{$btn}_y"])) ? true : false;
}

function link_to($to, $text = "", $parms = array(), $options = array()) {
   $htmloptions = "";
   foreach ($options as $k => $v) {
      $htmloptions .= " $k=\"" . htmlspecialchars($v) . "\"";
   }
   if (!$text) $text = $to;
   if (is_null($parms)) $parms = array();
   return sprintf(
      '<a href="%s"%s>%s</a>',
      XorcApp::$inst->ctrl->url($to, $parms),
      $htmloptions,
      $text
   );
}


function flash($msg = "") {
   return XorcApp::$inst->flash($msg);
}

function flash_var($key, $val = null) {
   #print_r(XorcApp::$inst);
   return XorcApp::$inst->flash_var($key, $val);
}

function subaction($cont, $act) {
   return XorcApp::$inst->subaction($cont, $act);
}

function slot($name) {
   return XorcApp::$inst->outv[$name];
}

function error_404($msg = "") {
   header("HTTP/1.0 404 Not Found");
   XorcApp::$inst->render("/_error_404.html");
   XorcApp::$inst->terminate(false);
}

function log_error($msg, $file = null) {
   XorcApp::$inst->log($msg, $file);
}

function log_db_error($msg, $newline = "\n") {
   $msg = trim(strip_tags($msg));
   $msg = html_entity_decode($msg);
   $msg = "\033[1;34m" . $msg . "\033[0m";
   XorcApp::$inst->log($msg);
}

function add_route($r) {
   XorcApp::$inst->router->add_route($r);
}

function count_variant($total, $zero, $one, $many) {
   if (!$total) return sprintf($zero, $total);
   if ($total > 1) return sprintf($many, $total);
   return sprintf($one, $total);
}

function urlify($name, $allow = "") {
   /* 
		funktioniert nur mir mb_ funktionen und UTF-8 Zeichen
	*/
   if (function_exists("mb_strtolower")) {
      $name = mb_strtolower($name, "UTF-8");
      $tr = array("ä" => "ae", "ö" => "oe", "ü" => "ue", "ß" => "ss", " " => "-");
      $from = array_keys($tr);
      $to = array_values($tr);
      $name = str_replace($from, $to, $name);

      # simplify other languages funny charakters
      $name = htmlentities($name, ENT_COMPAT, "UTF-8");
      $name = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|ring|slash);/', '$1', $name);
      $name = html_entity_decode($name, ENT_COMPAT, "UTF-8");
   }
   $name = preg_replace("/[^-_a-z0-9$allow]/", "", $name);
   $name = preg_replace("/-+/", "-", $name);
   return $name;
}
function truncate(
   $string,
   $length = 80,
   $etc = '...',
   $break_words = false,
   $middle = false
) {
   mb_internal_encoding("UTF-8");
   $string = strip_tags($string);
   if ($length == 0)
      return '';

   if (mb_strlen($string) > $length) {
      $length -= mb_strlen($etc);
      if (!$break_words && !$middle) {
         $string = preg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $length + 1));
      }
      if (!$middle) {
         return mb_substr($string, 0, $length) . $etc;
      } else {
         return mb_substr($string, 0, $length / 2) . $etc . mb_substr($string, -$length / 2);
      }
   } else {
      return $string;
   }
}

function camelcase_to_underscore($name) {
   return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
}
function underscore_to_camelcase($name, $ucfirst = false) {
   if ($ucfirst) $name = ucfirst($name);

   // 5.3: function($m){return strtoupper($m[1]);}
   $f = function ($m) {
      return strtoupper($m[1]);
   };
   return preg_replace_callback('/_([a-z])/', $f, $name);
}

function remove_bom($str) {
   if (substr($str, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
      $str = substr($str, 3);
   }
   return $str;
}

function xorc_ini($key, $set = null) {
   list($grp, $gkey) = explode(".", $key, 2) + [1 => null];
   if (!is_null($set)) {
      if (!$gkey && is_array($set)) {
         Xorcapp::$inst->conf[$grp] = $set;
      } elseif ($grp && $gkey) {
         Xorcapp::$inst->conf[$grp][$gkey] = $set;
      }
   }
   if (!$gkey) {
      $ret = Xorcapp::$inst->conf[$grp];
      if (!$ret) $ret = array();
      return $ret;
   } else {
      if (!isset(Xorcapp::$inst->conf[$grp][$gkey])) {
         return null;
      } else {
         return Xorcapp::$inst->conf[$grp][$gkey];
      }
   }
}

function client_is_ie() {
   return (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false));
}

###
##
# compat old prototype stuff

if (!defined("XORC_JS_JQUERY")) {
   include_once(__DIR__ . "/xorc_helper_prototype.php");
} else {
   include_once(__DIR__ . "/xorc_helper_jquery.php");
}

###
## compat diverse
###

if (!function_exists('sys_get_temp_dir')) {
   function sys_get_temp_dir() {
      if (!empty($_ENV['TMP'])) {
         return realpath($_ENV['TMP']);
      }
      if (!empty($_ENV['TMPDIR'])) {
         return realpath($_ENV['TMPDIR']);
      }
      if (!empty($_ENV['TEMP'])) {
         return realpath($_ENV['TEMP']);
      }
      $tempfile = tempnam(uniqid(rand(), TRUE), '');
      if (file_exists($tempfile)) {
         unlink($tempfile);
         return realpath(dirname($tempfile));
      }
   }
}

function jTraceEx($e, $seen = null) {
   $starter = $seen ? 'Caused by: ' : '';
   $result = array();
   if (!$seen) $seen = array();
   $trace  = $e->getTrace();
   $prev   = $e->getPrevious();
   $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
   $file = $e->getFile();
   $line = $e->getLine();
   while (true) {
      $current = "$file:$line";
      if (is_array($seen) && in_array($current, $seen)) {
         $result[] = sprintf(' ... %d more', count($trace) + 1);
         break;
      }
      $result[] = sprintf(
         ' at %s%s%s(%s%s%s)',
         count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
         count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
         count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
         $line === null ? $file : basename($file),
         $line === null ? '' : ':',
         $line === null ? '' : $line
      );
      if (is_array($seen))
         $seen[] = "$file:$line";
      if (!count($trace))
         break;
      $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
      $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
      array_shift($trace);
   }
   $result = join("\n", $result);
   if ($prev)
      $result  .= "\n" . jTraceEx($prev, $seen);

   return $result;
}
