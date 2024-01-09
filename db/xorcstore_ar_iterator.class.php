<?php

/**
 * Iterator über Resultsets
 *
 * kann objekte oder assoz. arrays erzeugen
 *
 * @author Robert Wagner
 * @version $Id$
 * @copyright 20sec.net, 28 January, 2006
 * @package xorc
 **/



class Xorcstore_AR_iterator  implements ArrayAccess, Iterator, Countable, JsonSerializable {


   private $rs;
   private $query;
   private $dbh;
   private $_arr = null;
   private $_cache = array();
   private $pager = null;

   #public $is="iterable";

   var $create_obj;
   private $split = false;

   #[\ReturnTypeWillChange]
   function jsonSerialize() {
      return array('paginate' => $this->pager, 'items' => $this->to_array());
   }

   function set_pager($pager) {
      $this->pager = $pager;
   }
   function pager() {
      return $this->pager;
   }
   function __construct($rs, $co = "") {
      $this->rs = $rs;
      $this->_cache = array();
      $this->create_obj = $co;
   }

   #[\ReturnTypeWillChange]
   function rewind() {
      $this->rs->MoveFirst();
   }

   #[\ReturnTypeWillChange]
   function valid() {
      return !$this->rs->EOF;
   }

   #[\ReturnTypeWillChange]
   function key() {
      return $this->rs->_currentRow;
   }

   #[\ReturnTypeWillChange]
   function current() {
      # initialisierte objekte immer wiederverwenden
      if ($this->_cache[$this->rs->_currentRow] ?? null) {
         return $this->_cache[$this->rs->_currentRow];
      }
      $o = $this->init_object($this->rs->fields);
      $this->_cache[$this->rs->_currentRow] = $o;
      return $o;
   }

   #[\ReturnTypeWillChange]
   function next() {
      $this->rs->MoveNext();
   }

   function next_object() {
      if ($this->rs && !$this->rs->EOF) {
         $ret = $this->rs->fields;
         $this->rs->MoveNext();
         #		adodb_movenext($this->rs);
         #print $this->create_obj;
         $o = new $this->create_obj;
         $o->set($ret);
         $o->init_loaded_object();
         return $o;
      } else return false;
   }

   function next_hash() {
      if ($this->rs && !$this->rs->EOF) {
         $ret = $this->rs->fields;
         if ($this->split) {
            $retL = array();
            foreach ($ret as $k => $v) {
               preg_match("/^t(\d)_(.*)$/", $k, $m);
               $retL[$m[1]][$m[2]] = $v;
            }
            $ret = $retL;
         }
         $this->rs->MoveNext();
         return $ret;
      } else return false;
   }

   function init_object($fields) {
      if (($k = xorcstore_reflection::sti_type_by_name($this->create_obj)) && $fields[$k]) {
         //            print "OK";
         $o = new $fields[$k];
      } else {
         //            print "NEE".$fields[$k];
         $o = new $this->create_obj;
      }
      $o->set($fields, null, true);
      $o->fields_unserialize();
      $o->was_loaded();
      #			$o->update_relations();
      $o->load_files($fields);
      $o->after_load();
      return $o;
   }

   function total_rows() {
      return $this->rs->_numOfRows;
   }

   #[\ReturnTypeWillChange]
   function count() {
      return $this->rs->_numOfRows;
   }

   #[\ReturnTypeWillChange]
   function first() {
      if (!is_null($this->_arr)) return $this->_arr[0];
      $this->rewind();
      return $this->current();
   }

   #[\ReturnTypeWillChange]
   function offsetExists($offset) {
      $this->to_array();
      if (isset($this->_arr[$offset]))  return true;
      else return false;
   }

   #[\ReturnTypeWillChange]
   function offsetGet($offset) {
      if ($this->offsetExists($offset))  return $this->_arr[$offset];
      else return (false);
   }

   #[\ReturnTypeWillChange]
   function offsetSet($offset, $value) {

      # if ($offset)  $this->objectArray[$offset] = $value;
      #   else  $this->objectArray[] = $value;
   }

   #[\ReturnTypeWillChange]
   function offsetUnset($offset) {
      if (isset($this->_cache[$offset])) unset($this->_cache[$offset]);
      #   unset ($this->objectArray[$offset]);
   }

   function to_array() {
      log_error("#### ITERATOR MAKES AN ARRAY######");
      if (is_null($this->_arr)) {
         $this->_arr = array();
         foreach ($this as $o) {
            $this->_arr[] = $o;
         }
      }
      return $this->_arr;
      //  log_error("#### END ######");
   }

   function to_array_indexed($prop) {
      return array_combine(array_map(function ($e) use ($prop) {
         return $e->$prop;
      }, $this->to_array()), $this->to_array());
   }

   function getArrayxx() {
      if (is_null($this->_arr)) {
         print "MAKING ARRAY";
         $this->to_array();
      }
      return $this->_arr;
   }


   function iterator_to_arrayxx() {
      $this->_arr = array();
      foreach ($this as $r) {
         $this->_arr[] = $this->init_object($r);
      }
   }


   function to_xml($opts = array()) {
      $def = array("skip_instruct" => false, "indent" => 3, 'include' => array());
      $opts = array_merge($def, $opts);
      if (!is_array($opts['include'])) $opts['include'] = array($opts['include']);
      $xml = "";
      if (!$opts['skip_instruct']) $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
      $xml .= "<items>\n";
      $opts['skip_instruct'] = true;
      foreach ($this as $i) {
         $xml .= $i->to_xml($opts);
      }
      $xml .= "</items>\n";
      return $xml;
   }

   /*
         function __call($func, $params)
         {
            return call_user_func_array(array($this->rs, $func), $params);
         }


         function hasMore()
         {
            return !$this->rs->EOF;
         }
         */

   function free() {
      $this->rs = null;
      $this->query = null;
      $this->dbh = null;
      if ($this->_arr) foreach ($this->_arr as $k => $v) $this->_arr[$k]->free_memory();
      $this->_arr = null;
      $this->pager = null;
   }
}
