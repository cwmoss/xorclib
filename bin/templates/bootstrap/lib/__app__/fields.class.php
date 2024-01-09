<?php

class fields{
	
	public $entity;
	public $name;
	public $prop;
	
	public static $f;
	public static $eopts = array();
	public static $sourcefile;
	public static $emap = array('u'=>'user', 'l'=>'app_login');
	
	function __construct($entity, $name, $prop){
		$this->entity=$entity;
		$this->name=$name;
		$this->prop=$prop;
	}
	
	function get_is_group(){
	   return ($this->type=='group');
	}
	
	function get_group(){
	   if($this->type!='group') return null;
	   $search = $this->prop['catch'];
	   if(!$search) $search = '^'.$this->name.'_';
	   $g=array();
	   foreach(self::$f[$this->entity] as $name=>$prop){
	      if(preg_match("/$search/", $name)) $g[]=$name;
	   }
	   return $g;
	}
	
	function get_noindex(){
	   # || $this->type=='file'
	   # if($this->type=='group') return true;
		return ($this->prop['idx']===false)?true:false;
	}
	
	function get_nosearch(){
	   if($this->noindex) return true;
	   return ($this->prop['search']===false)?true:false;
	}

	/*
	   in generisches suchfeld aufnehmen
	*/
	function get_in_body(){
	   $t = $this->type;
	   if($t=='integer' || $t=='flag' || $t=='yn' || $t=='date' || $t=='datetime') return false;
	   return ($this->prop['body']===false)?false:true;
	}
	
	function get_reference(){
	   if($this->prop['ref']){
	      return $this->prop['ref'];
	   }
	   if($this->type=='flag') return 'flag';
	   if($this->type=='yn') return 'yn';
	   return null;
	}
	
	function get_reference_items(){
	   $r = $this->reference;
	   if($r){
	      return domain::items($r);
	   }
	   return null;
	}
	
	function get_reference_items_arr(){
	   $r = $this->reference_items;
	   if($r){
	      $r = array_to_keyvalue($r);
	   }
	   return $r;
	}
	
	function get_comparison_operators(){
	   $op = ['='=>1, '!='=>1, '~'=>1, '!~'=>1];
	   if($this->reference){
	      unset($op['~'], $op['!~']);
	   }
	   return array_keys($op);
	}
	
	function get_type(){
		return ($this->prop['type'])?$this->prop['type']:'c';
	}
	
	function get_len(){
		return ($this->prop['len'])?$this->prop['len']:128;
	}
	
	function get_is_secret(){
	   return ($this->prop['secret'])?true:false;
	}
	
	function get_is_custom_field(){
	   return ($this->prop['cf'])?true:false;
	}
	
	function get_notoggle(){
	   return ($this->prop['notoggle'])?true:false;
	}
	
	function get_is_im(){
	   return ($this->prop['is_im'])?true:false;
	}
	
	function get_index_name(){
		if($this->prop['idx']===true) return $this->name;
		if($this->prop['idx']) return $this->prop['idx'];
		$type=$this->type;
		if($type=='c' || $type=='text'){
			return $this->name.'_t';
		}elseif($type=='integer' || $type=='flag' || $type=='yn'){
			return $this->name.'_i';
		}elseif($type=='datetime' || $type=='date'){
			return $this->name.'_dt';
		}elseif($type=='taxonomy' || $type=='group'){
   		return $this->name.'_ss';
   	}elseif($type=='file'){
   	   return "{$this->name}_txt";
   	}else{
			return $this->name;
		}
	}
	
	function get_title(){
		return ($this->prop['title'])?$this->prop['title']:$this->name;
	}
	function get_search_title(){
	   if($this->prop['stitle']) return $this->prop['stitle'];
	   $t = $this->title;
	   $suffix = $this->prop['ssuffix'];
	   if(!$suffix) $suffix = self::get_entity_option($this->entity, "ssuffix");
	   if($suffix){
	      $t.=$suffix;
	   }
	   return $t;
	}
	function get_is_required(){
		return ($this->prop['idx']===true)?true:false;
	}
	
	function __get($prop){
		$m='get_'.$prop;
		if(method_exists($this, $m)){
			return $this->$m();
		}
	}
	
	static function get_entity_option($entity, $name){
	   return @self::$eopts[$entity][$name];
	}
	
	static function get_ref_lists($entity='person'){
	   $lists = array();
	   foreach(self::get_fields_for($entity) as $f){
	      $ref = $f->reference;
	      if($ref && !isset($lists[$ref])){
	         $lists[$ref] = $f->reference_items;
	      }
	   }
	   return $lists;
	}
	
	static function get_ref_lists_arr($entity='person'){
	   $lists = array();
	   foreach(self::get_fields_for($entity) as $f){
	      $ref = $f->reference;
	      if($ref && !isset($lists[$ref])){
	         $lists[$ref] = domain::items_arr($ref); //$f->reference_items_arr;
	      }
	   }
	   return $lists;
	}
	
	static function init_custom_fields($entity){
		self::load();
		$cf=array();
		foreach(self::$f[$entity] as $name=>$prop) {
			if(@$prop['cf']){
				$cf[]=$name;
			}
		}
		person_meta::whitelist($entity, $cf);
	}
	
	static function get_fields_for($entity, $filter=null){
		self::load();
		foreach(self::$f[$entity] as $name=>$prop) {
		   $f = new fields($entity, $name, $prop);
		   if($filter && !$f->$filter) continue;
			#yield $f;
		}
	}
	
	static function get_secret_fields($entity='person'){
	   self::load();
	   $s = array();
		foreach(self::$f[$entity] as $name=>$prop){
		   if($prop['secret']) $s[]=$name;
		}
		return $s;
	}
	
	static function get_field($entity, $name=null){
		self::load();
		if(is_null($name)){
		   list($entity, $name2) = explode('.', $entity);
		   if(!$name2){
		      $name = $entity;
		      $entity='person';
		   }else{
		      if(self::$emap[$entity]) $entity = self::$emap[$entity];
		      $name = $name2;
		   }
		}
		if(isset(self::$f[$entity][$name]))
		   return new fields($entity, $name, self::$f[$entity][$name]);
		else
		   return null;
	}
	
	static function load(){
		if(!self::$f){
			$f=self::parse_raw();
			$f=self::convert($f);
			self::$f=$f;
		}
		return self::$f;
	}
	
	static function parse_raw(){
		$p = new parserutil;
		$sections = $p->parse_file_sections(self::$sourcefile);
		$s = array();
		foreach($sections as $sec=>$cont){
			$s[$sec] = $p->parse_macro_arr($cont, '#_');
		}
		return $s;
	}
	
	static function convert($f){
		$conv=array();
		foreach($f as $sec=>$cont){
			$conv[$sec]=array();
			foreach($cont as $id=>$props){
			   # hack: entity options direkt speichern
			   if($id=='__OPTIONS__'){
			      self::$eopts[$sec] = self::convert_field($props);
			      continue;
			   }
				$conv[$sec][$id] = self::convert_field($props);
			}
		}
		return $conv;
	}
	
	static function convert_field($prop){
		#print_r($prop);
		if(!isset($prop['title']) && $prop['_args_'][0]){
			$prop['title'] = $prop['_args_'][0];
		}
		if(isset($prop['type']) && $type=self::parse_type($prop['type'])){
			list($prop['type'], $prop['len']) = $type;
		}
		if(in_array('req', $prop['_args_'])){
			$prop['required']=true;
		}
		unset($prop['_args_']);
		return $prop;
	}
	
	static function parse_type($type){
		if(preg_match('!(\w+)\((\d+)\)!', $type, $mat)){
			$type = strtolower($mat[1]);
			$len = $mat[2];
			return array($type, $len);
		}
		return false;
	}
}

?>