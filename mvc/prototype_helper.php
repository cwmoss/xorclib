<?php

function form_remote_tag($name, $url, $opts=[], $htmlopts=[]){
   $htmlopts=array_merge(["method"=>"post"], $htmlopts);
   $action=$htmlopts['action'] ?: $url;
   $opts['form']=true;
   $opts['method']="'".$htmlopts['method']."'";
   $onsubmit=remote_function(url_for($url), $opts);
   $htmlopts['onsubmit']=$onsubmit.";return false;";
   return form_tag($name, $action, $htmlopts);
}

function remote_function($url, $opts=[]){
    $opt = [];
    $update="";
    
    if($opts['update'] && is_array($opts['update'])){
        $u=[];
        if($opts['update']['success']) $u[]="success:'{$opts['update']['success']}'";
        if($opts['update']['failure']) $u[]="failure:'{$opts['update']['failure']}'";
        $update='{'.join(",", $u).'}';
        unset($opts['update']);
    }elseif($opts['update']){
        $update="'{$opts['update']}'";
        unset($opts['update']);
    }
    
    $jsopts=options_for_ajax($opts);
    $func=$update?"new Ajax.Updater({$update}, ":"new Ajax.Request(";
    $func.="'$url', $jsopts)";
    
    if($opts['before']) $func = "{$opts['before']}; {$func}";
    if($opts['after']) $func = "{$func}; {$opts['after']}";
    if($opts['condition']) $func = "if ({$opt['condition']}) { {$func}; }";
    if($opts['confirm']) $func = "if (confirm('".escape_js($opts['confirm'])."')) { {$func}; }";
    return $func;
}


function options_for_ajax($opts=[]){
   $js_options = build_callbacks($opts);
   $js_options['asynchronous'] = ($opts['type'] != 'synchronous');
   if($opts['method']) $js_options['method'] = $opts['method'];
   if($opts['position']){
      $js_options['insertion'] = "Insertion.{$opts['position']}";
   }
   $js_options['evalScripts']  = $opts['script']?true:false;
   if($opts['form']){
      $js_options['parameters'] = 'Form.serialize(this)';
   }elseif($opts['submit']){
      $js_options['parameters'] = "Form.serialize('{$opts['submit']}')";
   }elseif($opts['with']){
      $js_options['parameters'] = $opts['with'];
   }

   return js_options($js_options);
}

function build_callbacks($opts=[]){
    $callbacks=["uninitialized", "loading", "loaded", "interactive", "complete", "failure", "success"];
    $cb=[];    
    foreach($opts as $call=>$code){
        if(in_array($call, $callbacks)){
            $cb['on'.ucfirst((string) $call)]=$code;
        }else{
#              $cb[$call]=$code;
        }
    }
  //  if(!isset($cb['evalScripts'])) $cb['evalScripts']=true;
    return $cb;
}

?>
