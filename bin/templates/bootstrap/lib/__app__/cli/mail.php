<?php

print_r(stream_get_transports());


#$to = 'robbie.wilhelm@gmail.com';
$to = 'rw@20sec.net';

xorcapp::$inst->load_controller('register');

xorc\mailer::send('welcome', array('to'=>$to), array('name'=>'Robbie'));