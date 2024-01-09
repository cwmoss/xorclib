<?php
include_once("xorctest/diverse.class.php");

class TestBase extends UnitTestCase{
   
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

   function test_set_attributes(){
      $ts = new Topic;
      $t=$ts->find(1);
      $t->set(array("title" => "Budget", "author_name" => "Jason" ));
      $t->save();
      $this->assertEqual("Budget", $t->title);
      $this->assertEqual("Jason", $t->author_name);
  #   print_r($ts->find(1));
      $this->assertEqual($this->f['topics']['first']['author_email_address'],
         $ts->find(1)->author_email_address);
   } 

/*
   function test_integers_as_nil(){
     test = AutoId.create('value' => '')
     assert_nil AutoId.find(test.id).value
   } 

   function test_set_attributes_with_block(){
     topic = Topic.new do |t|
       t.title       = "Budget"
       t.author_name = "Jason"
     } 

     assert_equal("Budget", topic.title)
     assert_equal("Jason", topic.author_name)
   } 
*/
/*
   function test_respond_to?(){
     topic = Topic.find(1)
     assert topic.respond_to?("title")
     assert topic.respond_to?("title?")
     assert topic.respond_to?("title=")
     assert topic.respond_to?(:title)
     assert topic.respond_to?(:title?)
     assert topic.respond_to?(:title=)
     assert topic.respond_to?("author_name")
     assert topic.respond_to?("attribute_names")
     assert !topic.respond_to?("nothingness")
     assert !topic.respond_to?(:nothingness)
   } 

   function test_array_content(){
     topic = Topic.new
     topic.content = %w( one two three )
     topic.save

     assert_equal(%w( one two three ), Topic.find(topic.id).content)
   } 

   function test_hash_content(){
     topic = Topic.new
     topic.content = { "one" => 1, "two" => 2 }
     topic.save

     assert_equal 2, Topic.find(topic.id).content["two"]

     topic.content["three"] = 3
     topic.save

     assert_equal 3, Topic.find(topic.id).content["three"]
   } 

   function test_update_array_content(){
     topic = Topic.new
     topic.content = %w( one two three )

     topic.content.push "four"
     assert_equal(%w( one two three four ), topic.content)

     topic.save

     topic = Topic.find(topic.id)
     topic.content << "five"
     assert_equal(%w( one two three four five ), topic.content)
   } 
*/
   function test_case_sensitive_attributes_hash(){
     # DB2 is not case-sensitive
     # return true if current_adapter?(:DB2Adapter)
      $c=new Computer;
   #   print_r($this->f['computers']['workstation']);
     # var_dump($c->find_first());
      $this->assertEqual($this->f['computers']['workstation'], $c->find_first()->get());
   } 

   function test_create(){
     $t = new Topic;
     $t->title = "New Topic";
     $t->save();
     $tr = $t->find($t->id);
     $this->assertEqual("New Topic", $tr->title);
   } 

   function test_save(){
     $topic = new Topic;
     $topic->set(array('title' => "New Topic"));
     $this->assertTrue($topic->save());
   } 
   

   function test_hashes_not_mangled(){
      $t = new Topic;
      $new_topic = array('title' => "New Topic" );
      $new_topic_values = array('title' => "AnotherTopic" );

      $topic = new Topic($new_topic);
      // $topic->set($new_topic);
      // print_r($topic);
      $this->assertEqual($new_topic['title'], $topic->title);

      $topic->set($new_topic_values);
      $this->assertEqual($new_topic_values['title'], $topic->title);
   } 

   function test_create_many(){
      $t = new Topic;
      $topics = $t->create(array( array( "title" => "first" ), array( "title" => "second" )));
      $this->assertEqual(2, count($topics));
      $this->assertEqual( "first", $topics[0]->title );
   } 

   function test_create_columns_not_equal_attributes(){
     $topic = new Topic;
     $topic->title = 'Another New Topic';
     $topic->does_not_exist='test';
     $this->assertNoErrors($topic->save());
   } 

   function test_create_through_factory(){
      $t = new Topic;
      $topic = $t->create(array("title" => "New Topic"));
      $topicReloaded = $t->find($topic->id);
      $this->assertEqual($topic->get(), $topicReloaded->get());
   } 

   function test_update(){
      $t = new Topic;
     $topic = new Topic;
     $topic->title = "Another New Topic";
     $topic->written_on = "2003-12-12 23:23:00";
     $topic->save();
     $topicReloaded = $t->find($topic->id);
     $this->assertEqual("Another New Topic", $topicReloaded->title);

     $topicReloaded->title = "Updated topic";
     $topicReloaded->save();

     $topicReloadedAgain = $t->find($topic->id);

     $this->assertEqual("Updated topic", $topicReloadedAgain->title);
   } 

   function test_update_columns_not_equal_attributes(){
      $t = new Topic;
     $topic = new Topic;
     $topic->title = "Still another topic";
     $topic->save();

     $topicReloaded = $t->find($topic->id);
     $topicReloaded->title = "A New Topic";
      $topicReloaded->does_not_exist='test';
     $this->assertNoErrors($topicReloaded->save());
   } 

   function test_write_attribute(){
     $topic = new Topic;
     $topic->title = "Still another topic";
     $this->assertEqual( "Still another topic", $topic->title);

     $topic->title ="Still another topic: part 2";
     $this->assertEqual("Still another topic: part 2", $topic->title);
   } 

   function test_read_attribute(){
     $topic = new Topic;
     $topic->title = "Don't change the topic";
     $this->assertEqual( "Don't change the topic", $topic->title);

   } 

   function test_read_attribute_when_false(){
     $topic = new Topic;
     $topic = $topic->find_first();
     $topic->approved = false;
     $this->assertFalse($topic->approved, "approved should be false");

   } 

   function test_read_attribute_when_true(){
   $topic = new Topic;
   $topic = $topic->find_first();
   $topic->approved = true;
   $this->assertTrue($topic.approved, "approved should be true");
   } 

/*
   function test_read_write_boolean_attribute
     topic = Topic.new
     # puts ""
     # puts "New Topic"
     # puts topic.inspect
     topic.approved = "false"
     # puts "Expecting false"
     # puts topic.inspect
     assert !topic.approved?, "approved should be false"
     topic.approved = "false"
     # puts "Expecting false"
     # puts topic.inspect
     assert !topic.approved?, "approved should be false"
     topic.approved = "true"
     # puts "Expecting true"
     # puts topic.inspect
     assert topic.approved?, "approved should be true"
     topic.approved = "true"
     # puts "Expecting true"
     # puts topic.inspect
     assert topic.approved?, "approved should be true"
     # puts ""
   } 

   function test_reader_generation
     Topic.find(:first).title
     Firm.find(:first).name
     Client.find(:first).name
     if ActiveRecord::Base.generate_read_methods
       assert_readers(Topic,  %w(type replies_count))
       assert_readers(Firm,   %w(type))
       assert_readers(Client, %w(type ruby_type rating?))
     else
       [Topic, Firm, Client].each {|klass| assert_equal klass.read_methods, {}}
     } 
   } 

   function test_reader_for_invalid_column_names
     # column names which aren't legal ruby ids
     topic = Topic.find(:first)
     topic.s} (:define_read_method, "mumub-jumbo".to_sym, "mumub-jumbo", nil)
     assert !Topic.read_methods.include?("mumub-jumbo")
   } 

   function test_non_attribute_access_and_assignment
     topic = Topic.new
     assert !topic.respond_to?("mumbo")
     assert_raises(NoMethodError) { topic.mumbo }
     assert_raises(NoMethodError) { topic.mumbo = 5 }
   } 

   function test_preserving_date_objects
     # SQL Server doesn't have a separate column type just for dates, so all are returned as time
     return true if current_adapter?(:SQLServerAdapter)

     if current_adapter?(:SybaseAdapter)
       # Sybase ctlib does not (yet?) support the date type; use datetime instead.
       assert_kind_of(
         Time, Topic.find(1).last_read, 
         "The last_read attribute should be of the Time class"
       )
     else
       assert_kind_of(
         Date, Topic.find(1).last_read, 
         "The last_read attribute should be of the Date class"
       )
     } 
   } 

   function test_preserving_time_objects
     assert_kind_of(
       Time, Topic.find(1).bonus_time,
       "The bonus_time attribute should be of the Time class"
     )

     assert_kind_of(
       Time, Topic.find(1).written_on,
       "The written_on attribute should be of the Time class"
     )
   } 
*/
   function test_destroy(){
     $topic = new Topic;
     $topic->title = "Yet Another New Topic";
     $topic->written_on = "2003-12-12 23:23:00";
     $topic->save();
     $topic->destroy();
#     assert_raise(ActiveRecord::RecordNotFound) { Topic.find(topic.id) }
      $t=$topic->find($topic->id);
#      $this->assertWantedPattern("/not found/i", $t->error);
      $this->assertFalse($t);
   } 


   function test_destroy_returns_self(){
     $topic = new Topic(array("title" => "Yet Another Title"));
     $this->assertTrue($topic->save());
     $destr=$topic->destroy();
     $this->assertNoErrors($topic->get(), $destr->get(), "destroy did not return destroyed object");
     $this->assertEqual($topic->get(), $destr->get(), "destroy did not return destroyed object");
   } 

   function test_record_not_found_exception(){
      $t = new Topic;
      $this->assertFalse($t->find(99999));
   #  assert_raises(ActiveRecord::RecordNotFound) { topicReloaded = Topic.find(99999) }
      $t = new Topic(99999);
      print_r($t);
      $this->assertWantedPattern("/not found/i", $t->error);
   } 

   function test_initialize_with_attributes(){
     $topic = new Topic(array( 
       "title" => "initialized from attributes", "written_on" => "2003-12-12 23:23"
     ));

     $this->assertEqual("initialized from attributes", $topic->title);
   } 

/*
   function test_initialize_with_invalid_attribute(){
     begin
       topic = Topic.new({ "title" => "test", 
         "last_read(1i)" => "2005", "last_read(2i)" => "2", "last_read(3i)" => "31"})
     rescue ActiveRecord::MultiparameterAssignmentErrors => ex
       assert_equal(1, ex.errors.size)
       assert_equal("last_read", ex.errors[0].attribute)
     } 
   } 
*/

   function test_load(){
      $this->__construct(); //clear
      $t=new Topic;
     $topics = $t->find(array('order' => 'id'));    
     $this->assertEqual(2, count($topics));
     $this->assertEqual($this->f['topics']['first']['title'], $topics->first()->title);
     
     $topics = $t->find_all(array('order' => 'id'));    
       $this->assertEqual(2, count($topics));
       $this->assertEqual($this->f['topics']['first']['title'], $topics->first()->title);
   } 

   function test_load_with_condition(){
      $t=new Topic;
     $topics = $t->find(array('conditions' => array("author_name = 'Mary'")));

     $this->assertEqual(1, count($topics));
     $this->assertEqual($this->f['topics']['second']['title'], $topics->first()->title);
   } 
/*
   function test_table_name_guesses(){
     assert_equal "topics", Topic.table_name

     assert_equal "categories", Category.table_name
     assert_equal "smarts", Smarts.table_name
     assert_equal "credit_cards", CreditCard.table_name
     assert_equal "master_credit_cards", MasterCreditCard.table_name

     ActiveRecord::Base.pluralize_table_names = false
     [Category, Smarts, CreditCard, MasterCreditCard].each{|c| c.reset_table_name}
     assert_equal "category", Category.table_name
     assert_equal "smarts", Smarts.table_name
     assert_equal "credit_card", CreditCard.table_name
     assert_equal "master_credit_card", MasterCreditCard.table_name
     ActiveRecord::Base.pluralize_table_names = true
     [Category, Smarts, CreditCard, MasterCreditCard].each{|c| c.reset_table_name}

     ActiveRecord::Base.table_name_prefix = "test_"
     Category.reset_table_name
     assert_equal "test_categories", Category.table_name
     ActiveRecord::Base.table_name_suffix = "_test"
     Category.reset_table_name
     assert_equal "test_categories_test", Category.table_name
     ActiveRecord::Base.table_name_prefix = ""
     Category.reset_table_name
     assert_equal "categories_test", Category.table_name
     ActiveRecord::Base.table_name_suffix = ""
     Category.reset_table_name
     assert_equal "categories", Category.table_name

     ActiveRecord::Base.pluralize_table_names = false
     ActiveRecord::Base.table_name_prefix = "test_"
     Category.reset_table_name
     assert_equal "test_category", Category.table_name
     ActiveRecord::Base.table_name_suffix = "_test"
     Category.reset_table_name
     assert_equal "test_category_test", Category.table_name
     ActiveRecord::Base.table_name_prefix = ""
     Category.reset_table_name
     assert_equal "category_test", Category.table_name
     ActiveRecord::Base.table_name_suffix = ""
     Category.reset_table_name
     assert_equal "category", Category.table_name
     ActiveRecord::Base.pluralize_table_names = true
     [Category, Smarts, CreditCard, MasterCreditCard].each{|c| c.reset_table_name}
   } 
*/

   function test_destroy_all(){
      $t=new Topic;
      $this->assertEqual(2, count($t));

      $t->destroy_all("author_name = 'Mary'");
      $this->assertEqual(1, count($t));
   } 

   function test_destroy_many(){
      $c=new Client;
      $this->assertEqual(3, count($c));
      $c->destroy(array(2, 3));
      $this->assertEqual(1, count($c));
   } 

   function test_delete_many(){
      $this->__construct();
      $t=new Topic;
      $t->delete(array(1, 2));
      $this->assertEqual(0, count($t));
   } 

   function test_boolean_attributes(){
      $this->__construct();
      $t=new Topic;
      $this->assertFalse($t->find(1)->approved);
      $this->assertTrue($t->find(2)->approved);
   } 

/*
   function test_increment_counter(){
     Topic.increment_counter("replies_count", 1)
     assert_equal 1, Topic.find(1).replies_count

     Topic.increment_counter("replies_count", 1)
     assert_equal 2, Topic.find(1).replies_count
   } 

   function test_decrement_counter(){
     Topic.decrement_counter("replies_count", 2)
     assert_equal 1, Topic.find(2).replies_count

     Topic.decrement_counter("replies_count", 2)
     assert_equal 0, Topic.find(1).replies_count
   } 
*/
   function test_update_all(){
     # The ADO library doesn't support the number of affected rows
  #   return true if current_adapter?(:SQLServerAdapter)
      $t=new Topic;
     $this->assertEqual(2, $t->update_all("content = 'bulk updated!'"));
     $this->assertEqual("bulk updated!", $t->find(1)->content);
     $this->assertEqual("bulk updated!", $t->find(2)->content);
     $this->assertEqual(2, $t->update_all(array('content'=>'bulk updated again!')));
     $this->assertEqual("bulk updated again!", $t->find(1)->content);
     $this->assertEqual("bulk updated again!", $t->find(2)->content);
   } 

   function test_update_many(){
      $t=new Topic;
     $topic_data = array( 1 => array("content" => "1 updated"), 2 => array( "content" => "2 updated" ) );
     $updated = $t->update($topic_data);

     $this->assertEqual(2, count($updated));
     $this->assertEqual("1 updated", $t->find(1)->content);
     $this->assertEqual( "2 updated", $t->find(2)->content);
     
     
     $topic_data = array( 1 => array("content" => "1 ++ updated"),
         2 => array( "content" => "2 ++ updated" ),
         555 => array( "content" => "555 not found ++ updated" ));
     $updated = $t->update($topic_data);

     $this->assertEqual(2, count($updated));
   } 

   function test_delete_all(){
     # The ADO library doesn't support the number of affected rows
     # return true if current_adapter?(:SQLServerAdapter)
     $t=new Topic;
     $this->assertEqual(2, $t->delete_all());
   } 

   function test_update_by_condition(){
      $this->__construct();
      $t=new Topic;
     $total=$t->update_all("content = 'bulk updated!'", "approved = 1");
     // print_r($t->find_by_id(1));
     $this->assertEqual(1, $total);
     $this->assertEqual( "Have a nice day", $t->find(1)->content);
     $this->assertEqual( "bulk updated!", $t->find(2)->content);
   } 


   function test_attribute_present(){
     $t = new Topic;
     $t->title = "hello there!";
     $t->written_on = date("Y-m-d H:i:s");
     $this->assertTrue($t->attribute_present("title"));
     $this->assertTrue($t->attribute_present("written_on"));
     $this->assertFalse($t->attribute_present("content"));
   } 

   function test_attribute_keys_on_new_instance(){
     $t = new Topic;
     $this->assertEqual(null, $t->title, "The topics table has a title column, so it should be nil");
#     assert_raise(NoMethodError) { t.title2 }
   } 

/*
   function test_class_name(){
     assert_equal "Firm", ActiveRecord::Base.class_name("firms")
     assert_equal "Category", ActiveRecord::Base.class_name("categories")
     assert_equal "AccountHolder", ActiveRecord::Base.class_name("account_holder")

     ActiveRecord::Base.pluralize_table_names = false
     assert_equal "Firms", ActiveRecord::Base.class_name( "firms" )
     ActiveRecord::Base.pluralize_table_names = true

     ActiveRecord::Base.table_name_prefix = "test_"
     assert_equal "Firm", ActiveRecord::Base.class_name( "test_firms" )
     ActiveRecord::Base.table_name_suffix = "_tests"
     assert_equal "Firm", ActiveRecord::Base.class_name( "test_firms_tests" )
     ActiveRecord::Base.table_name_prefix = ""
     assert_equal "Firm", ActiveRecord::Base.class_name( "firms_tests" )
     ActiveRecord::Base.table_name_suffix = ""
     assert_equal "Firm", ActiveRecord::Base.class_name( "firms" )
   } 
*/
   function test_null_fields(){
      $t = new Topic;
     $this->assertNull($t->find(1)->parent_id);
     $this->assertNull($t->create(array("title" => "Hey you"))->parent_id);
   } 

/*
   function test_default_values(){
     topic = Topic.new
     assert topic.approved?
     assert_nil topic.written_on
     assert_nil topic.bonus_time
     assert_nil topic.last_read

     topic.save

     topic = Topic.find(topic.id)
     assert topic.approved?
     assert_nil topic.last_read

     # Oracle has some funky default handling, so it requires a bit of 
     # extra testing. See ticket #2788.
#     if current_adapter?(:OracleAdapter)
#       test = TestOracleDefault.new
#       assert_equal "X", test.test_char
#       assert_equal "hello", test.test_string
#       assert_equal 3, test.test_int
#     } 
   } 


   function test_utc_as_time_zone(){
     # Oracle and SQLServer do not have a TIME datatype.
     return true if current_adapter?(:SQLServerAdapter) || current_adapter?(:OracleAdapter)

     Topic.default_timezone = :utc
     attributes = { "bonus_time" => "5:42:00AM" }
     topic = Topic.find(1)
     topic.attributes = attributes
     assert_equal Time.utc(2000, 1, 1, 5, 42, 0), topic.bonus_time
     Topic.default_timezone = :local
   } 
*/
   function test_default_values_on_empty_strings(){
     $topic = new Topic;
     $topic->approved  = null;
     $topic->last_read = null;

     $topic->save();

     $topic = $topic->find($topic->id);
     $this->assertNull($topic->last_read);

     # Sybase adapter does not allow nulls in boolean columns
#     if current_adapter?(:SybaseAdapter)
#       assert topic.approved == false
#     else
       $this->assertNull($topic->approved);
#     } 
   } 

/*
   function test_equality(){
     assert_equal Topic.find(1), Topic.find(2).topic
   } 
*/
   function test_equality_of_new_records(){
   # was soll NotEqual hier bedeuten???
     $this->assertEqual(new Topic, new Topic);
   } 

/*
   function test_hashing(){
     assert_equal [ Topic.find(1) ], [ Topic.find(2).topic ] & [ Topic.find(1) ]
   } 

   function test_destroy_new_record(){
     client = Client.new
     client.destroy
     assert client.frozen?
   } 

   function test_destroy_record_with_associations(){
     client = Client.find(3)
     client.destroy
     assert client.frozen?
     assert_kind_of Firm, client.firm
     assert_raises(TypeError) { client.name = "something else" }
   } 
*/
   function test_update_attribute(){
      $t=new Topic;
      $this->assertFalse($t->find(1)->approved);
      $t->find(1)->update_attr("approved", true);
      $this->assertTrue($t->find(1)->approved);
   } 



   function test_mass_assignment_protection(){
     $firm = new Firm;

     $firm->set(array("name" => "Next Angle", "rating" => 5 ));
     
     # TODO defaults!

     $this->assertEqual(null, $firm->rating);
   } 



   function test_customized_primary_key_remains_protected(){
     $subscriber = new Subscriber(array('nick' => 'webster123', 'name' => 'nice try'));
     $this->assertNull($subscriber->id());
     // print_r($subscriber->schema['keys']);

     $keyboard = new Keyboard(array('key_number' => 9, 'name' => 'nice try'));
     $this->assertNull($keyboard->id());
   } 

   function test_customized_primary_key_remains_protected_when_refered_to_as_id(){
      $subscriber = new Subscriber(array('id' => 'webster123', 'name' => 'nice try'));
        $this->assertNull($subscriber->id());

        $keyboard = new Keyboard(array('id' => 9, 'name' => 'nice try'));
        $this->assertNull($keyboard->id());
   } 

   function test_mass_assignment_protection_on_defaults(){
     $firm = new Firm;
     $firm->set(array("id" => 5, "type" => "Client"));
     $this->assertNull( $firm->id);
     $this->assertEqual( "Firm", $firm->type);
   } 

   function test_mass_assignment_accessible(){
     $reply = new Reply(array("title" => "hello", "content" => "world", "approved" => true));
     $reply->save();

     $this->assertTrue($reply->approved);

     $reply->approved = false;
     $reply->save();

     $this->assertFalse($reply->approved);
   } 

/*
   function test_mass_assignment_protection_inheritance(){
     assert_nil LoosePerson.accessible_attributes
     assert_equal [ :credit_rating, :administrator ], LoosePerson.protected_attributes

     assert_nil LooseDesc} ant.accessible_attributes
     assert_equal [ :credit_rating, :administrator, :phone_number  ], LooseDesc} ant.protected_attributes

     assert_nil TightPerson.protected_attributes
     assert_equal [ :name, :address ], TightPerson.accessible_attributes

     assert_nil TightDesc} ant.protected_attributes
     assert_equal [ :name, :address, :phone_number  ], TightDesc} ant.accessible_attributes
   } 

   function test_multiparameter_attributes_on_date(){
     attributes = { "last_read(1i)" => "2004", "last_read(2i)" => "6", "last_read(3i)" => "24" }
     topic = Topic.find(1)
     topic.attributes = attributes
     # note that extra #to_date call allows test to pass for Oracle, which 
     # treats dates/times the same
     assert_date_from_db Date.new(2004, 6, 24), topic.last_read.to_date
   } 

   function test_multiparameter_attributes_on_date_with_empty_date(){
     attributes = { "last_read(1i)" => "2004", "last_read(2i)" => "6", "last_read(3i)" => "" }
     topic = Topic.find(1)
     topic.attributes = attributes
     # note that extra #to_date call allows test to pass for Oracle, which 
     # treats dates/times the same
     assert_date_from_db Date.new(2004, 6, 1), topic.last_read.to_date
   } 

   function test_multiparameter_attributes_on_date_with_all_empty(){
     attributes = { "last_read(1i)" => "", "last_read(2i)" => "", "last_read(3i)" => "" }
     topic = Topic.find(1)
     topic.attributes = attributes
     assert_nil topic.last_read
   } 

   function test_multiparameter_attributes_on_time(){
     attributes = { 
       "written_on(1i)" => "2004", "written_on(2i)" => "6", "written_on(3i)" => "24", 
       "written_on(4i)" => "16", "written_on(5i)" => "24", "written_on(6i)" => "00"
     }
     topic = Topic.find(1)
     topic.attributes = attributes
     assert_equal Time.local(2004, 6, 24, 16, 24, 0), topic.written_on
   } 

   function test_multiparameter_attributes_on_time_with_empty_seconds(){
     attributes = { 
       "written_on(1i)" => "2004", "written_on(2i)" => "6", "written_on(3i)" => "24", 
       "written_on(4i)" => "16", "written_on(5i)" => "24", "written_on(6i)" => ""
     }
     topic = Topic.find(1)
     topic.attributes = attributes
     assert_equal Time.local(2004, 6, 24, 16, 24, 0), topic.written_on
   } 

   function test_multiparameter_mass_assignment_protector(){
     task = Task.new
     time = Time.mktime(2000, 1, 1, 1)
     task.starting = time 
     attributes = { "starting(1i)" => "2004", "starting(2i)" => "6", "starting(3i)" => "24" }
     task.attributes = attributes
     assert_equal time, task.starting
   } 

   function test_multiparameter_assignment_of_aggregation(){
     customer = Customer.new
     address = Address.new("The Street", "The City", "The Country")
     attributes = { "address(1)" => address.street, "address(2)" => address.city, "address(3)" => address.country }
     customer.attributes = attributes
     assert_equal address, customer.address
   } 

   function test_attributes_on_dummy_time(){
     # Oracle and SQL Server do not have a TIME datatype.
     return true if current_adapter?(:SQLServerAdapter) || current_adapter?(:OracleAdapter)

     attributes = {
       "bonus_time" => "5:42:00AM"
     }
     topic = Topic.find(1)
     topic.attributes = attributes
     assert_equal Time.local(2000, 1, 1, 5, 42, 0), topic.bonus_time
   } 

   function test_boolean(){
     b_false = Booleantest.create({ "value" => false })
     false_id = b_false.id
     b_true = Booleantest.create({ "value" => true })
     true_id = b_true.id

     b_false = Booleantest.find(false_id)
     assert !b_false.value?
     b_true = Booleantest.find(true_id)
     assert b_true.value?
   } 

   function test_boolean_cast_from_string(){
     b_false = Booleantest.create({ "value" => "0" })
     false_id = b_false.id
     b_true = Booleantest.create({ "value" => "1" })
     true_id = b_true.id

     b_false = Booleantest.find(false_id)
     assert !b_false.value?
     b_true = Booleantest.find(true_id)
     assert b_true.value?    
   } 

   function test_clone(){
     topic = Topic.find(1)
     cloned_topic = nil
     assert_nothing_raised { cloned_topic = topic.clone }
     assert_equal topic.title, cloned_topic.title
     assert cloned_topic.new_record?

     # test if the attributes have been cloned
     topic.title = "a" 
     cloned_topic.title = "b" 
     assert_equal "a", topic.title
     assert_equal "b", cloned_topic.title

     # test if the attribute values have been cloned
     topic.title = {"a" => "b"}
     cloned_topic = topic.clone
     cloned_topic.title["a"] = "c" 
     assert_equal "b", topic.title["a"]

     cloned_topic.save
     assert !cloned_topic.new_record?
     assert cloned_topic.id != topic.id
   } 

   function test_clone_with_aggregate_of_same_name_as_attribute(){
     dev = DeveloperWithAggregate.find(1)
     assert_kind_of DeveloperSalary, dev.salary

     clone = nil
     assert_nothing_raised { clone = dev.clone }
     assert_kind_of DeveloperSalary, clone.salary
     assert_equal dev.salary.amount, clone.salary.amount
     assert clone.new_record?

     # test if the attributes have been cloned
     original_amount = clone.salary.amount
     dev.salary.amount = 1
     assert_equal original_amount, clone.salary.amount

     assert clone.save
     assert !clone.new_record?
     assert clone.id != dev.id
   } 

   function test_clone_preserves_subtype(){
     clone = nil
     assert_nothing_raised { clone = Company.find(3).clone }
     assert_kind_of Client, clone
   } 

   function test_bignum(){
     company = Company.find(1)
     company.rating = 2147483647
     company.save
     company = Company.find(1)
     assert_equal 2147483647, company.rating
   } 

   # TODO: ext}  defaults tests to other databases!
   if current_adapter?(:PostgreSQLAdapter)
     function test_default
       default = Default.new

       # fixed dates / times
       assert_equal Date.new(2004, 1, 1), default.fixed_date
       assert_equal Time.local(2004, 1,1,0,0,0,0), default.fixed_time

       # char types
       assert_equal 'Y', default.char1
       assert_equal 'a varchar field', default.char2
       assert_equal 'a text field', default.char3
     } 

     class Geometric < ActiveRecord::Base; } 
     function test_geometric_content

       # accepted format notes:
       # ()'s aren't required
       # values can be a mix of float or integer

       g = Geometric.new(
         :a_point        => '(5.0, 6.1)',
         #:a_line         => '((2.0, 3), (5.5, 7.0))' # line type is currently unsupported in postgresql
         :a_line_segment => '(2.0, 3), (5.5, 7.0)',
         :a_box          => '2.0, 3, 5.5, 7.0',
         :a_path         => '[(2.0, 3), (5.5, 7.0), (8.5, 11.0)]',  # [ ] is an open path
         :a_polygon      => '((2.0, 3), (5.5, 7.0), (8.5, 11.0))',
         :a_circle       => '<(5.3, 10.4), 2>'
       )

       assert g.save

       # Reload and check that we have all the geometric attributes.
       h = Geometric.find(g.id)

       assert_equal '(5,6.1)', h.a_point
       assert_equal '[(2,3),(5.5,7)]', h.a_line_segment
       assert_equal '(5.5,7),(2,3)', h.a_box   # reordered to store upper right corner then bottom left corner
       assert_equal '[(2,3),(5.5,7),(8.5,11)]', h.a_path
       assert_equal '((2,3),(5.5,7),(8.5,11))', h.a_polygon
       assert_equal '<(5.3,10.4),2>', h.a_circle

       # use a geometric function to test for an open path
       objs = Geometric.find_by_sql ["select isopen(a_path) from geometrics where id = ?", g.id]
       assert_equal objs[0].isopen, 't'

       # test alternate formats when defining the geometric types

       g = Geometric.new(
         :a_point        => '5.0, 6.1',
         #:a_line         => '((2.0, 3), (5.5, 7.0))' # line type is currently unsupported in postgresql
         :a_line_segment => '((2.0, 3), (5.5, 7.0))',
         :a_box          => '(2.0, 3), (5.5, 7.0)',
         :a_path         => '((2.0, 3), (5.5, 7.0), (8.5, 11.0))',  # ( ) is a closed path
         :a_polygon      => '2.0, 3, 5.5, 7.0, 8.5, 11.0',
         :a_circle       => '((5.3, 10.4), 2)'
       )

       assert g.save

       # Reload and check that we have all the geometric attributes.
       h = Geometric.find(g.id)

       assert_equal '(5,6.1)', h.a_point
       assert_equal '[(2,3),(5.5,7)]', h.a_line_segment
       assert_equal '(5.5,7),(2,3)', h.a_box   # reordered to store upper right corner then bottom left corner
       assert_equal '((2,3),(5.5,7),(8.5,11))', h.a_path
       assert_equal '((2,3),(5.5,7),(8.5,11))', h.a_polygon
       assert_equal '<(5.3,10.4),2>', h.a_circle

       # use a geometric function to test for an closed path
       objs = Geometric.find_by_sql ["select isclosed(a_path) from geometrics where id = ?", g.id]
       assert_equal objs[0].isclosed, 't'
     } 
   } 

   function test_auto_id(){
     auto = AutoId.new
     auto.save
     assert (auto.id > 0)
   } 

   function quote_column_name(name)(){
     "<#{name}>"
   } 

   function test_quote_keys(){
     ar = AutoId.new
     source = {"foo" => "bar", "baz" => "quux"}
     actual = ar.s} (:quote_columns, self, source)
     inverted = actual.invert
     assert_equal("<foo>", inverted["bar"])
     assert_equal("<baz>", inverted["quux"])
   } 

   function test_column_name_properly_quoted
     col_record = ColumnName.new
     col_record.references = 40
     assert col_record.save
     col_record.references = 41
     assert col_record.save
     assert_not_nil c2 = ColumnName.find(col_record.id)
     assert_equal(41, c2.references)
   } 

   MyObject = Struct.new :attribute1, :attribute2

   function test_serialized_attribute
     myobj = MyObject.new('value1', 'value2')
     topic = Topic.create("content" => myobj)  
     Topic.serialize("content", MyObject)
     assert_equal(myobj, topic.content)
   } 

   function test_serialized_attribute_with_class_constraint
     myobj = MyObject.new('value1', 'value2')
     topic = Topic.create("content" => myobj)
     Topic.serialize(:content, Hash)

     assert_raise(ActiveRecord::SerializationTypeMismatch) { Topic.find(topic.id).content }

     settings = { "color" => "blue" }
     Topic.find(topic.id).update_attribute("content", settings)
     assert_equal(settings, Topic.find(topic.id).content)
     Topic.serialize(:content)
   } 

   function test_quote
     author_name = "\\ \001 ' \n \\n \""
     topic = Topic.create('author_name' => author_name)
     assert_equal author_name, Topic.find(topic.id).author_name
   } 

   function test_class_level_destroy
     should_be_destroyed_reply = Reply.create("title" => "hello", "content" => "world")
     Topic.find(1).replies << should_be_destroyed_reply

     Topic.destroy(1)
     assert_raise(ActiveRecord::RecordNotFound) { Topic.find(1) }
     assert_raise(ActiveRecord::RecordNotFound) { Reply.find(should_be_destroyed_reply.id) }
   } 

   function test_class_level_delete
     should_be_destroyed_reply = Reply.create("title" => "hello", "content" => "world")
     Topic.find(1).replies << should_be_destroyed_reply

     Topic.delete(1)
     assert_raise(ActiveRecord::RecordNotFound) { Topic.find(1) }
     assert_nothing_raised { Reply.find(should_be_destroyed_reply.id) }
   } 

   function test_increment_attribute
     assert_equal 0, topics(:first).replies_count
     topics(:first).increment! :replies_count
     assert_equal 1, topics(:first, :reload).replies_count

     topics(:first).increment(:replies_count).increment!(:replies_count)
     assert_equal 3, topics(:first, :reload).replies_count
   } 

   function test_increment_nil_attribute
     assert_nil topics(:first).parent_id
     topics(:first).increment! :parent_id
     assert_equal 1, topics(:first).parent_id
   } 

   function test_decrement_attribute
     topics(:first).increment(:replies_count).increment!(:replies_count)
     assert_equal 2, topics(:first).replies_count

     topics(:first).decrement!(:replies_count)
     assert_equal 1, topics(:first, :reload).replies_count

     topics(:first).decrement(:replies_count).decrement!(:replies_count)
     assert_equal -1, topics(:first, :reload).replies_count
   } 

   function test_toggle_attribute
     assert !topics(:first).approved?
     topics(:first).toggle!(:approved)
     assert topics(:first).approved?
     topic = topics(:first)
     topic.toggle(:approved)
     assert !topic.approved?
     topic.reload
     assert topic.approved?
   } 
*/
   function test_reload(){
      $t=new Topic;
      $t1 = $t->find(1);
      $t2 = $t->find(1);
      $t1->title = "something else";
      $this->assertNotEqual($t1->title, $t2->title);
      $t1->save();
      $this->assertNotEqual($t1->title, $t2->title);
      $t2->reload();
      $this->assertEqual($t1->title, $t2->title);
   } 
/*
   function test_define_attr_method_with_value
     k = Class.new( ActiveRecord::Base )
     k.s} (:define_attr_method, :table_name, "foo")
     assert_equal "foo", k.table_name
   } 

   function test_define_attr_method_with_block
     k = Class.new( ActiveRecord::Base )
     k.s} (:define_attr_method, :primary_key) { "sys_" + original_primary_key }
     assert_equal "sys_id", k.primary_key
   } 

   function test_set_table_name_with_value
     k = Class.new( ActiveRecord::Base )
     k.table_name = "foo"
     assert_equal "foo", k.table_name
     k.set_table_name "bar"
     assert_equal "bar", k.table_name
   } 

   function test_set_table_name_with_block
     k = Class.new( ActiveRecord::Base )
     k.set_table_name { "ks" }
     assert_equal "ks", k.table_name
   } 

   function test_set_primary_key_with_value
     k = Class.new( ActiveRecord::Base )
     k.primary_key = "foo"
     assert_equal "foo", k.primary_key
     k.set_primary_key "bar"
     assert_equal "bar", k.primary_key
   } 

   function test_set_primary_key_with_block
     k = Class.new( ActiveRecord::Base )
     k.set_primary_key { "sys_" + original_primary_key }
     assert_equal "sys_id", k.primary_key
   } 

   function test_set_inheritance_column_with_value
     k = Class.new( ActiveRecord::Base )
     k.inheritance_column = "foo"
     assert_equal "foo", k.inheritance_column
     k.set_inheritance_column "bar"
     assert_equal "bar", k.inheritance_column
   } 

   function test_set_inheritance_column_with_block
     k = Class.new( ActiveRecord::Base )
     k.set_inheritance_column { original_inheritance_column + "_id" }
     assert_equal "type_id", k.inheritance_column
   } 

   function test_count_with_join
     res = Post.count_by_sql "SELECT COUNT(*) FROM posts LEFT JOIN comments ON posts.id=comments.post_id WHERE posts.#{QUOTED_TYPE} = 'Post'"
     res2 = nil
     assert_nothing_raised do
       res2 = Post.count("posts.#{QUOTED_TYPE} = 'Post'",
                         "LEFT JOIN comments ON posts.id=comments.post_id")
     } 
     assert_equal res, res2

     res3 = nil
     assert_nothing_raised do
       res3 = Post.count(:conditions => "posts.#{QUOTED_TYPE} = 'Post'",
                         :joins => "LEFT JOIN comments ON posts.id=comments.post_id")
     } 
     assert_equal res, res3

     res4 = Post.count_by_sql "SELECT COUNT(p.id) FROM posts p, comments c WHERE p.#{QUOTED_TYPE} = 'Post' AND p.id=c.post_id"
     res5 = nil
     assert_nothing_raised do
       res5 = Post.count(:conditions => "p.#{QUOTED_TYPE} = 'Post' AND p.id=c.post_id",
                         :joins => "p, comments c",
                         :select => "p.id")
     } 

     assert_equal res4, res5 

     res6 = Post.count_by_sql "SELECT COUNT(DISTINCT p.id) FROM posts p, comments c WHERE p.#{QUOTED_TYPE} = 'Post' AND p.id=c.post_id"
     res7 = nil
     assert_nothing_raised do
       res7 = Post.count(:conditions => "p.#{QUOTED_TYPE} = 'Post' AND p.id=c.post_id",
                         :joins => "p, comments c",
                         :select => "p.id",
                         :distinct => true)
     } 
     assert_equal res6, res7
   } 

   function test_clear_association_cache_stored     
     firm = Firm.find(1)
     assert_kind_of Firm, firm

     firm.clear_association_cache
     assert_equal Firm.find(1).clients.collect{ |x| x.name }.sort, firm.clients.collect{ |x| x.name }.sort
   } 

   function test_clear_association_cache_new_record
      firm            = Firm.new
      client_stored   = Client.find(3)
      client_new      = Client.new
      client_new.name = "The Joneses"
      clients         = [ client_stored, client_new ]

      firm.clients    << clients

      firm.clear_association_cache

      assert_equal    firm.clients.collect{ |x| x.name }.sort, clients.collect{ |x| x.name }.sort
   } 

   function test_interpolate_sql
     assert_nothing_raised { Category.new.s} (:interpolate_sql, 'foo@bar') }
     assert_nothing_raised { Category.new.s} (:interpolate_sql, 'foo bar) baz') }
     assert_nothing_raised { Category.new.s} (:interpolate_sql, 'foo bar} baz') }
   } 

   function test_scoped_find_conditions
     scoped_developers = Developer.with_scope(:find => { :conditions => 'salary > 90000' }) do
       Developer.find(:all, :conditions => 'id < 5')
     } 
     assert !scoped_developers.include?(developers(:david)) # David's salary is less than 90,000
     assert_equal 3, scoped_developers.size
   } 

   function test_scoped_find_limit_offset
     scoped_developers = Developer.with_scope(:find => { :limit => 3, :offset => 2 }) do
       Developer.find(:all, :order => 'id')
     }     
     assert !scoped_developers.include?(developers(:david))
     assert !scoped_developers.include?(developers(:jamis))
     assert_equal 3, scoped_developers.size

     # Test without scoped find conditions to ensure we get the whole thing
     developers = Developer.find(:all, :order => 'id')
     assert_equal Developer.count, developers.size
   } 

   function test_base_class
     assert LoosePerson.abstract_class?
     assert !LooseDesc} ant.abstract_class?
     assert_equal LoosePerson,     LoosePerson.base_class
     assert_equal LooseDesc} ant, LooseDesc} ant.base_class
     assert_equal TightPerson,     TightPerson.base_class
     assert_equal TightPerson,     TightDesc} ant.base_class
   } 

   function test_assert_queries
     query = lambda { ActiveRecord::Base.connection.execute 'select count(*) from developers' }
     assert_queries(2) { 2.times { query.call } }
     assert_queries 1, &query
     assert_no_queries { assert true }
   } 
*/

   function test_to_xml(){
      $this->__construct();
      $t=new Topic;
      $xml = $t->find_first()->to_xml(array('indent' => 0, 'skip_instruct' => true));
#     bonus_time_in_current_timezone = topics(:first).bonus_time.xmlschema
#     written_on_in_current_timezone = topics(:first).written_on.xmlschema
#     last_read_in_current_timezone = topics(:first).last_read.xmlschema
print $xml;
      $firstline=split("\n", $xml);
     $this->assertEqual( "<topic>", $firstline[0]);
     $this->assertWantedPattern("!<title.*?>The First Topic</title>!", $xml);
     $this->assertWantedPattern("!<author-name.*?>David</author-name>!", $xml);
     $this->assertWantedPattern('!<id type="integer">1</id>!', $xml);
     $this->assertWantedPattern('!<replies-count type="integer">0</replies-count>!', $xml);
     $this->assertWantedPattern('!<written-on type="datetime">.*?</written-on>!', $xml);
     $this->assertWantedPattern('!<content.*?>Have a nice day</content>!', $xml);
     $this->assertWantedPattern('!<author-email-address.*?>david@loudthinking.com</author-email-address>!', $xml);
     $this->assertWantedPattern('!<parent-id.*?></parent-id>!', $xml);
#     if current_adapter?(:SybaseAdapter) or current_adapter?(:SQLServerAdapter)
#       assert xml.include?(%(<last-read type="datetime">#{last_read_in_current_timezone}</last-read>))
#     else
       $this->assertWantedPattern('!<last-read type="date">2004-04-15</last-read>!', $xml);
#     } 
     # Oracle and DB2 don't have true boolean or time-only fields
#     unless current_adapter?(:OracleAdapter) || current_adapter?(:DB2Adapter)
#       assert xml.include?(%(<approved type="boolean">false</approved>)), "Approved should be a boolean"
       $this->assertWantedPattern('!<bonus-time type="datetime">!', $xml);
#     } 
   } 


   function test_to_xml_skipping_attributes(){
      $t=new Topic;
      $xml = $t->find_first()->to_xml(array('indent' => 0, 'skip_instruct' => true, 
         'except' => 'title'));

     $firstline=split("\n", $xml);
     $this->assertEqual( "<topic>", $firstline[0]);
     $this->assertNoUnwantedPattern("!<title.*?>The First Topic</title>!", $xml);
     $this->assertWantedPattern("!<author-name.*?>David</author-name>!", $xml);    

     $xml = $t->find_first()->to_xml(array('indent' => 0, 'skip_instruct' => true, 
        'except' => array('title', 'author_name')));
        
        $this->assertNoUnwantedPattern("!<title.*?>The First Topic</title>!", $xml);
        $this->assertNoUnwantedPattern("!<author-name.*?>David</author-name>!", $xml);   
   } 

   function test_to_xml_including_has_many_association(){
      $t=new Topic;
      $xml = $t->find_first()->to_xml(array('indent' => 2, 'skip_instruct' => true, 
         'include' => 'replies'));
      
      print $xml;
      
      $firstline=split("\n", $xml);
      $this->assertEqual( "<topic>", $firstline[0]);

      $this->assertWantedPattern("!<replies>.*?<reply>!ms", $xml);
      $this->assertWantedPattern("!<title>The Second Topic's of the day</title>!", $xml);
   } 
/*
   function test_to_xml_including_belongs_to_association
     xml = companies(:first_client).to_xml(:indent => 0, :skip_instruct => true, :include => :firm)
     assert !xml.include?("<firm>")

     xml = companies(:second_client).to_xml(:indent => 0, :skip_instruct => true, :include => :firm)
     assert xml.include?("<firm>")
   } 

   function test_to_xml_including_multiple_associations
     xml = companies(:first_firm).to_xml(:indent => 0, :skip_instruct => true, :include => [ :clients, :account ])
     assert_equal "<firm>", xml.first(6)
     assert xml.include?(%(<account>))
     assert xml.include?(%(<clients><client>))
   } 
*/

   function test_to_xml_including_multiple_associations_with_options(){
      $c=new Company;
      $xml = $c->find_by_id($this->f['companies']['first_firm']['id'])->to_xml(array(
         'indent'  => 0, 'skip_instruct' => true, 
         'include' => array('clients' => array('only' => 'name' ))
         ));
     

     print $xml;
     
     $firstline=split("\n", $xml);
     $this->assertEqual( "<firm>", $firstline[0]);

     $this->assertWantedPattern("!<client>\s*<name>Summit</name>\s*</client>!ms", $xml);
     $this->assertWantedPattern("!<clients>\s*<client>!ms", $xml);
   } 

   function test_except_attributes(){
      $t=new Topic;
      
      $x=array('id', 'author_name','type', 'approved', 'replies_count', 'bonus_time', 'written_on', 'content', 'author_email_address', 'parent_id', 'last_read');
      sort($x);
      $y=array_keys($t->find_first()->attributes(array('title'), 'except'));
      sort($y);
      $this->assertEqual($x, $y);
     
     #    print_r(array_keys($t->find_first()->attributes(array('title'), 'except')));
#     assert_equal(
#       %w( replies_count bonus_time written_on content author_email_address parent_id last_read), 
#       topics(:first).attributes(:except => [ :title, :id, :type, :approved, :author_name ]).keys
#     )
   } 

   function test_include_attributes(){
      $t=new Topic;
      $this->assertEqual(array('title'), array_keys($t->find_first()->attributes(array('title'), 'only')));
      $this->assertEqual(array('title'), array_keys($t->find_first()->attributes(array('title'))));
   #  assert_equal(%w( title author_name type id approved ), topics(:first).attributes(:only => [ :title, :id, :type, :approved, :author_name ]).keys)
   } 

   function test_type_name_with_module_should_handle_beginning(){
#     assert_equal 'ActiveRecord::Person', ActiveRecord::Base.s} (:type_name_with_module, 'Person')
#     assert_equal '::Person', ActiveRecord::Base.s} (:type_name_with_module, '::Person')
   } 


}

?>