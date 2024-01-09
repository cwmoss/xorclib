<?php

function name_for($what, $tname){
	static $naming;
	if(!$naming) $naming = new Naming;
	
	$sing=$naming->singular($tname);
	$using=ucfirst($sing);
	switch($what){
		case "model": return $using; 
		case "controller": return $using."_C";
		case "modelvar": return $sing;
		case "inifile": return $sing.".ini";
		case "controllerfile": return $sing."_c.class.php";
		case "modelfile": return $sing.".class.php";
		case "viewfileprefix": return $sing."_";
		case "confuserfile": return $sing.".php";
		case "human": return human_colname($tname);
	}
	
}

function human_colname($col){
   $col=preg_replace("/_id$/", "", $col);
   $col=str_replace("_", " ", $col);
   $col=ucfirst($col);
   return $col;
}

function db_string_to_dsn($str){
    $el=explode(":", $str);
    return sprintf("%s://%s:%s@%s/%s", $el[0], $el[2], $el[3], $el[1]?$el[1]:"localhost", $el[4]);
}

function create_controllers($ti, $template, $vars){
	foreach($ti as $tname=>$info){
		$def=array_merge($vars['form'][$tname]['fields'], $vars['form'][$tname]['keys']);
		$tab=$vars['colnames'][$tname];
		$idname=$vars['idnames'][$tname];
		$tpl=resolve_template($template, array(
			'form-elements'=>$def,
			'columns'=>$tab,
			'controller-name'=>name_for('controller', $tname),
			"model-id"=>$idname,
			'model-name'=>name_for('model', $tname),
			'model-var'=>name_for('modelvar', $tname),
			));
			
		$files[name_for('controllerfile', $tname)]=$tpl;
	}
	return $files;
}

function create_views($ti, $template, $vars){
	$views=array("index", "edit");
	
	foreach($ti as $tname=>$info){
		$def=array_merge($vars['form'][$tname]['fields'], $vars['form'][$tname]['keys']);
		$tab=$vars['colnames'][$tname];
		$idname=$vars['idnames'][$tname];
		foreach($views as $v){
			$name=$template."_".$v.".html";
			$tpl=resolve_template($name, array(
				"form-elements"=>$def,
				"columns"=>$tab,
				"model-id"=>$idname,
				"model-name"=>name_for('model', $tname),
				"model-var"=>name_for('modelvar', $tname)));
			$files[name_for('viewfileprefix', $tname).$v.".html"]=$tpl;
		}
	}
	return $files;
}

function create_confusers($ti, $template, $vars){
	foreach($ti as $tname=>$info){
		$def=array_merge($vars['form'][$tname]['fields'], $vars['form'][$tname]['keys']);
		$tab=$vars['colnames'][$tname];	
		$idname=$vars['idnames'][$tname];
		$tpl=resolve_template($template, array(
			'form-elements'=>$def,
			'columns'=>$tab,
			'controller-name'=>name_for('controller', $tname),
			"model-id"=>$idname,
			'model-name'=>name_for('model', $tname),
			'model-var'=>name_for('modelvar', $tname),
			));
			
		$files[name_for('confuserfile', $tname)]=$tpl;
	}
	return $files;
}

function create_routes($ti, $template){
	$tabs=array_keys($ti);

	return array("routes.txt" => resolve_template($template, 
		array('first-controller'=>name_for('modelvar', $tabs[0]))
		));
}

function get_includepath($path){
	$ip=ini_get("include_path");
#	$ip=get_cfg_var("include_path");
	$path=array('.', $path);
	$pL=explode(PATH_SEPARATOR, $ip);
	
	if($pL[0]=='.') array_shift($pL);
	
	$path=join(PATH_SEPARATOR, array_merge($path, $pL));
	return $path;
}

function create_htaccess($opts, $template){
	return array(".htaccess" => resolve_template($template,
		$opts
		));
}

function create_app_ini($opts, $template){
   $cont=resolve_template($template, $opts);
	return array(
	   $opts['app-name'].'_dist.ini' => $cont,
	   $opts['app-name'].'_dev.ini' => $cont,
	   $opts['app-name'].'_local.ini' => $cont
	   );
}

function create_app_main($opts, $template){
	return array("index.php" => resolve_template($template, $opts));
}

function create_app_fusermain($opts, $template){
	return array("prepend.php" => resolve_template($template, $opts));
}

function create_app_class($ti, $template, $appname){
	$inc=array();
	if($ti) foreach($ti as $tname=>$info){
		$inc[]=array("appname"=>$appname, 
			"modelfile"=>name_for("modelfile", $tname));
	}
	
	return array($appname.".class.php" => resolve_template($template,
		array(
			'includes'=>$inc,
			'app-classname'=>ucfirst($appname)
			)
		));
}
function create_app_bin($ti, $template, $appname){
	$inc=array();
	if($ti) foreach($ti as $tname=>$info){
		$inc[]=array("appname"=>$appname, 
			"modelfile"=>name_for("modelfile", $tname));
	}
	
	return array($appname => resolve_template($template,
		array(
			'includes'=>$inc,
			'appname'=>$appname,
			'envname'=>strtoupper($appname),
			'year'=>date("Y")
			)
		));
}


function create_layout($ti, $templates, $opts){
	$navigation=array();
	if($ti) foreach($ti as $tname=>$info){
		$navigation[]=array('url'=> name_for("modelvar", $tname),
			'description'=>$tname);
	}
	
	$opts['navigation']=$navigation;
	foreach($templates as $name){
		$files[$name]=resolve_template($name, $opts);
	}
	return $files;
}

function copy_public_files($src, $dest, $descr){
	$files=array(
		"css/default.css", "css/xorc.css", "css/jqmodal.css",
		"js/jquery-1.3.2.min.js", "js/jqModal.js", "js/xorc.js",
		"gfx/shade.png"
		);
	foreach($files as $f){
		file_copy_dialog($src."/$f", $dest."/$f", $descr);
	}
}

function write_file($src, $dest, $data, $descr=null){
	$cont=resolve_template($src, $data);
	create_directory(dirname($dest));
	if(!$descr) $descr = basename($dest);
	file_write_dialog($dest, $descr, $cont);
}

function write_files($files, $dir, $descr){
	create_directory($dir);
	
#	print_r($files); return;
	foreach($files as $f=>$cont){
#		print "INHALT:".$cont;
		$f_name = "$dir/$f";
		// evtl. sind unterverzeichnisse gefragt
		create_directory(dirname($f_name));
		file_write_dialog($f_name, $descr." ".basename($f), $cont);
	}
}

function create_directory($dir){
	if(!is_dir($dir)){
		print("creating directory $dir ..\n");
		`mkdir -p $dir`;
	}
}

function template($name){
	global $templatepath;
	$path=$templatepath;
	if(!$path) $path=XORC_LIB_PATH."/bin/templates";
	$name=$path."/$name";
	if(file_exists($name) && is_file($name)){
		return join("", file($name));
	}else{
		print "#### missing template: $name\n";
		return false;
	}	
}

function resolve_template($name, $vars){
	$tpl=template($name);
	if($tpl===false) return $tpl;
	if(preg_match_all("!<loop\s+([-_\w]+)>\n?(.*?)</loop>!is", $tpl, $mat, PREG_SET_ORDER)){
		foreach($mat as $k=>$v){
			if(isset($vars[$v[1]])&&is_array($vars[$v[1]])){
				$repl="";
				foreach($vars[$v[1]] as $vals){
					$block=$v[2];
					foreach($vals as $key=>$value){
						$block=str_replace("<$key>", $value, $block);
					}
					$repl.=$block;
				}
				$tpl=preg_replace("!<loop\s+{$v[1]}>.*?</loop>!is", $repl, $tpl, 1);
			}else{
				if(!isset($vars[$v[1]])){
					print "!! WARN: tpl>loop {$v[1]} is not set\n";
				}else{
					print "!! WARN: tpl>loop {$v[1]} is not an array\n";
				}
			}
		}
	}
	foreach($vars as $key=>$value){
		$tpl=str_replace("<$key>", $value, $tpl);
	}
	return $tpl;
}

function collect_table_info($dbcon, $prefix="", $tables=""){

   	define('ADODB_ASSOC_CASE', 0);
   	define('XORC_DB_ADODB_VERSION', 'adodb5');
   	
	include_once(XORC_LIB_PATH."/db/xorcstore_connector.class.php");
	new XorcStore_Connector("_db", array('dsn'=>$dbcon, 'debug'=>false, 'prefix'=>$prefix));


	$ti=array();	// tableinfo
	print "connecting to DB ($dbcon) prefix: $prefix\n";
	if($tables){
	   print "selecting only these tables: ".join(",", $tables)."\n";
	}

	$_db=XorcStore_Connector::get();
	$tab=$_db->MetaTables('TABLES');
	print "collecting table info:\n";
	foreach($tab as $t){
		print "\t$t";
		$tname=strtolower($t);
		if($prefix){
			if(preg_match("/^{$prefix}_(.*)$/", $tname, $mat)){
				$tname=$mat[1];
			}else{
				print " .. skipped\n";
				continue;
			}
		}
		// exclude fancy oracle tables like bin$gxujy0t0bjvgqkjacgfreg==$0
		if(preg_match('/\$/', $tname)){
			print " .. skipped\n";
			continue;
		}
		// exclude info_schema tables like encore_schema_information
		if(preg_match('/schema_information$/', $tname)){
			print " .. skipped\n";
			continue;
		}
		if($tables && !in_array($tname, $tables)){
		   print " $tname not in selection skipped\n";
		   continue;
		}
		print " .. OK\n";
		$ti[$tname]=array('keys'=>$_db->MetaPrimaryKeys($t),
			'cols'=>$_db->MetaColumns($t)
			);
	}
	return $ti;
}

function collect_colnames($ti){
	$colnames=array();
	foreach($ti as $tname=>$info){
		$modelvar=name_for('modelvar', $tname);

		$keys=$info['keys'];
		if($keys) foreach($keys as $k){
			$is_key[strtolower($k)]=1;
		}
	
		$tab=array();
		$cols=$info['cols'];		
		foreach($cols as $c){
			$colname=strtolower($c->name);
			if(!$is_key[$colname]){
				$tab[]=array("key"=>$colname, 
					"value"=>"\${$modelvar}->prop['{$colname}']",
					"value-ar"=>"\${$modelvar}->{$colname}",
					"human" => name_for("human", $colname)
					);
			}else{
				$idname=$colname;
			}
		}
		$colnames[$tname]=$tab;
	}
	return $colnames;
}

function collect_idnames($ti){
	$idnames=array();
	foreach($ti as $tname=>$info){
		$keys=$info['keys'];
		if($keys) foreach($keys as $k){
			$idnames[$tname]=$k;
			break;
		}
	}
	return $idnames;	
}

function collect_forms($ti){
	$form=array();
	foreach($ti as $tname=>$info){

		$keys=$info['keys'];
		$key_cols=array();
		if($keys) foreach($keys as $k){
			$key_cols[]=array("name"=>strtolower($k),
				"definition"=>"'type'=>'hidden'");
			$is_key[strtolower($k)]=1;
		}
	
		$cols=$info['cols'];
		$def=array();
		foreach($cols as $c){
			$colname=strtolower($c->name);
			if($is_key[$colname]) continue;
			# autodates
			if(preg_match("/^(created|modified)(_(at|on))?$/", $colname)) continue;
			# foreign keys
			if(preg_match("/_id$/", $colname)) continue;
			
			if(preg_match("/int|number/i", $c->type)){
				$type="'type'=>'text', 'display'=>\"$colname\"";
			}elseif(preg_match("/char|clob|text/i", $c->type)){
				if($c->max_length==-1){
					if(preg_match("/clob|text/i", $c->type)){
						$type="'type'=>'textarea', 'display'=>\"$colname\"";
					}else{
						$type="'type'=>'text', 'display'=>\"$colname\"";
					}
				}elseif($c->max_length<=256){
					$type="'type'=>'text', 'display'=>\"$colname\", 'valid_max'=>{$c->max_length}, 'valid_max_e'=>\"Max. length is {$c->max_length} characters.\"";
				}elseif($c->max_length<=512){
					$type="'type'=>'textarea', 'display'=>\"$colname\", 'valid_max'=>{$c->max_length}, 'valid_max_e'=>\"TMax. length is {$c->max_length} characters.\",\n\t\t'extra'=>'rows=2'";
				}else{
					$type="'type'=>'textarea', 'display'=>\"$colname\", 'valid_max'=>{$c->max_length}, 'valid_max_e'=>\"Max. length is {$c->max_length} characters.\"";
				}
			}elseif(preg_match("/time/i", $c->type)){
				$type="'type'=>'date', 'display'=>\"$colname\", 'format'=>'d-m-Y H:i', 'yearrange'=>'".date("Y")."-'";
			}elseif(preg_match("/date/i", $c->type)){
				$type="'type'=>'date', 'display'=>\"$colname\", 'format'=>'d-m-Y', 'yearrange'=>'".date("Y")."-'";
			}else{
				$type="'type'=>'text', 'display'=>\"$colname\"";
			}
			$def[]=array("name"=>$colname, "definition"=>$type, 
			   "human" => name_for("human", $colname));
		}
		$form[$tname]=array('keys'=>$key_cols, 'fields'=>$def);
	}
	return $form;
}

function create_ini_files($ti){
	$files=array();
	foreach($ti as $tname=>$info){
		$def="; autogenerated file - generator: generator.php\n[table]\nname=$tname\n\n[keys]\n";

		if($info['keys']) foreach($info['keys'] as $k){
			$def.=strtolower($k)."=1\n";
		}

		$def.="\n[fields]\n; 1-number, 2-string, 3-date, 4-datetime, 5-blob\n";
		$autod=""; $autoi="";

		foreach($info['cols'] as $c){
			$colname=strtolower($c->name);
			if(preg_match("/int|number|deci/i", $c->type)){
				$type=1;
				if($colname=='version') $autoi.="version=1\n";
			}elseif(preg_match("/char|text/i", $c->type)){
				$type=2;
			}elseif(preg_match("/time/i", $c->type)){
				$type=4;
				if(preg_match("/^created(_(at|on))?$/", $colname, $md)) $autod.="created={$md[0]}\n";
				if(preg_match("/^modified(_(at|on))?$/", $colname, $md)) $autod.="modified={$md[0]}\n";
			}elseif(preg_match("/date/i", $c->type)){
				$type=4;	//oracledate == datetime
			}elseif(preg_match("/lob/i", $c->type)){
				$type=5;
			}else{
				$type="???unknown type???";
				print "\n*** WARNING: unknown type {$c->type} in table $tname, column {$colname} ***\n".
						"*** please refine the inifile by yourself ***\n\n";
			}
			$def.="$colname=$type\n";
		}

		if($autod) $def.="\n[autodate]\n".$autod;
		if($autoi) $def.="\n[autoinc]\n".$autoi;
		
		$files[name_for('inifile', $tname)]=$def;
	}
	return $files;
}

function create_models($ti, $template){
	foreach($ti as $tname=>$info){
		$tpl=resolve_template($template, array('model-name'=> name_for('model', $tname)));
		$files[name_for('modelfile', $tname)]=$tpl;
	}
	return $files;
}
?>