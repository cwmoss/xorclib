<?php

/**
 * 	xorc store ORM, easy & tight
 *	GPL applied
 *
 * @author Robert Wagner
 * @version $Id$
 * @copyright 20sec.net, 28 January, 2006
 * @package xorc
 **/

/**
 *	generisches datenbankhandling
 *	selectOne, insert, update, delete, select
 *
 *	erwartet folgende arrays:
 *		$this->schema['table']['name']	-- tabellenname (scalar)
 *		$this->schema['table']['db']		name der globalen variable mit (ado-) db connect
 *		$this->schema['table']['sequence']	sequenzname fuer primaerschluessel, falls vorhanden
 *		$this->schema['keys']		-- schluesselfelder
 *		$this->schema['fields'] 	-- tabellenfelder (ohne schluessel)
 *
 *		$this->schema['relations']
 *		$this->prop 	-- Objektwerte fuer Felder
 **/

include_once("xorcstore_reflection.class.php");
include_once("xorcstore_ar_iterator.class.php");
include_once("xorcstore_assoc.class.php");
include_once("xorcstore_error.class.php");
include_once("xorcstore_validation.class.php");

class Xorcstore_AR implements Countable, JsonSerializable {
    private $prop = array();
    private $_assoc = array();    // associations
    //	var $schema;
    private $loaded_from_store = false;
    private $saved_to_store = false;
    private $_last_modification = "";
    private $update_protected = array();
    private $iterator = 'Xorcstore_ar_iterator';
    private $classfilesuffix = ".php";

    private $xfiles = array();

    public $protected = array();
    /**
     * @var Xorcstore_Errors
     */
    public $error = null;
    public $errors = null;
    public $validation = null;
    public $klas;

    private $_extensions = array();

    public $MASTER = null;
    public $SCHEMAPATH = null;

    public static $__schema = [];

    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->get();
    }

    public function __construct($id = "") {
        $this->xorcstore_start($id);
    }

    public function xorcstore_start($id = "") {
        $klas = strtolower(get_class($this));
        if (!isset(self::$__schema[$klas])) {
            xorcstore_reflection::read_schema($this, $klas);
            $validation = new xorcstore_validation($this);
            self::$__schema[$klas] = $validation;
        } else {
            $validation = self::$__schema[$klas];
        }

        $this->klas = $klas;
        $this->validation = $validation;


        $this->init_new();

        if ($id && !is_array($id)) {
            $idobj = $this->find($id);
            if (!$idobj) {
                //	print "NOT FOUND";
                $this->error = "not found!";
            } else {
                $this->set($idobj->get(), null, true);


                #			$this->update_relations();
                $this->loaded_from_store = true;
                $this->after_load();
            }
        } elseif ($id) {
            // create unsaved record from hash#
            $this->create_prop();
            $this->set($id);
        }

        if ($t = $this->sti_type()) {
            if (!$this->loaded_from_store) {
                $this->$t = $this->klas;
            }
        }
        $this->load_files();
        $this->errors = new Xorcstore_Errors;
    }

    public function build_object($prop) {
        $k = null;
        if ($t = $this->sti_type()) {
            $k = $prop[$t] ?? null;
        }
        if (!$k) {
            $k = get_class($this);
        }
        $o = new $k;

        $o->set($prop, null, true);
        $o->fields_unserialize();
        #	$o->update_relations();
        $o->loaded_from_store = true;
        $o->after_load();
        return $o;
    }

    public function reload() {
        $o = $this->find($this->id());
        $this->set($o->get());
    }

    public function be_new() {
        $this->loaded_from_store = $this->saved_to_store = false;
    }
    public function was_loaded() {
        $this->loaded_from_store = true;
    }

    public function is_new_record() {
        return (!$this->loaded_from_store && !$this->saved_to_store);
    }

    public function is_created_record() {
        return (!$this->loaded_from_store && $this->saved_to_store);
    }

    public function create_prop() {
        foreach (xorcstore_reflection::columns($this) as $k => $v) {
            $this->prop[$k] = null;
        }
    }

    public function object_relation_get($rel) {
        if (xorcstore_reflection::assoc_exists($this, $rel)) {
            if (!isset($this->_assoc[$rel])) {
                $this->init_relation($rel);
            }
            return $this->_assoc[$rel];
        }
    }
    public function init_relation($rel) {
        $r = xorcstore_reflection::assoc_opts($this, $rel);
        $clas = "xorcstore_assoc_{$r[0]}";

        //TODO clone mit diesem ersetzen und dann in assoc umbauen... -> schneller + eventuell keine fehler durch destruct der kopie ?
        # $clone = clone $this;
        $this->_assoc[$rel] = new $clas($this->klas, $rel, $r[1], $this);
        #		$this->_assoc[$rel]->set_owner($this);
    }


    public function delete_relations() {
        #  var_dump($this->_assoc);
        foreach (xorcstore_reflection::assocs($this) as $rel => $opts) {
            $type = $r[0];
            #			print_r($this);
            #		print "REMOVING $rel\n";
            #print_r($this->_assoc[$rel]);
            #		var_dump($this->$rel);
            $this->$rel;
            #var_dump($rel);
            #var_dump($this->_assoc[$rel]);
            $this->_assoc[$rel]->parent_deleted();
        }
    }

    public function name_for_relation($clas) {
        return $this->schema['relation_names'][$clas][0];
    }

    public function condition_to_sql() {
        $cL = array();
        #	   print "COND2SQL";
        $prefix = "";

        foreach (func_get_args() as $cond) {
            #	      print "one";
            #	      print_r($cond);
            if (!$cond) {
                continue;
            }
            if (is_string($cond) && $cond[0] == ".") {
                $prefix = str_replace(".", "", $cond);
                if (!$prefix) {
                    $prefix = "t.";
                } else {
                    $prefix = $prefix .= ".";
                }
                continue;
            }
            if (!is_array($cond)) {
                $cond = array($cond);
            }
            foreach ($cond as $k => $v) {
                if (is_int($k)) {
                    if (!is_array($v)) {
                        $cL[] = $v;
                    } else {
                        $cond = array_shift($v);
                        #  print "COND $cond";
                        foreach ($v as $cval) {
                            $cond = preg_replace("/\?/", $cval, $cond, 1);
                        }
                        #  print $cond;
                        $cL[] = $cond;
                    }
                } else {
                    if (!is_array($v)) {
                        $cL[] = "$prefix$k=" . $this->db_value($k, $v);
                    } else {
                        // conditions !=, like, ~ ...
                        if ($v[1] == "between") {
                            $cL[] = "$prefix$k BETWEEN " . $this->db_value($k, $v[0]) . " AND " . $this->db_value($k, $v[2]);
                        } elseif ($v[1] == "regexp_like") {
                            $cL[] = "REGEXP_LIKE($prefix$k, " . $this->db_value($k, $v[0]) . ")";
                        } elseif ($v[1] == "in") {
                            $inL = array();
                            foreach ($v[0] as $inval) {
                                $inL[] = $this->db_value($k, $inval);
                            }
                            $cL[] = "$prefix$k IN(" . join(",", $inL) . ")";
                        } else {
                            if (!$v[1]) {
                                $op = "=";
                            } else {
                                if ($v[1] == "~") {
                                    $op = "~";  // db_regex_funktion()
                                } elseif ($v[1] == "like") {
                                    $op = "like";
                                } else {
                                    $op = $v[1];
                                }
                            }
                            $cL[] = "$prefix$k $op " . $this->db_value($k, $v[0]);
                        }
                    }
                }
            }
        }
        # print join(" AND ", $cL);
        #	   print_r($cL);

        return join(" AND ", array_filter($cL));
    }


    public function set_to_sql($set) {
        $keys = xorcstore_reflection::primary_key($this);
        $keys = array($keys);
        $setV = array();
        foreach ($set as $f => $val) {
            if (in_array($f, $keys)) {
                continue;
            }
            if ($this->is_a_blob($f)) {
                //print "blob!";
                $blobs[] = $f;
                continue;
            }
            $setV[] = "$f = " . $this->db_value($f, $set[$f]);
        }

        return join(", ", $setV);
    }

    public function _select(
        $sel = "",
        $cond = array(),
        $ord = "",
        $group = "",
        $limit = "",
        $offset = "",
        $page = "",
        $joins = "",
        $include = "",
        $ret = "all"
    ) {
        #log_error("==== ### SELECT =================================================");
        $con = &$this->connection();
        #if($joins)		print_r($this->condition_to_sql($cond));
        $tf = xorcstore_reflection::sti_type_condition($this);
        #log_error("==== ### STI finder: ".get_class($this));
        #log_error($tf);
        #log_error(xorcstore_reflection::$r);
        #      print "TF:";print_r($tf);
        if (!$sel) {
            $sel = "*";
        }

        if ($ord) {
            $ord = "ORDER BY $ord";
        }
        if ($group) {
            $group = "GROUP BY $group";
        }

        $tab = $this->table();
        if ($joins) {
            #	print_r($joins); exit;
            if ($sel == '*') {
                $sel = "t.*";
            }
            if ($cond || $tf) {
                $cond = $this->condition_to_sql(".t", $cond, $tf);
            }
            $tabL = array($tab . " t");
            $jcondL = array();
            if (!is_array($joins[0])) {
                $joins = array($joins);
            }
            foreach ($joins as $k => $j) {
                if ($j['type']) {
                    $jtype = $j['type'];
                } else {
                    $jtype = "JOIN";
                }
                if ($j['conditions']) {
                    $jon = "ON ({$j['conditions']})";
                } else {
                    $jon = "";
                }
                if ($j['in_result']) {
                    $sel = "j{$k}.*, " . $sel;
                }
                #      $tabL[]=$j['table']." j$k";
                $tabL[] = "$jtype {$j['table']} j$k $jon";
                $_jcond = $this->condition_to_sql(".j" . $k, $j['jconds'], $tf);
                if ($_jcond) {
                    $jcondL[] = $_jcond;
                }
            }
            /*
                     $tab = join(", ", $tabL);
                     $jcond= join(" AND ", $jcondL);
                     if($cond && $jcond) $cond= "WHERE ".$jcond." AND ".$cond;
                        elseif($cond) $cond = "WHERE ".$cond;
                     else $cond="WHERE ".$jcond;
            */
            # jetzt mit der ON syntax
            $tab = join(" ", $tabL);
            $jcond = "";
            if ($jcondL) {
                $jcond = join(' AND ', $jcondL);
            }
            #		var_dump($cond);
            #		var_dump($jcondL);
            #		var_dump($jcond);
            if ($cond && $jcond) {
                $cond = "WHERE " . $jcond . " AND " . $cond;
            } elseif ($cond) {
                $cond = "WHERE " . $cond;
            } elseif ($jcond) {
                $cond = "WHERE " . $jcond;
            }
        } else {
            if ($cond || $tf) {
                $cond = "WHERE " . $this->condition_to_sql($cond, $tf);
            } else {
                $cond = "";
            }
        }

        #      print_r($condx);
        $qs = "SELECT $sel FROM $tab $cond $group $ord";
        #      print $qs;
        #      print "\n";
        if ($ret == 'query') {

            #			print $qs; die();

            return $qs;
        }

        if ($limit) {
            if (!$offset) {
                if (!$page) {
                    $page = 1;
                }
                $offset = ($page - 1) * $limit;
            }
            if ($ret != "first") {
                $total = $this->count_by_sql("SELECT count(*) FROM $tab $cond");
                #		      print "TOTAL: $total";
                $rs = $con->SelectLimit($qs, $limit, $offset);
                $li = new $this->iterator($rs, $this->klas);
                $li->set_pager($this->pager($li->total_rows(), $limit, $offset, $total));
                return $li;
            } else {
                $rs = $con->SelectLimit($qs, 1, $offset);
                if ($rs && !$rs->EOF) {
                    $o = $this->build_object($rs->fields);
                    $rs->Close();
                    return ($o);
                } else {
                    return (false);
                }
            }
        } else {
            $rs = $con->Execute($qs);
            if ($ret == "first") {
                if ($rs && !$rs->EOF) {
                    $o = $this->build_object($rs->fields);
                    $rs->Close();
                    return ($o);
                } else {
                    return (false);
                }
            } else {
                $li = new $this->iterator($rs, $this->klas);
                return $li;
            }
        }
    }

    public function distinct($fields, $opts = array()) {
        $con = &$this->connection();
        $qs = "SELECT DISTINCT $fields FROM " . $this->table();
        if ($opts['conditions']) {
            $cond = $this->condition_to_sql($opts['conditions']);
        } else {
            $cond = "";
        }
        if ($cond) {
            $qs .= " WHERE " . $cond;
        }
        $ret = array();
        $rs = $con->Execute($qs);
        while ($rs && !$rs->EOF) {
            $ret[] = current($rs->fields);
            $rs->MoveNext();
        }

        return $ret;
    }

    public function pager($totalrs, $limit, $offset, $total) {
        $p = array();
        if ($offset > $total) {
            $offset = $total;
        }
        $page = floor($offset / $limit) + 1;
        $p['this'] = $page;
        $p['maxpp'] = $limit;
        $p['total'] = $total;
        $p['offset'] = $offset;
        $p['totalpages'] = ceil($p['total'] / $limit);
        if (!$p['totalpages']) {
            $p['totalpages'] = 1;
        }
        // $p['real']=($page*$maxpp<$p['total'])?$maxpp:($p['total']-($page-1)*$maxpp);
        $p['real'] = $totalrs;
        $p['less'] = ($page <= 1) ? false : true;
        $p['prev'] = ($page == 1) ? $page : $page - 1;
        $p['more'] = ($page >= $p['totalpages']) ? false : true;
        $p['next'] = ($page == $p['totalpages']) ? $page : $page + 1;
        $p['first'] = $p['total'] ? ($page - 1) * $limit + 1 : 0;
        $p['last'] = $p['total'] ? $p['first'] + $p['real'] - 1 : 0;
        return $p;
    }

    # :conditions: An SQL fragment like "administrator = 1" or [ "user_name = ?", username ]. See conditions in the intro.
    # :order: An SQL fragment like "created_at DESC, name".
    # :group: An attribute name by which the result should be grouped. Uses the GROUP BY SQL-clause.
    # :limit: An integer determining the limit on the number of rows that should be returned.
    # :offset: An integer determining the offset from where the rows should be fetched. So at 5, it would skip the first 4 rows.
    # :joins: An SQL fragment for additional joins like "LEFT JOIN comments ON comments.post_id = id". (Rarely needed). The records will be returned read-only since they will have attributes that do not correspond to the table’s columns. Pass :readonly => false to override.
    # :include: Names associations that should be loaded alongside using LEFT OUTER JOINs. The symbols named refer to already defined associations. See eager loading under Associations.
    # :select: By default, this is * as in SELECT * FROM, but can be changed if you for example want to do a join, but not include the joined columns.
    # :readonly:

    public function find($args = null, $ret = null) {
        #		print "find:$args $ret";
        if (is_null($args) && is_null($ret)) {
            return false;
        }
        if (is_null($ret)) {
            $ret = "all";
        }
        if (is_null($args)) {
            return $this->_select();
        }
        #	   if(is_null($args)) return $this->_select(null,array(),null,null,1,null,null,null,null,$ret);
        if (!is_array($args)) {
            return $this->_select(
                "",
                array($this->primary_key() => $args),
                "",
                "",
                1,
                "",
                "",
                "",
                "",
                "first"
            );
        }
        if (!count($args)) {
            return array();
        }   # leeres array
        /*fuer ids in und limit...
        if(isset($args[0])){//args are ids
            return $this->_select("", array(ids_to_sql_clause($args)));
        }
        if(isset($args['conditions'][0])) {//conditions are ids
            $args['conditions'] = ids_to_sql_clause($args['conditions']);
        }
        */

        if (isset($args[0])) {
            return $this->_select("", array($this->primary_key() . " IN (" . join(",", $args) . ")"));
        }
        return $this->_select(
            $args['select'] ?? "",
            $args['conditions'] ?? [],
            $args['order'] ?? "",
            $args['group'] ?? "",
            $args['limit'] ?? "",
            $args['offset'] ?? "",
            $args['page'] ?? "",
            $args['joins'] ?? null,
            $args['include'] ?? null,
            $ret
        );
    }

    /* fuer ids in und limit...
    protected function ids_to_sql_clause(array $args){
        return $this->primary_key()." IN (".join(",", $args).")";
    }
    */

    public function find_by_id($ids) {
        return $this->find($ids, "first");
    }

    public function find_all($args = null) {
        return $this->find($args, "all");
    }

    public function find_first($args = null) {
        if (is_null($args)) {
            $args = array();
        }
        $args['limit'] = 1;
        return $this->find($args, "first");
    }

    public function find_by_sql($sql) {
        return $this->select($sql);
    }

    public function chunk($limit, $args = null, $cb = null) {
        if (is_callable($args)) {
            $cb = $args;
            $args = array();
        }
        $args['limit'] = $limit;
        $args['page'] = 1;
        $res = $this->find($args, 'all');
        while ($res && count($res)) {
            if (call_user_func($cb, $res) === false) {
                break;
            }
            $args['page']++;
            $res = $this->find($args, 'all');
        }
    }

    public function is_unique($fields_with_values = null) {
        if (!$fields_with_values) {
            return false;
        }
        return (($this->find_first($fields_with_values) === false) ? true : false);
    }

    public function exists($id) {
        if (!is_numeric($id)) {
            return false;
        }
        if ($this->find($id)) {
            return true;
        } else {
            return false;
        }
    }

    public function destroy($ids = null) {
        if (is_null($ids)) {
            return $this->destroy_me();
        }
        if (!is_array($ids)) { //is id
            return $this->destroy_all(sprintf("%s = %s LIMIT 1", $this->primary_key(), $ids));
        }
        return $this->destroy_all(sprintf("%s IN(%s)", $this->primary_key(), join(",", $ids)));
    }

    public function destroy_all($cond = null) {
        foreach ($this->find_all(array("conditions" => $cond)) as $d) {
            $d->destroy_me();
        }
    }

    public function destroy_me() {
        if ($this->before_destroy() !== false) {
            $this->delete_relations();
            $con = &$this->connection();
            foreach (array($this->primary_key()) as $k) {
                $wV[] = "$k = " . $this->db_value($k, $this->prop[$k]);
            }
            $w = join(" AND ", $wV);
            $q = "DELETE from " . $this->table() . " WHERE $w";
            //		 print "$q<br>";
            $rs = $con->Execute($q);
            $this->delete_files();
            $this->after_destroy();
            return $this;
            //return $con->Affected_Rows();
            //$this->chekkOut();
        }
    }

    public function delete($ids = null) {
        if (is_null($ids)) {
            return $this->delete_me();
        }
        if (!$ids) {
            return false;
        } // 0 ID oder ähnliches
        if (!is_array($ids)) { //is id
            return $this->delete_all(sprintf("%s = %s", $this->primary_key(), $ids));
        } #LIMIT 1 nur in mysql moeglich
        return $this->delete_all(sprintf("%s IN(%s)", $this->primary_key(), join(",", $ids)));
    }

    public function delete_all($cond = null) {
        $con = &$this->connection();
        if ($cond) {
            $cond = "WHERE " . $this->condition_to_sql($cond);
        }
        $q = "DELETE from " . $this->table() . " $cond";
        $rs = $con->Execute($q);
        return $con->Affected_Rows();
    }

    public function delete_me() {
        return $this->delete($this->id());
    }

    public function next_id_from_sequence($sequence_name) {
        $con = &$this->connection();
        $SEQ = Xorcstore_Reflection::prefix($this) . $sequence_name;
        $ID = $con->GenID($SEQ);
        return $ID;
    }

    public function _create($fields = "") {
        $keys = xorcstore_reflection::primary_key($this);
        $keysL = array($keys);
        $SEQ = xorcstore_reflection::sequence($this);
        $IDF = xorcstore_reflection::idfunction($this);
        $con = &$this->connection();
        $idreturned_after_insert = false;
        if ($this->saved_to_store) {
            return false;
        }
        $ID = null;
        if (sizeof($keysL) > 1) {
            /* mehrteiliger schluessel */
            foreach ($keysL as $k) {
                if (!isset($this->prop[$k])) {
                    return false;
                }
                $valV[] = $this->db_value($k, $this->prop[$k]);
                $insF[] = $k;
            }
        } else {
            /* einfacher schluessel */

            if ($SEQ) {
                $ID = $con->GenID($SEQ);
            } elseif ($idfunc = $IDF) {
                $ID = $this->$idfunc();
            } elseif ($this->id()) {
                $ID = $this->id();
            }
            if ($ID) {
                $this->id($ID);
            } elseif (!$this->id()) {
                $idreturned_after_insert = true;
            }
        }

        //		print "KEY: {$this->prop[$this->schema['keys'][0]]}~";
        //		$this->set_relation_fkeys();
        $fields = array_keys(xorcstore_reflection::columns($this));
        $blobs = [];
        foreach ($fields as $f) {
            // certain DBs don't like inserts with nullvalues in key
            if (in_array($f, $keysL) && !$ID) {
                continue;
            }
            if ($this->is_a_blob($f)) {
                $blobs[] = $f;
                continue;
            }
            $valV[] = $this->db_value($f, $this->prop[$f] ?? null);
            $insF[] = $this->sql_namequote($f);
        }
        $names = @join(", ", $insF);
        $val = @join(", ", $valV);

        $q = "INSERT INTO " . $this->table() . " ($names) VALUES ($val)";

        //		print_r($this);

        $rs = $con->Execute($q);
        //		print_r($rs);

        if ($idreturned_after_insert) {
            //		print "GETTING ID:";
            //			$ID=$con->Insert_ID($this->schema['table']['name'], $this->schema['keys'][0]);
            $ID = $con->Insert_ID();
            //		print "~$id~";
            $this->id($ID);
        }

        $this->saved_to_store = true;
        $this->_last_modification = "create";

        if ($blobs) {
            $this->update_blobs($blobs);
        }

        $this->unsaved_relations();
        return true;
    }

    public function unsaved_relations() {
        foreach ($this->_assoc as $r) {
            $r->parent_saved();
        }
    }

    public function _update($fields = "") {
        $con = &$this->connection();
        $keys = xorcstore_reflection::primary_key($this);
        $keysL = array($keys);
        $protect_chekk = false;
        foreach ($keysL as $k) {
            if (!isset($this->prop[$k])) {
                return false;
            }
            $wV[] = "$k = " . $this->db_value($k, $this->prop[$k]);
        }
        $w = join(" AND ", $wV);

        if (!$fields) {
            $fields = array_keys(xorcstore_reflection::columns($this));
            $protect_chekk = true;
        }

        $blobs = array();

        #		$this->set_relation_fkeys();

        foreach ($fields as $f) {
            if (in_array($f, $keysL)) {
                continue;
            }
            if ($this->is_a_blob($f)) {
                //print "blob!";
                $blobs[] = $f;
                continue;
            }
            if (
                $protect_chekk && isset($this->update_protected[$f]) &&
                $this->update_protected[$f]
            ) {
                continue;
            }
            $setV[] = $this->sql_namequote($f) . " = " . $this->db_value($f, $this->prop[$f] ?? null);
        }

        $set = @join(", ", $setV);
        $q = "UPDATE " . $this->table() . " SET $set WHERE $w";
        //		print_r($this);
        $rs = $con->Execute($q);

        $this->saved_to_store = true;
        $this->_last_modification = "update";

        if ($blobs) {
            $this->update_blobs($blobs, $w);
        }
        return true;
    }


    public function increment($col, $dir = "+1") {
        $con = &$this->connection();
        $keys = xorcstore_reflection::primary_key($this);
        $keysL = array($keys);
        foreach ($keysL as $k) {
            if (!isset($this->prop[$k])) {
                return false;
            }
            $wV[] = "$k = " . $this->db_value($k, $this->prop[$k]);
        }
        $w = join(" AND ", $wV);
        $q = "UPDATE " . $this->table() . " SET $col = $col $dir WHERE $w";
        $rs = $con->Execute($q);
    }

    public function decrement($col) {
        return $this->increment($col, "-1");
    }

    public function db_value($f, $val = null) {
        $con = &$this->connection();
        //		print "CON"; print_r($this->connection());
        //		print_r($con);

        if (xorcstore_reflection::is_serialize_field($this, $f)) {
            $val = $this->fields_serialize($f);
        } elseif (is_array($val)) {
            log_error("#### ===> array für spaltenwert ($f) !!");
            #		   var_dump($val);
            return null;
        }

        if (!isset($val)) {
            return "null";
        }

        $DBF = xorcstore_reflection::dbfunctions($this);
        if (isset($DBF[$f])) {
            $funcstart = $DBF[$f] . "(";
            $funcend = ")";
        } else {
            $funcstart = $funcend = "";
        }
        // bei join conditions auf string zurückfallen
        //    statt number
        if ($this->is_a_string($f) || !xorcstore_reflection::type_of($this, $f)) {
            return $funcstart . $con->Quote($val) . $funcend;
        }
        if ($this->is_a_date($f)) {
            //log_error("db-value date");
            //log_error($val);
            return $funcstart . $con->DBDate((string) $val) . $funcend;
        }
        if ($this->is_a_datetime($f)) {
            //log_error("db-value time");
            //log_error($val);
            return $funcstart . $con->DBTimeStamp((string) $val) . $funcend;
        }
        if ($val && $this->is_a_float($f)) {
            # evtl. LC_NUMERIC = de normalisieren
            $norm = str_replace(",", ".", ((float) $val));
            return $norm;
        }
        // else: this should be a number
        /* numbers are a bit special */
        if ($val === 0 || $val === 0.0 || $val === "0" || $val === false) {
            return "0";
        }
        if (!$val) {
            return "null";
        }
        if (is_int($val)) {
            return $val;
        }
        if (is_string($val) && ctype_digit($val)) {
            return $val;
        }
        // invalid
        return "'--invalid numeric--'";
        # return $val;
    }

    /*
    function is_digit($digit) {
        if(is_int($digit)) {
            return true;
        } elseif(is_string($digit)) {
            return ctype_digit($digit);
        } else {
            // booleans, floats and others
            return false;
        }
    }
    */


    public function save($validate = true) {
        $ok = $this->insert_or_update($validate);
        if ($ok) {
            $this->update_files();
        }
        return $ok;
    }

    public function auto_columns() {
        $con = &$this->connection();
        if ($con->databaseType == 'postgres7') {
            $mt = microtime(); // 0.39676600 1148833029
            preg_match("/^0\.(\d+) /", $mt, $m);
            $dat = date("Y-m-d H:i:s.") . $m[1];
        } else {
            $dat = date("Y-m-d H:i:s");
        }
        //log_error($con);

        $created = xorcstore_reflection::auto_created($this);
        $modified = xorcstore_reflection::auto_modified($this);
        $inc = xorcstore_reflection::auto_increment($this);
        //print("set?".isset($this->prop[$this->schema['autodate']['created']]));
        if (
            $created &&
            !isset($this->prop[$created])
        ) {
            $this->prop[$created] = $dat;
        }
        if ($modified) {
            $this->prop[$modified] = $dat;
        }

        foreach ($inc as $k => $v) {
            $this->prop[$k] += $v;
        }
    }

    public function is_valid($ev = null) {
        # custom validation event
        if (!is_null($ev)) {
            return $this->validation->validate_event($ev, $this, true);
        }

        # cre/up/save
        $ok1 = $this->validation->validate_event("save", $this, true);
        if ($this->is_new_record()) {
            $ok2 = $this->validation->validate_event("create", $this);
        } else {
            $ok2 = $this->validation->validate_event("update", $this);
        }
        return (!($ok1 === false || $ok2 === false));
    }

    public function last_modification() {
        return $this->_last_modification;
    }

    public function insert_or_update($validate = true) {
        if (($ok = $this->before_validation()) === false) {
            return $ok;
        }
        if (!$this->loaded_from_store && !$this->saved_to_store) {
            if (($ok = $this->before_validation_on_create()) === false) {
                return $ok;
            }
            #print_r($this);

            if ($validate) {
                $ok1 = $this->validation->validate_event("save", $this, true);
                $ok2 = $this->validation->validate_event("create", $this);

                if ($ok1 === false || $ok2 === false) {
                    return false;
                }
            }


            if (($ok = $this->after_validation()) === false) {
                return $ok;
            }
            if (($ok = $this->after_validation_on_create()) === false) {
                return $ok;
            }

            if (($ok = $this->before_save()) === false) {
                return $ok;
            }
            foreach (xorcstore_reflection::extensions($this) as $ext => $opts) {
                #   log_error("[AR] magick BEFORE-SAVE EXTENSION ".get_class($ext));
                if ($ok = call_user_func_array(array($ext, 'before_save'), array($this, $opts)) === false) {
                    return $ok;
                }
            }
            if (($ok = $this->before_create()) === false) {
                return $ok;
            }
            foreach (xorcstore_reflection::extensions($this) as $ext => $opts) {
                #   log_error("[AR] magick BEFORE-SAVE EXTENSION ".get_class($ext));
                if ($ok = call_user_func_array(array($ext, 'before_create'), array($this, $opts)) === false) {
                    return $ok;
                }
            }

            $this->auto_columns();

            $ok = $this->_create();
            if (($aok = $this->after_create()) === false) {
                return $ok;
            }
            if (($aok = $this->after_save()) === false) {
                return $ok;
            }
        } else {
            if (($ok = $this->before_validation_on_update()) === false) {
                return $ok;
            }

            if ($validate) {
                $ok1 = $this->validation->validate_event("save", $this, true);
                $ok2 = $this->validation->validate_event("update", $this);

                if ($ok1 === false || $ok2 === false) {
                    return false;
                }
            }

            if (($ok = $this->after_validation()) === false) {
                return $ok;
            }
            if (($ok = $this->after_validation_on_update()) === false) {
                return $ok;
            }

            if (($ok = $this->before_save()) === false) {
                return $ok;
            }


            foreach (xorcstore_reflection::extensions($this) as $ext => $opts) {
                #      log_error("[AR] magick BEFORE-SAVE EXTENSION ".get_class($ext));
                if ($ok = call_user_func_array(array($ext, 'before_save'), array($this, $opts)) === false) {
                    return $ok;
                }
            }
            if (($ok = $this->before_update()) === false) {
                return $ok;
            }
            foreach (xorcstore_reflection::extensions($this) as $ext => $opts) {
                #   log_error("[AR] magick BEFORE-SAVE EXTENSION ".get_class($ext));
                if ($ok = call_user_func_array(array($ext, 'before_update'), array($this, $opts)) === false) {
                    return $ok;
                }
            }

            $this->auto_columns();
            $ok = $this->_update();

            if (($aok = $this->after_update()) === false) {
                return $ok;
            }
            if (($aok = $this->after_save()) === false) {
                return $ok;
            }
        }
        return $ok;
    }



    public function setup_files() {
        return;
        foreach ($this->schema['_files'] as $k) {
            include_once("xorcstore_file.class.php");
            $f = new XorcStore_File;
            $f->setup($k, $this->schema[$k]);
            $this->xfiles[$k] = $f;
        }
    }

    public function fields_serialize($f = null) {
        #print "SERIALIZE $f\n";
        if ($f) {
            return json_encode($this->$f);
        }
        $f = array();
        foreach (xorcstore_reflection::serializations($this) as $name => $opts) {
            if (is_string($opts)) {
                $name = $opts;
            }
            $f[$name] = json_encode($this->$name);
        }
        return $f;
    }

    public function fields_unserialize() {
        foreach (xorcstore_reflection::serializations($this) as $name => $opts) {
            if (is_string($opts)) {
                $name = $opts;
            }
            // log_error("###### unserialize $name");
            // wieder zurück ins prop schreiben
            #   log_error("unserialize: PROP $name");
            $this->prop[$name] = $this->$name;
            // deserialisieren
            $dec = json_decode($this->$name, true);
            if ($dec == 'null') {
                $dec = array();
            }
            $this->$name = $dec;
            #   log_error($this->prop);
        }
        // return $f;
    }

    public function load_files($prop = array()) {
        return;
        $this->setup_files();
        // print_r($this->prop);
        foreach ($this->xfiles as $name => $f) {
            $f->load($this->prop['id'], $this->prop[$name], $prop);
        }
    }

    public function update_files() {
        return;
        $vals = array();
        foreach ($this->xfiles as $f) {
            $vals = array_merge($vals, $f->sql());
        }

        if (!$vals) {
            return;
        }
        $con = &$this->connection();

        foreach ($this->schema['keys'] as $k) {
            if (!isset($this->prop[$k])) {
                return false;
            }
            $wV[] = "$k = " . $this->db_value($k, $this->prop[$k]);
        }
        $w = join(" AND ", $wV);

        $set = join(", ", $vals);
        $q = "UPDATE {$this->schema['table']['name']} SET $set WHERE $w";

        $rs = $con->Execute($q);

        foreach ($this->xfiles as $f) {
            $f->id($this->prop['id']);
            $f->save();
        }
    }

    public function delete_files() {
        foreach ($this->xfiles as $f) {
            $f->remove();
        }
    }

    public function update_blobs($blobs, $where = "") {
        $con = &$this->connection();
        $keys = array($this->primary_key());
        if (!$where) {
            foreach ($keys as $k) {
                if (!isset($this->prop[$k])) {
                    return false;
                }
                $wV[] = "$k = " . $this->db_value($k, $this->prop[$k]);
            }
            $where = join(" AND ", $wV);
        }
        foreach ($blobs as $b) {
            // non oracle databases
            //	$conn->Execute("INSERT INTO $this->schema['table']['name'] (id, clobcol) VALUES (1, null)');

            $val = $this->prop[$b];
            if (xorcstore_reflection::is_serialize_field($this, $b)) {
                $val = $this->fields_serialize($b);
            }
            $con->UpdateClob($this->table(), $b, $val, $where);
        }
    }

    public function update_protect($props) {
        if (!is_array($props)) {
            $props = array($props);
        }
        foreach ($props as $p) {
            $this->update_protected[$p] = true;
        }
    }

    public function is_a_int($f) {
        return xorcstore_reflection::type_of($this, $f) == 1 ? true : false;
    }
    public function is_a_string($f) {
        return xorcstore_reflection::type_of($this, $f) == 2 ? true : false;
    }
    public function is_a_date($f) {
        return xorcstore_reflection::type_of($this, $f) == 3 ? true : false;
    }
    public function is_a_datetime($f) {
        return xorcstore_reflection::type_of($this, $f) == 4 ? true : false;
    }
    public function is_a_blob($f) {
        return xorcstore_reflection::type_of($this, $f) == 5 ? true : false;
    }
    public function is_a_file($f) {
        return xorcstore_reflection::type_of($this, $f) == 6 ? true : false;
    }
    public function is_a_float($f) {
        return xorcstore_reflection::type_of($this, $f) == 7 ? true : false;
    }


    /*
        trigger
    */

    /* OLD
        function before_save(){return true;}
        function after_save(){return true;}
        function before_delete(){return true;}
        function after_delete(){return true;}
        function after_load(){return true;}

    */
    public function after_load() {
        return true;
    }
    public function after_create() {
        return true;
    }
    public function after_destroy() {
        return true;
    }
    public function after_save() {
        return true;
    }
    public function after_update() {
        return true;
    }
    public function after_validation() {
        return true;
    }
    public function after_validation_on_create() {
        return true;
    }
    public function after_validation_on_update() {
        return true;
    }
    public function before_create() {
        return true;
    }
    public function before_destroy() {
        return true;
    }
    public function before_save() {
        return true;
    }
    public function before_update() {
        return true;
    }
    public function before_validation() {
        return true;
    }
    public function before_validation_on_create() {
        return true;
    }
    public function before_validation_on_update() {
        return true;
    }

    public function after_find() {
    }
    public function after_initialize() {
    }




    public function table() {
        return xorcstore_reflection::table($this);
    }

    public function primary_key($name = false) {
        if ($name === false) {
            return xorcstore_reflection::primary_key($this);
        }
        xorcstore_reflection::primary_key($this, $name);
        return $name;
    }

    public function id($val = false) {
        if ($val === false) {
            return $this->prop[xorcstore_reflection::primary_key($this)] ?? null;
        }
        $this->prop[xorcstore_reflection::primary_key($this)] = $val;
        return $val;
    }

    public function id_quoted() {
        return $this->db_value(xorcstore_reflection::primary_key($this), $this->id());
    }

    public function type($f) {
        $types = array("unknown", "int", "string", "date", "datetime", "blob");
        $t = $types[xorcstore_reflection::type_of($this, $f)];
        if (!$t) {
            return "unknown";
        }
        return $t;
    }

    public function select($qs) {
        $con = &$this->connection();
        $rs = $con->Execute($qs);
        $li = new $this->iterator($rs, $this->klas);
        return $li;
    }

    public function select_limit($qs, $limit, $offset) {
        $con = &$this->connection();
        $rs = $con->SelectLimit($qs, $limit, $offset);
        $li = new $this->iterator($rs, $this->klas);
        $li->set_pager($this->pager($li->total_rows(), $limit, $offset, $total));
        return $li;
    }

    public function select_limit_pager($qs, $page = 1, $limit = 20) {
        $con = &$this->connection();

        $total = $this->count_by_any_sql($qs);

        if (!$page) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;

        $rs = $con->SelectLimit($qs, $limit, $offset);
        $li = new $this->iterator($rs, $this->klas);
        $li->set_pager($this->pager($li->total_rows(), $limit, $offset, $total));
        return $li;
    }

    public function select_all($attr = "*", $q = "", $fq = "") {
        $s = "";
        $con = &$this->connection();
        if ($fq) {
            $sql = $fq;
        } else {
            if ($q) {
                $q = "WHERE $q";
            }
            if ($s) {
                $s = "ORDER BY $s";
            }
            $sql = "SELECT $attr FROM " . $this->table() . " $q $s";
        }
        $arr = $con->GetAll($sql);
        return $arr;
    }

    public function query_free($qs) {
        $con = &$this->connection();
        $rs = $con->Execute($qs);
        return $rs;
    }

    public function sql_date($fmt, $field = false) {
        $con = &$this->connection();
        $rs = $con->SQLDate($fmt, $field);
        return $rs;
    }

    public function sql_datetime($date) {
        $con = &$this->connection();
        $rs = $con->DBDate($date);
        return $rs;
    }

    public function sql_stringquote($str) {
        $con = &$this->connection();
        $quoted = $con->Quote($str);
        return $quoted;
    }

    public function sql_namequote($n) {
        $q = Xorcstore_reflection::db_adapter($this, 'nameq');
        return $q . $n . $q;
    }

    public function sql_substr() {
        return Xorcstore_reflection::db_adapter($this, 'substr');
    }

    public function sql_now() {
        return Xorcstore_reflection::db_adapter($this, 'sysTimeStamp');
    }

    public function count_by_any_sql($q = "") {
        if ($q && preg_match("/^select/i", $q)) {
            $q = preg_replace("/^select (.*?) from (.*)$/im", "SELECT count(*) AS total FROM $2", $q, 1);
        } else {
            if ($q) {
                $q = "WHERE $q";
            }
            $q = "SELECT count(*) AS total FROM " . $this->table() . " $q";
        }
        # print "COUNT BY ANY SQL $q";
        return $this->count_by_sql($q);
    }


    public function trans_start() {
        $con = &$this->connection();
        $con->StartTrans();
    }

    public function trans_end() {
        $con = &$this->connection();
        $con->CompleteTrans();
    }

    public function trans_fail() {
        $con = &$this->connection();
        $con->FailTrans();
    }

    public function lock_row($id) {
        $con = &$this->connection();
        return $con->rowLock($this->table(), "id=$id");
    }

    public function un_set($prop) {
        if (!is_array($prop)) {
            $prop = array($prop);
        }
        $cols = xorcstore_reflection::columns($this);
        foreach ($prop as $p) {
            if (isset($cols[$p])) {
                unset($this->prop[$p]);
            }
        }
        $this->init();
    }

    public function init_new() {
    }
    public function init() {
    }
    public function clear() {
        $this->prop = array();
    }


    public function column_names() {
        return xorcstore_reflection::columns($this);
    }

    public function content_columns() {
        $col = array();
        $keys = array($this->primary_key());
        foreach (xorcstore_reflection::columns($this) as $f => $type) {
            if (in_array($f, $keys)) {
                continue;
            }
            if (preg_match("/_(id|count)$/", $f)) {
                continue;
            }
            if ($f == "type") {
                continue;
            }
            $col[] = $f;
        }
        return $col;
    }



    public function sti_type() {
        return xorcstore_reflection::sti_type($this);
    }


    public function sanitize_conditions($sql) {
        return $sql;
    }

    public function attribute_names() {
        $a = array_keys(xorcstore_reflection::columns($this));
        sort($a);
        return $a;
    }

    public function attribute_present($attr) {
        return isset($this->prop[$attr]);
    }

    public function create($attr = array(), $scope = array()) {
        if (isset($attr[0])) {
            // many objects to create
            $attr2 = array();
            foreach ($attr as $a) {
                $attr2[] = array_merge($a, $scope);
            }
            return array_map(array($this, 'create'), $attr2);
        } else {
            $k = get_class($this);
            $o = new $k(array_merge($attr, $scope));


            $o->save();
            return $o;
        }
    }

    public function find_or_create($cond) {
        log_error("F-OR-C");
        log_error($cond);
        $o = $this->find_first(array("conditions" => $cond));
        if (!$o) {
            $o = $this->create($cond);
        }
        return $o;
    }

    public function attributes($attrs = array(), $mode = "only") {
        $ret = array();
        if (!is_array($attrs)) {
            $attrs = array();
        }
        #echo sizeof($attrs);
        if ($mode == 'only') {
            $attrs = array_intersect(array_keys($this->prop), $attrs);
        } elseif (sizeof($attrs) < 1) {
            #echo "hhhh";
            return $this->prop;
        } else {
            $attrs = array_diff(array_keys($this->prop), $attrs);
        }
        foreach ($attrs as $a) {
            $ret[$a] = $this->prop[$a];
        }
        return $ret;
    }

    public function to_xml($opts = array()) {
        $def = array("skip_instruct" => false, "indent" => 3, 'include' => array());
        $opts = array_merge($def, $opts);
        if (!is_array($opts['include'])) {
            $opts['include'] = array($opts['include']);
        }

        $xml = "";
        if (!$opts['skip_instruct']) {
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        }
        $fields = $this->attribute_names();
        $indent = str_repeat(" ", $opts['indent']);

        if ($opts['except']) {
            if (!is_array($opts['except'])) {
                $opts['except'] = array($opts['except']);
            }
            $fields = array_diff($fields, $opts['except']);
        } elseif ($opts['only']) {
            if (!is_array($opts['only'])) {
                $opts['only'] = array($opts['only']);
            }
            $fields = array_intersect($fields, $opts['only']);
        }

        $xent = str_replace("_", "-", strtolower(get_class($this)));
        $xml .= "<$xent>\n" . $indent . join("\n$indent", array_map(array($this, 'attr_to_xml'), $fields));

        foreach ($opts['include'] as $rel => $relopts) {
            $reldef = array("skip_instruct" => true, "indent" => $opts['indent'] * 2);
            if (is_array($relopts)) {
                $relopts = array_merge($relopts, $reldef);
            } else {
                $rel = $relopts;
                $relopts = $reldef;
            }
            $tag = str_replace("_", "-", strtolower($rel));
            $xml .= "\n" . $indent . "<$tag>\n";
            if (xorcstore_reflection::assoc_single_exists($this, $rel)) {
                if ($this->$rel) {   # could be null!
                    $xml .= $this->$rel->to_xml($relopts);
                }
            } else {
                foreach ($this->$rel as $rel_o) {
                    $xml .= $rel_o->to_xml($relopts);
                }
            }
            $xml .= "\n" . $indent . "</$tag>\n";
        }
        $xml .= "\n</$xent>\n";
        return $xml;
    }

    public function attr_to_xml($attr, $opts = null) {
        $tag = str_replace("_", "-", strtolower($attr));
        $type = $this->type($attr);
        if ($type == "int") {
            $type = "integer";
        }
        if ($type == "string") {
            $type = "";
        }
        if ($type) {
            $type = sprintf(' type="%s"', $type);
        }
        if (!$type && $opts) {
            $val = sprintf('<![CDATA[%s]]>', $this->$attr);
        } else {
            $val = htmlspecialchars($this->$attr, ENT_NOQUOTES);
        }
        return sprintf('<%s%s>%s</%s>', $tag, $type, $val, $tag);
    }


    public function attributes_before_type_cast() {
        #Returns a hash of cloned attributes before typecasting and deserialization.
    }

    public function attributes_with_quotes_pre_oracle() {
        #
    }

    public function copy() {
        return $this->create($this->get($this->content_columns()));
    }


    public function has_attribute($attr) {
        $cols = xorcstore_reflection::columns($this);
        return isset($cols[$attr]);
    }


    # Turns an attribute that’s currently true into false and vice versa. Returns self.
    public function toggle($attr) {
        $this->prop[$attr] = !$this->prop[$attr];
        return $this;
    }

    # Toggles the attribute and saves the record.
    public function toggle_now($attr) {
        $this->toggle($attr);
        $this->update_attr($attr, $this->prop[$attr]);
    }

    public function update($id, $attr = null) {
        if (is_null($attr)) {
            return array_filter(array_map(array($this, 'update'), array_keys($id), array_values($id)));
        } else {
            $o = $this->find_by_id($id);
            //   if(!$o){
            //      $o=$this->build_object($attr);
            //   }
            if (!$o) {
                return false;
            }
            $o->set($attr);
            $o->save();
            return $o;
        }
    }

    public function update_all($set, $cond = null) {
        if (!is_null($cond)) {
            $cond = "WHERE " . $this->condition_to_sql($cond);
        }
        if (is_array($set)) {
            $set = $this->set_to_sql($set);
        }
        $q = "UPDATE " . $this->table() . " SET $set $cond";
        $con = &$this->connection();
        $con->Execute($q);
        return $con->Affected_Rows();
    }


    /*
       Updates a single attribute and saves the record. This is especially useful for boolean flags on existing records. Note: This method is overwritten by the Validation module that’ll make sure that updates made with this method doesn’t get subjected to validation checks. Hence, attributes can be updated even if the full object isn’t valid.
    */

    public function update_attr($attr, $val) {
        $this->prop[$attr] = $val;
        $this->_update(array($attr));
    }

    public function update_attrs($attrs) {
        $this->set($attrs);
        $this->save();
    }

    public function raw($attr) {
        return $this->prop[$attr];
    }

    public function raw_set($attr, $val) {
        $this->prop[$attr] = $val;
        return $val;
    }

    public function map($vals, $finderparms = null) {
        if (!is_array($vals)) {
            $id = "id";
            $val = $vals;
        } else {
            $first = key($vals);
            if (is_numeric($first)) {
                $id = "id";
                $val = array_values($vals);
            } else {
                $id = $first;
                if (is_array($vals[$first])) {
                    $val = array_values($vals[$first]);
                } else {
                    $val = $vals[$first];
                }
            }
        }
        $map = array();
        #		var_dump($this->find_all($finderparms));
        foreach ($this->find_all($finderparms) as $o) {
            if (is_array($val)) {
                $arr = array();
                foreach ($val as $v) {
                    $arr[$v] = $o->$v;
                }
                $map[$o->$id] = $arr;
            } else {
                $map[$o->$id] = $o->$val;
            }
        }
        return $map;
    }

    public function __set($prop, $value) {
        if (method_exists($this, "set_" . $prop)) {
            return call_user_func_array(array($this, "set_" . $prop), array($value)); # call_user_method("set_".$prop, $this, $value);
        } elseif (xorcstore_reflection::column_exists($this, $prop)) {
            return $this->prop[$prop] = $value;
        } elseif (xorcstore_reflection::assoc_exists($this, $prop)) {
            if (!isset($this->_assoc[$prop])) {
                $this->init_relation($prop);
            }
            return $this->_assoc[$prop]->set($value);
        } else {
            foreach (xorcstore_reflection::extensions($this) as $ext => $opts) {
                #log_error("[AR] magick SET $prop");
                if (call_user_func_array(array($ext, 'set'), array($this, $opts, $prop, $value))) {
                    return;
                }
            }
            return $this->_set_more($prop, $value);
        }
    }

    //function __destruct(){
    //print 'BANG!'.$this->klas.$this->id."\n";
    //}

    public function __get($prop) {
        #   print "GET $prop";
        #   print xorcstore_reflection::column_exists($this, $prop);
        #   print_r(xorcstore_reflection::$r);
        #log_error("[AR] magick GET $prop");
        if (method_exists($this, "get_" . $prop)) {
            #	      print "CALLING GET "."get_".$prop;
            return call_user_func(array($this, "get_" . $prop)); # return call_user_method("get_".$prop, $this);
        } elseif (xorcstore_reflection::column_exists($this, $prop)) {
            return $this->prop[$prop] ?? null;
        } elseif (xorcstore_reflection::assoc_exists($this, $prop)) {
            #print "CALLING ASSOC $prop#";
            if (!isset($this->_assoc[$prop])) {
                #print "INIT";
                $this->init_relation($prop);
            }
            #var_dump($this->_assoc[$prop]);
            return $this->_assoc[$prop]->get();
        }

        foreach (xorcstore_reflection::extensions($this) as $ext => $opts) {
            log_error("[AR] magick GET EXTENSION " . get_class($ext));
            if (call_user_func_array(array($ext, 'match_get'), array($this, $opts, $prop))) {
                return call_user_func_array(array($ext, 'get'), array($this, $opts, $prop));
            }
        }
        return $this->_get_more($prop);
    }

    public function _set_more($prop, $value) {
        #trigger_error("(SET) unknown property $prop for object ".get_class($this));
        #      var_dump(debug_backtrace());
        #      debug_print_backtrace();
        return false;
    }

    public function _get_more($prop) {
        trigger_error("(GET) unknown property $prop for object " . get_class($this));
        #      var_dump(debug_backtrace());
        #      debug_print_backtrace();
        return false;
    }

    public function __call($m, $args) {
        if (preg_match("/^find_by_(.*)/", $m, $mat)) {
            $s = array_shift($args);
            $args['conditions'] = array($mat[1] => $s);
            return $this->find_first($args);
            //      call_user_func_array(array(&$this->mother, $m), $args);
        } elseif (preg_match("/^find_all_by_(.*)/", $m, $mat)) {
            #	      print_r($args);
            $fargs = array();
            if ($args[1] ?? null) {
                $fargs = $args[1];
            }
            $cond = $fargs['conditions'] ?? null;
            if ($cond && (!is_array($cond))) {
                $cond = array($cond);
            }
            if (!$cond) {
                $cond = array();
            }
            $cond[$mat[1]] = $args[0];
            $fargs['conditions'] = $cond;
            #	      print_r($fargs);
            return $this->find_all($fargs);
        } elseif (preg_match("/^find_or_create_by_(.*)/", $m, $mat)) {
            $s = array_shift($args);
            # $args['conditions']=array($mat[1]=>$s);
            return $this->find_or_create(array($mat[1] => $s));
        } elseif (preg_match("/^has_(.*)/", $m, $mat)) {
            $r = $mat[1];
            if (xorcstore_reflection::assoc_many_exists($this, $r)) {
                return !$this->_assoc[$r]->is_empty($args);
            }
        } elseif (preg_match("/^create_(.*)/", $m, $mat)) {
            $r = $mat[0];
            if (xorcstore_reflection::assoc_single_exists($this, $r)) {
                if (!isset($this->_assoc[$r])) {
                    $this->init_relation($r);
                }
                return $this->_assoc[$r]->create($args);
            }
        }
        trigger_error("(CALL) unknown method $m for object " . get_class($this));
    }

    /*
        if an AR object was stored in a session,
        we have to be shure, that reflection is loaded right
    */
    public function __wakeup() {
        new $this->klas;
    }

    public function get($prop = null) {
        if (is_null($prop)) {
            return $this->prop;
        } elseif (is_array($prop)) {
            $ret = array();
            foreach ($prop as $k) {
                $ret[$k] = $this->prop[$k];
            }
            return $ret;
        } else {
            return $this->prop[$prop];
        }
    }

    public function get2() {
        #   log_error("+++++++++++ GET2 ++++++++++++");
        $ret = array();
        #	log_error($this->prop);
        foreach ($this->prop as $k => $v) {
            #	   log_error("key: $k");
            $ret[$k] = $this->$k;
        }
        return $ret;
    }

    public function set($prop, $val = "", $unprotected = false) {
        if (!is_array($prop)) {
            $this->$prop = $val;
            return;
        }
        //		print "PROPTEST";print_r($prop);

        foreach ($prop as $k => $v) {
            #		   print("SET $k TO $v (#$unprotected#{$this->protected[$k]}#)");
            if ($unprotected) {
                $this->$k = $v;
            } elseif (
                !isset($this->protected[$k]) &&
                !xorcstore_reflection::is_primary_key($this, $k) &&
                xorcstore_reflection::sti_type($this) != $k
            ) {
                $this->$k = $v;
            }

            /*	   else print("NOT SET $k TO $v [".
                      $this->sti_type()."#".$this->sti_type()==$k."#".
                      $this->protected[$k]."#".
                      in_array($k, $this->schema['keys']).
                      "]// \n");
             */
            //			if(!$this->protected[$k] && isset($this->schema['fields'][$k])) $this->prop[$k]=$v;
            //			if($this->schema['keys'][$k]) $this->update_relations();
        }
        //		$this->update_relations();
        //		$this->init();
    }

    #[\ReturnTypeWillChange]
    public function count() {
        $cond = "";
        #	   print_r($this);
        $tf = xorcstore_reflection::sti_type_condition($this);
        #	   print("CTF:");print_r($tf);
        if ($tf) {
            return $this->count_by_conditions($tf);
        } else {
            return $this->count_by_sql("SELECT COUNT(*) FROM " . $this->table() . " $cond");
        }
    }

    public function count_by_sql($sql) {
        $sql = $this->sanitize_conditions($sql);
        #      print "CQ:".$sql;
        $rs = $this->query_free($sql);
        if ($rs) {
            #		   log_error($rs);
            return array_shift($rs->fields);
        } else {
            return false;
        }
    }

    public function count_by_conditions($cond) {
        #	   print_r($cond);
        $cond = $this->condition_to_sql($cond);
        if ($cond) {
            $cond = "WHERE " . $cond;
        }
        return $this->count_by_sql("SELECT COUNT(*) FROM " . $this->table() . " $cond");
    }

    public function calculate($op, $col, $opts = array()) {
        if ($op == "average") {
            $op = "AVG";
        } elseif ($op == "minimum") {
            $op = "MIN";
        } elseif ($op == "maximum") {
            $op = "MAX";
        }
        $selop = strtolower($op) . "_$col";
        $rsC = array();
        $sel = "";
        if ($opts['conditions']) {
            $cond = "WHERE " . $this->condition_to_sql($opts['conditions']);
        }
        if ($opts['order']) {
            $order = "ORDER BY " . $opts['order'];
        }
        if ($opts['having']) {
            $having = "HAVING " . $opts['having'];
        }
        if ($opts['group']) {
            $grp = $opts['group'];

            #print_r($this);
            #var_dump(xorcstore_reflection::column_exists($this, $grp));
            #var_dump(xorcstore_reflection::assoc_belongs_to_exists($this, $grp));

            if (
                !xorcstore_reflection::column_exists($this, $grp) &&
                xorcstore_reflection::assoc_belongs_to_exists($this, $grp)
            ) {
                $this->$grp;
                $grp = $this->_assoc[$grp]->fkey;
            }

            $group = "GROUP BY " . $grp;
            $rsC[] = $grp;

            $sel = join(",", $rsC);
        }
        if ($sel) {
            $sel = ", " . $sel;
        }

        $q = "SELECT $op($col) AS $selop $sel FROM " . $this->table() . " $cond $group $having $order";
        #	$con=$this->connection();
        #	$ALL=$con->GetAll($q);
        #	print_r($ALL);
        $RS = $this->query_free($q);

        $rL = array();
        while (!$RS->EOF) {
            #		print_r($rsC);
            # print_r($RS->fields);
            #	   $data=array_shift($RS->fields);
            $fa = $rsC[0] ? explode(",", $rsC[0]): [];
            #		print_r(sizeof($fa));
            if (strlen($fa[0]) === 0) {
                $rL[0] = $RS->fields[$selop];
            } elseif (sizeof($fa) == 1) {
                $rL[$RS->fields[trim($fa[0])]] = $RS->fields[$selop];
            } elseif (sizeof($fa) == 2) {
                $rL[$RS->fields[trim($fa[0])]][$RS->fields[trim($fa[1])]] = $RS->fields[$selop];
            } else {
                $rL[] = $RS->fields[$selop];
            }
            // $rL[]=$RS->fields;
            $RS->MoveNext();
        }
        #print_r($rL);
        #if(sizeof($rL)==1) return array_shift($rL[0]);
        if ($rsC && sizeof($rsC)) {
            return $rL;
        }
        #	echo "HHH";
        return $rL[0];
    }

    public function average($col, $opts = array()) {
        return $this->calculate("average", $col, $opts);
    }
    public function sum($col, $opts = array()) {
        return $this->calculate("sum", $col, $opts);
    }
    public function minimum($col, $opts = array()) {
        return $this->calculate("minimum", $col, $opts);
    }
    public function maximum($col, $opts = array()) {
        return $this->calculate("maximum", $col, $opts);
    }

    public function protect($f) {
        if (!is_array($f)) {
            $f = array($f);
        }
        foreach ($f as $pf) {
            $this->protected[$pf] = true;
        }
    }

    public function unprotect($f) {
        if (!is_array($f)) {
            $f = array($f);
        }
        foreach ($f as $pf) {
            $this->protected[$pf] = false;
        }
    }

    public function protect_keys() {
        $this->protect($this->schema['keys']);
    }
    public function unprotect_keys() {
        $this->unprotect($this->schema['keys']);
    }

    public function &connection($db = "", $clear = false) {
        static $con;
        if ($clear) {
            $con = null;
            return;
        }
        if (!$db) {
            $db = xorcstore_reflection::database($this);
        }
        //		print("looking for {$this->schema['table']['db']}:".$GLOBALS[$this->schema['table']['db']]."~");

        if (!$con) {
            $con = XorcStore_Connector::get($db);
        }

        // vor dem XS_Connector lief das über globale variablen
        //		if(!$con) $con =& $GLOBALS[$db];
        //		if(!$con) {global $_db; $con =& $_db;}
        return $con;
    }

    /**
     * Sample:
     * 'program_audio'=>array(
     *			'class'=>'program',
     *			'fkey'=>'audio_id',
     *			'dependent'=>'none', //(or dependents will be deleted!)
     *		),
     */
    public function has_many() {
        return array();
    }
    public function belongs_to() {
        return array();
    }
    public function has_one() {
        return array();
    }

    /**
     * Sample:
     *	'files'=>array(
     *		'join_table'=>'programs_files',
     *		'class'=>'content_file',
     *		'dependent'=>'none', //(or dependents will be deleted!)
     *		'fkey'=>'file_id',
     *		'myfkey'=>'program_id',
     *	)
     * @return array
     */
    public function has_many_belongs_to_many() {
        return array();
    }

    public function define_schema_default($or_inifile_not_found = null, $klas = null) {
        if (!is_null($or_inifile_not_found)) {
            print "ERR! could not load ini file [{$or_inifile_not_found}] " .
                "for class [" . get_class($this) . "]\nif you don't want to use" .
                " ini files at all, then undefine XORC_DB_SCHEMAPATH OR provide" .
                " a define_schema function for your objects\n";
            return array();
        }
        // guess table name
        include_once("xorc/div/naming.class.php");
        $n = new Naming;
        return array("table" => $n->plural($klas));
    }

    public function free_memory() {
        foreach ($this->_assoc as $name => $ass) {
            $this->_assoc[$name]->free();
        }
        $this->_assoc = array();
        $this->connection("", "CLEAR");
    }
    /*
    #
    # implements Iterator
    #
        public function current()
       {
           return current($this->prop);
       }

       public function key()
       {
           return key($this->prop);
       }

       public function next()
       {
           next($this->prop);
       }

       public function rewind()
       {
           reset($this->prop);
       }

       public function valid()
       {
           return (current($this->prop) !== FALSE);
       }

    */
}

interface I_Xorcstore_AR_extension {
    public function match_get($o, $opts, $k);
    public function get($o, $opts, $k);
    public function set($o, $opts, $k, $v);
    public function before_create($o, $opts);
    public function before_update($o, $opts);
    public function before_destroy($o, $opts);
    public function before_save($o, $opts);
}
