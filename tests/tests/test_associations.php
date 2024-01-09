<?php


class TestAssociations extends UnitTestCase{
   
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

     function test_force_reload(){
       $firm = new Firm(array("name" => "A New firm, Inc"));
      print_r(xorcstore_reflection::$r);
       $firm->save();
       $firm->clients->reload(); # forcing to load all clients
       #print_r($firm);
       #print_r(new Client);
       #var_dump(count($firm->clients));
       $this->assertTrue($firm->clients->is_empty(), "New firm shouldn't have client objects");
       $this->assertFalse($firm->has_clients(), "New firm shouldn't have clients");
       $this->assertEqual(0, count($firm->clients), "New firm should have 0 clients");

       $client = new Client(array("name" => "TheClient.com", "firm_id" => $firm->id));
       $client->save();

       $this->assertTrue($firm->clients->is_empty(), "New firm shouldn't have cached client objects");
       $this->assertFalse($firm->has_clients(), "New firm shouldn't have cached clients");
       $this->assertEqual(0, count($firm->clients), "New firm should have cached 0 clients");

       $this->assertFalse($firm->clients->is_empty(true), "New firm should have reloaded client objects");
       $this->assertEqual(1, count($firm->clients), "New firm should have reloaded clients count");
       
     }

/*
     function test_storing_in_pstore(){
       require "tmpdir"
       store_filename = File.join(Dir.tmpdir, "ar-pstore-association-test")
       File.delete(store_filename) if File.exists?(store_filename)
       require "pstore"
       apple = $firm->create("name" => "Apple")
       natural = Client.new("name" => "Natural Company")
       apple.clients << natural

       db = PStore.new(store_filename)
       db.transaction do
         db["apple"] = apple
       }

       db = PStore.new(store_filename)
       db.transaction do
         assert_equal "Natural Company", db["apple"].clients.first.name
       }
     }
   }
}
*/
}


   class HasOneAssociationsTest extends UnitTestCase{
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

     function test_has_one(){
        $c=new Company;
        $a=new Account;
       $this->assertEqual($c->find_by_id($this->f['companies']['first_firm']['id'])->account, 
         $a->find(1));
       $this->assertEqual($a->find(1)->credit_limit,
         $c->find_by_id($this->f['companies']['first_firm']['id'])->account->credit_limit);
     }

     function test_proxy_assignment(){
#       company = companies(:first_firm)
#       assert_nothing_raised { company.account = company.account }
     }

     function test_triple_equality(){
#       assert Account === companies(:first_$firm).account
#       assert companies(:first_$firm).account === Account
     }

     function test_type_mismatch(){
#       assert_raises(ActiveRecord::AssociationTypeMismatch) { companies(:first_$firm).account = 1 }
#       assert_raises(ActiveRecord::AssociationTypeMismatch) { companies(:first_$firm).account = Project.find(1) }
     }

     function test_natural_assignment(){
        $f=new Firm; $a=new Account;
       $apple = $f->create(array("name" => "Apple"));
       $citibank = $a->create(array("credit_limit" => 10));
       $apple->account = $citibank;
       print_r($apple);
       $this->assertEqual($apple->id, $citibank->firm_id);
     }
/*
     function test_natural_assignment_to_nil{
       old_account_id = companies(:first_$firm).account.id
       companies(:first_$firm).account = nil
       companies(:first_$firm)->save()
       assert_nil companies(:first_$firm).account
       # account is dep}ent, therefore is destroyed when reference to owner is lost
       assert_raises(ActiveRecord::RecordNotFound) { Account.find(old_account_id) } 
     }

     function test_assignment_without_replacement{
       apple = $firm->create("name" => "Apple")
       citibank = Account.create("credit_limit" => 10)
       apple.account = citibank
       assert_equal apple.id, citibank.$firm_id

       hsbc = apple.build_account({ :credit_limit => 20}, false)
       assert_equal apple.id, hsbc.$firm_id
       hsbc->save()
       assert_equal apple.id, citibank.$firm_id

       nykredit = apple.create_account({ :credit_limit => 30}, false)
       assert_equal apple.id, nykredit.$firm_id
       assert_equal apple.id, citibank.$firm_id
       assert_equal apple.id, hsbc.$firm_id
     }

     function test_assignment_without_replacement_on_create{
       apple = $firm->create("name" => "Apple")
       citibank = Account.create("credit_limit" => 10)
       apple.account = citibank
       assert_equal apple.id, citibank.$firm_id

       hsbc = apple.create_account({:credit_limit => 10}, false)
       assert_equal apple.id, hsbc.$firm_id
       hsbc->save()
       assert_equal apple.id, citibank.$firm_id
     }

     function test_dependence{
       num_accounts = Account.count
       $firm = $firm->find(1)
       assert !$firm->account.nil?
       $firm->destroy                
       assert_equal num_accounts - 1, Account.count
     }

     function test_succesful_build_association{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->save

       account = $firm->build_account("credit_limit" => 1000)
       assert account->save()
       assert_equal account, $firm->account
     }

     function test_failing_build_association{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->save

       account = $firm->build_account
       assert !account->save()
       assert_equal "can't be empty", account.errors.on("credit_limit")
     }

     function test_build_association_twice_without_saving_affects_nothing{
       count_of_account = Account.count
       $firm = $firm->find(:first)
       account1 = $firm->build_account("credit_limit" => 1000)
       account2 = $firm->build_account("credit_limit" => 2000)

       assert_equal count_of_account, Account.count
     }

     function test_create_association{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->save
       assert_equal $firm->create_account("credit_limit" => 1000), $firm->account
     }

     function test_build{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->save

       $firm->account = account = Account.new("credit_limit" => 1000)
       assert_equal account, $firm->account
       assert account->save()
       assert_equal account, $firm->account
     }

     function test_build_before_child_saved{
       $firm = $firm->find(1)

       account = $firm->account.build("credit_limit" => 1000)
       assert_equal account, $firm->account
       assert account.new_record?
       assert $firm->save
       assert_equal account, $firm->account
       assert !account.new_record?
     }

     function test_build_before_either_saved{
       $firm = $firm->new("name" => "GlobalMegaCorp")

       $firm->account = account = Account.new("credit_limit" => 1000)
       assert_equal account, $firm->account
       assert account.new_record?
       assert $firm->save
       assert_equal account, $firm->account
       assert !account.new_record?
     }

     function test_failing_build_association{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->save

       $firm->account = account = Account.new
       assert_equal account, $firm->account
       assert !account->save()
       assert_equal account, $firm->account
       assert_equal "can't be empty", account.errors.on("credit_limit")
     }

     function test_create{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->save
       $firm->account = account = Account.create("credit_limit" => 1000)
       assert_equal account, $firm->account
     }

     function test_create_before_save{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->account = account = Account.create("credit_limit" => 1000)
       assert_equal account, $firm->account
     }

     function test_dep}ence_with_missing_association{
       Account.destroy_all
       $firm = $firm->find(1)
       assert $firm->account.nil?
       $firm->destroy
     }

     function test_assignment_before_parent_saved{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->account = a = Account.find(1)
       assert $firm->new_record?
       assert_equal a, $firm->account
       assert $firm->save
       assert_equal a, $firm->account
       assert_equal a, $firm->account(true)
     }

     function test_assignment_before_child_saved{
       $firm = $firm->find(1)
       $firm->account = a = Account.new("credit_limit" => 1000)
       assert !a.new_record?
       assert_equal a, $firm->account
       assert_equal a, $firm->account
       assert_equal a, $firm->account(true)
     }

     function test_assignment_before_either_saved{
       $firm = $firm->new("name" => "GlobalMegaCorp")
       $firm->account = a = Account.new("credit_limit" => 1000)
       assert $firm->new_record?
       assert a.new_record?
       assert_equal a, $firm->account
       assert $firm->save
       assert !$firm->new_record?
       assert !a.new_record?
       assert_equal a, $firm->account
       assert_equal a, $firm->account(true)
     }
   
*/
   }

   class HasManyAssociationsTest extends UnitTestCase{


     function setUp(){
       $_GLOBALS['cd']=array();
     }

     function force_signal37_to_load_all_clients_of_firm(){
        $c=new Company;
        #companies(:first_$firm).clients_of_$firm->each {|f| }
     }

     function test_counting(){
        $firm=new Firm;
        $this->assertEqual(2, $firm->find_first()->clients->count());
        $this->assertEqual(2, count($firm->find_first()->clients));
     }

     function test_finding(){
        $firm=new Firm;
        $this->assertEqual(2, sizeof($firm->find_first()->clients));
     }

     function test_find_many_with_merged_options(){
        $firm=new Firm;
     #   $this->assertEqual(1, companies(:first_firm).limited_clients.size
   #     $this->assertEqual(1, companies(:first_firm).limited_clients.find(:all).size
   #     $this->assertEqual(2, companies(:first_firm).limited_clients.find(:all, :limit => nil).size
     }

     function test_triple_equality(){
        $firm=new Firm;
        $this->assertFalse(is_array($firm->find_first()->clients));
        $this->assertTrue(is_object($firm->find_first()->clients));
   #    assert $firm->find(:first).clients === Array
     }

     function test_finding_default_orders(){
        $firm=new Firm;
        $this->assertEqual("Summit", $firm->find_first()->clients->first()->name);
     }

     function test_finding_with_different_class_name_and_order(){
        $firm=new Firm;
        $this->assertEqual("Microsoft", $firm->find_first()->clients_sorted_desc->first()->name);
     }

     function test_finding_with_foreign_key(){
        $firm=new Firm;
        $this->assertEqual("Microsoft", $firm->find_first()->clients_of_firm->first()->name);
     }

     function test_finding_with_condition(){
        $firm=new Firm;
        
        $this->assertEqual("Microsoft", $firm->find_first()->clients_like_ms->first()->name);
     }

     function test_finding_using_sql(){
       $f = new Firm;
       $firm = $f->find_first();
       print_r($firm->clients_using_sql);
       print_r($firm->clients_using_sql->get()->opts);
       print_r($firm->clients_using_sql->get()->opts);
       print_r($firm->clients_using_sql->get()->first());
       $first_client = $firm->clients_using_sql->first();
       print_r(       $first_client);
       $this->assertNotNull($first_client);
       $this->assertEqual("Microsoft", $first_client->name);
       $this->assertEqual(1, count($firm->clients_using_sql));
       $this->assertEqual(1, count($f->find_first()->clients_using_sql));
     }

/*
     function test_counting_using_sql(){
       assert_equal 1, $firm->find(:first).clients_using_counter_sql.size
       assert_equal 0, $firm->find(:first).clients_using_zero_counter_sql.size
     }

     function test_counting_non_existant_items_using_sql(){
       assert_equal 0, $firm->find(:first).no_clients_using_counter_sql.size
     }

     function test_belongs_to_sanity(){
       c = Client.new
       assert_nil c.$firm

       if c.$firm
         assert false, "belongs_to failed if check"
       }

       unless c.$firm
       else
         assert false,  "belongs_to failed unless check"
       }
     }

     function test_find_ids(){
       $firm = $firm->find(:first)

       assert_raises(ActiveRecord::RecordNotFound) { $firm->clients.find }

       client = $firm->clients.find(2)
       assert_kind_of Client, client

       client_ary = $firm->clients.find([2])
       assert_kind_of Array, client_ary
       assert_equal client, client_ary.first

       client_ary = $firm->clients.find(2, 3)
       assert_kind_of Array, client_ary
       assert_equal 2, client_ary.size
       assert_equal client, client_ary.first

       assert_raises(ActiveRecord::RecordNotFound) { $firm->clients.find(2, 99) }
     }

     function test_find_all(){
       $firm = $firm->find_first
       assert_equal $firm->clients, $firm->clients.find_all
       assert_equal 2, $firm->clients.find(:all, :conditions => "#{QUOTED_TYPE} = 'Client'").length
       assert_equal 1, $firm->clients.find(:all, :conditions => "name = 'Summit'").length
     }

     function test_find_all_sanitized(){
       $firm = $firm->find_first
       assert_equal $firm->clients.find_all("name = 'Summit'"), $firm->clients.find_all(["name = '%s'", "Summit"])
       summit = $firm->clients.find(:all, :conditions => "name = 'Summit'")
       assert_equal summit, $firm->clients.find(:all, :conditions => ["name = ?", "Summit"])
       assert_equal summit, $firm->clients.find(:all, :conditions => ["name = :name", { :name => "Summit" }])
     }

     function test_find_first(){
       $firm = $firm->find_first
       client2 = Client.find(2)
       assert_equal $firm->clients.first, $firm->clients.find_first
       assert_equal client2, $firm->clients.find_first("#{QUOTED_TYPE} = 'Client'")
       assert_equal client2, $firm->clients.find(:first, :conditions => "#{QUOTED_TYPE} = 'Client'")
     }

     function test_find_first_sanitized(){
       $firm = $firm->find_first
       client2 = Client.find(2)
       assert_equal client2, $firm->clients.find_first(["#{QUOTED_TYPE} = ?", "Client"])
       assert_equal client2, $firm->clients.find(:first, :conditions => ["#{QUOTED_TYPE} = ?", 'Client'])
       assert_equal client2, $firm->clients.find(:first, :conditions => ["#{QUOTED_TYPE} = :type", { :type => 'Client' }])
     }

     function test_find_in_collection(){
       assert_equal Client.find(2).name, companies(:first_$firm).clients.find(2).name
       assert_raises(ActiveRecord::RecordNotFound) { companies(:first_$firm).clients.find(6) }
     }

     function test_find_grouped(){
       all_clients_of_$firm1 = Client.find(:all, :conditions => "$firm_id = 1")
       grouped_clients_of_$firm1 = Client.find(:all, :conditions => "$firm_id = 1", :group => "$firm_id", :select => '$firm_id, count(id) as clients_count')
       assert_equal 2, all_clients_of_$firm1.size
       assert_equal 1, grouped_clients_of_$firm1.size
     }

     function test_adding(){
       force_signal37_to_load_all_clients_of_$firm
       natural = Client.new("name" => "Natural Company")
       companies(:first_$firm).clients_of_$firm << natural
       assert_equal 2, companies(:first_$firm).clients_of_$firm->size # checking via the collection
       assert_equal 2, companies(:first_$firm).clients_of_$firm(true).size # checking using the db
       assert_equal natural, companies(:first_$firm).clients_of_$firm->last
     }

     function test_adding_a_mismatch_class(){
       assert_raises(ActiveRecord::AssociationTypeMismatch) { companies(:first_$firm).clients_of_$firm << nil }
       assert_raises(ActiveRecord::AssociationTypeMismatch) { companies(:first_$firm).clients_of_$firm << 1 }
       assert_raises(ActiveRecord::AssociationTypeMismatch) { companies(:first_$firm).clients_of_$firm << Topic.find(1) }
     }

     function test_adding_a_collection(){
       force_signal37_to_load_all_clients_of_$firm
       companies(:first_$firm).clients_of_$firm->concat([Client.new("name" => "Natural Company"), Client.new("name" => "Apple")])
       assert_equal 3, companies(:first_$firm).clients_of_$firm->size
       assert_equal 3, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_adding_before_save(){
       no_of_$firms = $firm->count
       no_of_clients = Client.count
       new_$firm = $firm->new("name" => "A New $firm, Inc")
       new_$firm->clients_of_$firm->push Client.new("name" => "Natural Company")
       new_$firm->clients_of_$firm << (c = Client.new("name" => "Apple"))
       assert new_$firm->new_record?
       assert c.new_record?
       assert_equal 2, new_$firm->clients_of_$firm->size
       assert_equal no_of_$firms, $firm->count      # $firm was not saved to database.
       assert_equal no_of_clients, Client.count  # Clients were not saved to database.
       assert new_$firm->save
       assert !new_$firm->new_record?
       assert !c.new_record?
       assert_equal new_$firm, c.$firm
       assert_equal no_of_$firms+1, $firm->count      # $firm was saved to database.
       assert_equal no_of_clients+2, Client.count  # Clients were saved to database.
       assert_equal 2, new_$firm->clients_of_$firm->size
       assert_equal 2, new_$firm->clients_of_$firm(true).size
     }

     function test_invalid_adding(){
       $firm = $firm->find(1)
       assert !($firm->clients_of_$firm << c = Client.new)
       assert c.new_record?
       assert !$firm->valid?
       assert !$firm->save
       assert c.new_record?
     }

     function test_invalid_adding_before_save(){
       no_of_$firms = $firm->count
       no_of_clients = Client.count
       new_$firm = $firm->new("name" => "A New $firm, Inc")
       new_$firm->clients_of_$firm->concat([c = Client.new, Client.new("name" => "Apple")])
       assert c.new_record?
       assert !c.valid?
       assert !new_$firm->valid?
       assert !new_$firm->save
       assert c.new_record?
       assert new_$firm->new_record?
     }

     function test_build(){
       new_client = companies(:first_$firm).clients_of_$firm->build("name" => "Another Client")
       assert_equal "Another Client", new_client.name
       assert new_client.new_record?
       assert_equal new_client, companies(:first_$firm).clients_of_$firm->last
       assert companies(:first_$firm)->save()
       assert !new_client.new_record?
       assert_equal 2, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_build_many(){
       new_clients = companies(:first_$firm).clients_of_$firm->build([{"name" => "Another Client"}, {"name" => "Another Client II"}])
       assert_equal 2, new_clients.size

       assert companies(:first_$firm)->save()
       assert_equal 3, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_invalid_build(){
       new_client = companies(:first_$firm).clients_of_$firm->build
       assert new_client.new_record?
       assert !new_client.valid?
       assert_equal new_client, companies(:first_$firm).clients_of_$firm->last
       assert !companies(:first_$firm)->save()
       assert new_client.new_record?
       assert_equal 1, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_create(){
       force_signal37_to_load_all_clients_of_$firm
       new_client = companies(:first_$firm).clients_of_$firm->create("name" => "Another Client")
       assert !new_client.new_record?
       assert_equal new_client, companies(:first_$firm).clients_of_$firm->last
       assert_equal new_client, companies(:first_$firm).clients_of_$firm(true).last
     }

     function test_create_many(){
       companies(:first_$firm).clients_of_$firm->create([{"name" => "Another Client"}, {"name" => "Another Client II"}])
       assert_equal 3, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_find_or_create(){
       number_of_clients = companies(:first_$firm).clients.size
       the_client = companies(:first_$firm).clients.find_or_create_by_name("Yet another client")
       assert_equal number_of_clients + 1, companies(:first_$firm, :refresh).clients.size
       assert_equal the_client, companies(:first_$firm).clients.find_or_create_by_name("Yet another client")
       assert_equal number_of_clients + 1, companies(:first_$firm, :refresh).clients.size
     }

     function test_deleting(){
       force_signal37_to_load_all_clients_of_$firm
       companies(:first_$firm).clients_of_$firm->delete(companies(:first_$firm).clients_of_$firm->first)
       assert_equal 0, companies(:first_$firm).clients_of_$firm->size
       assert_equal 0, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_deleting_before_save(){
       new_$firm = $firm->new("name" => "A New $firm, Inc.")
       new_client = new_$firm->clients_of_$firm->build("name" => "Another Client")
       assert_equal 1, new_$firm->clients_of_$firm->size
       new_$firm->clients_of_$firm->delete(new_client)
       assert_equal 0, new_$firm->clients_of_$firm->size
     }

     function test_deleting_a_collection(){
       force_signal37_to_load_all_clients_of_$firm
       companies(:first_$firm).clients_of_$firm->create("name" => "Another Client")
       assert_equal 2, companies(:first_$firm).clients_of_$firm->size
       companies(:first_$firm).clients_of_$firm->delete([companies(:first_$firm).clients_of_$firm[0], companies(:first_$firm).clients_of_$firm[1]])
       assert_equal 0, companies(:first_$firm).clients_of_$firm->size
       assert_equal 0, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_delete_all(){
       force_signal37_to_load_all_clients_of_$firm
       companies(:first_$firm).clients_of_$firm->create("name" => "Another Client")
       assert_equal 2, companies(:first_$firm).clients_of_$firm->size
       companies(:first_$firm).clients_of_$firm->delete_all
       assert_equal 0, companies(:first_$firm).clients_of_$firm->size
       assert_equal 0, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_delete_all_with_not_yet_loaded_association_collection(){
       force_signal37_to_load_all_clients_of_$firm
       companies(:first_$firm).clients_of_$firm->create("name" => "Another Client")
       assert_equal 2, companies(:first_$firm).clients_of_$firm->size
       companies(:first_$firm).clients_of_$firm->reset
       companies(:first_$firm).clients_of_$firm->delete_all
       assert_equal 0, companies(:first_$firm).clients_of_$firm->size
       assert_equal 0, companies(:first_$firm).clients_of_$firm(true).size
     }

     function test_clearing_an_association_collection(){
       $firm = companies(:first_$firm)
       client_id = $firm->clients_of_$firm->first.id
       assert_equal 1, $firm->clients_of_$firm->size

       $firm->clients_of_$firm->clear

       assert_equal 0, $firm->clients_of_$firm->size
       assert_equal 0, $firm->clients_of_$firm(true).size
       assert_equal [], Client.destroyed_client_ids[$firm->id]

       # Should not be destroyed since the association is not dep}ent.
       assert_nothing_raised do
         assert Client.find(client_id).$firm->nil?
       }
     }

     function test_clearing_a_dep}ent_association_collection(){
       $firm = companies(:first_$firm)
       client_id = $firm->dep}ent_clients_of_$firm->first.id
       assert_equal 1, $firm->dep}ent_clients_of_$firm->size

       # :dep}ent means destroy is called on each client
       $firm->dep}ent_clients_of_$firm->clear

       assert_equal 0, $firm->dep}ent_clients_of_$firm->size
       assert_equal 0, $firm->dep}ent_clients_of_$firm(true).size
       assert_equal [client_id], Client.destroyed_client_ids[$firm->id]

       # Should be destroyed since the association is dep}ent.
       assert Client.find_by_id(client_id).nil?
     }

     function test_clearing_an_exclusively_dep}ent_association_collection(){
       $firm = companies(:first_$firm)
       client_id = $firm->exclusively_dep}ent_clients_of_$firm->first.id
       assert_equal 1, $firm->exclusively_dep}ent_clients_of_$firm->size

       assert_equal [], Client.destroyed_client_ids[$firm->id]

       # :exclusively_dep}ent means each client is deleted directly from
       # the database without looping through them calling destroy.
       $firm->exclusively_dep}ent_clients_of_$firm->clear

       assert_equal 0, $firm->exclusively_dep}ent_clients_of_$firm->size
       assert_equal 0, $firm->exclusively_dep}ent_clients_of_$firm(true).size
       assert_equal [3], Client.destroyed_client_ids[$firm->id]

       # Should be destroyed since the association is exclusively dep}ent.
       assert Client.find_by_id(client_id).nil?
     }                                                    

     function test_clearing_without_initial_access{
       $firm = companies(:first_$firm)

       $firm->clients_of_$firm->clear

       assert_equal 0, $firm->clients_of_$firm->size
       assert_equal 0, $firm->clients_of_$firm(true).size
     }

     function test_deleting_a_item_which_is_not_in_the_collection{
       force_signal37_to_load_all_clients_of_$firm
       summit = Client.find_first("name = 'Summit'")
       companies(:first_$firm).clients_of_$firm->delete(summit)
       assert_equal 1, companies(:first_$firm).clients_of_$firm->size
       assert_equal 1, companies(:first_$firm).clients_of_$firm(true).size
       assert_equal 2, summit.client_of
     }

     function test_deleting_type_mismatch{
       david = Developer.find(1)
       david.projects.reload
       assert_raises(ActiveRecord::AssociationTypeMismatch) { david.projects.delete(1) }
     }

     function test_deleting_self_type_mismatch{
       david = Developer.find(1)
       david.projects.reload
       assert_raises(ActiveRecord::AssociationTypeMismatch) { david.projects.delete(Project.find(1).developers) }
     }

     function test_destroy_all{
       force_signal37_to_load_all_clients_of_$firm
       assert !companies(:first_$firm).clients_of_$firm->empty?, "37signals has clients after load"
       companies(:first_$firm).clients_of_$firm->destroy_all
       assert companies(:first_$firm).clients_of_$firm->empty?, "37signals has no clients after destroy all"
       assert companies(:first_$firm).clients_of_$firm(true).empty?, "37signals has no clients after destroy all and refresh"
     }

     function test_dep}ence{
       $firm = companies(:first_$firm)
       assert_equal 2, $firm->clients.size
       $firm->destroy
       assert Client.find(:all, :conditions => "$firm_id=#{$firm->id}").empty?
     }

     function test_destroy_dep}ent_when_deleted_from_association{
       $firm = $firm->find(:first)
       assert_equal 2, $firm->clients.size

       client = $firm->clients.first
       $firm->clients.delete(client)

       assert_raise(ActiveRecord::RecordNotFound) { Client.find(client.id) }
       assert_raise(ActiveRecord::RecordNotFound) { $firm->clients.find(client.id) }
       assert_equal 1, $firm->clients.size
     }

     function test_three_levels_of_dep}ence{
       topic = Topic.create "title" => "neat and simple"
       reply = topic.replies.create "title" => "neat and simple", "content" => "still digging it"
       silly_reply = reply.replies.create "title" => "neat and simple", "content" => "ain't complaining"

       assert_nothing_raised { topic.destroy }
     }

     uses_transaction :test_dep}ence_with_transaction_support_on_failure
     function test_dep}ence_with_transaction_support_on_failure{
       $firm = companies(:first_$firm)
       clients = $firm->clients
       assert_equal 2, clients.length
       clients.last.instance_eval { def before_destroy() raise "Trigger rollback" } }

       $firm->destroy rescue "do nothing"

       assert_equal 2, Client.find(:all, :conditions => "$firm_id=#{$firm->id}").size
     }

     function test_dep}ence_on_account{
       num_accounts = Account.count
       companies(:first_$firm).destroy
       assert_equal num_accounts - 1, Account.count
     }

     function test_dep}s_and_nullify{
       num_accounts = Account.count
       num_companies = Company.count

       core = companies(:rails_core)
       assert_equal accounts(:rails_core_account), core.account
       assert_equal [companies(:leetsoft), companies(:jadedpixel)], core.companies
       core.destroy                                         
       assert_nil accounts(:rails_core_account).reload.$firm_id
       assert_nil companies(:leetsoft).reload.client_of
       assert_nil companies(:jadedpixel).reload.client_of


       assert_equal num_accounts, Account.count
     }

     function test_included_in_collection{
       assert companies(:first_$firm).clients.include?(Client.find(2))
     }

     function test_adding_array_and_collection{
       assert_nothing_raised { $firm->find(:first).clients + $firm->find(:all).last.clients }
     }

     function test_find_all_without_conditions{
       $firm = companies(:first_$firm)
       assert_equal 2, $firm->clients.find(:all).length
     }

     function test_replace_with_less{
       $firm = $firm->find(:first)
       $firm->clients = [companies(:first_client)]
       assert $firm->save, "Could not save $firm"
       $firm->reload
       assert_equal 1, $firm->clients.length
     } 


     function test_replace_with_new{
       $firm = $firm->find(:first)
       new_client = Client.new("name" => "New Client")
       $firm->clients = [companies(:second_client),new_client]
       $firm->save
       $firm->reload
       assert_equal 2, $firm->clients.length
       assert !$firm->clients.include?(:first_client)
     }

     function test_replace_on_new_object{
       $firm = $firm->new("name" => "New $firm")
       $firm->clients = [companies(:second_client), Client.new("name" => "New Client")]
       assert $firm->save
       $firm->reload
       assert_equal 2, $firm->clients.length
       assert $firm->clients.include?(Client.find_by_name("New Client"))
     }

     function test_assign_ids{
       $firm = $firm->new("name" => "Apple")
       $firm->client_ids = [companies(:first_client).id, companies(:second_client).id]
       $firm->save
       $firm->reload
       assert_equal 2, $firm->clients.length
       assert $firm->clients.include?(companies(:second_client))
     }
*/
   }


    class BelongsToAssociationsTest extends UnitTestCase{
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
        
        
     function test_belongs_to(){
         $c=new Client; $co=new Company;
         $this->assertEqual($this->f['companies']['first_firm']['name'], $c->find(3)->firm->name);
         $this->assertFalse(is_null($c->find(3)->firm), "Microsoft should have a firm");   
     }


     function test_proxy_assignment(){
        $a=new Account;
        $account = $a->find(1);
        $this->assertNoErrors($account->firm = $account->firm);
     }

     function test_triple_equality(){
#       assert Client.find(3).$firm === $firm
#       assert $firm === Client.find(3).$firm
     }

     function test_type_mismatch(){
 #      assert_raise(ActiveRecord::AssociationTypeMismatch) { Account.find(1).$firm = 1 }
 #      assert_raise(ActiveRecord::AssociationTypeMismatch) { Account.find(1).$firm = Project.find(1) }
     }

     function test_natural_assignment(){
        $a=new Account;
        $firm=new Firm;
       $apple = $firm->create(array("name" => "Apple"));
       $citibank = $a->create(array("credit_limit" => 10));
       $citibank->firm = $apple;
       print_r($citibank);
       print_r($apple);
       $this->assertEqual($apple->id, $citibank->firm_id);
     }

     function test_creating_the_belonging_object(){
        $a=new Account;
          $firm=new Firm;
       $citibank = $a->create(array("credit_limit" => 10));
       $apple    = $citibank->create_firm(array("name" => "Apple"));
       $this->assertEqual($apple, $citibank->firm);
       $citibank->save();
       $citibank->reload();
       $this->assertEqual($apple, $citibank->firm);
     }

/*
     function test_building_the_belonging_object{
       citibank = Account.create("credit_limit" => 10)
       apple    = citibank.build_$firm("name" => "Apple")
       citibank->save()
       assert_equal apple.id, citibank.$firm_id
     }

     function test_natural_assignment_to_nil{
       client = Client.find(3)
       client.$firm = nil
       client->save()
       assert_nil client.$firm(true)
       assert_nil client.client_of
     }

     function test_with_different_class_name{
       assert_equal Company.find(1).name, Company.find(3).$firm_with_other_name.name
       assert_not_nil Company.find(3).$firm_with_other_name, "Microsoft should have a $firm"
     }

     function test_with_condition{
       assert_equal Company.find(1).name, Company.find(3).$firm_with_condition.name
       assert_not_nil Company.find(3).$firm_with_condition, "Microsoft should have a $firm"
     }

     function test_belongs_to_counter{
       debate = Topic.create("title" => "debate")
       assert_equal 0, debate.s}(:read_attribute, "replies_count"), "No replies yet"

       trash = debate.replies.create("title" => "blah!", "content" => "world around!")
       assert_equal 1, Topic.find(debate.id).s}(:read_attribute, "replies_count"), "First reply created"

       trash.destroy
       assert_equal 0, Topic.find(debate.id).s}(:read_attribute, "replies_count"), "First reply deleted"
     }

     function test_belongs_to_counter_with_reassigning{
       t1 = Topic.create("title" => "t1")
       t2 = Topic.create("title" => "t2")
       r1 = Reply.new("title" => "r1", "content" => "r1")
       r1.topic = t1

       assert r1->save()
       assert_equal 1, Topic.find(t1.id).replies.size
       assert_equal 0, Topic.find(t2.id).replies.size

       r1.topic = Topic.find(t2.id)

       assert r1->save()
       assert_equal 0, Topic.find(t1.id).replies.size
       assert_equal 1, Topic.find(t2.id).replies.size

       r1.topic = nil

       assert_equal 0, Topic.find(t1.id).replies.size
       assert_equal 0, Topic.find(t2.id).replies.size

       r1.topic = t1

       assert_equal 1, Topic.find(t1.id).replies.size
       assert_equal 0, Topic.find(t2.id).replies.size

       r1.destroy

       assert_equal 0, Topic.find(t1.id).replies.size
       assert_equal 0, Topic.find(t2.id).replies.size
     }

     function test_assignment_before_parent_saved{
       client = Client.find(:first)
       apple = $firm->new("name" => "Apple")
       client.$firm = apple
       assert_equal apple, client.$firm
       assert apple.new_record?
       assert client->save()
       assert apple->save()
       assert !apple.new_record?
       assert_equal apple, client.$firm
       assert_equal apple, client.$firm(true)
     }

     function test_assignment_before_child_saved{
       final_cut = Client.new("name" => "Final Cut")
       $firm = $firm->find(1)
       final_cut.$firm = $firm
       assert final_cut.new_record?
       assert final_cut->save()
       assert !final_cut.new_record?
       assert !$firm->new_record?
       assert_equal $firm, final_cut.$firm
       assert_equal $firm, final_cut.$firm(true)
     }

     function test_assignment_before_either_saved{
       final_cut = Client.new("name" => "Final Cut")
       apple = $firm->new("name" => "Apple")
       final_cut.$firm = apple
       assert final_cut.new_record?
       assert apple.new_record?
       assert final_cut->save()
       assert !final_cut.new_record?
       assert !apple.new_record?
       assert_equal apple, final_cut.$firm
       assert_equal apple, final_cut.$firm(true)
     }

     function test_new_record_with_foreign_key_but_no_object{
       c = Client.new("$firm_id" => 1)
       assert_equal $firm->find(:first), c.$firm_with_basic_id
     }

     function test_forgetting_the_load_when_foreign_key_enters_late{
       c = Client.new
       assert_nil c.$firm_with_basic_id

       c.$firm_id = 1
       assert_equal $firm->find(:first), c.$firm_with_basic_id
     }

     function test_field_name_same_as_foreign_key{
       computer = Computer.find(1)
       assert_not_nil computer.developer, ":foreign key == attribute didn't lock up" # '
     }

     function test_counter_cache{
       topic = Topic.create :title => "Zoom-zoom-zoom"
       assert_equal 0, topic[:replies_count]

       reply = Reply.create(:title => "re: zoom", :content => "speedy quick!")
       reply.topic = topic

       assert_equal 1, topic.reload[:replies_count]
       assert_equal 1, topic.replies.size

       topic[:replies_count] = 15
       assert_equal 15, topic.replies.size
     }

     function test_custom_counter_cache{
       reply = Reply.create(:title => "re: zoom", :content => "speedy quick!")
       assert_equal 0, reply[:replies_count]

       silly = SillyReply.create(:title => "gaga", :content => "boo-boo")
       silly.reply = reply

       assert_equal 1, reply.reload[:replies_count]
       assert_equal 1, reply.replies.size

       reply[:replies_count] = 17
       assert_equal 17, reply.replies.size
     }

     function test_store_two_association_with_one_save{
       num_orders = Order.count
       num_customers = Customer.count
       order = Order.new 

       customer1 = order.billing = Customer.new
       customer2 = order.shipping = Customer.new 
       assert order->save()
       assert_equal customer1, order.billing
       assert_equal customer2, order.shipping

       order.reload

       assert_equal customer1, order.billing
       assert_equal customer2, order.shipping        

       assert_equal num_orders +1, Order.count
       assert_equal num_customers +2, Customer.count
     }


     function test_store_association_in_two_relations_with_one_save{
       num_orders = Order.count
       num_customers = Customer.count
       order = Order.new 

       customer = order.billing = order.shipping = Customer.new 
       assert order->save()
       assert_equal customer, order.billing
       assert_equal customer, order.shipping

       order.reload

       assert_equal customer, order.billing
       assert_equal customer, order.shipping        

       assert_equal num_orders +1, Order.count
       assert_equal num_customers +1, Customer.count
     }

     function test_store_association_in_two_relations_with_one_save_in_existing_object{
       num_orders = Order.count
       num_customers = Customer.count
       order = Order.create

       customer = order.billing = order.shipping = Customer.new 
       assert order->save()
       assert_equal customer, order.billing
       assert_equal customer, order.shipping

       order.reload

       assert_equal customer, order.billing
       assert_equal customer, order.shipping        

       assert_equal num_orders +1, Order.count
       assert_equal num_customers +1, Customer.count
     }

     function test_store_association_in_two_relations_with_one_save_in_existing_object_with_values{
       num_orders = Order.count
       num_customers = Customer.count
       order = Order.create

       customer = order.billing = order.shipping = Customer.new 
       assert order->save()
       assert_equal customer, order.billing
       assert_equal customer, order.shipping

       order.reload

       customer = order.billing = order.shipping = Customer.new 

       assert order->save()
       order.reload    

       assert_equal customer, order.billing
       assert_equal customer, order.shipping        

       assert_equal num_orders +1, Order.count
       assert_equal num_customers +2, Customer.count
     }


     function test_association_assignment_sticks{
       post = Post.find(:first)

       author1, author2 = Author.find(:all, :limit => 2)
       assert_not_nil author1
       assert_not_nil author2

       # make sure the association is loaded
       post.author

       # set the association by id, directly
       post.author_id = author2.id

       # save and reload
       post->save()!
       post.reload

       # the author id of the post should be the id we set
       assert_equal post.author_id, author2.id
     }
*/
   }

/*
   class ProjectWithAfterCreateHook < ActiveRecord::Base
     set_table_name 'projects'
     has_and_belongs_to_many :developers,
       :class_name => "DeveloperForProjectWithAfterCreateHook",
       :join_table => "developers_projects",
       :foreign_key => "project_id",
       :association_foreign_key => "developer_id"

     after_create :add_david

     def add_david
       david = DeveloperForProjectWithAfterCreateHook.find_by_name('David')
       david.projects << self
     }
   }

   class DeveloperForProjectWithAfterCreateHook < ActiveRecord::Base
     set_table_name 'developers'
     has_and_belongs_to_many :projects,
       :class_name => "ProjectWithAfterCreateHook",
       :join_table => "developers_projects",
       :association_foreign_key => "project_id",
       :foreign_key => "developer_id"
   }

*/

   class HasAndBelongsToManyAssociationsTest extends UnitTestCase{
       function __construct(){
          $f=new XorcStore_Fixtures(XorcStore_Connector::get(), dirname(__FILE__)."/fixtures");
          $f->load("companies", "topics", "entrants", "developers", "developers_projects", "posts", 
             "accounts", "computers", "projects");
          $f->db->debug=false;
          $f->empty_db();
          $f->load_db();
          $f->db->debug=true;
          $this->f=$f->fixtures;
       }

       
     function test_has_and_belongs_to_many(){
        $d=new Developer;
       $david = $d->find(1);

       $this->assertFalse($david->projects->is_empty());
       $this->assertEqual(2, count($david->projects));

       $p=new Project;
       $ar = $p->find(1);
       $this->assertFalse($ar->developers->is_empty());
       $this->assertEqual(3, sizeof($ar->developers));
 #      print_r($ar->developers->to_array());
       $this->assertTrue($ar->developers->includes($david));
     }

     function test_triple_equality(){
 #      assert !(Array === Developer.find(1).projects)
 #      assert Developer.find(1).projects === Array
     }

     function test_adding_single(){
        $d=new Developer; $p=new Project;
       $jamis = $d->find(2);

       $jamis->projects->reload(); # causing the collection to load 
       $ac = $p->find(2);
       $this->assertEqual(1, count($jamis->projects));
       $this->assertEqual(1, count($ac->developers));    
print "PROJ-ADD\n";
print_r($ac);
       $jamis->projects[] = $ac;

        $this->assertEqual( 2, count($jamis->projects));
        $this->assertEqual( 2, $jamis->projects->count(true));
        $this->assertEqual( 2, $ac->developers->count(true));
     }


     function test_adding_type_mismatch(){
#       jamis = Developer.find(2)
#       assert_raise(ActiveRecord::AssociationTypeMismatch) { jamis.projects << nil }
#       assert_raise(ActiveRecord::AssociationTypeMismatch) { jamis.projects << 1 }
     }

     function test_adding_from_the_project(){
        $this->__construct();
        $d=new Developer; $p=new Project;
       $jamis = $d->find(2);
       $action_controller = $p->find(2);
       $action_controller->developers->reload();
       $this->assertEqual( 1, $jamis->projects->count());
       $this->assertEqual( 1, $action_controller->developers->count());
print "JAM\n";
       $action_controller->developers[] = $jamis; 
print "\JAM\n";
       $this->assertEqual( 2, $jamis->projects->get(true)->count());
       $this->assertEqual( 2, $action_controller->developers->count());
       $this->assertEqual( 2, $action_controller->developers->get(true)->count());
     }

     function test_adding_from_the_project_fixed_timestamp(){
        $this->__construct();
        $d=new Developer; $p=new Project;
       $jamis = $d->find(2);
       $action_controller = $p->find(2);
       $action_controller->developers->reload();
       $this->assertEqual( 1, $jamis->projects->count());
       $this->assertEqual( 1, $action_controller->developers->count());
       $updated_at = $jamis->updated_at;

       $action_controller->developers[] = $jamis;

       $this->assertEqual($updated_at, $jamis->updated_at);
       $this->assertEqual( 2, $jamis->projects->get(true)->count());
       $this->assertEqual( 2, $action_controller->developers->count());
       $this->assertEqual( 2, $action_controller->developers->get(true)->count());
     }

     function test_adding_multiple(){
        $this->__construct();
        $d=new Developer; $p=new Project;
       $aredridel = new Developer(array("name" => "Aredridel"));
       $aredridel->save();
       $aredridel->projects->reload();
       $aredridel->projects[]=array($p->find(1), $p->find(2));
       $this->assertEqual( 2, $aredridel->projects->count());
       $this->assertEqual( 2, $aredridel->projects->get(true)->count());
     }

     function test_adding_a_collection(){
        $d=new Developer; $p=new Project;
#       $aredridel = $d->new(array("name" => "Aredridel"));
#       $aredridel->save();
#       $aredridel->projects->reload();
#       $aredridel->projects->concat([Project->find(1), Project->find(2)]);
#       $this->assertEqual( 2, aredridel->projects->size());
#       $this->assertEqual( 2, aredridel->projects(true).size());
     }
/*
     function test_adding_uses_default_values_on_join_table{
       ac = projects(:action_controller)
       assert !developers(:jamis).projects.include?(ac)
       developers(:jamis).projects << ac

       assert developers(:jamis, :reload).projects.include?(ac)
       project = developers(:jamis).projects.detect { |p| p == ac }
       assert_equal 1, project.access_level.to_i
     }

     function test_adding_uses_explicit_values_on_join_table{
       ac = projects(:action_controller)
       assert !developers(:jamis).projects.include?(ac)
       developers(:jamis).projects.push_with_attributes(ac, :access_level => 3)

       assert developers(:jamis, :reload).projects.include?(ac)
       project = developers(:jamis).projects.detect { |p| p == ac }
       assert_equal 3, project.access_level.to_i
     }

     function test_hatbm_attribute_access_and_respond_to{
       project = developers(:jamis).projects[0]
       assert project.has_attribute?("name")
       assert project.has_attribute?("joined_on")
       assert project.has_attribute?("access_level")
       assert project.respond_to?("name")
       assert project.respond_to?("name=")
       assert project.respond_to?("name?")
       assert project.respond_to?("joined_on")
       assert project.respond_to?("joined_on=")
       assert project.respond_to?("joined_on?")
       assert project.respond_to?("access_level")
       assert project.respond_to?("access_level=")
       assert project.respond_to?("access_level?")
     }

     function test_habtm_adding_before_save{
       no_of_devels = Developer.count
       no_of_projects = Project.count
       aredridel = Developer.new("name" => "Aredridel")
       aredridel.projects.concat([Project.find(1), p = Project.new("name" => "Projekt")])
       assert aredridel.new_record?
       assert p.new_record?
       assert aredridel->save()
       assert !aredridel.new_record?
       assert_equal no_of_devels+1, Developer.count
       assert_equal no_of_projects+1, Project.count
       assert_equal 2, aredridel.projects.size
       assert_equal 2, aredridel.projects(true).size
     }

     function test_habtm_adding_before_save_with_join_attributes{
       no_of_devels = Developer.count
       no_of_projects = Project.count
       now = Date.today
       ken = Developer.new("name" => "Ken")
       ken.projects.push_with_attributes( Project.find(1), :joined_on => now )
       p = Project.new("name" => "Foomatic")
       ken.projects.push_with_attributes( p, :joined_on => now )
       assert ken.new_record?
       assert p.new_record?
       assert ken->save()
       assert !ken.new_record?
       assert_equal no_of_devels+1, Developer.count
       assert_equal no_of_projects+1, Project.count
       assert_equal 2, ken.projects.size
       assert_equal 2, ken.projects(true).size

       kenReloaded = Developer.find_by_name 'Ken'
       kenReloaded.projects.each {|prj| assert_date_from_db(now, prj.joined_on)}
     }

     function test_habtm_saving_multiple_relationships{
       new_project = Project.new("name" => "Grimetime")
       amount_of_developers = 4
       developers = (0..amount_of_developers).collect {|i| Developer.create(:name => "JME #{i}") }

       new_project.developer_ids = [developers[0].id, developers[1].id]
       new_project.developers_with_callback_ids = [developers[2].id, developers[3].id]
       assert new_project->save()

       new_project.reload
       assert_equal amount_of_developers, new_project.developers.size
       amount_of_developers.times do |i|
         assert_equal developers[i].name, new_project.developers[i].name
       }
     }

     function test_build{
       devel = Developer.find(1)
       proj = devel.projects.build("name" => "Projekt")
       assert_equal devel.projects.last, proj
       assert proj.new_record?
       devel->save()
       assert !proj.new_record?
       assert_equal devel.projects.last, proj
     }

     function test_create{
       devel = Developer.find(1)
       proj = devel.projects.create("name" => "Projekt")
       assert_equal devel.projects.last, proj
       assert !proj.new_record?
     }

     function test_uniq_after_the_fact{
       developers(:jamis).projects << projects(:active_record)
       developers(:jamis).projects << projects(:active_record)
       assert_equal 3, developers(:jamis).projects.size
       assert_equal 1, developers(:jamis).projects.uniq.size
     }

     function test_uniq_before_the_fact{
       projects(:active_record).developers << developers(:jamis)
       projects(:active_record).developers << developers(:david)
       assert_equal 3, projects(:active_record, :reload).developers.size
     }

     function test_deleting{
       david = Developer.find(1)
       active_record = Project.find(1)
       david.projects.reload
       assert_equal 2, david.projects.size
       assert_equal 3, active_record.developers.size

       david.projects.delete(active_record)

       assert_equal 1, david.projects.size
       assert_equal 1, david.projects(true).size
       assert_equal 2, active_record.developers(true).size
     }

     function test_deleting_array{
       david = Developer.find(1)
       david.projects.reload
       david.projects.delete(Project.find(:all))
       assert_equal 0, david.projects.size
       assert_equal 0, david.projects(true).size
     }

     function test_deleting_with_sql{
       david = Developer.find(1)
       active_record = Project.find(1)
       active_record.developers.reload
       assert_equal 3, active_record.developers_by_sql.size

       active_record.developers_by_sql.delete(david)
       assert_equal 2, active_record.developers_by_sql(true).size
     }

     function test_deleting_array_with_sql{
       active_record = Project.find(1)
       active_record.developers.reload
       assert_equal 3, active_record.developers_by_sql.size

       active_record.developers_by_sql.delete(Developer.find(:all))
       assert_equal 0, active_record.developers_by_sql(true).size
     }

     function test_deleting_all{
       david = Developer.find(1)
       david.projects.reload
       david.projects.clear
       assert_equal 0, david.projects.size
       assert_equal 0, david.projects(true).size
     }

     function test_removing_associations_on_destroy{
       david = DeveloperWithBeforeDestroyRaise.find(1)
       assert !david.projects.empty?
       assert_nothing_raised { david.destroy }
       assert david.projects.empty?
       assert DeveloperWithBeforeDestroyRaise.connection.select_all("SELECT * FROM developers_projects WHERE developer_id = 1").empty?
     }

     function test_additional_columns_from_join_table{
       assert_date_from_db Date.new(2004, 10, 10), Developer.find(1).projects.first.joined_on
     }

     function test_destroy_all{
       david = Developer.find(1)
       david.projects.reload
       assert !david.projects.empty?
       david.projects.destroy_all
       assert david.projects.empty?
       assert david.projects(true).empty?
     }

     function test_rich_association{
       jamis = developers(:jamis)
       jamis.projects.push_with_attributes(projects(:action_controller), :joined_on => Date.today)

       assert_date_from_db Date.today, jamis.projects.select { |p| p.name == projects(:action_controller).name }.first.joined_on
       assert_date_from_db Date.today, developers(:jamis).projects.select { |p| p.name == projects(:action_controller).name }.first.joined_on
     }

     function test_associations_with_conditions{
       assert_equal 3, projects(:active_record).developers.size
       assert_equal 1, projects(:active_record).developers_named_david.size

       assert_equal developers(:david), projects(:active_record).developers_named_david.find(developers(:david).id)
       assert_equal developers(:david), projects(:active_record).salaried_developers.find(developers(:david).id)

       projects(:active_record).developers_named_david.clear
       assert_equal 2, projects(:active_record, :reload).developers.size
     }

     function test_find_in_association{
       # Using sql
       assert_equal developers(:david), projects(:active_record).developers.find(developers(:david).id), "SQL find"

       # Using ruby
       active_record = projects(:active_record)
       active_record.developers.reload
       assert_equal developers(:david), active_record.developers.find(developers(:david).id), "Ruby find"
     }

     function test_find_in_association_with_custom_finder_sql{
       assert_equal developers(:david), projects(:active_record).developers_with_finder_sql.find(developers(:david).id), "SQL find"

       active_record = projects(:active_record)
       active_record.developers_with_finder_sql.reload
       assert_equal developers(:david), active_record.developers_with_finder_sql.find(developers(:david).id), "Ruby find"
     }

     function test_find_in_association_with_custom_finder_sql_and_string_id{
       assert_equal developers(:david), projects(:active_record).developers_with_finder_sql.find(developers(:david).id.to_s), "SQL find"
     }

     function test_find_with_merged_options{
       assert_equal 1, projects(:active_record).limited_developers.size
       assert_equal 1, projects(:active_record).limited_developers.find(:all).size
       assert_equal 3, projects(:active_record).limited_developers.find(:all, :limit => nil).size
     }

     function test_new_with_values_in_collection{
       jamis = DeveloperForProjectWithAfterCreateHook.find_by_name('Jamis')
       david = DeveloperForProjectWithAfterCreateHook.find_by_name('David')
       project = ProjectWithAfterCreateHook.new(:name => "Cooking with Bertie")
       project.developers << jamis
       project->save()!
       project.reload

       assert project.developers.include?(jamis)
       assert project.developers.include?(david)
     }

     function test_find_in_association_with_options{
       developers = projects(:active_record).developers.find(:all)
       assert_equal 3, developers.size

       assert_equal developers(:poor_jamis), projects(:active_record).developers.find(:first, :conditions => "salary < 10000")
       assert_equal developers(:jamis),      projects(:active_record).developers.find(:first, :order => "salary DESC")
     }

     function test_replace_with_less{
       david = developers(:david)
       david.projects = [projects(:action_controller)]
       assert david->save()
       assert_equal 1, david.projects.length
     }

     function test_replace_with_new{
       david = developers(:david)
       david.projects = [projects(:action_controller), Project.new("name" => "ActionWebSearch")]
       david->save()
       assert_equal 2, david.projects.length
       assert !david.projects.include?(projects(:active_record))
     }

     function test_replace_on_new_object{
       new_developer = Developer.new("name" => "Matz")
       new_developer.projects = [projects(:action_controller), Project.new("name" => "ActionWebSearch")]
       new_developer->save()
       assert_equal 2, new_developer.projects.length
     }

     function test_consider_type{
       developer = Developer.find(:first)
       special_project = SpecialProject.create("name" => "Special Project")

       other_project = developer.projects.first
       developer.special_projects << special_project
       developer.reload

       assert developer.projects.include?(special_project)
       assert developer.special_projects.include?(special_project)
       assert !developer.special_projects.include?(other_project)
     }

     function test_update_attributes_after_push_without_duplicate_join_table_rows{
       developer = Developer.new("name" => "Kano")
       project = SpecialProject.create("name" => "Special Project")
       assert developer->save()
       developer.projects << project
       developer.update_attribute("name", "Bruza")
       assert_equal 1, Developer.connection.select_value(<<-}_sql).to_i
         SELECT count(*) FROM developers_projects
         WHERE project_id = #{project.id}
         AND developer_id = #{developer.id}
       }_sql
     }

     function test_updating_attributes_on_non_rich_associations{
       welcome = categories(:technology).posts.first
       welcome.title = "Something else"
       assert welcome->save()!
     }

     function test_updating_attributes_on_rich_associations{
       david = projects(:action_controller).developers.first
       david.name = "DHH"
       assert_raises(ActiveRecord::ReadOnlyRecord) { david->save()! }
     }


     function test_updating_attributes_on_rich_associations_with_limited_find{
       david = projects(:action_controller).developers.find(:all, :select => "developers.*").first
       david.name = "DHH"
       assert david->save()!
     }

     function test_join_table_alias{
       assert_equal 3, Developer.find(:all, :include => {:projects => :developers}, :conditions => 'developers_projects_join.joined_on IS NOT NULL').size
     }
     
   */  
} 

?>