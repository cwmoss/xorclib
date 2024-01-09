<?php

class Xorc_Finder{
  
	var $page;
	var $result;
	var $type;
	var $obj;
	var $errors;
	var $conf;
	var $_status=array();
	var $_cond=array();
	
	function Xorc_finder($obj, $parms=array(), $conf=array()){
	   $this->errors=new Xorcstore_Errors;
	   $this->obj=$obj;
		$conf=array_merge(
			array('order'=>'created_at', 'limit'=>20, 'page'=>1, 
				'name'=>$obj->klas."_finder", 'session'=>true, 
				'default'=>array(),
				'fixed'=>array(),
				'action'=>Xorcapp::$inst->current_action,
				'he_adapter'=>"he_adapter",
				'he_order'=>"@mdate NUMD",
				'he_adapter_method'=>"search"),
			$conf);
		$this->conf=$conf;
	   $this->init($parms);
	}

	function init($parms){
#	   log_error($parms);
	   $fdef = $this->conf['default'];
	   if(!is_array($fdef)) $fdef=array();
	   if($this->conf['fixed']) $fdef=array_merge($fdef, $this->conf['fixed']);
	   
		$def=array(
			'page'=>1,
			'finder'=>$fdef,
			'order'=>$this->conf['order']
			);
			
		if($this->conf['session'] && !isset($_SESSION[$this->conf['name']]))
			$_SESSION[$this->conf['name']]=$def;
		elseif($this->conf['session']) 
			$def=$_SESSION[$this->conf['name']];
		if($parms['finder']['_req']){
			$def['page']=1;
			if(!is_array($parms['finder'])) $parms['finder']=array();
			$def['finder']=array_merge($parms['finder'], $this->conf['fixed']);
		}
		
		if($parms['page']) $def['page']=$parms['page'];
		if($parms['order']){
			$def['page']=1;
			$def['order']=$parms['order'];
		}
		
		$this->set($def['finder']);	

		if($this->conf['session']) $_SESSION[$this->conf['name']]=$def;
		$this->_status=$def;
	}
		
	function set($parms){	
	   foreach($parms as $k=>$v){
	      $this->$k=$v;
	   }
	}

	function action(){
		return $this->conf['action'];
	}
	
	function search(){
	   if($this->_vt_q) $m="he_search";
		elseif($this->_tag) $m="tag_search";
		elseif($this->id) $m="id_search";
	   else $m="sql_search";
	
		if(method_exists($this, $this->obj->klas."_$m")){
			$m=$this->obj->klas."_$m";
		}
#		log_error("FINDER: m=$m");


		return $this->$m();
	}
  
  function sql_search(){
#		log_error("finder. sqlsearch.");
#		log_error($this->_status);
		$cond=array();
		foreach($this->_status['finder'] as $k=>$v){
			if($k[0]=="_") continue;
			if($k=="q") continue;
			if($k=="tag") continue;
			if($v){
			   if($this->_cond[$k]){
			      // bsp: 'title' = array('VALUE%', 'like')
			      $cond[$k]=array(str_replace("VALUE", $v, $this->_cond[$k][0]), $this->_cond[$k][1]);
			   }else{
			      $cond[$k]=$v;
			   }
			}
		}
		return $this->obj->find_all(array(
			"conditions"=>$cond, "order"=>$this->_status['order'], 
			"page"=>$this->_status['page'], "limit"=>$this->conf['limit']
			));
  }

  	function id_search(){
		$cond=array();
		return $this->obj->find_all_by_id($this->id, 
			array(
			"conditions"=>$cond, "page"=>1, "limit"=>$this->conf['limit']
			));
	}
	
	function tag_search(){
#	   log_error($this->_status);
		$offset=($this->_status['page']-1) * $this->conf['limit'];
		$t=tagger();
      return $t->objects_iterator($this->_tag, $this->conf['limit'], $this->_status['order'], $offset);
	}

	function he_search(){
	   $adapter=$this->conf['he_adapter'];
	   $method=$this->conf['he_adapter_method'];
	   
		$he=new $adapter;

		$rs=$he->$method($this->_vt_q, $this->conf['he_order'], $this->conf['limit'], $this->_status['page']);
		
		# rs is null if he connection failed
		if($rs) $rs->pager($he->pager($rs));
		else $rs=array();
		return $rs;
  	}
  	
  	function current_order_field(){
  	   list($field, $order) = explode(" ", $this->_status['order'], 2);
  	   return $field;
  	}
  	
  	function form(){
  	   return new Xorc_objectform($this, $this->conf['name']);
  	}
  	
  	function form_end($f){
  	   return hidden_field_tag($this->conf['name']."[_req]", 1).$f->finish();
  	}
}

?>