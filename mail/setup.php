<?php
namespace xorc;

require_once(__DIR__.'/view.php');
require_once(__DIR__.'/mailer.php');

if(!function_exists(__NAMESPACE__.'\log_debug')){
	
	function log_debug($m){
	   \log_error($m);
	}

	function log_info($m){
	   \log_error($m);
	}

	function log_warning($m){
	   \log_error($m);
	}

	function log_error($m){
	   \log_error($m);
	}

}



/*

# install swiftmailer
$ composer require swiftmailer/swiftmailer


# use 

require_once("xorc/mail/setup.php");
xorc\mailer::conf(xorc_ini('mail'));
xorc\mailer::send('welcome', $this->registration->email, 
				array('u'=>$this->registration));


# templates
# .txt files REQUIRED, .html files OPTIONAL

src/mails/_layout.html
src/mails/welcome.html
src/mails/welcome.txt

# welcome.txt

subject: <?=$sitename?> // Registrierung abschließen

Sehr geehrte/r <?=$u->name?>,  
...


# ini file

[mail]
; versand (technisch)
transport="smtp://robert.wagner@20sec.net:geheim@mail.20sec.de:465"

ssl=1
ssl_nocert=1

; oder
; ssl context vars ssl_*
; https://www.php.net/manual/de/context.ssl.php
;ssl_nocert=1
ssl_cafile = /Users/rw/dev/cacert.pem

;transport="smtp://localhost:25"

;transport="sendmail://localhost/usr/sbin/sendmail"

;pretend=1

; versand, default header

from=literaturpreis <no-reply@20sec.de>
return-path=bounce@20sec.de
;reply-to = #monitor


; variablen
sitename=HKW
monitor=rw@20sec.net


*/

?>