<?php
require_once("xorc/div/xh.class.php");

class Domain{

	public static $d;
	
	/*
	$helper=new XH;
	$cc=$helper->countrycodes();
	$cc_en=$helper->countrycodes("en", 100);
	$lc=$helper->langcodes();
	*/
	
	static function load(){
		if(!self::$d){
			$d=self::load_data();
			self::$d=$d;
		}
		return self::$d;
	}

	static function load_data(){
		$d = json_decode(file_get_contents(xorcapp::$inst->approot.'/conf/domain.json'), true);
		if(is_null($d)){
			print json_last_error();
		}
		foreach($d as $k=>$v){
			if(is_string($v) && preg_match("!^(\w+):(.*?)(\(.*?\))?$!", $v, $mat)){
				$m="self::load_from_{$mat[1]}";
				#print_r($mat);
				$m_args=array($mat[2]);
				if($mat[3]){
					$m_args=array_merge($m_args, explode(',', trim($mat[3], '()')));
				}
				$d[$k] = call_user_func_array($m, $m_args);
			}
		}
		return $d;
	}
	
	static function load_from_ini($f, $sections=true){
	   $c = customer();
	   if($c) $file=xorcapp::$inst->approot."/custom/$c/$f";
	   if(!file_exists($file))	$file = xorcapp::$inst->approot.'/conf/'.$f;
	  # print $file;
		$data = @parse_ini_file($file, $sections);
		return $data;
	}
	
	static function load_from_single_ini($f, $sections=true){
	   return self::load_from_ini($f, false);
	}
	
	static function load_from_class(){
		$args = func_get_args();
		$meth = array_shift($args);
		#print "LOAD CLASS $meth\n";
		#print_r($args);
		$res = call_user_func_array($meth, $args);
		#var_dump($res);
		return $res;
	}
	
	static function __callStatic($m, $args){
		return self::items($m, $args[0]);
	}
	
   static function items($name, $key=null){
      self::load();
      if(is_null($key)){
         return self::$d[$name];
      }else{
         return self::$d[$name][$key];
      }
   }
   
   static function items_arr($name){
      if($name=='timezones') return flat_array_to_keyvalue(self::items($name));
      return array_to_keyvalue(self::items($name));
   }
}

?>