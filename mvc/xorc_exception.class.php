<?php

class XorcRuntimeException extends Exception {

   public $opts = [];
   public $trace;

   static $defaults = array(
      "header" => "404",
      /*
         kein spezielles exception layout verwenden
         alternative:
            layout 0 // abschalten
            layout false // abschalten
            layout "error" // eignes error-layout verwenden
            
      */
      "layout" => "",
      "view" => "/errors/default.html"
   );

   function __construct($msg, $opts = array(), $code = 0) {
      log_error("xorcruntime exception");
      parent::__construct($msg, $code);
      $this->opts = $opts;
   }

   function options() {
      return array_merge(
         XorcRuntimeException::$defaults,
         $this->opts
      );
   }

   function set_message($m) {
      $this->message = $m;
   }
}

class XorcControllerNeedsAuthException extends XorcRuntimeException {

   public $cont;
   public $act;

   function __construct($msg, $code = 0, $cont = "", $act = "") {
      parent::__construct($msg, [], $code);
      $this->opts = ['header' => 403];
      log_error("needsauth exception");
      $this->cont = $cont;
      $this->act = $act;
   }
}

class XorcControllerForewardException extends XorcRuntimeException {
}

function xorc_exception_handler($e) {
   #   ob_end_flush(); flush();print $e->getMessage();
   if (!$e instanceof XorcRuntimeException) {
      $opts = XorcRuntimeException::$defaults;
   } else {
      #      $m="YES!";
      $opts = $e->options();
   }
   $class = get_class($e);
   $pclass = get_parent_class($e);
   $m = $e->getMessage();
   $app = Xorcapp::$inst;
   $trace = "";
   $fm = sprintf(
      "%s:\n   %s line: %s code: %s\n   via %s%s\n",
      $m,
      $e->getFile(),
      $e->getLine(),
      $e->getCode(),
      $class,
      $pclass ? ', ' . $pclass : ''
   );
   if ((defined('XORC_SAPI') &&  XORC_SAPI == "cli") || PHP_SAPI == 'cli') {
      print "\nERROR: " . $fm . "\n";
      //debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $e->getTraceAsString();
   } else {
      //ob_start();
      //$full = $e->getPrevious();
      // if (!$full) $full = $e;
      //rint jTraceEx($e);
      //var_dump($full->getTraceAsString());
      //var_dump(debug_backtrace());
      //debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      //debug_print_backtrace();
      //var_dump($e->context);
      //print $m."\n";
      //$trace=ob_get_clean();

      //$trace .= $fm . $e->getTraceAsString();
      $trace .= $fm . jTraceEx($e);

      if (isset($opts['use_content_type']) && $opts['use_content_type']) {
         $ct = $opts['content_type'];
         if (!$ct) {
            $ct = "error/html";
         }
         header("Content-Type: $ct");
      } else {
         header("HTTP/1.0 " . $opts['header']);
      }

      #log_error("EXCEPTION**** ctrl: ".@get_class($app->ctrl));
      #log_error($trace);

      print($trace);

      if (!$app->ctrl) $app->ctrl = new Xorc_Controller;
      if ($opts['vars']) {
         foreach ($opts['vars'] as $k => $v) {
            $app->ctrl->$k = $v;
         }
      }
      if ($opts['layout'] === false || $opts['layout'] === 0) {
         $app->ctrl->auto_off();
      } elseif ($opts['layout']) {
         $app->ctrl->layout($opts['layout']);
      }
      if (isset($opts['theme'])) $app->ctrl->theme($opts['theme']);
      if ($opts['view'] && $app->view) $app->out .= $app->view->render(
         $opts['view'],
         array("message" => $m, 'fullmessage' => $fm, 'trace' => $trace)
      );
      else $app->out .= $m;
      #print $app->out;
      #print_r($app->ctrl);
      if ($app->view) $app->render_page();
      else print $app->out;
   }
}

class XorcRuntimeErrorException extends ErrorException {
   public $context = array();

   public function __construct($errorString, $code, $severity, $filename, $linenumber, $context) {
      parent::__construct($errorString, $code, $severity, $filename, $linenumber);
      $this->context = $context;
   }

   #	public function getRecessTrace() {
   #		return $this->getTrace();
   #	}
}

function xorc_error_handler($errorNumber, $errorString, $errorFile, $errorLine, $errorContext) {
   if (ini_get('error_reporting') == 0) return true;
   #	throw new XorcRuntimeErrorException($errorString, 0, $errorNumber, $errorFile, $errorLine, $errorContext);
   print "FEHLER $errorString\n";
   #	ob_end_flush(); flush();
   return false;
}


#set_error_handler('xorc_error_handler');
set_exception_handler('xorc_exception_handler');
