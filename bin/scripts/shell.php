<?php
# error_reporting(E_ALL ^ E_NOTICE);
# error_reporting(E_ERROR | E_PARSE);

function __shell_error_handler($errno, $errstr, $errfile, $errline, $errctx){
    ## ... what is this errno again ?
    
    /*
    1	 E_ERROR
    2	E_WARNING
    4	E_PARSE
    8	E_NOTICE
    16	E_CORE_ERROR
    32	E_CORE_WARNING
    64	E_COMPILE_ERROR
    128	E_COMPILE_WARNING
    256	E_USER_ERROR
    512	E_USER_WARNING
    1024	E_USER_NOTICE
    2047	E_ALL
    2048	E_STRICT
    
    */
    
    $ignore=array("8", "1024". "2047", "2048");
    if(in_array($errno, $ignore)) return;
    #if ($errno > 4) return;
  
    throw new Exception(sprintf("%s:%d\r\n%s", $errfile, $errline, $errstr));
}

function __shell_print_var($var, $verbose=true){
   $v=var_export($var, true);
   $v=preg_replace("/\s/", "", $v);
   print "$v\n";
}

define('SHELL', 1); 
require 'php-shell-cmd.php';
#set_error_handler("__shell_error_handler");

#require 'PHP/Shell.php';

/** help!
the famous php shell brought to you by jan kneschke http://jan.kneschke.de/projects/php-shell/PHP_Shell-0.2.0/docs/html/index.html

*/
?>