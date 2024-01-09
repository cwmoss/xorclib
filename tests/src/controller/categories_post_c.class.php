<?php

class Categories_post_C extends Xorc_Controller{

	function index(){
		$categories_post=new Categories_post;
		$this->list=$categories_post->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$categories_post=new Categories_post($this->r['id']);
		$this->form->set($categories_post->get());
	}
	
	function save(){	
		$categories_post=new Categories_post($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$categories_post->set($this->form->get());
			$categories_post->save();
			flash("OK. Categories_post was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$categories_post=new Categories_post($this->r['id']);
			$categories_post->delete();
			flash("OK. Categories_post was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
			"category_id"=>array('type'=>'text', 'display'=>"category_id"),
			"post_id"=>array('type'=>'text', 'display'=>"post_id"),
	
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('categories_post', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>