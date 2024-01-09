<?php

$c = contest::lookup('20sec');

if(!$c){
   $c = contest::i()->create(['slug'=>'20sec', 'title'=>'dev contest', 'descr'=>'20sec.net developer FOTOWETTBEWERB',
      'finished_at'=>date('Y-m-d H:i:s', strtotime("+2 months"))
      ]);
   if($c->errors->count()) print $c->errors->all_as_string()."\n";
}

$u = user::i()->find_by_email('rw@20sec.net');
if(!$u){
   $u = new user;
   $u->set(['email'=>'rw@20sec.net', 'uname'=>'rw123456', 'role'=>'adm', 'status'=>1, 'contest_id'=>$c->id,
      'age'=>99, 'passwd'=>'12345678', 'passwd_confirm'=>'12345678', 
      'postalcode'=>'10245', 'city'=>'Berlin', 'country'=>'de', 'toc'=>1]);
   $u->save();
}
$u->password_update('12345678');

$u = user::i()->find_by_email('jury1@20sec.net');
if(!$u){
   $u = new user;
   $u->set(['email'=>'jury1@20sec.net', 'uname'=>'jury0001', 'lname'=>'Gerda', 'role'=>'jur', 'status'=>1, 'contest_id'=>$c->id,
      'age'=>99, 'passwd'=>'12345678', 'passwd_confirm'=>'12345678', 
      'postalcode'=>'10245', 'city'=>'Berlin', 'country'=>'de', 'toc'=>1]);
   $u->save();
}
if($u->errors->count()) print $u->errors->all_as_string()."\n";
else $u->password_update('12345678');

$u = user::i()->find_by_email('jury2@20sec.net');
if(!$u){
   $u = new user;
   $u->set(['email'=>'jury2@20sec.net', 'uname'=>'jury0002', 'lname'=>'Gerdhelm', 'role'=>'jur', 'status'=>1, 'contest_id'=>$c->id,
      'age'=>99, 'passwd'=>'12345678', 'passwd_confirm'=>'12345678', 
      'postalcode'=>'10245', 'city'=>'Berlin', 'country'=>'de', 'toc'=>1]);
   $u->save();
}
if($u->errors->count()) print $u->errors->all_as_string()."\n";
else $u->password_update('12345678');

$c->make_dirs();
