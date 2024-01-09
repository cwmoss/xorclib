<?php

class FancyAscii{

	var $conf;
	
	function FancyAscii($conf=array()){
		$this->conf=$conf;
	}

	function convert_from_html($html){
	print(htmlentities($html));
		$cont=preg_replace("!\n+!ms", "\n", $html);
		$cont=preg_replace_callback("!<h(\d)(.*?)>(.*?)</h\d>!msi", array(&$this, 'headline_cb'), $cont);

		$cont=preg_replace("!<b>(.*?)</b>!i", "**$1**", $cont);
		$cont=preg_replace("!<i>(.*?)</i>!i", "_$1_", $cont);

		$cont=preg_replace_callback("!<p(.*?)>(.*?)</p>!msi", array(&$this, 'brot_cb'), $cont);
		$cont=preg_replace_callback("!<hr(.*?)>!i", array(&$this, 'line_cb'), $cont);
		
		$cont=strip_tags($cont);
		return $cont;
	}
	
	function headline_cb($m){
		$text=$m[3];
		$level=$m[1];
		$conf=array(
		 "1"=>array("width"=>65, "top"=>"/", "bottom"=>"/", "right"=>"", "left"=>"", "indent"=>4, "ulmode"=>0),
 		 "2"=>array("width"=>65, "top"=>"/", "bottom"=>"=", "right"=>"", "left"=>"", "indent"=>2, "ulmode"=>1),
		 "3"=>array("width"=>65, "top"=>"/", "bottom"=>"-", "right"=>"", "left"=>"", "indent"=>2, "ulmode"=>1),
		 );
		$p=$conf[$level];		
//		print_r($t);print("LEVEL:$level~"); 
		return $this->headline($text, $p['width'], $p['top'], $p['bottom'], $p['left'], 
			$p['right'], $p['indent'], $p['ulmode']);
	}
	
	function brot_cb($m){
		$text=$m[2];
		return $this->brot($text);
	}
	
	function line_cb($m){
		return $this->line(".");
	}
		
	function headline($t, $width="65", $top="/", $bottom="/", $left="", $right="", $indent=4, $ulmode=false){
		$w=$width;
		$indentspace=str_repeat(" ", $indent);
		$text="\n\n";
		if(!$ulmode) $text.=str_repeat($top, $w)."\n"; // underlinemodus
		$text.=wordwrap("$indentspace$t", $w-($indent*2), "\n$indentspace")."\n"; 
		if(!$ulmode){
			$text.=str_repeat($bottom, $w)."\n";
		}else{
			$text.=$indentspace.str_repeat($bottom, strlen($t))."\n";
		}
		return $text;
	}
	
	function brot($t, $indent=2){
		$t=preg_replace("/\r?\n\r?/ms", " ", $t);
		$t=preg_replace("/<br.*?>/i", "\n ", $t);
		$w=65;
		$text=wordwrap("  $t", 57, "\n  "); 
		
		return($text."\n");
	}
	
	function line($linechar="-"){
		return "".str_repeat($linechar, 65)."";
	}
}

?>
