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


class Xorcstore_iterator {

	var $query;
	var $dbh;
	var $rs;
	var $create_obj;
	var $split=false;
	var $pager=null;
	
	function Xorcstore_iterator($rs="", $co=""){
		$this->rs=$rs;
		$this->create_obj=$co;
	}

   function set_pager($pager){
      $this->pager=$pager;
   }
   function pager(){
      return $this->pager;
   }
   
   function total_rows(){
      return $this->rs->RecordCount();
   }
	function next(){
		if($this->rs && !$this->rs->EOF){
			$ret = $this->rs->fields;
			$this->rs->MoveNext();
	#		adodb_movenext($this->rs);
			#print $this->create_obj;
			$o=new $this->create_obj;
			$o->set($ret);			
			$o->init_loaded_object();
			return $o;
		} else return false;
	}

	function next_typed(){
		if($this->rs && !$this->rs->EOF){
			$ret = $this->rs->fields;
			$this->rs->MoveNext();
			$o=new $this->create_obj;
			$o2=$o->load("", "", $ret);
//			$o->set($ret);
         $o2->init_loaded_object();
			return $o2;
		} else return false;
	}
   	
	function next_hash(){
		if($this->rs && !$this->rs->EOF){
			$ret = $this->rs->fields;
			if($this->split){
				$retL=array();
				foreach($ret as $k=>$v){
					preg_match("/^t(\d)_(.*)$/", $k, $m);
					$retL[$m[1]][$m[2]]=$v;
				}
				$ret=$retL;
			}
			$this->rs->MoveNext();
	#		adodb_movenext($this->rs);
			return $ret;
		} else return false;
	}
	
	function getNext(){
		return $this->next_hash();
	}
	
	function count(){
	   return $this->rs->RecordCount();
	}
}
