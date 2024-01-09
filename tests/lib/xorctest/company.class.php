<?php

class Company extends XorcStore_AR{
	
	#function has_one(){return array("friend"=>array("fkey"=>"friend_id"));}
	
	function before_destroy(){

	   if($this->firm){
	      $_GLOBALS['cd'][$this->firm->id]=$this->id;
	   }
	}
	
	/*
	[relations]
   has_many=clients
   clients.class=client
   belongs_to=company
   firm.class=firm
   company.fkey=client_of
   
   */
}

?>