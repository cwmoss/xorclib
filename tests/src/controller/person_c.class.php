<?php

class Person_C extends Xorc_Controller{

	function index(){
		$person=new Person;
		$this->list=$person->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$person=new Person($this->r['id']);
		$this->form->set($person->get());
	}
	
	function save(){	
		$person=new Person($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$person->set($this->form->get());
			$person->save();
			flash("OK. Person was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$person=new Person($this->r['id']);
			$person->delete();
			flash("OK. Person was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"first_name"=>array('type'=>'text', 'display'=>"first_name", 'valid_max'=>40, 'valid_max_e'=>"Max. length is 40 characters."),
			"lock_version"=>array('type'=>'text', 'display'=>"lock_version"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('person', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>