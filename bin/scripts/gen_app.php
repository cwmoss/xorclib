<?php
include_once("_genlib.php");
include_once("_genlib2.php");

$prefix=$opts['prefix'];
$path=$opts['t'];
if(!$path) $path=getcwd();

$appname=$opts['app']?$opts['app']:basename($path);
$applib="$path/lib/{$appname}";

define('OVERIDE_ALL', 1);

$db_string=$opts["db"];

if($db_string){
   $db_dsn=db_string_to_dsn($db_string);

   # print_r($opts);
   print "connect ($db_string) to: ".$db_dsn."\n";
}else{
   print "generating without db connection\n";
}

$templatepath = "$mypath/templates";
$type = "bootstrap";

$naming=new Naming;
$_db;	//global db object


// general options
$tplvars=array(
	"app-name" => $appname,
	"appname" => $appname,
	"include-path"=>get_includepath($path."/lib"),
	"app-path"=>$path,
	'env-name'=>strtoupper($appname),
	'envname' => strtoupper($appname),
	'app-name-uc'=>strtoupper($appname),
	'connect-string'=> $db_string,
	'connect-dsn' =>$db_dsn,
	'table-prefix' => $prefix,
	'year' => date('Y'),
	'first-controller' => 'register/index',
	'navigation' => array(array(
		'url'=> '/',
		'description'=> 'Start'))
	);

// dbtables - forms - listtables
if($db_string){
   $info=collect_table_info($db_string, $prefix);
   $forms=collect_forms($info);
   $colnames=collect_colnames($info);
   $idnames=collect_idnames($info);
   $vars=array("form"=>$forms, "colnames"=>$colnames, "idnames"=>$idnames);
}

if($info) foreach($info as $tname=>$x){
   $controllers=create_crudcontroller($tname, $info, "crudcontroller.class.php", $vars);
   write_files($controllers, "$path/src/controller", "controller class");

   $views=create_crudviews($tname, $info, "crud_view", $vars);
   write_files($views, "$path/src/view", "view template");
   
   $models=create_ar_models($tname, $info, "model.class.php");
   write_files($models, "$path/lib/$appname", "model class");
}

$dir_iterator = new RecursiveDirectoryIterator("$templatepath/$type");
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
// could use CHILD_FIRST if you so wish

foreach ($iterator as $file){
	if($file->isDir()) continue;
	$fname = $file->getFilename();
	if($fname[0]=='.') continue;
	$src = $file->getPathname();

	$src_short = str_replace("$templatepath/$type/", '', $src);
	$dest = str_replace('__app__', $appname, $src_short);
    // printf("%s\n", $file->getPathname());

    // printf("writing %s (%s)\n", $dest, $src_short);

    write_file("$type/$src_short", "{$path}/$dest", $tplvars, $dest);
}

$copy = array(
	'conf/__app__-dist.ini' => 'conf/__app___dev.ini',
	'conf/dot.htaccess' => 'public/.htaccess'
	);

foreach($copy as $src => $dest){
	$src = str_replace('__app__', $appname, $src);
	$dest = str_replace('__app__', $appname, $dest);

	file_copy_dialog($path."/$src", $path."/$dest", $dest);

}

`chmod ugo+x $path/bin/$appname`;
`chmod -R ugo+w $path/var/`;
`composer -d=lib update`;

# TODO: remove
`ln -s ../node_modules public/`;

#`npm install`;

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