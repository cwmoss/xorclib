<?php

class Account_C extends Xorc_Controller{

	function index(){
		$account=new Account;
		$this->list=$account->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$account=new Account($this->r['id']);
		$this->form->set($account->get());
	}
	
	function save(){	
		$account=new Account($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$account->set($this->form->get());
			$account->save();
			flash("OK. Account was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$account=new Account($this->r['id']);
			$account->delete();
			flash("OK. Account was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"firm_id"=>array('type'=>'text', 'display'=>"firm_id"),
			"credit_limit"=>array('type'=>'text', 'display'=>"credit_limit"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('account', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>