<?php

class Order_C extends Xorc_Controller{

	function index(){
		$order=new Order;
		$this->list=$order->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$order=new Order($this->r['id']);
		$this->form->set($order->get());
	}
	
	function save(){	
		$order=new Order($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$order->set($this->form->get());
			$order->save();
			flash("OK. Order was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$order=new Order($this->r['id']);
			$order->delete();
			flash("OK. Order was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"billing_customer_id"=>array('type'=>'text', 'display'=>"billing_customer_id"),
			"shipping_customer_id"=>array('type'=>'text', 'display'=>"shipping_customer_id"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('order', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>