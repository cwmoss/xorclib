<?php

class Funny_joke_C extends Xorc_Controller{

	function index(){
		$funny_joke=new Funny_joke;
		$this->list=$funny_joke->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$funny_joke=new Funny_joke($this->r['id']);
		$this->form->set($funny_joke->get());
	}
	
	function save(){	
		$funny_joke=new Funny_joke($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$funny_joke->set($this->form->get());
			$funny_joke->save();
			flash("OK. Funny_joke was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$funny_joke=new Funny_joke($this->r['id']);
			$funny_joke->delete();
			flash("OK. Funny_joke was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>50, 'valid_max_e'=>"Max. length is 50 characters."),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('funny_joke', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>