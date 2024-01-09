<?php
function read($length='255'){
	if (!isset ($GLOBALS['StdinPointer'])){
		$GLOBALS['StdinPointer'] = fopen ("php://stdin","r");
	}
	$line = fgets($GLOBALS['StdinPointer'], $length);
	return trim ($line);
}

function readc(){
	if (!isset ($GLOBALS['StdinPointer'])){
		$GLOBALS['StdinPointer'] = fopen ("php://stdin", "r");
	}
	$line = fgets($GLOBALS['StdinPointer']);
	return trim ($line);
}

function file_write_dialog($file, $text, $cont){
	print("  $text ..");
	$skip=true;
	$o_all = false;
	if(defined('OVERIDE_ALL') && OVERIDE_ALL === 1){
		$o_all = true;
	}

	if(file_exists($file) && $o_all !== true){
		print(" $file already exists. override? [Yn]");
		$answer=readc();
		if(!$answer || $answer=='y'){
			$skip=false;
		}
	}else{
		$skip=false;
	}

	if($skip){
		print(" .. skipped\n");
	}else{
		$cl=@fopen($file, "w");
		if($cl){
			fwrite($cl, $cont);
			fclose($cl);
			print(" .. OK\n");
		}else{
			print(" .. ERROR. couldn't write to $file\n");
		}
	}
}

function file_copy_dialog($src, $dest, $text){
	print("  $text ..");
	$skip=true;
	$o_all = false;
	if(defined('OVERIDE_ALL') && OVERIDE_ALL === 1){
		$o_all = true;
	}

	if(file_exists($dest) && $o_all !== true){
		print(" $dest already exists. override? [Yn]");
		$answer=readc();
		if(!$answer || $answer=='y'){
			$skip=false;
		}
	}else{
		$skip=false;
	}

	if($skip){
		print(" .. skipped\n");
	}else{
		create_directory(dirname($dest));
		$ok=@copy($src, $dest);
		if($ok){
			print(" .. OK\n");
		}else{
			print(" .. ERROR. couldn't copy $src to $dest\n");
		}
	}
}

function input_dialog($text, $default=""){
   print("$text [$default] ");
   $answer=readc();
   if(!$answer) $answer=$default;
   return $answer;
}
?>
