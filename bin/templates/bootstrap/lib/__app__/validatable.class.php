<?php

trait validatable{
   
   function validatable_init_validator(){
      return validator::init($this);
   }
   
   function validatable_fields($ev='save'){
      return $this->validatable_fields_default($ev);
   }
   
   function validatable_fields_default($ev='save'){
      return $this->attribute_names();
   }
   
   function validatable_validate($ev='save'){
      $fields = $this->validatable_fields($ev);
      # $this->errors->clear();
      $v = $this->validatable_init_validator();
      $v->validate($fields, $ev);
   }
}