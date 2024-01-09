<?php

Xorc::dbconnect($opts["db"], "_db");

$path=$opts['t'];

$tab=$_db->MetaTables('TABLES');
$naming=new Naming;

foreach($tab as $t){
	
	$tname=strtolower($t);
	
	
	if($opts['prefix']){
		if(preg_match("/^{$opts['prefix']}_(.*)$/", $tname, $mat)){
			$fname=$mat[1];
			$fname=$naming->singular($fname);
		}else{
			continue;
		}
	}else{
		$fname=$naming->singular($tname);
	}
	
	print("[$tname]\n");
	
	$keys=$_db->MetaPrimaryKeys($t);
	$key_cols="";
	$key_cols_arr=array();
	$def="";
	$tabdef="";
	if($keys) foreach($keys as $k){
		$key_cols.="\t\t\"".strtolower($k)."\"=>array('type'=>'hidden'),\n";
		$key_cols_arr[strtolower($k)]=1;
	}
	
	$cols=$_db->MetaColumns($t);
	
	foreach($cols as $c){
		$colname=strtolower($c->name);
		if($key_cols_arr[$colname]) continue;
		if(preg_match("/int|number/i", $c->type)){
			$type="'type'=>'text', 'display'=>\"$colname\"";
		}elseif(preg_match("/char|clob|text/i", $c->type)){
			if($c->max_length==-1){
				if(preg_match("/clob|text/i", $c->type)){
					$type="'type'=>'textarea', 'display'=>\"$colname\"";
				}else{
					$type="'type'=>'text', 'display'=>\"$colname\"";
				}
			}elseif($c->max_length<=128){
				$type="'type'=>'text', 'display'=>\"$colname\", \n\t\t\t'valid_max'=>{$c->max_length}, 'valid_max_e'=>\"Max. length is {$c->max_length} characters.\"";
			}elseif($c->max_length<=512){
				$type="'type'=>'textarea', 'display'=>\"$colname\",\n\t\t\t'valid_max'=>{$c->max_length}, 'valid_max_e'=>\"TMax. length is {$c->max_length} characters.\",\n\t\t'extra'=>'rows=2'";
			}else{
				$type="'type'=>'textarea', 'display'=>\"$colname\",\n\t\t\t'valid_max'=>{$c->max_length}, 'valid_max_e'=>\"Max. length is {$c->max_length} characters.\"";
			}
		}elseif(preg_match("/time/i", $c->type)){
			$type="'type'=>'date', 'display'=>\"$colname\",\n\t\t\t'format'=>'d-m-Y H:i', 'yearrange'=>'".date("Y")."-'";
		}elseif(preg_match("/date/i", $c->type)){
			$type="'type'=>'date', 'display'=>\"$colname\",\n\t\t\t'format'=>'d-m-Y', 'yearrange'=>'".date("Y")."-'";
		}else{
			$type="'type'=>'text', 'display'=>\"$colname\"";
		}
		$def.="\t\t\"$colname\"=>array($type),\n";
		$tabdef.="'$colname'=>\"$colname\",";
	}

	$def.=$key_cols;
	
	$phpfile="$path/{$fname}.php";
	
	$tpl=join("", file("$mypath/templates/confuser.php"));

	$tpl=str_replace("%confuser-name%", $fname, $tpl);
	$tpl=str_replace("%confuser-name-upper%", ucfirst($fname), $tpl);
	$tpl=str_replace("%form-elements%", $def, $tpl);
	$tpl=str_replace("%table-cols%", $tabdef, $tpl);
	
	file_write_dialog($phpfile, " >phpfile ..", $tpl);
}


?>
