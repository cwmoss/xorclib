<?php
$src=$margs[0];

if(!$src) die("please provide source yaml file.\n");

print "unserializing $src\n";

XorcApp::use_yaml();

$data = Spyc::YAMLLoad(file_get_contents($src));
print_r($data);

/** help!
tests a yaml file, trys to deserializ it
need: source file

ex: names.yaml
*/
?>