<?php

if($margs[0]){
	print "parsing from external file:\n";
	print_r(parse_ini_file($margs[0], true));
}

print_r(Xorcapp::$inst->conf);

?>