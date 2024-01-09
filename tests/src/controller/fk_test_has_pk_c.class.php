<?php

class Fk_test_has_pk_C extends Xorc_Controller{

	function index(){
		$fk_test_has_pk=new Fk_test_has_pk;
		$this->list=$fk_test_has_pk->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$fk_test_has_pk=new Fk_test_has_pk($this->r['id']);
		$this->form->set($fk_test_has_pk->get());
	}
	
	function save(){	
		$fk_test_has_pk=new Fk_test_has_pk($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$fk_test_has_pk->set($this->form->get());
			$fk_test_has_pk->save();
			flash("OK. Fk_test_has_pk was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$fk_test_has_pk=new Fk_test_has_pk($this->r['id']);
			$fk_test_has_pk->delete();
			flash("OK. Fk_test_has_pk was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('fk_test_has_pk', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>