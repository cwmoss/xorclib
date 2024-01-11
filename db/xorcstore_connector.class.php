<?php

// wraps adodb connector object
class driver {

   public $ignore_sequences;
   public $use_sequences;
   public $prefix;

   public function __construct(public $adodb) {
   }

   public function __get($prop) {
      return $this->adodb->$prop;
   }

   public function __call($name, $arguments) {
      return $this->adodb->$name(...$arguments);
   }
}

class XorcStore_Connector {

   public $con;

   function __construct($name = null, $details = null) {
      $this->add_connection($name, $details);
   }

   static function get($name = null) {

      $xsc = new XorcStore_Connector;

      return $xsc->get_connection($name);
   }

   function tell($name) {
      $xsc = new XorcStore_Connector;
      # print_r($xsc);
      return $xsc->con[$name][0]['dsn'];
   }

   function set($name, $details) {
      new XorcStore_Connector($name, $details);
   }

   function add_connection($name = null, $details = null) {
      static $con;
      if (!is_array($con)) {
         $con = array();
      }
      $this->con = &$con;
      if ($name && $details && !isset($this->con[$name])) {
         //         print "adding connection details for: $name.";
         $this->con[$name] = array(0 => $details);
      }
   }

   function get_connection($name = null) {
      if (!$name) $name = "_db"; // default name
      //      print_r($this);
      if (!isset($this->con[$name])) {
         // try the very first defined connection
         $cons = array_keys($this->con);
         if (!isset($cons[0])) {
            die("db connection '$name' is not defined PLUS there's no connection at all!\n");
         } else {
            $name = $cons[0];
         }
      }

      if (!isset($this->con[$name][1])) {
         $this->connect($name);
      }
      return $this->con[$name][1];
   }

   function get_connection_details($name = null) {
      if (!$name) $name = "_db"; // default name
      //      print_r($this);
      if (!isset($this->con[$name])) {
         // try the very first defined connection
         $cons = array_keys($this->con);
         if (!isset($cons[0])) {
            die("db connection '$name' is not defined PLUS there's no connection at all!\n");
         } else {
            $name = $cons[0];
         }
      }
      return $this->con[$name][0];
   }

   function connect($name = '_db') {
      $def = array('debug' => false, 'prefix' => "", 'persistent' => false);
      $def = array_merge($def, $this->con[$name][0]);
      if ($def['debug']) $def['debug'] = -1;
      global $ADODB_FETCH_MODE;
      $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

      # if (!defined('ADODB_ERROR_HANDLER_TYPE')) define('ADODB_ERROR_HANDLER_TYPE', E_USER_ERROR);
      if (!defined('ADODB_ERROR_HANDLER')) define('ADODB_ERROR_HANDLER', [$this, 'adodb_error_handler']);

      #		global $ADODB_QUOTE_FIELDNAMES; $ADODB_QUOTE_FIELDNAMES = true;

      if ($def['dsn'][0] == "!") {
         #print $def['dsn'];
         $dsn = str_replace("!", "", $def['dsn']);
         #  print "CON $dsn -".$def['dsn']."\n";
         $this->con[$name][1] = NEWADOConnection($dsn);
      } else {
         if (strpos($def['dsn'], '@') === false) {
            // alte dsn schreibweise: driver:host:user:pass:dbname
            list($driver, $host, $user, $pass, $db) = explode(":", $def['dsn']);
            $this->con[$name][1] = new driver(NewADOConnection($driver)); // NewADOConnection($driver);
            if ($def['persistent'])
               $this->con[$name][1]->PConnect($host, $user, $pass, $db);
            else
               $this->con[$name][1]->Connect($host, $user, $pass, $db);
            $this->con[$name][1]->debug = $def['debug'];
         } else {
            // neuer dsn driver://user:pass@host

            $opts = [];
            if ($def['persistent']) $opts["persist"] = "true";
            if ($def['debug']) {
               $opts["debug"] = $def['debug'];
               if (!defined("ADODB_OUTP")) define("ADODB_OUTP", "log_db_error");
            }
            $dsn = $def['dsn'];
            if ($opts) {
               $dsn .= "?";
               foreach ($opts as $k => $v) {
                  $dsn .= "{$k}={$v}";
               }
            }
            # if(defined(RW)) 
            # print "using: $dsn";

            $this->con[$name][1] = new driver(NEWADOConnection($dsn));
            // var_dump($this->con[$name][1]);
            //die();
            // $this->con[$name][1]->debug = $def['debug'];
         }
      }
      #print_r($this->con[$name]);
      if ($def['charset']) {
         #	      print "CHARSET: $name : {$def['charset']}\n";
         $this->con[$name][1]->SetCharSet($def['charset']);
      }
      $this->con[$name][1]->ignore_sequences = trim(@$def['ignore_sequences'] ?: "");
      $this->con[$name][1]->use_sequences = trim(@$def['use_sequences'] ?: "");
      $this->con[$name][1]->prefix = trim(@$def['prefix'] ?: "");

      if ($def['after_connect']) {
         $meth = $def['after_connect'];
         $meth($this->con[$name][1]);
      }
   }

   function adodb_error_handler($dbms, $fn, $errno, $errmsg, $p1, $p2, &$thisConnection) {

      $msg = match ($fn) {
         'EXECUTE' => "$dbms error: [$errno: $errmsg] in $fn(\"$p1\")",
         'PCONNECT', 'CONNECT' => "$dbms error: [$errno: $errmsg] in $fn($p1, '****', '****', $p2)",
         default => "$dbms error: [$errno: $errmsg] in $fn($p1, $p2)"
      };
      throw new ErrorException($msg);
   }
}
