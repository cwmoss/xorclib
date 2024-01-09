<?php

class Computer_C extends Xorc_Controller{

	function index(){
		$computer=new Computer;
		$this->list=$computer->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$computer=new Computer($this->r['id']);
		$this->form->set($computer->get());
	}
	
	function save(){	
		$computer=new Computer($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$computer->set($this->form->get());
			$computer->save();
			flash("OK. Computer was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$computer=new Computer($this->r['id']);
			$computer->delete();
			flash("OK. Computer was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"developer"=>array('type'=>'text', 'display'=>"developer"),
			"extendedwarranty"=>array('type'=>'text', 'display'=>"extendedwarranty"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('computer', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>