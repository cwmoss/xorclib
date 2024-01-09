<?php

class <controller-name> extends Xorc_Controller{

	function index(){
		$<model-var>=new <model-name>;
		$this->list=$<model-var>->select();
	}
	
	function create(){	
		$this->foreward('edit');
	}
	
	function edit(){		
		$<model-var>=new <model-name>($this->r['id']);
		$this->form->set($<model-var>->get());
	}
	
	function save(){	
		$<model-var>=new <model-name>($this->r['id']);
		
		if($this->form->action("delete"))
			return $this->delete();
			
		if($this->form->validate() && $this->form->validateMandatory()){
			$<model-var>->set($this->form->get());
			$<model-var>->save();
			flash("OK. <model-name> was saved.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		if($this->form->is_confirmed("delete")){
			$<model-var>=new <model-name>($this->r['id']);
			$<model-var>->delete();
			flash("OK. <model-name> was deleted.");
			$this->redirect("index");
		}
		return "edit";
	}
	
	function _init($action="save"){
		/* we define the fields in our form */
		$el=array(
	<loop form-elements>
		"<name>"=>array(<definition>),
	</loop>
		);
		
		$button=array(
			"reset"=>array("type"=>"reset", "display"=>"reset"),
			"save"=>array("type"=>"submit", "display"=>"save"),
			"delete"=>array("type"=>"submit", "display"=>"delete"),
		);
			
		if($action=="create") unset($button["delete"]);
		
		$this->form=new XorcForm('<model-var>', array_merge($el, $button), array("action"=>url("save")));
		$this->form->register_confirm("delete", "Do you really want to delete this entry?");
		$this->form->add_group(array_keys($button));
	}
}

?>