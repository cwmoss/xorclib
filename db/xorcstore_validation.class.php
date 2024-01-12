<?php
/*
   TODO: $obj->attribute_present vs. $obj->$col
   ??
   ?? validations auch auf attribute, die keine spalten sind?
   ??
*/

class Xorcstore_Validation {
   var $v = array("save" => array(), "create" => array(), "update" => array());

   var $per_obj = array("save" => array(), "create" => array(), "update" => array());
   var $lazy = array();

   var $low = array();

   static $types = array(
      "acceptance_of", "associated",
      "confirmation_of", "each", "exclusion_of", "format_of", "inclusion_of",
      "length_of", "numericality_of", "presence_of", "size_of", "uniqueness_of"
   );

   static $low_types = array(
      "save" => "validate", "create" => "validate_on_create",
      "update" => "validate_on_update"
   );
   static $msg = array(
      'inclusion' => "%s is not included in the list",
      'exclusion' => "%s is reserved",
      'invalid' => "%s is invalid",
      'confirmation' => "%s doesn't match confirmation",
      'accepted' => "%s must be accepted",
      'empty' => "%s can't be empty",
      'blank' => "%s can't be blank",
      'too_long' => "%s is too long (maximum is %d characters)",
      'too_short' => "%s is too short (minimum is %d characters)",
      'wrong_length' => "%s is the wrong length (should be %d characters)",
      'taken' => "%s has already been taken",
      'not_a_number' => "%s is not a number"
   );

   function __construct($ar) {
      $meth = get_class_methods($ar);
      $meth = array_flip($meth);
      foreach (self::$types as $t) {
         $m = "validates_" . $t;
         if (isset($meth[$m])) {
            $validations = $ar->$m();
            $this->add($m, $validations);
         }
      }
      /* 
         per instance validations
         will be loaded right before validation
      */
      foreach (self::$types as $t) {
         $m = "obj_validates_" . $t;
         if (isset($meth[$m])) $this->lazy[] = "validates_" . $t;
      }
      foreach (self::$low_types as $ev => $m) {
         if (isset($meth[$m])) $this->low[$ev] = 1;
      }
   }

   function add($m, $validations, $to = null) {
      if (is_null($to)) $to = &$this->v;
      else $to = &$this->per_obj;

      if (!is_array($validations)) $validations = array($validations);
      foreach ($validations as $k => $val) {
         $on = "save";
         if (is_numeric($k)) {
            array_push($to['save'], array($val, $m));
         } else {
            if (isset($val['on'])) $on = $val['on'];
            /*
               custom events?
            */
            if (!is_array($to[$on])) $to[$on] = array();
            array_push($to[$on], array($k, $m, $val));
         }
      }
   }

   function reload($ar) {
      $this->v = array("save" => array(), "create" => array(), "update" => array());
      $this->__construct($ar);
   }

   function validate_event($e, $obj, $clear = false) {
      #$ok=true;
      #print "VALIDATE EVENT $e ON OBJ";
      #var_dump($obj);
      if ($clear) $obj->errors->clear();
      if (isset($this->low[$e])) {
         $m = self::$low_types[$e];
         $obj->$m();
      }

      // 30.9.2015, berücksichtigung einer catch-all funktion
      //    das vereinfacht die verwendung eines
      //    eigenen validators
      if ($e != 'save' && method_exists($obj, 'validatable_validate')) {
         $obj->validatable_validate($e);
      }

      #		log_error("+++++++++||||||+++++ V-EV: $e");
      #		log_error($this->v);

      if ($this->v[$e]) foreach ($this->v[$e] as $vld) {
         $m = $vld[1];
         $opts = isset($vld[2]) ? $vld[2] : array();
         $err = $this->$m($e, $obj, $vld[0], $opts);
         #         log_error("validate $m");
         #         log_error($opts);
         if ($err !== true) {
            $obj->errors->add($err);
            #$ok=false;
         }
      }

      /*
         just loaded
      */
      $this->per_obj = array("save" => array(), "create" => array(), "update" => array());
      foreach ($this->lazy as $m) {
         $om = "obj_" . $m;
         $validations = $obj->$om();
         $this->add($m, $validations, 'per_obj');
      }

      if ($this->per_obj[$e] && is_array($this->per_obj[$e])) {
         foreach ($this->per_obj[$e] as $vld) {
            $m = $vld[1];
            $opts = isset($vld[2]) ? $vld[2] : array();
            $err = $this->$m($e, $obj, $vld[0], $opts);
            if ($err !== true) {
               $obj->errors->add($err);
            }
         }
      }
      //////  lazy end


      if (count($obj->errors)) return false;
      return true;
   }

   function test_if($e, $obj, $col, $opts) {
      $opts += ['if' => null, 'ifm' => null];
      if (!$opts['if'] && !$opts['ifm']) return true;
      if ($opts['if']) {
         $prop = $opts['if'];
         if ($obj->$prop) return true;
         else return false;
      }
      if ($opts['ifm']) {
         $meth = $opts['ifm'];
         $test = call_user_func_array(array($obj, $meth), array($e, $col));
         if ($test) return true;
         else return false;
      }
   }

   function validates_presence_of($e, $obj, $col, $opts = array()) {
      $opts = array_merge(array("msg" => self::$msg['empty'], "name" => ucfirst($col)), $opts);
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $v = $obj->$col;
      if (!trim($v) && trim($v) !== "0") {
         return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }

   function validates_inclusion_of($e, $obj, $col, $opts = array()) {
      # if(!$obj->attribute_present($col)) return true;
      $opts = array_merge(array("msg" => self::$msg['inclusion'], "name" => ucfirst($col)), $opts);
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      #log_error("one");
      $v = $obj->$col;
      if ($opts['allow_null'] && is_null($v)) return true;
      #log_error("two");
      if ($opts['in']) {
         if ($opts['ignore_case']) {
            $v = strtolower($v);
            $in = array_map('strtolower', $opts['in']);
         } else {
            $in = $opts['in'];
         }
         if (!in_array($v, $in)) return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }
      if (($opts['between'] && ($v < $opts['between'][0] || $v > $opts['between'][1]))) {
         # print_r($opts); print "--$v--";print_r($obj);
         return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }

   function validates_exclusion_of($e, $obj, $col, $opts = array()) {
      # if(!$obj->attribute_present($col)) return true;
      $opts = array_merge(array("msg" => self::$msg['exclusion'], "name" => ucfirst($col)), $opts);
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $v = $obj->$col;
      if ($opts['allow_null'] && is_null($v)) return true;
      if (($opts['in'] && in_array($v, $opts['in'])) ||
         ($opts['between'] && ($v >= $opts['between'][0] && $v <= $opts['between'][1]))
      ) {
         # print_r($opts); print "--$v--";print_r($obj);
         return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }

   function validates_length_of($e, $obj, $col, $opts = array()) {
      $opts = array_merge(array("name" => ucfirst($col)), $opts);
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $v = $obj->$col;
      if ($opts['allow_null'] && is_null($v)) return true;
      #      if(is_null($v)) return new Xorcstore_Error($col, sprintf(self::$msg['empty'], $col));

      if (function_exists('mb_strlen')) {
         log_error("MB,,,,strlen");
         $v = str_replace("\r\n", "\n", $v);
         $v = str_replace("\r", "\n", $v);
         $len = mb_strlen($v, "utf-8");
      } else {
         $len = strlen($v);
      }
      $min = $max = null;
      if ($opts['between']) list($min, $max) = $opts['between'];
      if (isset($opts['maximum'])) $max = $opts['maximum'];
      if (isset($opts['minimum'])) $min = $opts['minimum'];

      log_error("LENTEST LEN#MIN#MAX#IS+++$len#$min#$max#{$opts['is']}#!!!" . mb_strlen($v, "utf-8"));

      if ($opts['is'] && $len != $opts['is']) {
         $msg = $opts['msg_wrong_length'] ? $opts['msg_wrong_length'] : ($opts['msg'] ? $opts['msg'] : (self::$msg['wrong_length']));
         return new Xorcstore_Error($col, sprintf($msg, $opts['name'], $opts['is']));
      } elseif (!is_null($min) && ($len < $min || is_null($v))) {
         $msg = $opts['msg_too_short'] ? $opts['msg_too_short'] : ($opts['msg'] ? $opts['msg'] : (self::$msg['too_short']));
         return new Xorcstore_Error($col, sprintf($msg, $opts['name'], $min));
      } elseif (!is_null($max) && ($len > $max || is_null($v))) {
         log_error("IS NULL? ");
         log_error(is_null($v));
         $msg = $opts['msg_too_long'] ? $opts['msg_too_long'] : ($opts['msg'] ? $opts['msg'] : (self::$msg['too_long']));
         return new Xorcstore_Error($col, sprintf($msg, $opts['name'], $max));
      }
      return true;
   }

   function validates_confirmation_of($e, $obj, $col, $opts = array()) {
      # if(!$obj->attribute_present($col)) return true;
      if (!$obj->$col) return true;
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $confirm = $col . "_confirmation";
      if (is_null($obj->$confirm)) return true;
      $v = $obj->$col;
      if ($v != $obj->$confirm) {
         $opts = array_merge(array("msg" => self::$msg['confirmation'], "name" => ucfirst($col)), $opts);
         return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }


   function validates_acceptance_of($e, $obj, $col, $opts = array()) {
      $opts = array_merge(array(
         "msg" => self::$msg['accepted'],
         'accept' => 1, "name" => ucfirst($col)
      ), $opts);
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $v = $obj->$col;
      if (is_null($v)) {
         log_error("## val is null!!!");
         return true;
      }
      if ($v != $opts['accept']) {
         return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }

   function validates_uniqueness_of($e, $obj, $col, $opts = array()) {
      $opts = array_merge(array("msg" => self::$msg['taken'], "name" => ucfirst($col)), $opts);
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $v = $obj->$col;
      if (is_null($v)) return true;

      $scopeL = array($col => $v);
      if ($opts['scope']) {
         if (!is_array($opts['scope'])) $opts['scope'] = array($opts['scope']);
         foreach ($opts['scope'] as $scope) {
            $scopeL[$scope] = $obj->$scope;
         }
      }

      if (!$obj->is_new_record()) {
         $scopeL[] = "id != " . $obj->id_quoted();
      }

      $found = $obj->find_first(array("conditions" => $scopeL));
      if ($found) return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      return true;
   }


   function validates_format_of($e, $obj, $col, $opts = array()) {
      # if(!$obj->attribute_present($col)) return true;
      #		log_error("### VAL $col:{$obj->$col} ####################################");
      if (!$obj->$col && $obj->$col !== '0') return true;
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $opts = array_merge(array("msg" => self::$msg['invalid'], "name" => ucfirst($col)), $opts);
      $v = $obj->$col;
      $f = $opts['with'];
      if (!preg_match("/$f/", $v)) {
         return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }

   function validates_numericality_of($e, $obj, $col, $opts = array()) {
      $opts = array_merge(array("msg" => self::$msg['not_a_number'], "name" => ucfirst($col)), $opts);
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      $v = $obj->$col;

      #log_error($opts);

      if ($opts['allow_null'] && (is_null($v) || (is_string($v) && !trim($v)))) return true;

      if ($opts['modify_before_check'] ?? null) {
         $func = $opts['modify_before_check'];
         $v = $func($v);
      }
      if (!is_numeric($v) || ($opts['only_integer'] && (is_float($v) || !preg_match("/^[-+]?\d+$/", $v)))) {
         return new Xorcstore_Error($col, sprintf($opts['msg'], $opts['name']));
      }

      if (isset($opts['greater_than'])) {
         if ($v <= $opts['greater_than']) {
            $msg = $opts['msg_greater_than'];
            if (!$msg) $msg = $opts['msg'];
            return new Xorcstore_Error($col, sprintf($msg, $opts['name']));
         }
      }
      return true;
   }

   function validates_associated($e, $obj, $col, $opts = array()) {
      if (!$this->test_if($e, $obj, $col, $opts)) return true;
      if (xorcstore_reflection::assoc_many_exists($obj, $col)) {
         if ($obj->$col->is_empty()) return true;
         foreach ($obj->$col as $ass) {
            if (!$ass->is_valid()) return new Xorcstore_Error($col, $ass->errors->all_as_string());
         }
      } else {
         if (!$obj->$col) return true;
         if (!$obj->$col->is_valid()) return new Xorcstore_Error($col, $obj->$col->errors->all_as_string());
      }
      return true;
   }
}
