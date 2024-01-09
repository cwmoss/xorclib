<?php

class Company_C extends Xorc_Controller{

	function index(){
		$company=new Company;
		$this->list=$company->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$company=new Company($this->r['id']);
		$this->form->set($company->get());
	}
	
	function save(){	
		$company=new Company($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$company->set($this->form->get());
			$company->save();
			flash("OK. Company was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$company=new Company($this->r['id']);
			$company->delete();
			flash("OK. Company was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"type"=>array('type'=>'text', 'display'=>"type", 'valid_max'=>50, 'valid_max_e'=>"Max. length is 50 characters."),
			"ruby_type"=>array('type'=>'text', 'display'=>"ruby_type", 'valid_max'=>50, 'valid_max_e'=>"Max. length is 50 characters."),
			"firm_id"=>array('type'=>'text', 'display'=>"firm_id"),
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>50, 'valid_max_e'=>"Max. length is 50 characters."),
			"client_of"=>array('type'=>'text', 'display'=>"client_of"),
			"rating"=>array('type'=>'text', 'display'=>"rating"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('company', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>