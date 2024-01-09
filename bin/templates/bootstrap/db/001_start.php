<?php

class Migration_start extends XorcStore_Migration{
   
   
   function up(){
      
      $this->create_table("users", "
         id I AUTO KEY,
			contest_id I NOTNULL,
         email c(128) NOTNULL,
         uname c(32) NOTNULL,
         passwd c(64) NOTNULL,
         status I NOTNULL,
         role c(4),
         
         title I1,
         fname c(128),
         lname c(128),
         street c(128),
         number c(8),
         postalcode c(8),
         city c(128),
         country c(2),
         age I,
         toc I1,
         
         token c(64),
         token_expires_at T,
         
         confirmed_at T,
         login_count I,
         login_from c(32),
         login_at T,
         created_at T NOTNULL,
         modified_at T
         ");
      $this->create_index('users', 'email');
      
   }
   
   function down(){
      $this->drop_table("users");
   }
   
}

?>