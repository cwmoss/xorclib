<?php
namespace xorc;
use xorc\app;

class view{
	public $name;
	public $ctrl=null;
	public $file=null;
	// alle basispfade (main, module, themes)
	public $basepath=array();
	
	// für partials
	static $parent=null;
	static $found_partials=array();
	
	function __construct($name, $ctrl=null, $action=null, $basepath=null){
		if($ctrl) $cname=get_class($ctrl);
		else $cname='null';
		
	   log_debug("VIEW: name=$name ctr=$cname action=$action basepath=$basepath");	   	   
	   $this->ctrl = $ctrl;
	   $this->basepath = (array) $basepath;
	   if(!$this->is_partial()){
	      $this->name = $this->_resolve_viewname($name, $ctrl, $action);
	      self::$parent = $this;
	      self::$found_partials = array();
	   }else{
	      $this->name = $name;
	   }
	}
	
	function is_partial(){
	   return $this->basepath?false:true;
	}
	
	function _resolve_viewname($name, $ctrl, $action){
	   if($name===false) return;
	   if(!$name) $name=$action;
	   // name= ctr=kyff\controller\user (\kyff\controller\user) action=index
	   if($name[0]=='/'){
	      return $name;
	   }else{
	      $parts = explode('\\', get_class($ctrl));
	      $fname = join('/', $parts);
	      #$fname = str_replace('/controller/', '/views/', $fname);
	      $fname = preg_replace('!^.*?/controller/!', '', $fname);
	      $fname .= '/'.$name;
	      return $fname;
	   }
	}
	
	function _find_view($fname){
	   return $fname;
	   $file = $this->basepath[0].'/'.$fname;
	   return $file;
	}
	
	function _find_partial($name){
	   $dir = self::$parent->file;
	   log_debug("VIEW: partial $name (from $dir)");
	   #log_debug(self::$parent);
	   while($dir=dirname($dir)){
	      $f = self::$parent->basepath[0]."/$dir/_{$name}.html";
	      log_debug("check $f $dir");
	      #return false;
	      if(file_exists($f)) return $f;
	      if($dir==DIRECTORY_SEPARATOR) break;
	   }
	   return false;
	}
	
   function render($params=array()){
      if(!$this->is_partial()){
         $this->file = $this->_find_view($this->name);
         $file = $this->basepath[0].'/'.$this->file.'.html';
      }else{
         $file = self::$found_partials[$this->name];
         if(!$file && $file!==false){
            $file = $this->_find_partial($this->name);
            self::$found_partials[$this->name] = $file;
         }
         if(!$file) return 'missing partial '.$this->name;
         $this->file = $file;
      }
      $out=$this->_render_file($file, $params, $this->ctrl);
      return $out;
   }
	
	static function render_file($file, $params){
	   try{
	      $v = new self($file);
	      return $v->_render_file($file, $params);
	   }catch(\exception $e){
	      return false;
	   }
	}
	
	function _render_file($file, $params=array(), $ctrl=null){
		log_debug("[VIEW/INCL/START] $file");
		 #app::$inst->base
		if(!file_exists($file)){
		   log_error("VIEW $file is missing.");
			throw new \exception("missing view $file");
		}
      extract(array_merge((array) $ctrl, $params));
		ob_start();
		include($file);
		$out=ob_get_clean();
		return $out;
	}
}
?>