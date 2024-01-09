<?php

class Colnametest_C extends Xorc_Controller{

	function index(){
		$colnametest=new Colnametest;
		$this->list=$colnametest->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$colnametest=new Colnametest($this->r['id']);
		$this->form->set($colnametest->get());
	}
	
	function save(){	
		$colnametest=new Colnametest($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$colnametest->set($this->form->get());
			$colnametest->save();
			flash("OK. Colnametest was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$colnametest=new Colnametest($this->r['id']);
			$colnametest->delete();
			flash("OK. Colnametest was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"references"=>array('type'=>'text', 'display'=>"references"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('colnametest', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>