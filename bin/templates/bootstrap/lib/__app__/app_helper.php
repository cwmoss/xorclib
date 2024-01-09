<?php
require_once("xorc/div/tiny_template.php");

function initialize_app(){
#   $custdir = customer_directory();
	fields::$sourcefile=xorcapp::$inst->approot.'/conf/fields.txt'; 
#	hook::load($custdir."/hooks.php");
#	fields::init_custom_fields('person');
	tiny_template::add_snippets(file_get_contents(xorcapp::$inst->approot.'/src/view/__tiny.tpl.html'));
}

// xorc2 klassen zum mail verschicken
function mail_setup(){
	require_once('helper.php');
	require_once('view.php');
	require_once('mailer.php');

	# print_r(stream_get_transports());

	$conf = xorc_ini('mail');
	
	#$theme = $_SERVER['<app-name-uc>_THEME'];
	#if($theme){
	#	$conf['basepath'] = xorcapp::$inst->approot."/src/themes/{$theme}/mails";
	#}
	
	$host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
	
	#$t = the_contest()->email_transport;
	if($t) $conf['transport'] = $t;
	
	# $conf['from'] = sprintf('"%s" <no-reply@%s>', the_contest()->title, $host);
	# $conf['from'] = 'vivian-maier@camerawoman.de';
	
	#$from = the_contest()->email;
	#if(!$from) $from = 'vivian-maier@camerawoman.de';
	
	#$conf['from'] = sprintf('"%s" <%s>', the_contest()->title, $from);
	$conf['reply-to'] = $conf['from'];
	$conf['return-path'] = $conf['from'];
	xorc\mailer::conf($conf);
}


function txt($name){
	static $msg=null;
	if(is_null($msg)){
		#$file=the_contest()->message_file;
		if(!$file) $file = 'msg-sie.db';
		$file = Xorcapp::$inst->approot."/conf/".$file;
		$msg = unserialize(file_get_contents($file));
	}
	return $msg[$name];
}

function f($entity, $name=null){
   if(is_null($name)) return fields::get_field($entity);
   if(is_object($entity)) $entity = strtolower(get_class($entity));
   return fields::get_field($entity, $name);
}

function dat($dat){
   return date('d.m H:i', strtotime($dat));
}

function h($inp=""){
   return htmlspecialchars($inp, ENT_QUOTES);
}

function app_asset($name){
   return image_path('assets/'.$name);
}
function app_image($name){
   return image_path('images/'.$name);
}
function the_user(){
   static $u = null;
   if(!$u && is_object($_SESSION['_auth'])) $u=$_SESSION['_auth']->get_userobject();
   return $u;
}

function user_by_id($uid){
   $l = user::user_list();
   #print_r($l);
   return $l[$id];
}

function name_of($obj){
	if(is_object($obj)) return $obj->fullname;
	$n = domain::users($obj);
	if($n) return $n;
	return 'Unbekannt';
}

function json_encode_w_functions($data){
   $replace = array();
   
   array_walk_recursive($data, function(&$item, $key, &$replace){
      if(strpos($item, 'function(')===0){
         $key = md5($item);
         $replace['"'.$key.'"'] = $item;
         $item = $key;
      }
   }, $replace);

   $data = json_encode($data);
   $json = str_replace(array_keys($replace), $replace, $data);
   return $json;
}


function money($cent){
   return number_format(((float) $cent/100.0), 2, ',', '.');
}

function money_euro($cent){
   $e = ((float) $cent/100.0);
   $e = str_replace(",", ".", $e);
   return $e;
}

function definition_list($arr){
   $r="";
   foreach($arr as $k=>$v){
      $r.='<dt>'.h($k).'</dt><dd>'.h($v).'</dd>';
   }
   return $r;
}

function mail_to($str, $text=''){
	if(!$str) return $text;
	if(is_array($str)) $str=join('; ', $str);
	$strar = preg_split("/[\s,:;]+/", trim($str));
	foreach($strar as $email){
		$i++;
		$mail_text = $text?$text:$email;
		if($i>1 && $text) $mail_text = " ".$i;
		if(!$email)continue;
		$out[]="<a href=\"mailto:$email\">$mail_text</a>";
	}
	return join(' ',$out);
}

/**
 * @param $word in utf-8 encoding
 */
function utf82rtf($word){
	//does not work with multibyte
	$word = mb_convert_encoding($word,'ISO-8859-15','UTF-8');

	$out='';
	for($i=0;$i<strlen($word);$i++){
		$letter = $word{$i};
		$code=ord($letter);
		if($code>125)$out.="\\u$code\\'".dechex($code);//pre 125 = readable
		else $out.=$letter;
	}
	return $out;
}

/**
 * returns true if any of the given fields has content
 *
 * @param Array $list_of_fields 
 * @return Boolean
 * @author ae
 */
function has_any_content($list_of_fields){
   foreach($list_of_fields as $field){
      if( !empty($field) ){
         //es gibt auch noch isset($field) und is_null($field)
         //aber wir wollen nur wissen, ob die variable einen inhalt hat,
         //nicht, ob sie überhaupt existiert oder im zweifel vllt. NULL ist
         return true;
      }
   }
   return false;
}
function has_any_content_in($obj, $fields){
   foreach($fields as $field){
      $a = $obj->$field;
      if(!empty($a)) return true;
   }
   return false;
}
function allow_private($person){
   $user = the_user();
   return (!$person->private_is_secret || $user->can(User::SECRET));
}


function customer(){
   return xorc_ini('<app-name>.customer');
}
function customer_directory(){
   $custid = customer();
   return xorcapp::$inst->approot.'/custom/'.$custid;
}
function app_customize(){
   $custdir = customer_directory();
	fields::$sourcefile=$custdir.'/fields.txt'; 
	hook::load($custdir."/hooks.php");
	fields::init_custom_fields('person');
	tiny_template::add_snippets(file_get_contents($custdir.'/formular.tpl.html'));
}

function array_to_html($arr, $opts=array()){
	$o = array_merge(['outer'=>'ul', 'inner'=>'li', 'suppress_empty'=>1], $opts);
	if(!$arr && $o['suppress_empty']) return false;
	$arr = (array) $arr;
	$cls = (isset($o['class']))?" class=\"{$o['class']}\"":'';
	$html = sprintf('<%s%s>%s</%s>', $o['outer'],  $cls, join("\n", array_map(function($i) use($o){
		$cls = ($o['class_inner'])?" class=\"{$o['class_inner']}\"":'';
		return sprintf('<%s%s>%s</%s>', $o['inner'], $cls, $i, $o['inner']);
	}, $arr)), $o['outer']);
	return $html;
}

// fuer javascript
function array_to_keyvalue($arr){
   if(!$arr) return array();
   return array_map(function($k, $v){
      return array('k'=>$k, 'v'=>$v);
   }, array_keys($arr), $arr);
}

function flat_array_to_keyvalue($arr, $level=1){
   $flat = array();
   if(!$arr) return $flat;
   foreach($arr as $k=>$v){
      if(is_array($v)){
         $flat[] = array('k'=>$k, 'v'=>$k, 'l'=>$level, 'd'=>true);
         $flat = array_merge($flat, flat_array_to_keyvalue($v, $level+1));
      }else{
         $flat[] = array('k'=>$k, 'v'=>$v, 'l'=>$level);
      }
   }
   return $flat;
}


function has_row($f, $name){
   $entity = is_a($f->obj, 'person')?'person':'company';
   if(is_array($name)){
      if($name[0]) $name=$name[0];
      else $name=key($name);
   }
   $namef = $name;
   $namex = explode('.', $name);
   if($namex[1]){
      $entity = $namex[0];
      $namef = $namex[1];
   }
   $field = fields::get_field($entity, $namef);
   if($field){
      return true;
   }else{
      return false;
   }
}

/*
	# hauptobjekt setzen für zukünftige zugriffe
	row_dd((object) obj)
	
	#	$obj->$name UND field.title
	row_dd((string) name) 
	
	#	$value UND field.title
	#     ! name muss mit # beginnen, damit man es
	#     vom letzten fall unterscheiden kann
	row_dd((string) name, (string) value)
	
	#  value // titel OHNE feldbezug
	row_dd((string) $value, (string) $title)
	
	#	$obj->$name UND label
	row_dd((string) name, null, (string) label)
	
	# ad-hoc $obj->$name UND field.title OHNE hauptobjektüberschreibung
	row_dd((object) obj, (string) name)
	
*/

function row_dd($name, $value=null, $opts=null){
	static $o;
	if(is_object($name) && is_null($value)){
		$o=$name;
		return;
	}
	$tpl = 'display2';
	
	$data = [];
	if(is_object($name)){
		$obj = $name;
		$fname = $value;
	}else{
	   $obj = $o;
		$fname=$name;
		
		if(is_string($name) && is_null($value) && is_string($opts)){
		   $data['label']=$opts; 
		}elseif(is_string($name) && is_string($value)){
		   if($name[0]=='#'){
		      $fname=ltrim($name, '#');
		      $data['form']=$value;
		   }else{
		      $obj = null;
			   $data['form']=$name;
			   $data['label']=$label;
			}
		}
	}
	if($obj){
		$field = f(strtolower(get_class($obj)), $fname);
		if(!$data['form']){
		   if($field->reference){
            $val = domain::items($field->reference, $obj->$name);
         }else{
		      $val = $obj->$fname;
		   }
		   $data['form'] = $val;
		}
		if(!$data['label']) $data['label'] = $field->title;
	#	print_r($field);
	}
#	print $tpl;
#	print_r($data);
	return tiny_template::render_snippet($tpl, $data);
}

function if_row_d($f, $name, $type='text', $opts=array()){
   if(has_row($f, $name)){
      return row_d($f, $name, $type, $opts);
   }
   return "";
}

function if_row($f, $name, $type='text', $opts=array()){
   if(has_row($f, $name)){
      return row($f, $name, $type, $opts);
   }
   return "";
}

function row_d($f, $name, $type='text', $opts=array()){
   if(is_array($type)){
      $opts=$type;
      $type="";
   }
   $opts['display']=true;
   return row($f, $name, $type, $opts);
}

function row($f, $name, $type='text', $opts=array()){
   static $templates = null;
   if(!$templates){
      $templates = array_keys(tiny_template::$cache);
   }
   if(is_array($type)){
      $opts=$type;
      $type="";
   }
   
   #$entity = is_a($f->obj, 'user')?'user':'image';
   $entity = strtolower(get_class($f->obj));
   $errors=array();
   
   if(is_array($name)){
      $type = 'multi'.count($name);
      $data = ['label'=>$opts['label'], 'row_class'=>'form-group', 'form'=>''];
      $c=1;
      foreach($name as $k=>$html){
         if(is_numeric($k)){
            $fname = $html;
            if($opts['display']){
               $html=$f->obj->$fname.' ';
               $tpl = 'display';
            }else{
               $html=$f->text_field($fname, array('class'=>"form-control $fname"));
            }
         }else{
            $fname = $k;
         }
         if(!$data['label']){
            $data['label']=f($entity, $fname)->title;
         }
         if($f->error_on($fname)){
            $addclass="err";
            # $emsg=sprintf('<div class="el-err">%s</div>', $f->error_message_on($names[0]));
#            log_error("###E-MSG: $n ++ ".$f->error_message_on($n));
            $errors[]=$f->error_message_on($fname);
#            error_was_printed($n);
				$data['error'.$c]=$f->error_message_on($fname);
         }
         $data['name'.$c]=$f->fieldname($fname);
         $data['id'.$c]=$f->idname($fname);
			
         $data['form'.$c]=$html;
         $data['form'].=$html;
         $c++;
      }
   }else{
      if(!$type) $type='text';
      $namex = explode('.', $name);
      if($namex[1]){
         $entity = $namex[0];
         $name = $namex[1];
      }
      $field = fields::get_field($entity, $name);
      $label = $opts['label'];
      unset($opts['label']);
      
      if(!$label) $label = $field->title;
   
      $data = ['name'=>$f->fieldname($name), 'label'=>$label, 'row_class'=>'form-group', 'id'=>$f->idname($name), 'form'=>$form, 
         'append_field'=>$opts['append'],
         'append_html'=>$opts['append_html']
         ];
      unset($opts['append'], $opts['append_html']);
      
      $fopts = $opts; 
      if($opts['display']){
         $tpl='display';
         if($type!='phone') $type='display';
      }
      
      if($type=='text'){
         $fopts['class']='form-control ';
       #  $fopts['title']=$field->title;
         $data['form'] = $f->text_field($name, $fopts);
      }elseif($type=='password'){
         $fopts['class']='form-control ';
         $data['form'] = $f->password_field($name, $fopts);
      }elseif($type=='checkbox'){
         $data['form'] = $f->check_box($name, array('class'=>'')); #(sizeof($names)>1)?_($n):$label
         #$data['row_class'] = 'checkbox';
      }elseif($type=='phone'){
         #$data['id1'] = $name;
         if($opts['display']){
            $data['form'] = $f->obj->$name.' '.phone_actions($f, $name, $f->obj->$name, $opts['user'], $opts['cid']);
            $tpl = 'display';
         }else{
            $data = array_merge($data, phone_elements($f, $name, $f->obj->$name, $opts['user'], $opts['cid']));
         }
      }elseif($type=='radios'){
         $names_0 .= '_'.key($opts['items']);
         foreach($opts['items'] as $r_item_key=>$r_item){
            $tags[]=$f->radio_button($name, $r_item_key, array("label"=>$r_item, "class"=>"radio-inline"));
            if($opts['br']) $tags[]='<br/>';
         }
         $data['form']=join('', $tags);
      }elseif($type=='textarea'){
         $fopts['class'].=' form-control';
         $data['form'] = $f->text_area($name, $fopts);
         if($fopts['size']) list($w, $h) = explode('x', $fopts['size']);
         if($h && $h<=2) $tpl="text";
      }elseif($type=='selectbox'){
         $items=$fopts['items'];
         unset($fopts['items']);
         if(!$items && $field->reference) $items = domain::items($field->reference);
         $fopts['class'].=' form-control';
         $data['form'] = $f->select_box($name, $items, $fopts);
      }elseif($type=='file'){
         //$data['form']=$f->file_field($name, $fopts);
         $fopts['class']='form-control-upload input-sm';
         $data['form'] = file_field_tag($name, $fopts);
      }elseif($type=='display'){
         if($field->reference){
            $val = domain::items($field->reference,$f->obj->$name);
         }else{
            $val = $f->obj->$name;
         }
         $data['form']=$val;
      }
      
      if($f->error_on($name)){
         $errors[]=$f->error_message_on($name);
      }
   }
   if(!$tpl) $tpl=$type;

	$msg = join(" <br>\n", array_filter(array_unique($errors)));
   $data['error'] = $msg;
	// kompat. w. jquery validate
	// <label id="age-error" class="error" for="age">Es dürfen leider nur volljährige Personen mitmachen.</label>
	
   #print $tpl;
   #print_r($templates);
   if(!in_array($tpl, $templates)) $tpl='general';
   return tiny_template::render_snippet($tpl, $data);
}



function html_tag($name, $content, $opts=array()){
   if(!is_null($content)) $content .= "</$name>";
   return sprintf('<%s %s>%s', $name, opts_to_html($opts), $content);
}


function csrf_token(){
   return hidden_field_tag('_toktok_', $_SESSION['___x_auth']->_toktok);
}
function csrf_token_array(){
   return array('_toktok_' => $_SESSION['___x_auth']->_toktok_GET);
}
function csrf_token_array_POST(){
   return array('_toktok_' => $_SESSION['___x_auth']->_toktok);
}
function csrf_token_ok(){
   if($_SERVER['REQUEST_METHOD']!='POST') 
      return csrf_token_ok_GET();
  
   return ($_SESSION['___x_auth']->_toktok && $_POST['_toktok_']==$_SESSION['___x_auth']->_toktok);
}
function csrf_token_ok_GET(){  
   return ($_SESSION['___x_auth']->_toktok_GET && $_GET['_toktok_']==$_SESSION['___x_auth']->_toktok_GET);
}
function req_is_post(){
   return ($_SERVER['REQUEST_METHOD']=='POST');
}

function drupal_validate_utf8($text) {
  if (strlen($text) == 0) {
    return TRUE;
  }
  return (preg_match('/^./us', $text) == 1);
}
/*
   htmlspecialchars wird hier nicht verwendet, da es bereits
   bei den form_helpern ausgeführt wird.
   zu beachten ist die ausführung in non-form feldern, wie in der
   übersichtsseite. dafür gibts die h() funktion
*/
function do_clean_input(&$inp){
   $inp=drupal_validate_utf8($inp) ? strip_tags($inp) : '';
}

/*
  whitelist variante für admin interface
*/
function do_clean_input_wl(&$inp){
   $whitelist="<p><a><b><i><strong><em><br><h1><h2><h3><h4><h5><ul><li><img><div><span>";
   $inp=drupal_validate_utf8($inp) ? strip_tags_w_attributes($inp, $whitelist) : '';
}

// nicht für php7
function xxstrip_tags_w_attributes($sSource, $aAllowedTags = ""){
   $aDisabledAttributes = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 
      'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 
      'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 
      'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 
      'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 
      'ondataavaible', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 
      'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 
      'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterupdate', 
      'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 
      'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 
      'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup', 'onmousewheel', 
      'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 
      'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit', 'onrowsdelete', 
      'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 
      'onstop', 'onsubmit', 'onunload');
      
   if (empty($aDisabledAttributes)) return strip_tags($sSource, $aAllowedTags);

   return preg_replace('/\s(' . implode('|', $aDisabledAttributes) . ').*?([\s\>])/', '\\2', preg_replace('/<(.*?)>/ie', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . implode('|', $aDisabledAttributes) . ")[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", strip_tags($sSource, $aAllowedTags)) );
}

// https://github.com/cloakedcode/snippets/blob/master/strip_html.php
// --------------------------------------------------------------
// Strips out javascript attributes (e.g. onclick, onblur)
// --------------------------------------------------------------
function strip_tags_w_attributes($text, $wl="")
{
	$aDisabledAttributes = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavaible', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	return preg_replace('/<(.*?)>/ie', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . implode('|', $aDisabledAttributes) . ")[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", $text);
}

function meta_tags($meta=null, $value=null){
	static $tags=null;
	$defaults = array('title'=>xorcapp::$inst->ctrl->title, 'type'=>'article', 'image'=>false, 
		'url'=>$_SERVER["SCRIPT_URI"], 'description'=>'', 'locale'=>'de_DE', 'site_name'=>'');
		
	// beim ersten aufruf, defaults setzen
	if(!$tags) $tags = $defaults;
	
	// werte abfragen
	if($meta=='?') return $tags;
	
	// werte setzen
	if($meta && !is_array($meta)){
		$meta = array($meta => $value);
		$tags = array_merge($tags, $meta);
		return $tags;
	}

	// ausgabe
	$og=array();
	foreach($tags as $k=>$v){
		if($v===false) continue;
		if(!preg_match('/:/', $k)){
			$k='og:'.$k;
		}
		$og[]=sprintf('<meta property="%s" content="%s" />', h($k), h($v));
	}
	return join("\n", $og);
}

function banner($all){
	$sel = rand(0, count($all)-1);
	return sprintf('<a href="%s" target="_blank"><img src="%s" title="werbung"></a>', 
		$all[$sel][0], themed_asset('gfx/'.$all[$sel][1]));
}
?>