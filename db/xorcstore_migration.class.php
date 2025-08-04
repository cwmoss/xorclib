<?php

class Xorcstore_Migration {

   var $db;

   /**
    * @var ADODB_DataDict
    */
   var $dict;
   var $dry = false;

   function __construct($db = null) {
      if (!is_object($db)) {
         $dummy = null;
         $this->dict = NewDataDictionary($dummy, $db);
      } else {
         $this->dict = NewDataDictionary($db);
         $this->db = $db;
      }
   }

   function create_table($tab, $cols) {
      //last character is ',' ? cut it off!
      $cols = trim($cols);
      if ($cols[strlen($cols) - 1] == ',') $cols = substr($cols, 0, strlen($cols) - 1);

      // print "$cols\n";

      $ct = $this->dict->CreateTableSQL($this->table($tab), $cols);

      // NO TRIGGERS
      foreach ($ct as $k => $ddl) {
         if (preg_match("/ trigger /i", $ddl)) unset($ct[$k]);
      }
      $this->exec($ct);
   }

   function drop_table($tab) {
      $this->exec($this->dict->DropTableSQL($this->table($tab)));
   }

   function rename_table($old, $new) {
      $this->exec($this->dict->RenameTableSQL($this->table($old), $this->table($new)));
   }

   function unrename_table($old, $new) {
      $this->rename_table($new, $old);
   }

   function alter_table($tab, $cols) {
      $this->exec($this->dict->ChangeTableSQL($this->table($tab), $cols));
   }

   function add_column($tab, $col) {
      $this->exec($this->dict->AddColumnSQL($this->table($tab), $col));
   }

   function drop_column($tab, $col) {
      $this->exec($this->dict->DropColumnSQL($this->table($tab), $col));
   }

   function alter_column($tab, $col) {
      $this->exec($this->dict->AlterColumnSQL($this->table($tab), $col));
   }

   function rename_column($tab, $old, $new) {
      $tab = $this->table($tab);
      $sql = $this->dict->RenameColumnSQL($tab, $old, $new);

      //mysql(i) want to know the fieldtype again...
      if (preg_match("/mysql/", $this->dict->connection->databaseType)) {
         $sql[0] .= ' ' . $this->type_for_column($tab, $old);
      }

      $this->exec($sql);
   }

   function type_for_column($table, $column) {
      $schema = "";
      $this->dict->connection->_findschema($table, $schema);

      if ($schema) {
         $dbName = $this->dict->connection->database;
         $this->dict->connection->SelectDB($schema);
      }
      global $ADODB_FETCH_MODE;
      $save = $ADODB_FETCH_MODE;
      $ADODB_FETCH_MODE = ADODB_FETCH_NUM;

      if ($this->dict->connection->fetchMode !== false) $this->dict->connection->SetFetchMode(false);
      $rs = $this->dict->connection->Execute(sprintf($this->dict->connection->metaColumnsSQL, $table));

      while (!$rs->EOF) {
         if ($rs->fields[0] == $column) {
            $found = $rs->fields[1];
            break;
         }
         $rs->MoveNext();
      }
      $rs->Close();

      $ADODB_FETCH_MODE = $save;

      #if(!$found)throw new Exception("No fieldtype found for Table: $table - Column: $column");
      return $found;
   }

   function print_column_lengths($table) {
      $columns = $this->dict->MetaColumns($this->table($table));
      foreach ($columns as $column) {
         if (!$column->max_length || $column->type != 'varchar') continue;
         print "'" . $column->name . "'" . ' => ' . $column->max_length . ",\n";
      }
   }

   function unrename_column($tab, $old, $new) {
      $this->rename_column($tab, $new, $old);
   }

   function create_index($tab, $cols, $extra = null, $idxname = null) {
      if ($extra == "UNIQUE") $extra = array($extra);
      if (!$idxname) $idxname = $this->index($tab, $cols);
      $this->exec($this->dict->CreateIndexSQL($idxname, $this->table($tab), $cols, $extra));
   }

   function drop_index($tab, $idxname) {
      $this->exec($this->dict->DropIndexSQL($this->index($tab, $idxname), $this->table($tab)));
   }

   function ddl($table, $ddl) {
      $ddl = str_replace("%TABLE%", $this->table($table), $ddl);
      $this->exec(array($ddl));
   }

   function exec($ddl) {
      $debug = join("\n", $ddl);
      print "-- Generated DDL for Migration\n$debug\n";
      if (!$this->dry) $this->dict->ExecuteSQLArray($ddl);
   }

   function table_exists($tab) {
      $tabs = $this->db->MetaTables('TABLES');
      return in_array($this->table($tab), $tabs);
   }

   function dump() {
      $prefix = $this->db->prefix;
      print("<?php\nclass MigrationDump extends Xorcstore_Migration{\n");
      $up = $down = array();
      foreach ($this->dict->MetaTables() as $t) {
         print "TABLE $t";
         if ($prefix && preg_match("/^{$prefix}_(.*)$/", $t, $mat)) {
            $tab = $mat[1];
         } else {
            if ($prefix) continue;
            $tab = $t;
         }
         $up[] = "\$this->create_table('$tab', '";
         $down[] = "\$this->drop_table('$tab');";
         $cols = $this->dict->MetaColumns($t);
         $keys = $this->dict->MetaPrimaryKeys($t);
         if (!is_array($keys)) $keys = array();
         $keys = array_flip($keys);
         $bigtext = array();
         foreach ($cols as $c) {
            $type = $this->dict->MetaType($c);
            if ($type == "R") $type = "I AUTO";
            if ($type == 'X' && $c->max_length > 0 && $c->max_length < 256) {
               $type = 'C';
            }
            if ($type == "C") $type .= "({$c->max_length})";
            if ($type == "X") $bigtext[] = $c->name;
            if ($type == 'R' || isset($keys[$c->name])) $type .= " KEY";
            $up[] = '      ' . $c->name . " $type,";
         }
         array_push($up, rtrim(array_pop($up), ","));
         $up[] = "   ');";
         $idx = $this->dict->MetaIndexes($t);
         //        print("INDEX fuer $t:\n");print_r($idx);
         foreach ($idx as $iname => $i) {
            if ($prefix)
               $iname = preg_replace("/^{$prefix}_/", "", $iname);
            // we do not create an index for primary keys
            if (sizeof(array_diff($i['columns'], array_flip($keys))) == 0) continue;
            // we do not create (full) text index
            if (sizeof(array_diff($bigtext, $i['columns'])) != sizeof($bigtext)) continue;
            $U = ($i['unique']) ? ', "UNIQUE"' : "";
            $up[] = "\$this->create_index('$tab', '" . join(",", $i['columns']) . "'$U);";
         }
      }
      print("   function up(){\n      " . join("\n      ", $up) . "\n   }\n");
      print("   function down(){\n      " . join("\n      ", $down) . "\n   }\n");
      print("}\n?>");
   }

   function dump_data($file, $tables = array()) {
      //      Xorc::use_yaml();
      $fp = fopen($file, 'wb');
      $prefix = $this->db->prefix;

      foreach ($this->dict->MetaTables() as $t) {
         if ($prefix && preg_match("/^{$prefix}_(.*)$/", $t, $mat)) {
            $tab = $mat[1];
         } else {
            if ($prefix) continue;
            $tab = $t;
         }

         if ($tables && !in_array($tab, $tables)) continue;

         $bools = array();
         foreach ($this->dict->MetaColumns($t) as $col) {
            if ($this->dict->MetaType($col) == 'L') {
               $bools[] = $col->name;
            }
         }
         fwrite($fp, "===$tab===\n");
         $rs = $this->db->Execute("SELECT * FROM $t");
         while ($rs && $arr = $rs->FetchRow()) {
            //           $res=array();
            //           foreach($arr as $k=>$v){$res['`'.$k.'`']=$v;}  ## useless :(
            foreach ($bools as $bool) {
               if ($arr[$bool] == "t") $arr[$bool] = 1;
               elseif ($arr[$bool] == "f") $arr[$bool] = 0;
            }
            fwrite($fp, "-" . serialize($arr) . "\n");
            //           fwrite($fp, Spyc::YAMLDump($arr)); // Spyc::YAMLLoad
         }
      }
      fclose($fp);
   }

   function dump_data_csv($table, $dir) {
      $colsep = "@@@-@@@";
      $rowsep = "\n<#@@-@#@-@@#>\n";
      $fp = fopen($dir . "/" . $table . "-export.csv", 'wb');
      $prefix = $this->db->prefix;
      // if($prefix) $table="$prefix"."_".$table;

      foreach ($this->dict->MetaTables() as $t) {
         if ($prefix && preg_match("/^{$prefix}_(.*)$/", $t, $mat)) {
            $tab = $mat[1];
         } else {
            if ($prefix) continue;
            $tab = $t;
         }

         if ($tab != $table) continue;

         $cols = array();
         $bools = array();
         $time = array();
         $date = array();
         foreach ($this->dict->MetaColumns($t) as $col) {
            if ($this->dict->MetaType($col) == 'L') {
               $bools[] = $col->name;
            }
            if ($this->dict->MetaType($col) == 'T') {
               $time[] = $col->name;
            }
            if ($this->dict->MetaType($col) == 'D') {
               $date[] = $col->name;
            }
            $cols[] = $col->name;
         }

         #        fwrite($fp, join(",", $cols)."\n");
         print(join(",", $cols) . "\n");

         $rs = $this->db->Execute("SELECT * FROM $t");
         while ($rs && $arr = $rs->FetchRow()) {
            //           $res=array();
            //           foreach($arr as $k=>$v){$res['`'.$k.'`']=$v;}  ## useless :(
            foreach ($bools as $bool) {
               if ($arr[$bool] == "t") $arr[$bool] = 1;
               elseif ($arr[$bool] == "f") $arr[$bool] = 0;
            }
            foreach ($time as $t) {
               $arr[$t] = substr($arr[$t], 0, 19);
            }
            foreach ($date as $t) {
               $arr[$t] = substr($arr[$t], 0, 10);
            }
            fwrite($fp, join($colsep, $arr) . $rowsep);
            //           fwrite($fp, Spyc::YAMLDump($arr)); // Spyc::YAMLLoad
         }
      }
      fclose($fp);
   }

   function load_data_yaml($file) {
      Xorc::use_yaml();
      dl('syck.so');
      $fp = fopen($file, 'rb');
      while (!feof($fp)) {
         $buffer = fgets($fp);
         if (preg_match("/^===([-_a-z0-9]{3,32})===$/", $buffer, $mat)) {
            print "\nLoading Table {$mat[1]}\n";
            $table = $this->table($mat[1]);
            $c = 1;
            $data = "";
         } elseif (preg_match("/^---$/", $buffer) && $data) {
            print $c++ . " ";
            $this->insert_yaml_data($table, $data);
            $data = "";
         } else {
            $data .= $buffer;
         }
      }
      fclose($fp);
   }

   function insert_yaml_data($table, $data) {
      $fields = Spyc::YAMLLoad($data);
      //  $fields=syck_load($data);
      //  print_r($fields);
      // $this->db->AutoExecute($table, $fields, 'INSERT');
   }

   function meta_info($tab) {
      $info = array();
      foreach ($this->dict->MetaColumns($tab) as $col) {
         // print_r($col);
         $info[$col->name] = array(
            "len" => $col->max_length,
            'type' => $this->dict->MetaType($col)
         );
      }
      return $info;
   }

   function load_data($file) {
      $fp = fopen($file, 'rb');
      $MAX = 100000;
      while (!feof($fp)) {
         $buffer = fgets($fp);
         if (preg_match("/^===([-_a-z0-9]{3,32})===$/", $buffer, $mat)) {
            print "\nLoading Table {$mat[1]}\n";
            $table = $this->table($mat[1]);
            $tableinfo = $this->meta_info($table);
            $datefields = array();
            $clobs = array();
            foreach ($tableinfo as $name => $info) {
               if ($info['type'] == "D" || $info['type'] == "T")
                  $datefields[] = $name;
               if ($info['type'] == "XL")
                  $clobs[] = $name;
            }
            $c = 1;
            $data = "";
         } else {
            $data .= $buffer;
            //print "#$buffer#\n";
            if ($data[0] == "-" && $data[strlen($data) - 2] == "}" && $data[strlen($data) - 3] == ";") {
               $data = ltrim($data, "-");
               // print "#$sdata#\n";
               $fields = unserialize($data);
               if (!$fields) {
                  // we do not have the full data yet... trial n error :)
                  $data = "-" . $data;
                  continue;
               }
               $big = array();
               foreach ($datefields as $name) {
                  if (preg_match("/^0000-00-00/", $fields[$name]))
                     $fields[$name] = str_replace(
                        "0000-00-00",
                        "1970-01-01",
                        $fields[$name]
                     );
               }
               foreach ($clobs as $name) {
                  $big[$name] = $fields[$name];
                  unset($fields[$name]);
               }

               $c++;
               if ($c > $MAX) {
                  print $c . "x";
               } else {
                  print $c . " ";
                  //      print_r($fields);
                  $this->db->AutoExecute($table, $fields, 'INSERT');
                  foreach ($big as $name => $cont) {
                     $this->db->UpdateClob($table, $name, $cont, 'id=' . $fields['id']);
                  }
               }
               $data = "";
            }
         }
      }
      fclose($fp);
      print "\n////\nFinished!\n";
   }

   function restore_sequences($tables = array()) {
      foreach ($this->query_tables($tables) as $t) {
         #         print $t;return;
         $this->restore_sequence($t, $t . "_seq");
      }
   }

   function restore_sequence($t, $seq) {
      $keys = $this->db->MetaPrimaryKeys($t);
      if (sizeof($keys) > 1) {
         print "skipping table $t cause combound key\n";
         return;
      } elseif (!$keys) {
         print "skipping table $t cause no primary key found\n";
         return;
      }
      $key = $keys[0];
      $rs = $this->db->Execute("SELECT max($key) as mk FROM $t");
      if ($rs && $arr = $rs->FetchRow()) {
         $start = $arr['mk'] + 20;
      } else {
         print "skipping table $t cause max($key) select failed\n";
         return;
      }

      $q = "drop sequence $seq";
      print "dropping sequence: $q\n";
      #$this->db->Execute($q);
      @$this->db->DropSequence($seq);

      $q = "create sequence $seq start with $start";
      print "recreating sequence: $q\n";
      $this->db->CreateSequence($seq, $start);
      #$this->db->Execute($q);
      print "\n";
   }

   function query_tables($tables = array()) {
      $qtab = array();
      $prefix = $this->db->prefix;
      foreach ($this->dict->MetaTables() as $t) {
         if ($prefix && preg_match("/^{$prefix}_(.*)$/", $t, $mat)) {
            $tab = $mat[1];
         } else {
            if ($prefix) continue;
            $tab = $t;
         }

         if ($tables && !in_array($tab, $tables)) continue;

         // exclude fancy oracle tables like bin$gxujy0t0bjvgqkjacgfreg==$0
         if (preg_match('/\$/', $tname)) {
            #print " .. skipped\n";
            continue;
         }
         $qtab[] = $t;
      }

      #      print "PREFIX $prefix";
      #      print_r($qtab);

      return $qtab;
   }

   function table($tab, $short = false) {
      if ($short) {
         $nameL = explode("_", $tab);
         $name = "";
         foreach ($nameL as $n) {
            $name .= substr($n, 0, 3);
         }
         $tab = $name;
      }

      if ($this->db && $this->db->prefix) {
         return $this->db->prefix . "_" . $tab;
      } else {
         return $tab;
      }
   }

   function index($tab, $cols) {
      if (preg_match("/,/", $cols)) {
         $cols = str_replace(" ", "", $cols);
         $cols = str_replace(",", "_", $cols);
      }
      return $this->table($tab, true) . "_idx_" . $cols;
   }

   // funktionen zur versionsverwaltung
   function version_table() {
      // $this->create_table("schema_information", "version I8 NOTNULL");
      $this->ddl("schema_information", "CREATE TABLE IF NOT EXISTS %TABLE% (version INT NOT NULL)");
      $version = $this->version();
      if (is_null($version)) {
         $this->db->Execute("INSERT INTO " . $this->table("schema_information") . " VALUES(0)");
      }
   }

   // funktionen zur sessionverwaltung (adodb sessiontable)
   //    http://phplens.com/lens/adodb/docs-session.htm

   /* SESSKEY char(32) not null,
     EXPIRY int(11) unsigned not null,
     EXPIREREF varchar(64),
	   DATA text not null,
	   primary key (sesskey)
*/

   function session_table() {
      $this->create_table("sessions", "
         sesskey c(32) KEY,
         expiry i8 NOTNULL,
         expireref c(64),
         data XL NOTNULL
         ");
   }

   function version($version = null) {
      if (!is_null($version)) {
         print "-- SET VERSION $version\n";
         if (!$this->dry) $rs = $this->db->Execute("UPDATE " . $this->table("schema_information") . " SET version=$version");
         return $version;
      } else {
         print "-- GET VERSION\n";
         $rs = $this->db->Execute("SELECT * FROM " . $this->table("schema_information"));
         if ($rs && !$rs->EOF) return $rs->fields['version'];
      }
      return null;
   }
}
