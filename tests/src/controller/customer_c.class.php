<?php

class Customer_C extends Xorc_Controller{

	function index(){
		$customer=new Customer;
		$this->list=$customer->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$customer=new Customer($this->r['id']);
		$this->form->set($customer->get());
	}
	
	function save(){	
		$customer=new Customer($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$customer->set($this->form->get());
			$customer->save();
			flash("OK. Customer was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$customer=new Customer($this->r['id']);
			$customer->delete();
			flash("OK. Customer was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"balance"=>array('type'=>'text', 'display'=>"balance"),
			"address_street"=>array('type'=>'text', 'display'=>"address_street", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"address_city"=>array('type'=>'text', 'display'=>"address_city", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"address_country"=>array('type'=>'text', 'display'=>"address_country", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"gps_location"=>array('type'=>'text', 'display'=>"gps_location", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('customer', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>