<?php
// legacy yaml support
// yaml ext laden muss jetzt im mutterscript erfolgen
// dl('syck.so');

class xorcstore_fixtures{
   var $files;
   var $fixtures;
	var $mode; // json, yml
   var $db;
   
   function __construct($db=null, $dir, $mode='json'){
      $this->db=$db;
      $this->files=$dir;
		$this->mode=$mode;
   }
   
   function load(){
      foreach(func_get_args() as $tab){
			$m="load_{$this->mode}";
			$file="$this->files/{$tab}.{$this->mode}";
         $this->fixtures[$tab]=$this->$m($file);
      }
   }
   
   function load_db(){
      foreach($this->fixtures as $tab=>$f){
         foreach($f as $fields){
            $this->db->AutoExecute($this->table($tab), $fields, 'INSERT');
         }
      }
   }
   
   function unload_db(){
      
   }
   
   function empty_db(){
      foreach($this->fixtures as $tab=>$f){
         $this->db->Execute("DELETE FROM ".$this->table($tab));
      }
   }
   
	function load_json($file){
		return json_decode(file_get_contents($file));
	}
	
   function load_yml($file){
      # Xorc::use_yaml();
      # $data=file_get_contents($file)
      ob_start();include($file);$data=ob_get_clean();
      # print $data; 
      $fix=syck_load($data);
      # Spyc::YAMLLoad($data);
      return $fix;
   }
   
   function table($tab){
      if($this->db && $this->db->prefix){
         return $this->db->prefix."_".$tab;
      }else{
         return $tab;
      }
   }
}

?>