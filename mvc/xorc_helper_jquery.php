<?php

include("pquery_0/pquery/classes/JavaScript.php");
include("pquery_0/pquery/classes/JQuery.php");

function link_to_remote($to, $text="", $parms=array(), $remoteopts=array(), $htmlopts=array()){
   $j=new JQuery;
	if(!$text) $text=$to;
//	$default=array("asynchronous"=>true, "evalScripts"=>true);
   $default=array();
	$remoteopts=array_merge($default, $remoteopts);
	
#	$json = new Services_JSON();
	$url=XorcApp::$inst->ctrl->url($to, $parms);
   $htmlopts['href']=$url;
   $remoteopts['url']=$url;
   return $j->link_to_remote($text, $remoteopts, $htmlopts);
}

function form_remote_tag($name, $url, $opts=array(), $htmlopts=array()){
   $htmlopts=array_merge(array("method"=>"post"), $htmlopts);
   $action=($htmlopts['action'])?$htmlopts['action']:$url;

   $options=array_merge($opts, $htmlopts);
   $options['type']=$options['method'];
   $options['url']=$action;
#   log_error($options);
   if($opts['multipart']){
      $addopts=array();
      foreach($options as $k=>$v){
         if($k=='class' || $k=="id") $addopts[$k]=$v;
      }
      $addopts=opts_to_html($addopts);   
      return '<form enctype="multipart/form-data" action="'.$options['url']."?xorciframe=1".
         '" onsubmit="callToServer();this.target=\'RSIFrame\'" method="post" '.$addopts.'  >';
   }else{
      $j=new JQuery;
      return $j->form_remote_tag($options);
   }
}

function remote_function($url, $opts=array()){
   
   $j=new JQuery;
   $options=$opts;
   $options['url']=$url;
   return $j->remote_function($options);
}

?>