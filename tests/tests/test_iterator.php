<?php
function test_filter_id($o){
   return $o->id()%2;
}

class TestIterator extends UnitTestCase{
   
   function __construct(){
      $f=new XorcStore_Fixtures(XorcStore_Connector::get(), dirname(__FILE__)."/fixtures");
      $f->load("companies", "topics", "entrants", "developers", "developers_projects", "posts", 
         "accounts", "computers");
      $f->db->debug=false;
      $f->empty_db();
      $f->load_db();
      $f->db->debug=true;
      $this->f=$f->fixtures;
   }

   function test_count(){
      $ts = new Topic;
      $t=$ts->find();
      $this->assertTrue(count($t)>1);
      $this->assertEqual(count($this->f['topics']), count($t));

   }
   
   function test_array_returned(){
      $ts = new Topic;
      $t=$ts->find();
      $this->assertEqual($this->f['topics']['second']['title'], $t[1]->title);
      
      # print_r($t->to_array());
      $m=array_filter($t->to_array(), 'test_filter_id');
      $this->assertEqual(1, sizeof($m));

   }
   
   function test_iterator_objects_are_references(){
      $ts = new Topic;
      $t=$ts->find();
      
      $m2=$t->to_array();
      
      $this->assertEqual($this->f['topics']['first']['title'], $t->first()->title);
      $this->assertEqual($t[0]->title, $m2[0]->title);
      $this->assertEqual($t->first()->title, $m2[0]->title);
      
      $m2[0]->title="wow!";
      $this->assertNotEqual($this->f['topics']['first']['title'], $t->first()->title);
      $this->assertEqual($t[0]->title, $m2[0]->title);
      $this->assertEqual($t->first()->title, $m2[0]->title);
   }
} 
?>