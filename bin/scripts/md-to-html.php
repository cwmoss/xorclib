<?php

/*
	create html from markdown
*/

$txtf = $margs[0];
$txtf = $argv[1];

$txt = file_get_contents($txtf);

require_once("xorc/text/markdownextra/markdown.php");

print(Markdown($txt));