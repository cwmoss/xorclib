<?php

class Developer_C extends Xorc_Controller{

	function index(){
		$developer=new Developer;
		$this->list=$developer->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$developer=new Developer($this->r['id']);
		$this->form->set($developer->get());
	}
	
	function save(){	
		$developer=new Developer($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$developer->set($this->form->get());
			$developer->save();
			flash("OK. Developer was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$developer=new Developer($this->r['id']);
			$developer->delete();
			flash("OK. Developer was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"salary"=>array('type'=>'text', 'display'=>"salary"),
			"updated_at"=>array('type'=>'date', 'display'=>"updated_at", 'format'=>'d-m-Y H:i', 'yearrange'=>'2006-'),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('developer', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>