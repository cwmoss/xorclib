<?php

class admin_controller extends <appname>_controller{
   
   public $layout='admin';
   public $require_auth=true;
   
   function _check_sanity(){
      /*
         ein paar html codes sind hier, im admin bereich erlaubt
      */
		array_walk_recursive($this->r, "do_clean_input_wl");
		
		# anti-clickjacking header
		header("X-Frame-Options: SAMEORIGIN");
   }
   
}