<?php

function link_to_remote($to, $text="", $parms=array(), $remoteopts=array(), $htmlopts=array()){
	if(!$text) $text=$to;
//	$default=array("asynchronous"=>true, "evalScripts"=>true);
   $default=array();
	$remoteopts=array_merge($default, $remoteopts);
	$url=XorcApp::$inst->ctrl->url($to, $parms);
	$js=remote_function($url, $remoteopts);
	return sprintf('<a href="%s" onclick="%s;return false;">%s</a>',
		$url, $js, $text);
}


function js_options($opts=array()){
    $o=array();
    foreach($opts as $k=>$v){
       if($v===false) $v='false';
       elseif($v===true) $v='true';
#       elseif(!is_numeric($v)) $v = "'$v'";
       $o[]="$k:$v";
    }
    return '{'.join(", ", $o).'}';
}

function js_options_with_strings($opts=array()){
    $o=array();
    foreach($opts as $k=>$v){
       if($v===false) $v='false';
       elseif($v===true) $v='true';
       elseif(!is_numeric($v)) $v = "'$v'";
       $o[]="$k:$v";
    }
    return '{'.join(", ", $o).'}';
}

// Escape carrier returns and single and double quotes for JavaScript segments.
function escape_js($js=""){
   //$js=preg_replace("/\r\n|\n|\r/", "\\n", $js);
   //$js=preg_replace("/([\"'])/", '\\\$1', $js);
    $js=str_replace(array("\r\n", "\n", "\r"), "\\n", addslashes($js));
    return $js;
}

      
// 'onclick'=>"new Ajax.Updater('cedit', url('edit', $liste["id"]', {onComplete: hide_seek()})")

include_once("prototype_helper.php");
?>