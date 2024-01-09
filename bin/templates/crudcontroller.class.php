<?php

class <controller-name> extends Xorc_Controller{

	// user create mode
	public $create=false;

	function index(){
		$limit = 50;
	   $page = $this->r['page'];
	   if(!$page) $page=1;

		$<model-var>=new <model-name>;
		if($this->r['q']){
			$this->items=$<model-var>->search($this->r['q']);
			$this->q = $this->r['q'];
		}else{
			$this->items=$<model-var>->find_all(array("conditions"=>array(),
				"order"=>"created_at DESC", 'limit'=>$limit, 'page'=>$page));
		}
		$this->pager = $this->items->pager();
	}
	
	function create(){	
		$this-><model-var>=new <model-name>;
		$this->create=true;
	}
	
	function edit(){		
		$this-><model-var>=<model-name>::i()->find($this->r['id']);
		if(!$this-><model-var>) throw new XorcRuntimeException("Nicht gefunden.", array("header"=>404));
	}
	
	function save(){
		if($id=$this->r['id']){
			$this-><model-var>=<model-name>::i()->find($this->r['id']);
			if(!$this-><model-var>) throw new XorcRuntimeException("Nicht gefunden.", array("header"=>404));
		}else{
			// create new object
			$this->create=true;
			$this-><model-var>=new <model-name>;
		}	
		$this-><model-var>->set($this->r['<model-var>']);
		if($this-><model-var>->save()){
			flash("OK. <model-name> wurde gespeichert.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		$this-><model-var>=<model-name>::i()->find($this->r['id']);
		if(!$this-><model-var>) throw new XorcRuntimeException("Nicht gefunden.", array("header"=>404));
		$this-><model-var>->destroy();
		flash("OK. <model-name> wurde gelöscht.");
		$this->redirect("index");
	}
	
}


