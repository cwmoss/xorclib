<?php

class <model-name> extends XorcStore_AR{
	
	
   public function define_schema(){
      return array('table'=>'<table-name>');
   }
	
	static function i(){return new self;}
}

?>