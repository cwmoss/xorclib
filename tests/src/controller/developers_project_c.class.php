<?php

class Developers_project_C extends Xorc_Controller{

	function index(){
		$developers_project=new Developers_project;
		$this->list=$developers_project->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$developers_project=new Developers_project($this->r['id']);
		$this->form->set($developers_project->get());
	}
	
	function save(){	
		$developers_project=new Developers_project($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$developers_project->set($this->form->get());
			$developers_project->save();
			flash("OK. Developers_project was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$developers_project=new Developers_project($this->r['id']);
			$developers_project->delete();
			flash("OK. Developers_project was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"developer_id"=>array('type'=>'text', 'display'=>"developer_id"),
			"project_id"=>array('type'=>'text', 'display'=>"project_id"),
			"joined_on"=>array('type'=>'date', 'display'=>"joined_on", 'format'=>'d-m-Y', 'yearrange'=>'2006-'),
			"access_level"=>array('type'=>'text', 'display'=>"access_level"),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('developers_project', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>