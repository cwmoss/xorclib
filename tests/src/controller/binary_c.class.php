<?php

class Binary_C extends Xorc_Controller{

	function index(){
		$binary=new Binary;
		$this->list=$binary->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$binary=new Binary($this->r['id']);
		$this->form->set($binary->get());
	}
	
	function save(){	
		$binary=new Binary($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$binary->set($this->form->get());
			$binary->save();
			flash("OK. Binary was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$binary=new Binary($this->r['id']);
			$binary->delete();
			flash("OK. Binary was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"data"=>array('type'=>'text', 'display'=>"data"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('binary', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>