<?php

$loader = require 'vendor/autoload.php';
// $loader->add('twentyseconds\\', dirname(__DIR__)."/");

class <app-name> extends XorcApp{
	var $include_path=__FILE__;

	function load_classes(){
      	include_once("xorc/mvc/xorc_objectform.class.php");
      	include_once("xorc/mvc/formtag_helper.php");
		include_once("xorc/div/util.php");
#		include_once("xorc/text/markdown/markdown.php");
		include_once("<app-name>/app_helper.php");


    	Xorc_Autoloader::add("autoloader_xorc");
      	Xorc_Autoloader::add("autoloader_pear");
      	mb_internal_encoding("UTF-8");
      	$this->deutsch();
      	initialize_app();
      	$this->mails = $this->appbase."/src/mails";
	}

	function deutsch(){
	   Xorcstore_Validation::$msg = array_merge(Xorcstore_Validation::$msg,
	   array(
		   'empty' => "%s darf nicht leer sein",
		   'blank' => "%s darf nicht leer sein",
		   'taken' => "%s ist bereits in Benutzung",
			'invalid' => "%s ist ungültig",
		   'too_long' => "%s ist zu lang (maximal %d Zeichen)",
		   'too_short' => "%s ist zu kurz (mind. %d Zeichen)",
		   'wrong_length' => "%s hat die falsche Länge. (sollen %d zeichen sein)",
		   'not_a_number' => "%s ist keine Zahl.",
		   'accepted' => "%s muß zugestimmt werden",
		   'inclusion' => "%s ist nicht in der Liste der erlaubten Werte",
		   'exclusion' => "%s ist reserviert.",
		   'confirmation' => "%s stimmt nicht überein."
	   ));
	   validator::update_messages(array(
		   'empty' => "{name} darf nicht leer sein",
		   'blank' => "{name} darf nicht leer sein",
		   'taken' => "{name} ist bereits in Benutzung",
			'invalid' => "{name} ist ungültig",
		   'too_long' => "{name} ist zu lang (maximal {val} Zeichen)",
		   'too_short' => "{name} ist zu kurz (mind. {val} Zeichen)",
		   'wrong_length' => "{name} hat die falsche Länge. (sollen {val} zeichen sein)",
		   'not_a_number' => "{name} ist keine Zahl.",
		   'accepted' => "{name} muß zugestimmt werden",
		   'inclusion' => "{name} ist nicht in der Liste der erlaubten Werte",
		   'exclusion' => "{name} ist reserviert.",
		   'confirmation' => "{name} stimmt nicht überein."
	   ));
	}
}

