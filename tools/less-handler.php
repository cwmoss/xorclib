<?php
// seit mavericks check ich das nicht mehr, 
//		wo man die pfade für den apache setzten kann
$path = getenv('PATH'); putenv( "PATH=/usr/local/bin:$path" );

#phpinfo();
$input = @$_SERVER["PATH_TRANSLATED"];
$compress = @$_GET['compress']==1 ? '-x':'';

#print $input;

header("Content-Type: text/css");

$cmd = "lessc --no-color $compress $input 2>&1";
passthru($cmd);



?>
