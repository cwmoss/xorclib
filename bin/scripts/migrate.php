<?php

include_once(XORC_LIB_PATH . "/db/xorcstore_migration.class.php");
include_once(XORC_LIB_PATH . "/db/xorcstore_connector.class.php");

$command = $margs[0];

$migdir = $opts['migrations'];
if (!$migdir) $migdir = dirname($mypath) . "/db";

$con = null;
if ($opts['db']) {
   $con = new XorcStore_Connector("_db", ['dsn' => $opts['db'], 'prefix' => $opts['prefix']]);
}
if ($command != 'new') {
   // migrationobject is not needed for classfile generation
   $m = new Xorcstore_Migration(XorcStore_Connector::get($con));
}


if ($command == "dump" || $command == "dumpdata" || $command == "dumpdatacsv") {

   if ($command == "dump") {
      $m->dump();
   } else {
      $file = $margs[1];
      if (!$file) die("please provide a datafile to write serial data.\n");
      if ($command == "dumpdatacsv") {
         $table = $margs[2];
         $m->dump_data_csv($table, $file);
      } else {
         $table = $margs[2];
         if (trim($table)) $tables = explode(",", $table);
         else $tables = array();
         $m->dump_data($file, $tables);
      }
   }
} elseif ($command == "load") {
   $file = $margs[1];
   if (!$file) die("please provide a migrationfile to load.\n");

   include($file);

   $direction = $margs[2];
   $clasn = "Migration_" . str_replace('-', '_', basename($file, '.php'));
   $m = new $clasn(XorcStore_Connector::get($con));
   if ($direction == "down") {
      $m->down();
   } else {
      $m->up();
   }
} elseif ($command == "loaddata") {
   $file = $margs[1];
   if (!$file) die("please provide a serialize datafile to load.\n");
   $m->load_data($file);
} elseif ($command == "init") {
   $m->version_table();
} elseif ($command == "sessions") {
   $m->session_table();
} elseif ($command == 'new') {

   include_once(__DIR__ . "/_genlib.php");

   $title = $margs[1];
   $title = preg_replace("/[^-_a-z0-9]/", "", $title);
   if (!$title) die("please provide a migrationtitle\n");
   $clasn = str_replace("-", "_", $title);

   $versions = find_versions($migdir);
   $nextversion = array_pop(array_keys($versions));
   $nextversion++;
   $filename = sprintf("%03d_%s.php", $nextversion, $title);
   $tpl = resolve_template("migration.php", array("migration-title" => $clasn));
   write_files(
      array($filename => $tpl),
      $migdir,
      "generating new migrationfile $filename\n"
   );
} else {
   $dry = false;
   if ($command == "dry") $dry = true;

   $versions = find_versions($migdir);
   $src = $m->version();

   //   print_r($versions);
   if ($src === null) die("versiontable missing\n");

   if (preg_match("/^version=(\d+)$/", (string) $command, $mat)) {
      $dest = $mat[1];
      if ($margs[1] == "dry") $dry = true;
   } else {
      $dest = array_pop(array_keys($versions));
   }

   print "-- START MIGRATION FROM VERSION $src TO $dest => dry? $dry\n";

   if ($src == $dest) {
      print("-- application is up to date (version=$src)\n");
      exit;
   }
   if ($src < $dest) {
      $dir = 1;
      $display = "upgrading";
   } else {
      $dir = -1;
      $display = "downgrading";
      $versions = array_reverse($versions);
   }

   foreach ($versions as $v) {
      if (($dir == 1 && $v[2] > $src && $v[2] <= $dest) ||
         ($dir == -1 && $v[2] <= $src && $v[2] > $dest)
      ) {

         print("-- $display with migrationfile {$v[2]} [{$v[0]} in {$v[1]}]\n");
         include($v[1]);
         $clasn = $v[0];
         $m = new $clasn(XorcStore_Connector::get($con));
         $m->dry = $dry;
         if ($dir == 1) {
            $m->up();
            $version = $v[2];
         } else {
            $m->down();
            $version = $v[2] - 1;
         }
         $m->version($version);
      }
   }
}

function find_versions($basedir) {
   $versions = array();
   foreach (glob("$basedir/*.php") as $f) {
      print "-- testing file $f";
      if (preg_match("/^(\d+)_([-_a-z0-9]+)/", basename($f), $mat)) {
         print "... OK\n";
         $versions[sprintf("%d", $mat[1])] = array(
            "Migration_" . str_replace("-", "_", $mat[2]),
            $f,
            sprintf("%d", $mat[1])
         );
      } else {
         print "... skip\n";
      }
   }
   ksort($versions);
   return $versions;
}

/** help!
migrationtools use -h migrate for all options

all migrations are done against the database
specified with --db=DSN --prefix=PREFIX

migrate [version=VERSION_NUMBER] [dry]
   up/ downgrade to highest version or VERSION_NUMBER
   dry means all ddl is printed but not executed
   all migrations are in db/VERSION_NUMBER_description.php
   
migrate new description
   generate new migrationfile in db/ directory
   
migrate init
   generating versiontable for database/ prefix
   
migrate dump
   prints schema migration to screen
   use dump > db/schemafilename.php for further use with load
   
migrate load migrationfile [down]
   load migrationfile to create a schema
   option down reverses the ddl
   
migrate dumpdata migrationdatafile
   creates a database dump
   
migrate loaddata migrationdatafile
   loads a database dump
 */
