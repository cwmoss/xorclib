<?php

class Topic_C extends Xorc_Controller{

	function index(){
		$topic=new Topic;
		$this->list=$topic->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$topic=new Topic($this->r['id']);
		$this->form->set($topic->get());
	}
	
	function save(){	
		$topic=new Topic($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$topic->set($this->form->get());
			$topic->save();
			flash("OK. Topic was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$topic=new Topic($this->r['id']);
			$topic->delete();
			flash("OK. Topic was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"title"=>array('type'=>'textarea', 'display'=>"title", 'valid_max'=>255, 'valid_max_e'=>"TMax. length is 255 characters.",
		'extra'=>'rows=2'),
			"author_name"=>array('type'=>'textarea', 'display'=>"author_name", 'valid_max'=>255, 'valid_max_e'=>"TMax. length is 255 characters.",
		'extra'=>'rows=2'),
			"author_email_address"=>array('type'=>'textarea', 'display'=>"author_email_address", 'valid_max'=>255, 'valid_max_e'=>"TMax. length is 255 characters.",
		'extra'=>'rows=2'),
			"written_on"=>array('type'=>'date', 'display'=>"written_on", 'format'=>'d-m-Y H:i', 'yearrange'=>'2006-'),
			"bonus_time"=>array('type'=>'date', 'display'=>"bonus_time", 'format'=>'d-m-Y H:i', 'yearrange'=>'2006-'),
			"last_read"=>array('type'=>'date', 'display'=>"last_read", 'format'=>'d-m-Y', 'yearrange'=>'2006-'),
			"content"=>array('type'=>'textarea', 'display'=>"content"),
			"approved"=>array('type'=>'text', 'display'=>"approved"),
			"replies_count"=>array('type'=>'text', 'display'=>"replies_count"),
			"parent_id"=>array('type'=>'text', 'display'=>"parent_id"),
			"type"=>array('type'=>'text', 'display'=>"type", 'valid_max'=>50, 'valid_max_e'=>"Max. length is 50 characters."),
			"id"=>array('type'=>'hidden'),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('topic', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>