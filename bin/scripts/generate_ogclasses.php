<?php
//Xorc::dbconnect($opts["db"], "_db");

$path=$opts['t'];

$tmap=array(1=>"Fixnum", 2=>"String", 3=>"Time", 4=>"Time", 5=>"Blob");

$naming=new Naming;

$def="";
foreach(glob("$path/*.ini") as $filename){
	
	$kname=ucfirst(str_replace(".ini", "", basename($filename)));
	
	$conf=parse_ini_file($filename, true);
	
	$pfx=$opts['prefix']?$opts['prefix']."_":"";
	
	$def.="class $kname\n";	
	foreach($conf['fields'] as $k=>$v){
		$def.="\tproperty :$k, {$tmap[$v]}\n";
	}
	
	$def.="\tset_table :$pfx{$conf['table']['name']}\n";
	foreach($conf['keys'] as $k=>$v){
		$def.="\tset_primary_key :$k\n";
	}
	
	if($conf['relations']['has_many'])
	foreach(split(",", $conf['relations']['has_many']) as $r){
		$def.="\thas_many ".ucfirst($r)."\n";
	}
	if($conf['relations']['belongs_to'])
	foreach(split(",", $conf['relations']['belongs_to']) as $r){
		$def.="\tbelongs_to ".ucfirst($r).", :field=>:{$conf['relations']["$r.fkey"]}\n";
	}
	
	$def.="\n\nend\n\n";
	
}

print $def;
?>
