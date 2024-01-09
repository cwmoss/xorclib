<?php

class Auto_id_test_C extends Xorc_Controller{

	function index(){
		$auto_id_test=new Auto_id_test;
		$this->list=$auto_id_test->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$auto_id_test=new Auto_id_test($this->r['id']);
		$this->form->set($auto_id_test->get());
	}
	
	function save(){	
		$auto_id_test=new Auto_id_test($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$auto_id_test->set($this->form->get());
			$auto_id_test->save();
			flash("OK. Auto_id_test was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$auto_id_test=new Auto_id_test($this->r['id']);
			$auto_id_test->delete();
			flash("OK. Auto_id_test was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"value"=>array('type'=>'text', 'display'=>"value"),
			"auto_id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('auto_id_test', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>