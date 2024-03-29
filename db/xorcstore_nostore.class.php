<?php
require_once(__DIR__ . "/xorcstore_error.class.php");
require_once(__DIR__ . "/xorcstore_validation.class.php");

class Xorcstore_Nostore {

	public $validation = null;
	public $errors = null;
	public $klas;

	function __construct($parms = null) {
		$this->errors = new Xorcstore_Errors;
		$this->validation = new xorcstore_validation($this);

		if (!is_null($parms)) $this->set($parms);
	}

	function set($parms, $allow = null) {
		#   var_dump( $parms);
		if (!is_null($allow)) {
			$allow_check = true;
			if (is_string($allow)) {
				$allow = explode(' ', $allow);
			}
		} else {
			$allow_check = false;
		}
		if ($parms) foreach ($parms as $k => $v) {
			if ($k[0] == '_' || $k == 'errors' || $k == 'validation') continue;
			if ($allow_check && !in_array($k, $allow)) continue;
			$this->$k = $v;
		}
	}

	function set_only_predefined($parms, $allow = null) {
		if (!is_null($allow)) {
			$allow_check = true;
			if (is_string($allow)) {
				$allow = explode(' ', $allow);
			}
		} else {
			$allow_check = false;
		}
		if ($parms) foreach ($parms as $k => $v) {
			if ($k[0] == '_' || $k == 'errors' || $k == 'validation') continue;
			if ($allow_check && !in_array($k, $allow)) continue;
			if (property_exists($this, $k)) $this->$k = $v;
		}
	}

	function get() {
		$arr = array();
		foreach ($this as $k => $v) {
			$arr[$k] = $v;
		}
		return $arr;
	}
	//clear war = true, warum???
	function is_valid($ev = "save", $clear = true) {
		# custom validation event
		if (!is_null($ev)) return $this->validation->validate_event($ev, $this, $clear);

		# cre/up/save
		$ok1 = $this->validation->validate_event("save", $this, $clear);
		if ($this->is_new_record()) {
			$ok2 = $this->validation->validate_event("create", $this);
		} else {
			$ok2 = $this->validation->validate_event("update", $this);
		}
		return (!($ok1 === false || $ok2 === false));
	}

	function __wakeup() {
		$this->errors = new Xorcstore_Errors;
		$this->validation = new xorcstore_validation($this);
	}

	function __get($prop) {
		#   print "GET $prop";
		#   print xorcstore_reflection::column_exists($this, $prop);
		#   print_r(xorcstore_reflection::$r);
		#log_error("[AR] magick GET $prop");
		if (method_exists($this, "get_" . $prop)) {
			#	      print "CALLING GET "."get_".$prop;
			return call_user_func(array($this, "get_" . $prop)); # return call_user_method("get_".$prop, $this);

		} elseif (xorcstore_reflection::column_exists($this, $prop)) {
			return $this->prop[$prop];
		}
	}
}
