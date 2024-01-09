<?php

class Comment_C extends Xorc_Controller{

	function index(){
		$comment=new Comment;
		$this->list=$comment->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$comment=new Comment($this->r['id']);
		$this->form->set($comment->get());
	}
	
	function save(){	
		$comment=new Comment($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$comment->set($this->form->get());
			$comment->save();
			flash("OK. Comment was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$comment=new Comment($this->r['id']);
			$comment->delete();
			flash("OK. Comment was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"post_id"=>array('type'=>'text', 'display'=>"post_id"),
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
		
		$this->form=new XorcForm('comment', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>