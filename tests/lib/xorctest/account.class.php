<?php

class Account extends XorcStore_AR{
	function belongs_to(){return array(
	   'firm' => array('class'=>'firm', 'fkey'=>'firm_id')
	   );
	}
}

?>