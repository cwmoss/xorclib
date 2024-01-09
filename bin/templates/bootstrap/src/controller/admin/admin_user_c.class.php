<?php

class admin_User_C extends admin_controller{

	// user create mode
	public $create=false;
	
	function index(){
	   $limit = 50;
	   $page = $this->r['page'];
	   if(!$page) $page=1;
		$user=new user;
		if($this->r['q']){
			$this->items=$user->search($this->r['q']);
			$this->q = $this->r['q'];
		}else{
			$this->items=$user->find_all(array("conditions"=>array(),
				"order"=>"created_at DESC", 'limit'=>$limit, 'page'=>$page));
		}
		$this->pager = $this->items->pager();
	}
	
	function create(){	
		$this->euser=new User;
		$this->euser->country='de';
		$this->create=true;
	}
	
	function edit(){		
		$this->euser=User::i()->find($this->r['id']);
		if(!$this->euser) throw new XorcRuntimeException("Nicht gefunden.", array("header"=>404));
	}
	
	function save(){
		if($id=$this->r['id']){
			$this->euser=User::i()->find($this->r['id']);
			if(!$this->euser) throw new XorcRuntimeException("Nicht gefunden.", array("header"=>404));
		}else{
			// create new object
			$this->create=true;
			$this->euser=new User;
		}	
		$this->euser->set($this->r['user']);
		$this->euser->contest_id = $this->contest->id;
		if($this->euser->save()){
			# passwort nur beim anlegen
			if($this->create) $this->euser->password_update($this->r['user']['passwd']);
			
			flash("OK. User wurde gespeichert.");	
			$this->redirect("index");
		}
		return "edit";
	}
	
	function delete(){
		$this->euser=User::i()->find($this->r['id']);
		if(!$this->euser) throw new XorcRuntimeException("Nicht gefunden.", array("header"=>404));
		$this->euser->destroy();
		flash("OK. User wurde gelöscht.");
		$this->redirect("index");
	}
	
}

?>