<?php
namespace xorc\utils;
/*
	diverse textparser
*/
class parser{
	
	
	function parse_file_sections($file, $conf=array()){
		$def=array('section'=>'==', 'comment'=>'#');
		$conf=array_merge($def, $conf);
		$sections=array();
		$cur=null;
		foreach(file($file) as $line){
			$line=trim($line);
			if(!$line || $line[0]==$conf['comment']) continue;
			if(preg_match("!^{$conf['section']}(.*?)$!", $line, $mat)){
				$cur = trim($mat[1]);
				continue;
			}else{
				if(is_null($cur)) $cur='__undefined__';
				if(!isset($sections[$cur])) $sections[$cur]=array();
				$sections[$cur][]=$line;
			}
		}
		return $sections;
	}
	
	/*
		kyff macro style lines
	*/
	function parse_macro_arr($lines, $special='#~'){
		$ret=array();
		foreach($lines as $line){
			$m = $this->parse_macro($line, $special);
			$ret[$m['_id_']] = $m;
		}
		return $ret;
	}
	
	function parse_macro($line, $special='#~'){
		$line=str_replace("\t", " ", $line);
		list($id, $text) = explode(' ', $line, 2);
		return array_merge(array('_id_'=>$id), $this->parse_macro_attrs($text, $special));
	}

	function parse_macro_attrs($text, $special='#~'){
		$text = trim($text);
		$special = str_split($special);
		
		$atts = array('_args_'=>array());

		$pattern = '/([-+~#\w]+)\s*:\s*"([^"]*)"(?:\s|$)|'.
		   '([-+~#\w]+)\s*:\s*\'([^\']*)\'(?:\s|$)|'.
		   '([-+~#\w]+)\s*:\s*([^\s\'"]+)(?:\s|$)|'.
		   '"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

		$text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
		if(preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
			   #print_r($m);
			   $v="";
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes(rtrim($m[2], ','));
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes(rtrim($m[4], ','));
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes(rtrim($m[6], ','));
				elseif (isset($m[7]) and strlen(rtrim($m[7], ',')))
					$v = stripcslashes(rtrim($m[7], ','));
				elseif (isset($m[8]))
					$v = stripcslashes(rtrim($m[8], ','));

				if(!$v) continue;

				if($v[0]=='+'){
				   $atts[ltrim($v, '+-')] = true;
				}elseif($v[0]=='-'){
				   $atts[ltrim($v, '+-')] = false;
				}elseif(in_array($v[0], $special)){
				   $atts[$v[0]]=substr($v, 1);
				}else{
				   $atts['_args_'][]=$v;
				}
			}
		} else {
			# $atts = ltrim($text);
		}
		return $atts;
	}
}

?>