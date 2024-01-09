<?php

class Subscriber_C extends Xorc_Controller{

	function index(){
		$subscriber=new Subscriber;
		$this->list=$subscriber->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$subscriber=new Subscriber($this->r['id']);
		$this->form->set($subscriber->get());
	}
	
	function save(){	
		$subscriber=new Subscriber($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$subscriber->set($this->form->get());
			$subscriber->save();
			flash("OK. Subscriber was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$subscriber=new Subscriber($this->r['id']);
			$subscriber->delete();
			flash("OK. Subscriber was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"nick"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('subscriber', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>