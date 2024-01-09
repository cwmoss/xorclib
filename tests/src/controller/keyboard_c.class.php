<?php

class Keyboard_C extends Xorc_Controller{

	function index(){
		$keyboard=new Keyboard;
		$this->list=$keyboard->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$keyboard=new Keyboard($this->r['id']);
		$this->form->set($keyboard->get());
	}
	
	function save(){	
		$keyboard=new Keyboard($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$keyboard->set($this->form->get());
			$keyboard->save();
			flash("OK. Keyboard was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$keyboard=new Keyboard($this->r['id']);
			$keyboard->delete();
			flash("OK. Keyboard was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>50, 'valid_max_e'=>"Max. length is 50 characters."),
			"key_number"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('keyboard', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>