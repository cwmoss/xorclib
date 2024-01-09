<?php

class Reader_C extends Xorc_Controller{

	function index(){
		$reader=new Reader;
		$this->list=$reader->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$reader=new Reader($this->r['id']);
		$this->form->set($reader->get());
	}
	
	function save(){	
		$reader=new Reader($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$reader->set($this->form->get());
			$reader->save();
			flash("OK. Reader was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$reader=new Reader($this->r['id']);
			$reader->delete();
			flash("OK. Reader was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"post_id"=>array('type'=>'text', 'display'=>"post_id"),
			"person_id"=>array('type'=>'text', 'display'=>"person_id"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('reader', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>