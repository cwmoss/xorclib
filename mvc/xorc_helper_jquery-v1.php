<?php



function link_to_remote($to, $text="", $parms=array(), $remoteopts=array()){
	if(!$text) $text=$to;
//	$default=array("asynchronous"=>true, "evalScripts"=>true);
   $default=array();
	$remoteopts=array_merge($default, $remoteopts);
	
	$json = new Services_JSON();
	$url=XorcApp::$inst->ctrl->url($to, $parms);

	return sprintf('<a href="%s" data=\'%s\' class="remote">%s</a>',
		$url, $json->encode($remoteopts), $text);
}


function js_options($opts=array()){
   $json = new Services_JSON();
   return $json->encode($opts);
   
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
   
   $json = new Services_JSON();
   return $json->encode($opts);
   
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
   $json = new Services_JSON();
   return $json->encode($opts);
   
   //$js=preg_replace("/\r\n|\n|\r/", "\\n", $js);
   //$js=preg_replace("/([\"'])/", '\\\$1', $js);
    $js=str_replace(array("\r\n", "\n", "\r"), "\\n", addslashes($js));
    return $js;
}

?>