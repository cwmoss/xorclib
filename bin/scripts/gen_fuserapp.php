<?php
include_once("_genlib.php");

$prefix=$opts['prefix'];
$path=$opts['t'];
$appname=$opts['app']?$opts['app']:basename($path);
$applib="$path/lib/{$appname}";

$templatepath="$mypath/templates";

$naming=new Naming;
$_db;	//global db object

// general options
$tplvars=array(
	"app-name"=>$appname,
	"include-path"=>get_includepath($path."/lib")
	);

// dbtables - forms - listtables
$info=collect_table_info($opts["db"], $prefix);
$forms=collect_forms($info);
$colnames=collect_colnames($info);
$idnames=collect_idnames($info);
$vars=array("form"=>$forms, "colnames"=>$colnames, "idnames"=>$idnames);

$inifiles=create_ini_files($info);
write_files($inifiles, "$path/lib/$appname/schema", "inifile");

$models=create_models($info, "model.class.php");
write_files($models, "$path/lib/$appname", "model class");

$controllers=create_confusers($info, "confuser.php", $vars);
write_files($controllers, "$path/public", "confuser");

write_files(create_app_ini(array(
	'connect-string'=> $opts["db"],
	'table-prefix' => $prefix,
	'var-dir'=> $path."/var",
	'app-name'=> $appname
		), 
		"app.ini"), 
	$applib, "app ini");

write_files(create_app_fusermain($tplvars, "prepend.php"), "$path/public", "app main prepend");

write_files(create_app_class($info, "app.class", $appname), $applib, "app class");

write_files(create_layout($info, array("nav.php", "html_top.php", "html_bottom.php"), $tplvars), 
	"$path/public", "layout file");

copy_public_files(dirname($mypath)."/public", "$path/public", "html stuff");

print <<<EON
*** 
*** your application is now ready to run.
*** please link your public/ directory anywhere in your webserver root
*** eq. ln -s $path/public /www/my.domain.com/htdocs/$appname
*** 
*** have fun!
***
EON;


?>