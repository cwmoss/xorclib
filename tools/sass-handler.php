<?php
// seit mavericks check ich das nicht mehr, 
//		wo man die pfade für den apache setzten kann
$path = getenv('PATH'); putenv( "PATH=/usr/local/bin:$path" );

#phpinfo();
$input = @$_SERVER["PATH_TRANSLATED"];
$compress = @$_GET['compress']==1 ? '-t compressed':'';

#print $input;

header("Content-Type: text/css");

$cmd = "sassc $compress $input 2>&1";
passthru($cmd);



?>
