<?php

function create_crudcontroller($table, $ti, $template, $vars){
	$files=array();
	$info=$ti[$table];
	$tname=$table;
	
	$def=collect_objectform($table, $ti);
	
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
	
	return $files;
}

function create_crudviews($table, $ti, $template, $vars){
   $files=array();
	$info=$ti[$table];
	$tname=$table;
	
	$views=array("_form", "edit", "create", "index");
	
	$def=collect_objectform($table, $ti);
	$tab=$vars['colnames'][$tname];
	$idname=$vars['idnames'][$tname];
	foreach($views as $v){
	   if($v{0}=="_"){
	      $view=substr($v, 1);
	      $part="_";
	   }else{
	      $view=$v;
	      $part="";
	   }
	   
		$name=$part.$template."_".$view.".html";
		$tpl=resolve_template($name, array(
			"form-elements"=>$def,
			"columns"=>$tab,
			"model-id"=>$idname,
			"model-name"=>name_for('model', $tname),
			"model-var"=>name_for('modelvar', $tname)));
		$tplfile=$part.name_for('viewfileprefix', $tname).$view.".html";
		$files[$tplfile]=$tpl;
   }

	return $files;
}

function collect_objectform($table, $ti){
	$form=array();
	$info=$ti[$table];
	
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
			$type=sprintf('$f->text_field("%s")', $colname);
		}elseif(preg_match("/char|clob|text/i", $c->type)){
			if($c->max_length==-1){
				if(preg_match("/clob|text/i", $c->type)){
					$type=sprintf('$f->text_area("%s", array("size"=>"%s"))', 
					   $colname, "72x2");
				}else{
					$type=sprintf('$f->text_field("%s")', $colname);
				}
			}elseif($c->max_length<=256){
				$type=sprintf('$f->text_field("%s")', $colname);
			}elseif($c->max_length<=512){
				$type=sprintf('$f->text_area("%s", array("size"=>"%s"))', $colname, "72x2");
			}else{
			   $type=sprintf('$f->text_area("%s", array("size"=>"%s"))', $colname, "72x6");
			}
		}elseif(preg_match("/time/i", $c->type)){
			$type=sprintf('$f->text_field("%s", array("size"=>"%s"))', $colname, 19);
		}elseif(preg_match("/date/i", $c->type)){
			$type=sprintf('$f->text_field("%s", array("size"=>"%s"))', $colname, 10);
		}else{
			$type=sprintf('$f->text_field("%s")', $colname);
		}
		$def[]=array("name"=>$colname, "definition"=>$type,
		         "human" => name_for("human", $colname));
	}
	
	return $def;
}

function create_ar_models($table, $ti, $template){
#	foreach($ti as $tname=>$info){
   $tname=$table;
   $info=$ti[$table];
		$tpl=resolve_template($template, array(
		   'model-name'=> name_for('model', $tname),
		   'table-name'=> $tname
		   ));
		$files[name_for('modelfile', $tname)]=$tpl;
#	}
	return $files;
}
?>