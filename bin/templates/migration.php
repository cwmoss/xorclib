<?php

class Migration_<migration-title> extends XorcStore_Migration{
   
   
   function up(){
      $this->create_table("its_just_a_sample", "
         id I AUTO KEY,
         name c(64),
         no c(12) NOTNULL,
         created_at T NOTNULL,
         modified_at T
         ");
   }
   
   function down(){
      $this->drop_table("its_just_a_sample");
   }
   
}

?>