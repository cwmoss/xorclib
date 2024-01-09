<?php

class Project extends XorcStore_AR{
	function has_many_belongs_to_many(){return array(
	   "developers"=>array('uniq' => true, 'class'=>'developer', 'fkey'=>'developer_id',
	      'myfkey'=>'project_id', 'join_table' => 'developers_projects')
	      );
	}
}

?>