<?php

$mypath = dirname(dirname(realpath(__FILE__))); 

ini_set("include_path", ".".PATH_SEPARATOR.$mypath."/lib".
   PATH_SEPARATOR.$mypath."/lib/pear".
   PATH_SEPARATOR.ini_get("include_path"));

include("xorc/xorcapp.class.php");
include("<app-name>/<app-name>.class.php");

$conf=$_SERVER["<app-name-uc>_CONF"];
if(!$conf){
   if($_SERVER["XORC_ENV"]=="development"){
      $conf="{$mypath}/conf/<app-name>_dev.ini";
   }elseif($_SERVER["XORC_ENV"]=="local" || $_SERVER['HTTP_HOST'] == 'localhost'){
      $conf="{$mypath}/conf/<app-name>_local.ini";
   }else{
      $conf="{$mypath}/conf/<app-name>_prod.ini";
   }
}
$_SERVER["<app-name-uc>_CONF"]=$conf;



XorcApp::run("<app-name>");


?>
