<?php

class Project_C extends Xorc_Controller{

	function index(){
		$project=new Project;
		$this->list=$project->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$project=new Project($this->r['id']);
		$this->form->set($project->get());
	}
	
	function save(){	
		$project=new Project($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$project->set($this->form->get());
			$project->save();
			flash("OK. Project was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$project=new Project($this->r['id']);
			$project->delete();
			flash("OK. Project was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"name"=>array('type'=>'text', 'display'=>"name", 'valid_max'=>100, 'valid_max_e'=>"Max. length is 100 characters."),
			"type"=>array('type'=>'textarea', 'display'=>"type", 'valid_max'=>255, 'valid_max_e'=>"TMax. length is 255 characters.",
		'extra'=>'rows=2'),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('project', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>