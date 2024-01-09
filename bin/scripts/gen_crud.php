<?php
include_once("xorc/div/naming.class.php");
include_once("_genlib.php");
include_once("_genlib2.php");

// muss immer aus dem projektfolder aufgerufen werden
$path=getcwd();

if(basename($argv[0])=="xorc"){
   /* this is called from xorc-bin? */
   $appname=$opts['app']?$opts['app']:basename($path);
}else{
   /* no! it's called from an existing application */
   $appname=basename($argv[0]);
}

$applib="$path/lib/{$appname}";

$db_string=$opts["db"];
if($opts['db']){
   XorcStore_Connector::set("_db", 
      array('dsn'=>$opts['db'], 'prefix'=>$opts['prefix']));
   $con="_db";
}else{
   $con=null;
}
$xc=new XorcStore_Connector;
$con_det=$xc->get_connection_details($con);

# $db_dsn=db_string_to_dsn($db_string);
# $_db;	//global db object

$tables=$margs[0];
if(trim($tables)) $tables=explode(",", $tables);

$templatepath=XORC_LIB_PATH."/bin/templates";

$naming=new Naming;

// general options
$tplvars=array(
	"app-name"=>$appname,
	"include-path"=>get_includepath($path."/lib")
	);

// dbtables - forms - listtables
$info=collect_table_info($con_det['dsn'], $con_det['prefix'], $tables);

#print_r($con_det);
#print_r($info);

$forms=collect_forms($info);
$colnames=collect_colnames($info);
$idnames=collect_idnames($info);
$vars=array("form"=>$forms, "colnames"=>$colnames, "idnames"=>$idnames);

/*
$inifiles=create_ini_files($info);
write_files($inifiles, "$path/lib/$appname/schema", "inifile");

$models=create_models($info, "model.class.php");
write_files($models, "$path/lib/$appname", "model class");
*/

foreach($info as $tname=>$x){
   $controllers=create_crudcontroller($tname, $info, "crudcontroller.class.php", $vars);
   write_files($controllers, "$path/src/controller", "controller class");

   $views=create_crudviews($tname, $info, "crud_view", $vars);
   write_files($views, "$path/src/view", "view template");
   
   $models=create_ar_models($tname, $info, "model.class.php");
   write_files($models, "$path/lib/$appname", "model class");
}

print <<<EON
*** 
*** controller and views for $table generated.
***
*** have fun!
***
EON;


?>