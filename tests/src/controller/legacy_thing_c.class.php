<?php

class Legacy_thing_C extends Xorc_Controller{

	function index(){
		$legacy_thing=new Legacy_thing;
		$this->list=$legacy_thing->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$legacy_thing=new Legacy_thing($this->r['id']);
		$this->form->set($legacy_thing->get());
	}
	
	function save(){	
		$legacy_thing=new Legacy_thing($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$legacy_thing->set($this->form->get());
			$legacy_thing->save();
			flash("OK. Legacy_thing was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$legacy_thing=new Legacy_thing($this->r['id']);
			$legacy_thing->delete();
			flash("OK. Legacy_thing was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"tps_report_number"=>array('type'=>'text', 'display'=>"tps_report_number"),
			"version"=>array('type'=>'text', 'display'=>"version"),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('legacy_thing', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>