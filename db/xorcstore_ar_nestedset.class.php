<?php
/**
 * Nestet Set 
 *
 * Adapter für Xorcstore
 *
 * @author Jens Riedorf
 * @version $Id$
 * @copyright 20sec.net, 28 January, 2006
 * @package xorc
 **/

/*
  Nested Set Tree Library
  
  Author:  Rolf Brugger, edutech
  Version: 0.02, 5. April 2005
  URL:     http://www.edutech.ch/contribution/nstrees
  
  DB-Model by Joe Celko (http://www.celko.com/)
  
  References:
    http://www.sitepoint.com/article/1105/2
    http://searchdatabase.techtarget.com/tip/1,289483,sid13_gci537290,00.html
    http://dbforums.com/arch/57/2002/6/400142



  Datastructures:
  ---------------
  
  Handle:
    key: 'table':    name of the table that contains the tree structure
	key: 'lvalname': name of the attribute (field) that contains the left value
	key: 'rvalname': name of the attribute (field) that contains the right value
	
  Node:
    key 'l': left value
	key 'r': right value
	
	
  Orientation
  -----------
  
      n0
	 / | \
   n1  N  n3
     /   \
   n4     n5
   
  directions from the perspective of the node N:
    n0: up / ancestor
	n1: previous (sibling)
	n3: next (sibling)
	n4: first (child)
	n5: last (child)
     
*/




/* ******************************************************************* */
/* Tree Constructors */
/* ******************************************************************* */


class Xorcstore_AR_Nestedset extends Xorcstore_AR{
	var $ns = array();
	var $nese =array();
	var $neseparent;
	var $neselevel;
	function __construct($id=""){
		static $nese;
		if(!$nese){
		   $nese=$this->nese_setup();
		}
		$this->nese=$nese;
	   parent::__construct($id);
	}
	/*function xorcstore_start($id=""){
	   parent::xorcstore_start($id);
		static $nese;
		if(!$nese){
		   $nese=$this->nese_setup();
		}
		$this->nese=$nese;
	}*/
	

	function XorcStore_ns($id="", $db="_db"){
		$this->xorcstore_start($id, $db);
		//print_r($this);
		if($id){
			$this->xorcstore_ns_start($id);
		}
	}
	function xorcstore_ns_start($id){
		if(is_array($id)){
			foreach($this->schema['keys'] as $k){
				if($this->schema['fields'][$k]!= 1){
					$d = "'";	
				}
				$qkeys[]= "{$k} = {$d}{$id[$k]}{$d}";
				$this->prop[$k] = $id[$k];
			}
		}else{
			$qkeys[$this->schema['keys'][0]] = $id;
			$this->prop[$this->schema['keys'][0]] = $id;
		}
	}
	
	// damit man mal einen root anlegen kann
	// bitte mal genauer durchdenken bzgl. before_create()
	function create_root($id, $name){
	   $root = $this->nese['nsid'];
	   $this->$root=$id;
	   $this->descr=$name;
	   $this->ref_name=$name;
	   $this->l=1;
	   $this->r=2;
	   $this->auto_columns();
	   return $this->_create();
	}
	
	function get_flat(){
	  ####################################
	   $ns = $this->get_tree_as_array();
	   $ret=array();
	   foreach($ns as $leaf){
	      $ret[$leaf->id]=$leaf->descr;
	   }
	   return $ret;
	}
	
	function get_tree_as_array(){
	  #####################################
	   $walk = $this->walk_preorder($this->get_node_where(1));
		$ns = array();
		$parents=array();
		$prevlevel=-1;
		$_time_start=gettimeofday();
		$lastleft = 0;
		foreach($walk['recset'] as $k){
		    if($k->get($this->nese['lft'])==1){
		       $k->neselevel = 1;
		       $k->neseparent = -1;
		       array_push($parents, $k);
		    }else{
		       while($k->get($this->nese['lft']) > $parents[sizeof($parents)-1]->get($this->nese['rgt'])){
		          array_pop($parents);
		         }
		       $k->neseparent=$parents[sizeof($parents)-1]->id;
		       $k->neselevel = sizeof($parents)+1;
		       if($k->get($this->nese['rgt'])-$k->get($this->nese['lft']) > 1){
		          array_push($parents, $k);
   		    }
   		    if($parents[sizeof($parents)-1]->get($this->nese['rgt']) - $k->get($this->nese['rgt']) == 1){
   		       array_pop($parents);
   		       
   		    }
		   }
   		$ns[] = $k;
   	}
   	//print_r($ns);
   	/*if(!$_time_end) $_time_end=gettimeofday();
$_time_total= (float)($_time_end['sec'] - $_time_start['sec']) + ((float)($_time_end['usec'] - $_time_start['usec'])/1000000);
echo $_time_total;*/
   	return $ns;
	}
	
	function child_ids($include_me=false){
	   $left=$this->l;
	   if(!$include_me) $left++;
	   $ids=$this->map("id", array("conditions"=>
         array(
            $this->nese['nsid'] => $this->get($this->nese['nsid']), 
            "l"=>array($left, "between", $this->r)
            ),
         "order"=>"l"));
      return array_merge($ids);
	}
	
	function in(){
	   ###################################
    // indent: move to last child of previous sibling
   	$sibling = $this->prev_sibling($this->node_nsdata());
    	if ($this->valid_node($sibling)){
      	$paramnode = $this->move_to_last_child($this->node_nsdata(), $sibling);
		}
	}
	
	function possible_to_move_in(){
	   ##############################
	  return $this->valid_node($this->prev_sibling($this->node_nsdata()));   
	}
	
	function out(){
	  #######################################
      // outdent: move to next sibling of ancestor (ancestor must not be root!)
      $ancestor = $this->ancestor($this->node_nsdata());
      if($this->valid_node($ancestor) &&
          !($this->nst_equal($ancestor, $this->get_node_where(1)))){
         $node = $this->move_to_next_sibling($this->node_nsdata(), $ancestor);
      }
	}
	
	function possible_to_move_out(){
	  ####################################
	   $ancestor = $this->ancestor($this->node_nsdata());
	   return ($this->valid_node($ancestor) && !($this->nst_equal($ancestor, $this->get_node_where(1))));
	}
	
	function up(){
		####################################################
		$sibling = $this->prev_sibling($this->node_nsdata());
    if ($this->valid_node($sibling)){
      $paramnode = $this->move_to_prev_sibling($this->node_nsdata(), $sibling);
		}
	}
	
	function possible_to_move_up(){
	  #################################
	  return $this->valid_node($this->prev_sibling($this->node_nsdata())); 
	}
	
	function down(){
	  ####################################################
		$sibling = $this->next_sibling($this->node_nsdata());
    	if ($this->valid_node($sibling)){
	      $paramnode = $this->move_to_next_sibling($this->node_nsdata(), $sibling);
		}
	}
	
	function possible_to_move_down(){
	  ###########################################################
      return $this->valid_node($this->next_sibling($this->node_nsdata())); 
	}
	
	function node_nsdata(){
	  ############################################################
	   return array("l"=>$this->get($this->nese['lft']), "r"=>$this->get($this->nese['rgt'])); 
	}
	//last child
	function before_create(){
	  ###############################################################
      $parent = new Structure($this->neseparent);
      if($this->neseparent){
         $this->set($this->nese['lft'], $parent->get($this->nese['lft'])+1);
         $this->set($this->nese['rgt'], $parent->get($this->nese['lft'])+2);
         $this->_shift_rl_Values($this->get($this->nese['lft']), 2);
         return true;
		}
		return false;
	}
	
	function delete_node($l,$r){
		$con =& $this->connection();
		$node = array("l"=>$l, "r"=>$r);
		$leftanchor = $node['l'];
		$rs = $con->Execute("DELETE FROM ".$this->table()." WHERE "
		      .$this->nese['lft'].">=".$node['l']." AND ".$this->nese['rgt']."<=".$node['r']);
		$this->_shift_rl_values($node['r']+1, $node['l'] - $node['r'] -1);
		//if (!$res) {_prtError();}
		/*return nstGetNodeWhere ($thandle, 
		                 $thandle['lvalname']."<".$leftanchor
			   ." ORDER BY ".$thandle['lvalname']." DESC"
			 );*/

	}
	/* creates a new root record and returns the node 'l'=1, 'r'=2. */
	function nstNewRoot ($thandle, $othercols){
	  $newnode['l'] = 1;
	  $newnode['r'] = 2;
	  _insertNew ($thandle, $newnode, $othercols);
	  return $newnode;
	}
	
	function nstNewFirstChild ($thandle, $node, $othercols)
	/* creates a new first child of 'node'. */
	{
	  $newnode['l'] = $node['l']+1;
	  $newnode['r'] = $node['l']+2;
	  _shiftRLValues($thandle, $newnode['l'], 2);
	  _insertNew ($thandle, $newnode, $othercols);
	  return $newnode;
	}
	
	function nstNewLastChild ($thandle, $node, $othercols)
	/* creates a new last child of 'node'. */
	{
	  $newnode['l'] = $node['r'];
	  $newnode['r'] = $node['r']+1;
	  _shiftRLValues($thandle, $newnode['l'], 2);
	  _insertNew ($thandle, $newnode, $othercols);
	  return $newnode;
	}
	
	function nstNewPrevSibling ($thandle, $node, $othercols)
	{
	  $newnode['l'] = $node['l'];
	  $newnode['r'] = $node['l']+1;
	  _shiftRLValues($thandle, $newnode['l'], 2);
	  _insertNew ($thandle, $newnode, $othercols);
	  return $newnode;
	}
	
	function nstNewNextSibling ($thandle, $node, $othercols)
	{
	  $newnode['l'] = $node['r']+1;
	  $newnode['r'] = $node['r']+2;
	  _shiftRLValues($thandle, $newnode['l'], 2);
	  _insertNew ($thandle, $newnode, $othercols);
	  return $newnode;
	}
	
	
	/* *** internal routines *** */
	
	function _shift_rl_values ($first, $delta){ 
	####################################################
	/* adds '$delta' to all L and R values that are >= '$first'. '$delta' can also be negative. */
	if($delta > 0)
	  $delta = "+$delta";
		$this->update_all($this->nese['lft']."=".$this->nese['lft']."$delta",
		    array($this->nese['lft']=>array($first, ">="),
		    $this->nese['nsid']=>$this->get($this->nese['nsid'])
		    ));
		$this->update_all($this->nese['rgt']."=".$this->nese['rgt']."$delta",
		    array($this->nese['rgt']=>array($first, ">="),
		    $this->nese['nsid']=>$this->get($this->nese['nsid'])
		    ));
	}

	/* adds '$delta' to all L and R values that are >= '$first' and <= '$last'. '$delta' can also be negative. 
	   returns the shifted first/last values as node array.
	 */
	
	function _shift_rl_range ($first, $last, $delta){
	log_error(" ###################################");
	if($delta > 0)
	  $delta = "+$delta";
//		$con =& $this->connection();
//		$root = $this->get($this->nese['nsid']);
		$this->update_all($this->nese['lft']."=".$this->nese['lft']."$delta",
		    array(array("{$this->nese['lft']}>=?",$first),
		    $this->nese['lft']=>array($last, "<="),
		    $this->nese['nsid']=>$this->get($this->nese['nsid'])
		    ));
		$this->update_all($this->nese['rgt']."=".$this->nese['rgt']."$delta",
		    array(array("{$this->nese['rgt']}>=?",$first),
		    $this->nese['rgt']=>array($last, "<="),
		    $this->nese['nsid']=>$this->get($this->nese['nsid'])
		    ));
		//$con->EXECUTE("UPDATE ".$this->table()." SET ".$this->nese['lft']."=".$this->nese['lft']."+$delta WHERE ".$this->nese['lft'].">=$first AND ".$this->nese['lft']."<=$last AND {$this->nese['nsid']} = '{$root}'");
//		$con->EXECUTE("UPDATE ".$this->table()." SET ".$this->nese['rgt']."=".$this->nese['rgt']."+$delta WHERE ".$this->nese['rgt'].">=$first AND ".$this->nese['rgt']."<=$last AND {$this->nese['nsid']} = '{$root}'");
	  return array('l'=>$first+$delta, 'r'=>$last+$delta);
	}
	/****************delete******************************
	function _insert_new ($node){
	// creates a new root record and returns the node 'l'=1, 'r'=2. 
	  $this->set($this->nese['lft'], $node['l']);
	  $this->set($this->nese['rgt'], $node['r']);
	  $this->save();
	}*/
	
	
	/* ******************************************************************* */
	/* Tree Reorganization */
	/* ******************************************************************* */
	
	/* all nstMove... functions return the new position of the moved subtree. */
	function move_to_next_sibling ($src, $dst)
	/* moves the node '$src' and all its children (subtree) that it is the next sibling of '$dst'. */
	{
	  #############################################################
	  log_error("move_to_next_sibling");
	  return $this->_move_subtree ($src, $dst['r']+1);
	}

	/* moves the node '$src' and all its children (subtree) that it is the prev sibling of '$dst'. */
	
	function move_to_prev_sibling($src, $dst){
	  return $this->_move_subtree($src, $dst['l']);
	}
	
	function nstMoveToFirstChild($thandle, $src, $dst)
	/* moves the node '$src' and all its children (subtree) that it is the first child of '$dst'. */
	{
	  return _moveSubtree ($thandle, $src, $dst['l']+1);
	}

	/* moves the node '$src' and all its children (subtree) that it is the last child of '$dst'. */
	function move_to_last_child ($src, $dst){
	  ################################################
	  return $this->_move_subtree($src, $dst['r']);
	}

	/* '$src' is the node/subtree, '$to' is its destination l-value */
	
	function _move_subtree($src, $to){	 
      ###########################################
      $treesize = $src['r']-$src['l']+1;
      $this->_shift_rl_values($to, $treesize);
      if($src['l'] >= $to){ // src was shifted too?
         $src['l'] += $treesize;
         $src['r'] += $treesize;
      }
      /* now there's enough room next to target to move the subtree*/
      $newpos = $this->_shift_rl_range($src['l'], $src['r'], $to-$src['l']);
      /* correct values after source */
      $this->_shift_rl_values($src['r']+1, -$treesize);
      if($src['l'] <= $to){ // dst was shifted too?
         $newpos['l'] -= $treesize;
         $newpos['r'] -= $treesize;
      }  
      return $newpos;
	}
	
	/* ******************************************************************* */
	/* Tree Destructors */
	/* ******************************************************************* */
	
	function nstDeleteTree ($thandle)
	/* deletes the entire tree structure including all records. */
	{
	  $res = mysql_query("DELETE FROM ".$thandle['table']);
	  if (!$res) {_prtError();}
	}

	/* deletes the node '$node' and all its children (subtree). */
	
	function nstDelete ($thandle, $node){
		$con =& $this->connection();	
		$leftanchor = $node['l'];
		$res = mysql_query("DELETE FROM ".$thandle['table']." WHERE "
		      .$thandle['lvalname'].">=".$node['l']." AND ".$thandle['rvalname']."<=".$node['r']);
		_shiftRLValues($thandle, $node['r']+1, $node['l'] - $node['r'] -1);
		if (!$res) {_prtError();}
		return nstGetNodeWhere ($thandle, 
		                 $thandle['lvalname']."<".$leftanchor
			   ." ORDER BY ".$thandle['lvalname']." DESC"
			 );
	}
	
	function destroy($ids = NULL){
		#die("hallo der Cursor ist leider unsichtbar   ". $this->nese['rgt']);
		$r = $this->nese['rgt'];
		$l = $this->nese['lft'];
		if($this->$l+1!=$this->$r) return false;
		if(parent::destroy())$this->_shift_rl_values($this->$r+1, $this->$l - $this->$r - 1);
	}
	
	
	/* ******************************************************************* */
	/* Tree Queries */
	/*
	 * the following functions return a valid node (L and R-value), 
	 * or L=0,R=0 if the result doesn't exist.
	 */
	/* ******************************************************************* */
	
	function get_nsid(){
	  return $this->get($this->nese['nsid']);
	}
	
	/* returns the first node that matches the '$whereclause'. 
	   The WHERE-caluse can optionally contain ORDER BY or LIMIT clauses too. 
	 */
	function get_node_where($l){
   #########################################################
	  $s = $this->find_first(array('conditions'=>array($this->nese['lft']=>$l, $this->nese['nsid']=>$this->nsid)));
	  if ($s){
	     return $s->node_nsdata();
		  /*$noderes['l'] = $s->get($this->nese['lft']);
		  $noderes['r'] = $s->get($this->nese['rgt']);
   	  return $noderes;*/
	  }
	  return false;
	}
	
   function get_node_where_right($r){
      $s = $this->find_first(array('conditions'=>array($this->nese['rgt']=>$r, $this->nese['nsid']=>$this->get($this->nese['nsid']))));
      if($s){
         return $s->node_nsdata();
         /*$noderes['l'] = $s->get($this->nese['lft']);
         $noderes['r'] = $s->get($this->nese['rgt']);
         return $noderes;*/
      }
      return false;
   }
	
	function get_node_where_both($r, $l, $object=false){
      ###############################
      $s = $this->find_first(array('conditions'=>array(
         $this->nese['lft']=>array($l, "<"),
         $this->nese['rgt']=>array($r, ">"),
         $this->nese['nsid']=>$this->get($this->nese['nsid'])),
         'order'=>$this->nese['rgt']));
      if ($s){
			if($object) return $s;
         return $s->node_nsdata();
		}
      return false;
	}
	
	function nstGetNodeWhereLeft ($thandle, $leftval)
	/* returns the node that matches the left value 'leftval'. 
	 */
	{ return nstGetNodeWhere($thandle, $thandle['lvalname']."=".$leftval);
	}
	function nstGetNodeWhereRight ($thandle, $rightval)
	/* returns the node that matches the right value 'rightval'. 
	 */
	{ return nstGetNodeWhere($thandle, $thandle['rvalname']."=".$rightval);
	}
	
	function nstRoot ($thandle)
	/* returns the first node that matches the '$whereclause' */
	{ return nstGetNodeWhere ($thandle, $thandle['lvalname']."=1");
	}
	
	function nstFirstChild ($thandle, $node)
	{ return nstGetNodeWhere ($thandle, $thandle['lvalname']."=".($node['l']+1));
	}
	function nstLastChild ($thandle, $node)
	{ return nstGetNodeWhere ($thandle, $thandle['rvalname']."=".($node['r']-1));
	}
	function prev_sibling ($node){
	  ###################################
		return $this->get_node_where_right($node['l']-1);
	}
	function next_sibling ($node){
	  ########################################
		return $this->get_node_where($node['r']+1);
	}
	function ancestor ($node, $object=false){
	   ##################################
		return $this->get_node_where_both($node["r"], $node["l"], $object);
	}
	
	
	/* ******************************************************************* */
	/* Tree Functions */
	/*
	 * the following functions return a boolean value
	 */
	/* ******************************************************************* */

	/* only checks, if L-value < R-value (does no db-query)*/	
	function valid_node ($node){
	   #############################
	   return ($node['l'] < $node['r']);
	}
	function nstHasAncestor ($thandle, $node)
	{ return nstValidNode($thandle, nstAncestor($thandle, $node));
	}
	function nstHasPrevSibling ($thandle, $node)
	{ return nstValidNode($thandle, nstPrevSibling($thandle, $node));
	}
	function nstHasNextSibling ($thandle, $node)
	{ return nstValidNode($thandle, nstNextSibling($thandle, $node));
	}
	function nstHasChildren ($thandle, $node)
	{ return (($node['r']-$node['l'])>1);
	}
	function nstIsRoot ($thandle, $node)
	{ return ($node['l']==1);
	}
	function nstIsLeaf ($thandle, $node)
	{ return (($node['r']-$node['l'])==1);
	}
	function nstIsChild ($node1, $node2)
	/* returns true, if 'node1' is a direct child or in the subtree of 'node2' */
	{ return (($node1['l']>$node2['l']) and ($node1['r']<$node2['r']));
	}
	function nstIsChildOrEqual ($node1, $node2)
	{ return (($node1['l']>=$node2['l']) and ($node1['r']<=$node2['r']));
	}
	function nst_equal ($node1, $node2)
	{ return (($node1['l']==$node2['l']) and ($node1['r']==$node2['r']));
	}
	
	
	/* ******************************************************************* */
	/* Tree Functions */
	/*
	 * the following functions return an integer value
	 */
	/* ******************************************************************* */
	
	function nstNbChildren ($thandle, $node)
	{ return (($node['r']-$node['l']-1)/2);
	}
	
	function nstLevel ($thandle, $node)
	/* returns node level. (root level = 0)*/
	{ 
	  $res = mysql_query("SELECT COUNT(*) AS level FROM ".$thandle['table']." WHERE "
	                   .$thandle['lvalname']."<".($node['l'])
			   ." AND ".$thandle['rvalname'].">".($node['r'])
			 );
			   
	  if ($row = mysql_fetch_array ($res)) {
	    return $row["level"];
	  }else{
	    return 0;
	  }
	}
	
	/* ******************************************************************* */
	/* Tree Walks  */
	/* ******************************************************************* */
	
	function walk_preorder($node){
	  log_error("walk_preorder");
	/* initializes preorder walk and returns a walk handle */
		$con =& $this->connection();	
		$rs = $this->find_all(array('conditions' => 
		 array(
		    $this->nese['lft']=>array($node['l'], ">="),
		    $this->nese['rgt']=>array($node['r'], "<="),
		    $this->nese['nsid']=>$this->nsid
		    ), 'order'=> $this->nese['lft']
		 ));/*
		$rs = $con->Execute("SELECT * FROM ".$this->table()
	         ." WHERE ".$this->nese['lft'].">=".$node['l']
	         ."   AND ".$this->nese['rgt']."<=".$node['r']
	         ." AND ".$this->nese['nsid']." = '" .$this->prop[$this->nsid]."'"
	         ." ORDER BY ".$this->nese['lft']);*/
		//$li = new $this->iterator($rs, get_class($this));
	  return array('recset'=>$rs,
	               'prevl'=>$node['l'], 'prevr'=>$node['r'], // needed to efficiently calculate the level
	               'level'=>-2 );
	}
	
	function walk_next(&$walkhand){
	   log_error("walk_next");
	  if ($row = $walkhand['recset']->next_hash()){
	    // calc level
		$walkhand['level']+= $walkhand['prevl'] - $row[$this->nese['lft']] +2;
		// store current node
	    $walkhand['prevl'] = $row[$this->nese['lft']];
	    $walkhand['prevr'] = $row[$this->nese['rgt']];
	    $walkhand['row']   = $row;
	    return array('l'=>$row[$this->nese['lft']], 'r'=>$row[$this->nese['rgt']]);
	  } else{
	    return FALSE;
	  }
	}
	
	function nstWalkAttribute($thandle, $walkhand, $attribute)
	{
	  return $walkhand['row'][$attribute];
	}
	
	function nstWalkCurrent($thandle, $walkhand)
	{
	  return array('l'=>$walkhand['prevl'], 'r'=>$walkhand['prevr']);
	}
	function walk_level($walkhand)
	{
	  return $walkhand['level'];
	}
	
	
	
	/* ******************************************************************* */
	/* Printing Tools */
	/* ******************************************************************* */
	/* returns printview the $attributed attributes of the specified node */
	
	function print_attributes($singleset, $sep=",", $attributes=array()){
	   $attr = $singleset->get($attributes);
      return join($sep, $attr);
	}
	function print_nested_set($set, $sep=","){
	   $printtree = "";
	   foreach($set as $v){
	      for($i=1; $i < $v->neselevel; $i++)
	        $printtree .= "\t";
	      $printtree .= join($sep, $v->get())."\n";
	   }
	   return $printtree;
	}
	
	function breadcrumb(){
		$ret = array($this);
		$anc = $this->ancestor($this->node_nsdata(), true);
		while($anc->id!=$anc->rootid){
			$ret[] = $anc;
			$anc = $anc->ancestor($anc->node_nsdata(), true);
		}
		return $ret;	
	}
	
	function nstBreadcrumbsString ($thandle, $node)
	/* returns a string representing the breadcrumbs from $node to $root  
	   Example: "root > a-node > another-node > current-node"
	
	   Contributed by Nick Luethi
	 */
	{
	  // current node
	  $ret = nstNodeAttribute ($thandle, $node, "name");
	  // treat ancestor nodes
	  while(nstAncestor ($thandle, $node) != array("l"=>0,"r"=>0)){
	    $ret = "".nstNodeAttribute($thandle, nstAncestor($thandle, $node), "name")." &gt; ".$ret;
	    $node = nstAncestor ($thandle, $node);
	  }
	  return $ret;
	  //return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;breadcrumb: <font size='1'>".$ret."</font>";
	} 
	
	/* ******************************************************************* */
	/* internal functions */
	/* ******************************************************************* */
	
	function _prtError(){
	  //echo "<p>Error: ".mysql_errno().": ".mysql_error()."</p>";
	}
}
?>