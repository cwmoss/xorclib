<?php

class Developer extends XorcStore_AR{
   
   
   function get_salary(){
      if(!$this->prop['salary']) return 100000;
      return $this->prop['salary'];
   }
   
	function has_many_belongs_to_many(){return array(
	   'projects'=>array(
	      'class'=>'project', 'fkey'=>'project_id',
   	      'myfkey'=>'developer_id',
   	      'join_table' => 'developers_projects'
	      )
	   );
	}
	
	function validates_inclusion_of(){
	   return array(
	      'salary'=>array('between' => array(50000, 200000), 'allow_null'=>true)
	   );
	}
	
   function validates_length_of(){
      return array(
         'name'=> array('between' => array(3,20))
         );
   }
}

?>