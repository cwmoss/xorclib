<?php
// your appname is used for deriving pathnames and stuff :)
$appname="<app-name>";

// you will probably set your includepath
ini_set("include_path", "<include-path>");

// include the application classfile
include_once("<app-name>/<app-name>.class.php");

$_app=new <app-name>;

$app_title="<app-name>";

/*
	$THISURL contains the url of the working script
	$PHP_SELF often is right thing
	in another environment you'll probably change this 
*/
$THISURL=$_SERVER['PHP_SELF'];
?>