<?php

class Topic extends XorcStore_AR{
   
   var $title_confirmation=null;
   var $terms_of_service=null;
   var $terms_of_service_agree=null;
   var $eula=null;
   
   
	function has_many(){ return array(
	   "replies"=>array('dependent' => 'destroy', 'fkey' => "parent_id", 'class'=>'reply')
	   );
	}
	
	function validates_confirmation_of(){
	   return array("title");
	}
	
	function validates_acceptance_of(){
	   return array(
	      "terms_of_service"=>array("on"=>"create"),
	      "terms_of_service_agree"=>array("on"=>"create", "accept"=>"I agree->"),
	      'eula'=>array('msg' => "must be abided", 'on' => 'create')
	   );
	}
	
	
}

?>