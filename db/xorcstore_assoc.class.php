<?php

/**
 * Assoziationen
 *
 * geeignet zum verwalten von
 *  - 1:n beziehungen has_many bzw. belongs_to
 *  - n:m beziehungen has_may_belongs_to_many
 *  - 1:1 beziehungen belongs_to
 *
 * @author Robert Wagner
 * @version $Id$
 * @copyright 20sec.net, 28 January, 2006
 * @package xorc
 **/

class xorcstore_assoc implements JsonSerializable {
	var $motherclass;
	var $relname;
	var $classname;
	var $fkey;

	var $was_loaded = false;
	var $owner;

	public static $types = array(
		'has_many', 'belongs_to', 'has_one', 'has_many_belongs_to_many'
	);

	function __construct($mclass, $rel, $opts, $owner) {
		$this->motherclass = $mclass;
		$this->relname = $rel;
		$this->fkey = $opts['fkey'];
		$this->classname = $opts['class'];
		$this->opts = $opts;
		$this->owner = $owner;
		if ($this->opts['class_map']) {
			$this->opts['class_map_rev'] = array_flip($this->opts['class_map']);
		}
	}

	function set_owner($parent) {
		$this->owner = $parent;
	}

	function reload() {
		$this->get(true);
	}

	function parent_saved() {
		return $this->save();
	}
	function parent_deleted() {;
	}

	function free() {
		#$this->owner->free_memory();
		$this->owner = null;
		if ($this->obj) $this->obj->free_memory();
		$this->obj = null;
	}

	function type_for_class($obj) {
		return $this->opts['class_map'][strtolower(get_class($obj))];
	}

	function class_for_type($type) {
		return $this->opts['class_map_rev'][strtoupper($type)];
	}

	#[\ReturnTypeWillChange]
	function jsonSerialize() {
		if ($this->_rs) return $this->to_array();
		return $this->obj;
	}
}


class xorcstore_assoc_collection extends xorcstore_assoc implements ArrayAccess, Iterator, Countable {
	var $before_add, $after_add, $before_remove, $after_remove = null;

	var $_arr = null;
	var $_rs = null;
	var $_arr_valid = false;

	function is_empty($reload = false) {
		if (!$this->was_loaded || $reload) $this->get(true);
		return $this->count() ? false : true;
	}

	#[\ReturnTypeWillChange]
	function rewind() {
		if (is_null($this->_arr)) {
			$this->get()->_rs->rewind();
		} else $this->_arr_valid = (false !== reset($this->_arr));
	}

	#[\ReturnTypeWillChange]
	function valid() {
		if (is_null($this->_arr)) return $this->get()->_rs->valid();
		else return $this->_arr_valid;
	}

	#[\ReturnTypeWillChange]
	function key() {
		if (is_null($this->_arr)) return $this->get()->_rs->key();
		else return key($this->_arr);
	}

	#[\ReturnTypeWillChange]
	function current() {
		if (is_null($this->_arr)) return $this->get()->_rs->current();
		else return current($this->_arr);
	}

	#[\ReturnTypeWillChange]
	function next() {
		if (is_null($this->_arr)) $this->get()->_rs->next();
		else $this->_arr_valid = (false !== next($this->_arr));
	}

	#[\ReturnTypeWillChange]
	function first() {
		if (!is_null($this->_arr)) return $this->_arr[0];
		$this->get();
		$this->rewind();
		return $this->current();
	}

	#[\ReturnTypeWillChange]
	function offsetExists($offset) {
		$this->to_array();
		if (isset($this->_arr[$offset]))  return true;
		else return (false);
	}

	#[\ReturnTypeWillChange]
	function offsetGet($offset) {
		if ($this->offsetExists($offset)) return $this->_arr[$offset];
		else return (false);
	}

	#[\ReturnTypeWillChange]
	function offsetSet($offset, $value) {
		#print "ADDING :";
		#print_r($value);
		$this->to_array();
		if ($offset) {
			$obj = $this->add($value);
			if ($obj) $this->_arr[$offset] = $obj;
		} else {
			if (is_array($value) || (is_object($value) && method_exists($value, "next"))) {
				foreach ($value as $val) {
					# print_r($val);
					$obj = $this->add($val);
					if ($obj) $this->_arr[] = $obj;
				}
			} else {
				$obj = $this->add($value);
				if ($obj) $this->_arr[] = $obj;
			}
		}
	}

	#[\ReturnTypeWillChange]
	function offsetUnset($offset) {
		$this->to_array();
		$this->delete($this->_arr[$offset]);
		unset($this->$this->_arr[$offset]);
	}

	function to_array() {
		if (is_null($this->_arr)) {
			$this->_arr = $this->get()->_rs->to_array();
		}
		return $this->_arr;
	}

	#[\ReturnTypeWillChange]
	function count($reload = false) {
		log_error("XS_ASSOC HM: COUNTING $reload");
		if ($reload) $this->get(true);
		if (is_null($this->_arr)) {
			if (is_null($this->_rs)) {
				return $this->count_by_sql();
			} else {
				return $this->_rs->count();
			}
		} else return count($this->_arr);
	}

	function free() {
		#$this->owner->free_memory();
		$this->owner = null;
		if ($this->_arr) foreach ($this->_arr as $k => $v) $this->_arr[$k]->free_memory();
		$this->_arr = null;
		if ($this->_rs) $this->_rs->free();
		$this->_rs = null;
		$this->_arr_valid = false;
		if ($this->data) foreach ($this->data as $k => $v) $this->data[$k]->free_memory();
		$this->data = null;
	}

	function __toString() {
		return join(', ', array_map(function ($i) {
			return $i->id;
		}, $this->to_array()));
	}
}

class xorcstore_assoc_has_many extends xorcstore_assoc_collection {

	function __construct($mclass, $rel, $opts, $owner) {
		parent::__construct($mclass, $rel, $opts, $owner);
		if (!$this->fkey) {
			#print "MOTHER:".$this->motherclass;
			$this->fkey = $this->motherclass . "_id";
		}
		if (!isset($this->opts['dependent'])) {
			$this->opts['dependent'] = "delete_all";
		}
		if (isset($this->opts['validate']) && $this->opts['validate'] === false) {
			$this->opts['validate'] = false;
		} else {
			$this->opts['validate'] = true;
		}
		if ($this->opts['position']) {
			$this->seq = $this->opts['position'];
		}
	}

	function get($reload = false) {
		if (!$reload && $this->_rs) return $this;

		$id = $this->owner->id();

		if (!$id) {
			$this->_arr = array();
			return $this;
		}
		#if($cond) $cond[]=array($cond);
		if ($id) {
			$this->find($id);
		}
		$this->was_loaded = true;
		return $this;
	}

	function find($id) {
		$id = $this->owner->id();

		$o = new $this->classname;
		if ($this->opts['finder_sql']) {
			$this->_rs = $o->find_by_sql(str_replace("%ID%", $id, $this->opts['finder_sql']));
			return;
		}
		$cond = $this->opts['conditions'];
		if ($cond) {
			if (!is_array($cond)) $cond = array($cond);
			$cond = array_merge(array($this->fkey => $id), $cond);
		} else {
			$cond = array($this->fkey => $id);
		}

		# print_r($cond);
		$order = $this->opts['order'];
		if (!$order) $order = $this->seq;
		log_error("### ORDER: $order");
		$this->_rs = $o->find(array(
			'conditions' => $cond, 'limit' => $this->opts['limit'],
			'order' => $order
		));
		$o->free_memory();
	}

	function count_by_sql() {
		$id = $this->owner->id();
		$o = new $this->classname;

		if ($this->opts['counter_sql'])
			return $o->count_by_sql(str_replace("%ID%", $id, $this->opts['counter_sql']));
		if ($this->opts['finder_sql'])
			return $o->count_by_any_sql(str_replace("%ID%", $id, $this->opts['finder_sql']));

		$cond = ($opts['conditions']) ? $opts['conditions'] : array($this->fkey => $id);
		return $o->count_by_conditions($cond);
	}

	function get_join_sql($no, $leftid) {
		$o = new $this->classname;
		$rname = $o->name_for_relation($this->name);
		$f = array_keys($o->schema['fields']);

		$cols = array_map(function ($a) use ($no) {
			return "t{$no}.{$a} AS t{$no}_{$a}";
		}, $f);

		$tab = $o->table() . " AS t$no";
		$cond = "$leftid=t$no." . $o->{$rname}->fkey;
		return array($cols, $tab, $cond);
	}

	function set($objL) {
		$this->clear();
		if (!is_array($objL)) {
			$objL = array($objL);
		}
		foreach ($objL as $o) {
			$this->add($o);
		}
	}

	function add($obj) {
		$id = $this->owner->id();

		$fkey = $this->fkey;
		if ($id) {
			# if($obj->$fkey != $id){
			$obj->$fkey = $id;
			$obj->save($this->opts['validate']);
			#}
		}
		return $obj;
	}

	function save() {
		$id = $this->owner->id();
		$fkey = $this->fkey;
		foreach ($this as $obj) {
			if ($obj->is_new_record()) {
				$obj->$fkey = $id;
				$obj->save($this->opts['validate']);
			}
		}
	}

	function delete($objL) {
		if (!is_array($objL)) {
			$objL = array($objL);
		}
		foreach ($objL as $o) {
			$this->_remove($o);
		}
	}


	function clear() {
		#	   if($this->opts['dependent']=="delete_all"){
		#	      $o=new $this->classname;
		#	      $o->delete_all(array($this->fkey=>$this->owner->id()));
		#	   }else{
		$this->get();
		foreach ($this as $o) {
			$this->_remove($o);
		}
		#		}
	}

	function _remove($o) {
		if ($this->opts['dependent'] == 'destroy') {
			$o->destroy();
		} elseif ($this->opts['dependent'] == "delete_all") {
			$o->delete();
		} else {
			// nullify
			//   evtl. save($validate) aufrufen??? check AR-rails
			$fkey = $this->fkey;
			$o->update_attr($fkey, null);
		}
	}

	function create($parms = array()) {
		$obj = new $this->classname;

		$oL = $obj->create($parms, array($this->fkey => $this->owner->id()));
		if (is_array($oL)) {
			foreach ($oL as $ao) {
				$this->add($ao);
			}
		} else {
			$this->add($oL);
		}

		return $oL;
	}

	function parent_deleted() {
		return $this->clear();
	}

	function update_sequence($ids = array()) {
		log_error("SEQ-UPDATE");
		$order = array_flip($ids);
		if ($this->seq) {
			$c = 1;
			foreach ($this as $o) {
				$position = $order[$o->id] + 1;
				$o->update_attr($this->seq, $position);
				$c++;
			}
		}
	}
}



class xorcstore_assoc_has_many_belongs_to_many extends xorcstore_assoc_collection {

	var $data = array();
	var $data_unsaved = array();

	var $fkey;
	var $myfkey;
	var $jointable;
	var $keyQ;
	var $seq;
	public $additional_props;

	function __construct($mclass, $rel, $opts, $owner) {
		if (!$opts['join_table']) trigger_error('join_table not given!');
		if (!$opts['fkey']) trigger_error('fkey not given!');
		if (!$opts['myfkey']) trigger_error('myfkey not given!');

		#print_r($opts);
		parent::__construct($mclass, $rel, $opts, $owner);
		if (!$this->classname) $this->classname = $rel;
		if (!$this->fkey) {
			//TODO: sollte plural sein...
			#print "MOTHER:".$this->motherclass;
			$this->fkey = $rel . "_id";
		}
		$this->myfkey = $opts['myfkey'];
		if (!$this->myfkey) {
			#print "MOTHER:".$this->motherclass;
			$this->myfkey = $this->classname . "_id";
		}
		$this->jointable = xorcstore_reflection::prefix($this->owner) .
			$opts['join_table'];
		if (!$this->opts['dependent']) {
			$this->opts['dependent'] = "delete_all";
		}
		if ($this->opts['position']) {
			$this->seq = $this->opts['position'];
		}
		if ($this->opts['order']) {
			$this->order = $this->opts['order'];
		}
		if ($this->opts['additional_props']) {
			$this->additional_props = $this->opts['additional_props'];
		}
	}

	function get($reload = false) {
		if (!$reload && $this->_rs) return $this;
		if ($this->owner->id()) {
			//			print("GETTING # $this->key ");
			$o = new $this->classname;
			if ($this->seq) $s = "ORDER BY j.$this->seq";
			else $s = "";

			/*			$q="SELECT t.* FROM ".
				$o->table()." t, $this->jointable j ".
				"WHERE j.{$this->myfkey}=$this->keyQ AND ".
				"j.{$this->fkey}=t.{$o->schema['keys'][0]} $s";
*/

			//			print $q;
			#print_r($this);
			#print_r(xorcstore_reflection::$r);
			$this->_rs = $o->find_by_sql($this->join_sql($o->table(), $o->primary_key(), $this->jointable, $this->myfkey, $this->fkey, $this->owner->id_quoted(), $this->seq, $this->order));
			$this->_arr = null;
			$this->was_loaded = true;
		} else {
			$this->_arr = array();
			$this->_rs = array();
		}
		//		print("NO");

		return $this;
	}

	function join_sql($tab, $pk, $join, $joinkey, $fkey, $id, $seq = null, $order = null) {
		$add = $this->additional_props;
		if ($add) $add = ", $add";
		$sql = "SELECT t0.* $add FROM " . $tab . " t0 ";
		$sql .= sprintf(
			"LEFT OUTER JOIN %s ON %s.%s = %s.%s ",
			"$join t1",
			"t0",
			$pk,
			"t1",
			$fkey
		);
		// $sql.= sprintf("(AND %s.%s = %s)", "t1", $fkey, $id);
		$sql .= sprintf("WHERE %s.%s = %s", "t1", $joinkey, $id);
		$cond = $this->opts['conditions'];
		if ($cond) {
			$sql .= " AND $cond";
		}
		if ($seq) {
			$sql .= " ORDER BY t1.$seq";
		} elseif ($order) {
			$sql .= " ORDER BY t0.$order";
		}
		return $sql;
	}

	function set($obj) {
		$this->clear();
		$this[] = $obj;
	}

	function add($obj) {
		$oid = $obj->id();
		if (!$this->was_loaded) $this->get();

		if ($obj && $this->opts['uniq'] && $this->includes($obj)) return false;

		if ($this->owner->id()) {
			if (!$oid) {
				$ok = $obj->save();
				if (!$ok) return false;
			}
			$obj->query_free($this->insert_stmt($obj));
			return $obj;
		} else {
			return $obj;
		}
	}

	function save() {
		if (!$this->owner->id()) return;
		foreach ($this as $obj) {
			if ($obj->is_new_record()) {
				$ok = $obj->save();
				if ($ok) $obj->query_free($this->insert_stmt($obj));
			}
		}
	}

	function insert_stmt(&$obj) {
		$f = array($this->myfkey, $this->fkey);
		$v = array($this->owner->id(), $obj->id());
		if ($this->opts['fkey_type']) {
			$f[] = $this->opts['fkey_type'];
			$v[] = "'" . $this->type_for_class($obj) . "'";
		}
		if ($this->seq) {
			$f[] = "$this->seq";
			// $v[]=count($this)+1;
			$keyQ = $this->owner->id_quoted();
			$fkey = $obj->id_quoted();
			# coalesce(MAX(pos), 0) + 1 
			$v[] = "(SELECT coalesce(MAX({$this->seq}), 0) + 1 FROM " . $this->jointable . " mtm WHERE $this->myfkey=$keyQ)";
		}
		return sprintf(
			"INSERT INTO %s (%s)	VALUES (%s)",
			$this->jointable,
			join(",", $f),
			join(",", $v)
		);
	}

	function update_sequence($ids = array()) {
		log_error("SEQ-UPDATE");
		$keyQ = $this->owner->id_quoted();
		$order = array_flip($ids);
		if ($this->seq) {
			$c = 1;
			foreach ($this as $o) {
				$position = $order[$o->id] + 1;
				$q = "UPDATE {$this->jointable} SET {$this->seq}=$position WHERE $this->myfkey=$keyQ " .
					"AND $this->fkey=" . $o->id_quoted();
				$o->query_free($q);
				$c++;
			}
		}
	}

	function move_to_top($obj) {
		$ok = $this->add($obj);
		if ($ok) {
			$keyQ = $this->owner->id_quoted();
			$q = "UPDATE {$this->jointable} SET {$this->seq}={$this->seq} + 1  WHERE $this->myfkey=$keyQ ";
			$obj->query_free($q);
			$this->assume_top_position($obj);
		}
	}

	function assume_top_position($obj) {
		$keyQ = $this->owner->id_quoted();
		$q = "UPDATE {$this->jointable} SET {$this->seq}= 1  WHERE $this->myfkey=$keyQ " .
			"AND $this->fkey=" . $obj->id_quoted();
		$obj->query_free($q);
	}

	# nutzlos wg. $data vs. $_rs
	function sort_by_idlist($ids = array()) {
		log_error("IDLIST");
		if (!$this->owner->id()) return;
		log_error("IDLIST2");
		if (!$this->seq) return;
		log_error("IDLIST3");
		if (!$this->was_loaded) $this->get();
		$newdata = array();
		foreach ($ids as $id) {
			if (isset($this->data[$id])) {
				$newdata[$id] = $this->data[$id];
				unset($this->data[$id]);
			}
		}
		$this->data = array_merge($newdata, $this->data);
		// log_error($this->data);
		$this->update_sequence();
	}

	function delete($objL) {
		if (!is_array($objL)) {
			$objL = array($objL);
		}
		foreach ($objL as $o) {
			$this->_remove($o);
		}
	}


	function clear() {
		foreach ($this as $o) {
			$this->_remove($o);
		}
		$this->_rs = null;
	}

	function _remove($obj = null) {
		$cond = "";
		if (!$this->owner->id()) return;
		if (is_null($obj)) return;
		if (!is_object($obj)) {
			$id = $obj;
		} else {
			if ($obj->is_new_record()) return;
			$id = $obj->id_quoted();
		}
		$cond = sprintf("AND $this->fkey = %s", $id);

		$q = sprintf(
			"DELETE FROM %s WHERE %s = %s %s",
			$this->jointable,
			$this->myfkey,
			$this->owner->id(),
			$cond
		);
		$o = new $this->classname;
		$o->query_free($q);
	}

	// TODO
	function _parent_deleted() {
		$this->_remove();
		if ($this->opts['dependent'] == 'destroy') {
			foreach ($this as $o) $o->destroy();
		} elseif ($this->opts['dependent'] == "delete_all") {
			foreach ($this as $o) $o->delete();
		} else {
			// nothing
		}
	}

	function includes($obj) {
		if ($this->opts['fkey_type']) {
			$type = $this->type_for_class($obj);
		} else {
			$type = '-';
		}
		foreach ($this as $o) {
			if ($type != '-') {
				$typecol = $this->opts['fkey_type'];
				if ($o->id == $obj->id && $o->$typecol == $type) return true;
			} else {
				if ($o->id == $obj->id) return true;
			}
		}
		return false;
	}

	function count_by_sql() {
		//   print "HABTM COUNT";
		$o = new $this->classname;
		return $o->count_by_any_sql($this->join_sql($o->table(), $o->primary_key(), $this->jointable, $this->myfkey, $this->fkey, $this->owner->id()));
	}

	function to_array() {
		if (is_null($this->_arr)) {
			$this->_arr = $this->get()->_rs->to_array();
		}
		return $this->_arr;
	}
}


class xorcstore_assoc_has_one extends xorcstore_assoc {

	var $obj = null;

	function __construct($mclass, $rel, $opts, $owner) {
		parent::__construct($mclass, $rel, $opts, $owner);
		if (!$this->fkey) {
			#print "MOTHER:".$this->motherclass;
			$this->fkey = $this->motherclass . "_id";
		}
		if (!$this->classname) $this->classname = $rel;
	}

	function get($reload = false) {
		if (!$reload && $this->was_loaded) return $this->obj;
		$k = $this->classname;
		if ($this->owner->id()) {
			$o = new $k;
			$obj = $o->find_first(array("conditions" => array($this->fkey => $this->owner->id())));
			if ($obj) {
				$this->obj = $obj;
			}
			$this->was_loaded = true;
		}
		return $this->obj;
	}

	function set($obj) {
		$ok = false;
		if (!$this->was_loaded) $this->get();
		if (!$this->owner->is_new_record()) {
			$obj->{$this->fkey} = $this->owner->id();
			$ok = $obj->save();

			if ($ok) {
				if ($this->obj) {
					$this->obj->{$this->fkey} = null;
					$this->obj->save();
				}
				$this->obj = $obj;
			}
			return $ok;
		} else {
			$this->obj = $obj;
			return true;
		}
	}

	function save() {
		//	print_r($this->data);
		//	print "NAME: $this->name ~ $this->relname ~ $this->classname ~";
		if ($this->obj) {
			$this->obj->{$this->fkey} = $this->owner->id();
			$this->obj->save();
		}
	}
}

class xorcstore_assoc_belongs_to extends xorcstore_assoc {

	var $obj = null;

	function __construct($mclass, $rel, $opts, $owner) {
		parent::__construct($mclass, $rel, $opts, $owner);
		if (!$this->classname) $this->classname = $rel;
		if (!$this->fkey) {
			#print "MOTHER:".$this->motherclass;
			$this->fkey = $this->classname . "_id";
		}
	}

	function get($reload = false) {
		if (!$reload && $this->was_loaded) return $this->obj;
		if ($this->owner->{$this->fkey}) {
			#		print "REL: $this->relname $this->key~{$this->keyvals[$this->fkey]}~";
			#		print_r($this);
			$o = new $this->classname;
			$this->obj = $o->find($this->owner->{$this->fkey});
			$this->was_loaded = true;
		}
		return $this->obj;
	}

	function set($obj) {
		$this->obj = $obj;
		if (!is_null($obj)) {
			$this->owner->{$this->fkey} = $obj->id();
		} else {
			$this->owner->{$this->fkey} = null;
		}
	}

	function build($attrs) {
		$obj = new $this->classname;
		$obj = $obj->set($attrs);
		$this->obj = $obj;
		return $obj;
	}

	function create($attrs) {
		$o = new $this->classname;
		$obj = $o->create($attrs);
		$this->owner->{$this->fkey} = $obj->id();
		$this->obj = $o;
		return $obj;
	}

	function fkey_names() {
		return array($this->fkey);
	}


	function save() {
		/*      $ok=true;
      if($this->obj->is_new_record()){
         $ok=$this->obj->save();
      }
      if($ok && $this->owner->{$this->fkey}!=$this->obj->id()){
*/
		if ($this->obj)   $this->owner->{$this->fkey} = $this->obj->id();
		/*      }
*/
	}
}
