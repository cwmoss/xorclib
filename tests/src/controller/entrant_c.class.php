<?php

class Entrant_C extends Xorc_Controller{

	function index(){
		$entrant=new Entrant;
		$this->list=$entrant->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$entrant=new Entrant($this->r['id']);
		$this->form->set($entrant->get());
	}
	
	function save(){	
		$entrant=new Entrant($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$entrant->set($this->form->get());
			$entrant->save();
			flash("OK. Entrant was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$entrant=new Entrant($this->r['id']);
			$entrant->delete();
			flash("OK. Entrant was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'textarea', 'display'=>"name", 'valid_max'=>255, 'valid_max_e'=>"TMax. length is 255 characters.",
		'extra'=>'rows=2'),
			"course_id"=>array('type'=>'text', 'display'=>"course_id"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('entrant', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>