<?php

class Mixin_C extends Xorc_Controller{

	function index(){
		$mixin=new Mixin;
		$this->list=$mixin->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$mixin=new Mixin($this->r['id']);
		$this->form->set($mixin->get());
	}
	
	function save(){	
		$mixin=new Mixin($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$mixin->set($this->form->get());
			$mixin->save();
			flash("OK. Mixin was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$mixin=new Mixin($this->r['id']);
			$mixin->delete();
			flash("OK. Mixin was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"parent_id"=>array('type'=>'text', 'display'=>"parent_id"),
			"pos"=>array('type'=>'text', 'display'=>"pos"),
			"updated_at"=>array('type'=>'date', 'display'=>"updated_at", 'format'=>'d-m-Y H:i', 'yearrange'=>'2006-'),
			"lft"=>array('type'=>'text', 'display'=>"lft"),
			"rgt"=>array('type'=>'text', 'display'=>"rgt"),
			"root_id"=>array('type'=>'text', 'display'=>"root_id"),
			"type"=>array('type'=>'text', 'display'=>"type", 'valid_max'=>40, 'valid_max_e'=>"Max. length is 40 characters."),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('mixin', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>