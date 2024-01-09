<?php

class <appname>_controller extends xorc_controller{
   
   public $contest;

   public $bodyclass = "page";
   
   public $require_auth=array('optional'=>1);
   
   public $before_filter=array(
         '_check_sanity',
   #      '_load_contest',
   	   "_more_before_filter",
   #	   "_filter_initial_actions"
   	   );
   	   
   function _load_contest(){
      $c = $_SERVER['PHOCON_ID'];
		
   #   $this->contest = contest::lookup($c);
   #   xorcapp::$inst->contest = $this->contest;
      $this->title = $this->contest->title;
      
      // gelöscht?
      if(false && $this->contest->status==7 && xorcapp::$inst->req->path!='contest/not_found'){
         $this->redirect("contest/not_found");
      }

		$theme = $_SERVER['<app-name-uc>_THEME'];
		if($theme) $this->theme($theme);

   #   Validator::load(the_contest()->validation_file);
	}
	
	function _check_sanity(){
      # clean up bad input
		#if($_GET) array_walk_recursive($_GET, "do_clean_input");
		#if($_POST) array_walk_recursive($_POST, "do_clean_input");
		array_walk_recursive($this->r, "do_clean_input");
		
		# anti-clickjacking header
		header("X-Frame-Options: SAMEORIGIN");
   }
   
	function _more_before_filter(){}
}

