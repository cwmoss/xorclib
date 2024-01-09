<?php

class TestFinder extends UnitTestCase{
   
   function __construct(){
      $f=new XorcStore_Fixtures(XorcStore_Connector::get(), dirname(__FILE__)."/fixtures");
      $f->load("companies", "topics", "entrants", "developers", "developers_projects", "posts", "accounts");
      $f->db->debug=false;
      $f->empty_db();
      $f->load_db();
      $f->db->debug=true;
      $this->f=$f->fixtures;
   }

   function test_find(){
      $t=new Topic;
      // $x=$t->find(1);
      // var_dump($x);
      $this->assertEqual($this->f['topics']['first']['title'], $t->find(1)->title);
   }

   function test_exists(){
      $t=new Topic;
      $this->assertTrue($t->exists(1));
      var_dump($t->exists(45));
      $this->assertFalse($t->exists(45));
      $this->assertFalse($t->exists('foo'));
      $this->assertFalse($t->exists(array(2,3)));
   }


   function test_find_by_array_of_one_id(){
      $t=new Topic;

   //   var_dump($t->find(array(1)));
    //  $this->assertIsA("object", $t->find(array(1)));
      $this->assertEqual(1, $t->find(array(1))->total_rows());
      $this->assertEqual(1, count($t->find(array(1))));
   }


   function test_find_by_ids(){
      $t=new Topic;
      # find(1,2);
      $this->assertEqual(2, $t->find(array(1,2))->total_rows());
      $this->assertEqual(2, sizeof($t->find(array(1,2))));
      $this->assertEqual($this->f['topics']['second']['title'], $t->find(array( 2 ))->first()->title);
   }


   function test_find_an_empty_array(){
      $t=new Topic;
      $this->assertEqual(array(), $t->find(array()));
   }

   function test_find_by_ids_missing_one(){
      $t=new Topic;
      #assert_raises(ActiveRecord::RecordNotFound) {
        # var_dump($t->find(array(1, 2, 45)));
      #}
   }

}

?>