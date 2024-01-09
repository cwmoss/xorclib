<?php
include_once("xorc/db/xorcstore_migration.class.php");
include_once("xorc/db/xorcstore_connector.class.php");

if($opts['db']){
   XorcStore_Connector::set("_db", 
      array('dsn'=>$opts['db'], 'prefix'=>$opts['prefix']));
}else{
   new XorcStore_Connector;
}

$m=new XorcStore_Migration(XorcStore_Connector::get());

$tables=$margs[0];
$seq=$margs[1];

// einzelmodus
//    tabellenname MIT prefix + sequencename MIT prefix
   if($tables && $seq){
      print "restoring single sequence $seq for table $table\n";
      $m->restore_sequence($tables, $seq);
      
// dbmodus
//    alle tabellennamen werden aus der datenbank gelesen
//    evtl. einschränkungen tab1,tab2,... OHNE prefix
   }elseif(!$tables || ($tables && !preg_match("!/!", $tables))){
      print <<<EWARN
!!!!
!!!!  restoring sequences are made without any knowledge
!!!!  of the actual models
!!!!  it follows the standard naming theme
!!!!       <prefixed-table-name>_seq
!!!!  if you use other sequences, you have to update yourself
!!!!

EWARN;
      if($tables) $tables=explode(",", $tables);
      $m->restore_sequences($tables);      

// modellmodus
//    tabellen werden aus ini files gelesen
//    und mit datenbank abgeglichen
   }else{
      print "scanning schemafiles in directory $tables\n";
      foreach(glob($tables."/*.ini") as $ini){
         $conf=parse_ini_file($ini, true);
#         print_r($conf);
         if($conf['table']['name'] && $conf['table']['sequence']){
            print "restoring sequence ".$conf['table']['sequence']." for table ".
               $conf['table']['name']." found in $ini\n";
            $m->restore_sequence($m->table($conf['table']['name']), $m->table($conf['table']['sequence']));
         }
      }
   }
   



?>