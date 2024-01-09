<?php

class Task_C extends Xorc_Controller{

	function index(){
		$task=new Task;
		$this->list=$task->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$task=new Task($this->r['id']);
		$this->form->set($task->get());
	}
	
	function save(){	
		$task=new Task($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$task->set($this->form->get());
			$task->save();
			flash("OK. Task was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$task=new Task($this->r['id']);
			$task->delete();
			flash("OK. Task was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"starting"=>array('type'=>'date', 'display'=>"starting", 'format'=>'d-m-Y H:i', 'yearrange'=>'2006-'),
			"ending"=>array('type'=>'date', 'display'=>"ending", 'format'=>'d-m-Y H:i', 'yearrange'=>'2006-'),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('task', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>