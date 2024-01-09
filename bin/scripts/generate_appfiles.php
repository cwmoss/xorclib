<?php
Xorc::dbconnect($opts["db"], "_db");

$path=$opts['t'];

$tab=$_db->MetaTables('TABLES');

$naming=new Naming;

$nav="";
$includes="";

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
	$nav.="'{$fname}.php'=>\"".ucfirst($fname)."\",\n";
	$includes.='include_once("$appname/'.$fname.'.class");'."\n";
}
	

	$file="$path/nav.php";
	$cont=str_replace("%nav-elements%", $nav, join("", file("$mypath/templates/nav.php")));
	file_write_dialog($file, "nav.php", $cont);

$appname=ucfirst($opts['app']?$opts['app']:basename($path));
$appname_lc=strtolower($appname);

$file="$path/prepend.php";
$cont=str_replace("%app-name%", $appname, join("", file("$mypath/templates/prepend.php")));
$cont=str_replace("%app-name-lower%", $appname_lc, $cont);
$cont=str_replace("%class-files%", $includes, $cont);
file_write_dialog($file, "prepend.php", $cont);

file_write_dialog("$path/html_head.php", "html head", join("", file("$mypath/templates/html_head.php")));
file_write_dialog("$path/html_foot.php", "html foot", join("", file("$mypath/templates/html_foot.php")));
file_write_dialog("$path/xorcform.css", "xorc system css", join("", file("$mypath/templates/xorcform.css")));
file_write_dialog("$path/app.css", "custom app css", join("", file("$mypath/templates/app.css")));
	

?>
