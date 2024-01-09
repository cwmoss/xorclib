<?php

class App{
	public static $inst;
	public const KLAS="XX";
	
	public $name;
			
	function run($klas=""){
		if(!isset(self::$inst)){
			print self::class;
			print self::KLAS;
			print \SELF::class;
			if(!$klas) $klas=\SELF;
			self::$inst=new $klas;
		}
		return self::$inst;
	}
	
//	function run(){return self::__construct();}
	
	function name($name=""){
		if(!$name) return self::$inst->name;
		else self::$inst->name=$name;
	}
	
	function name2($name=""){
		if(!$name) return $this->name;
		else $this->name=$name;
	}
}	

class RApp extends App{
	final const KLAS=self::class;
	function go(){
		print "LOS gehts!";
	}
	
	public static function klas(){return self::class;}
}

$a = (new App())->run('rapp');
(new RApp())->run();

 $a->name("heinz");
// RApp::name2("hugo");
print $a->name();

print (new RApp())->run()->name();
// print $a;
 $a->go();
print (new RApp())->run()->klas();

$eins='das ist ein $x';
$x="--x--";
print "..{$eins}";

print parse_str($eins, $result);
?>
