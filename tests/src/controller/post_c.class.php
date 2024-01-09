<?php

class Post_C extends Xorc_Controller{

	function index(){
		$post=new Post;
		$this->list=$post->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$post=new Post($this->r['id']);
		$this->form->set($post->get());
	}
	
	function save(){	
		$post=new Post($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$post->set($this->form->get());
			$post->save();
			flash("OK. Post was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$post=new Post($this->r['id']);
			$post->delete();
			flash("OK. Post was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"author_id"=>array('type'=>'text', 'display'=>"author_id"),
			"title"=>array('type'=>'textarea', 'display'=>"title", 'valid_max'=>255, 'valid_max_e'=>"TMax. length is 255 characters.",
		'extra'=>'rows=2'),
			"body"=>array('type'=>'textarea', 'display'=>"body"),
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
		
		$this->form=new XorcForm('post', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>