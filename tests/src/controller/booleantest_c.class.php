<?php

class Booleantest_C extends Xorc_Controller{

	function index(){
		$booleantest=new Booleantest;
		$this->list=$booleantest->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$booleantest=new Booleantest($this->r['id']);
		$this->form->set($booleantest->get());
	}
	
	function save(){	
		$booleantest=new Booleantest($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$booleantest->set($this->form->get());
			$booleantest->save();
			flash("OK. Booleantest was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$booleantest=new Booleantest($this->r['id']);
			$booleantest->delete();
			flash("OK. Booleantest was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"value"=>array('type'=>'text', 'display'=>"value"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('booleantest', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>