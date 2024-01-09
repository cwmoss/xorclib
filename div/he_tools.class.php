<?php

class HE_tools{

   static $adapterclass;
   static $conf;
   
   // store the recent condition, for pager
   var $cond;
   
   function xx_from_doc($doc){
		//	**** obsolete use: static call
      // implement in childclass
      return $doc;
   }
   
   function to_doc($obj){
      // implement in childclass
   }

   // override in childclass
   function to_doc_uri($obj){
      $type=$obj->klas;
      return $type."/".$obj->id;
   }

   function search($searchterm, $order=null, $limit=20, $page=1, $offset=null){
      $node=$this->init_node();
      $cond = new EstraierPure_Condition;
		$cond->set_options(EstraierPure_Condition::SIMPLE);
      $this->set_condition($cond, $searchterm);
		$this->set_order($cond, $order);
      $cond->set_max($limit);

      $skip=($page-1) * $limit;
      # if someone needs a direct offset
      if(!is_null($offset)) $skip=$offset;
      
      $cond->set_skip($skip);
      $this->cond=$cond;
      #var_dump($cond);
      log_error("### ESTSEARCH");
		log_error($cond);
      $result = $node->search($cond, 0);
      return $result;
   }
   
   function search_raw($term, $order=null, $limit=20, $page=1, $offset=null){
      $node=$this->init_node();
      $cond = new EstraierPure_Condition;
		$cond->set_options(EstraierPure_Condition::USUAL);
		$cond->set_phrase($term);
     
		$this->set_order($cond, $order);
		
      $cond->set_max($limit);

      $skip=($page-1) * $limit;
      # if someone needs a direct offset
      if(!is_null($offset)) $skip=$offset;
      
      $cond->set_skip($skip);
      $this->cond=$cond;
      #var_dump($cond);
      log_error("### ESTSEARCH");
		log_error($cond);
      $result = $node->search($cond, 0);
      
      return $result;
   }
   
   function pager($result){
      $total=$result->hint("HIT");
# log_error($result->hint);
   	$offset=$this->cond->skip();
   	$limit=$this->cond->max();

   	$p=array();
		$page=floor($offset/$limit)+1;
		$p['this']=$page;
		$p['maxpp']=$limit;
		$p['total']=$total;
      $p['offset']=$offset;
		$p['totalpages']=ceil($p['total']/$limit);
		if(!$p['totalpages']) $p['totalpages']=1;
		// $p['real']=($page*$maxpp<$p['total'])?$maxpp:($p['total']-($page-1)*$maxpp);
      $p['real']=$result->doc_num();
		$p['less']=($page==1)?false:true;
		$p['prev']=($page==1)?$page:$page-1;
		$p['more']=($page==$p['totalpages'])?false:true;
		$p['next']=($page==$p['totalpages'])?$page:$page+1;
		$p['first']=$p['total']?($page-1)*$limit+1:0;
		$p['last']=$p['total']?$p['first']+$p['real']-1:0;
		return $p;
   }
   
   function set_condition($cond, $term){
      // default simple ANDED cond with respect of
      //   attr queries like author:smith
		
		log_error("****HE TOOLS QUERY****");
		
		if(is_array($term)){
			$words=array_shift($term);
			$words=preg_split("/\s+/", $words);
			foreach($term as $key=>$value){
				if(is_numeric($key)) continue;
				$cond->add_attr($key." ISTRINC ".$value);
			}
			$sterm="";
			foreach($words as $t) if($t) $sterm.=$t."* ";
	      $cond->set_phrase($sterm);
		}else{
			$term=preg_split("/\s+/", $term);

	      $attr=explode(":", $term[0], 2);
	      $attribute_given = (sizeof($attr) > 1) && trim($attr[0]) && trim($attr[1]);
	      if($attribute_given){
	         $eattr=trim($attr[0]);
	         $eattr="@".$eattr;
	         $cond->add_attr($eattr." ISTRINC ".$attr[1]);
	         array_shift($term);
	      }
			// $term=join(" AND ", $term);
			$sterm="";
			foreach($term as $t) $sterm.=$t."* ";
	      $cond->set_phrase($sterm);
		}
   }
   
   function set_order($cond, $order=""){
      if(is_null($order)) return;   # use he preferences (usually relevance, wich is *not* @weight)
      if(!$order) $order="@mdate NUMD";
      $cond->set_order($order);
   }
   
   function post($obj){
      $node=$this->init_node();
      $d = &$this->to_doc($obj);
      $ok=$node->put_doc($d);
      return $ok;
      
      /* 
         edit_doc updates only attributes,
         so it's no solution to the time-lag problem
      */
      $ID=$node->uri_to_id($d->attrs['@uri']);
      log_error($ID);
      log_error($d);
      $ok=false;
      
      if($ID && $ID > 0){
         log_error("### HE-POST AS EDIT $ID ###");
         $d->add_attr("@id", $ID);
   #      $ok=$node->out_doc($ID);
   #      log_error($ok);
         
         $ok=$node->put_doc($d);
         log_error($ok);
         
         $ok=$node->sync();
         log_error($ok);
         
         #$ok=$node->edit_doc($d);
         #log_error($ok);
         
         #$ok=$node->get_doc($ID);
         #log_error($ok);
      }
      
      if(!$ok){
         log_error($d);
         $ok=$node->put_doc($d);
         log_error($ok);
      }
   }
   
   function remove($obj){
      $node=$this->init_node();
      $uri = $this->to_doc_uri($obj);
      $ok=$node->out_doc_by_uri($uri);
      #log_error($ok);
      #log_error($node);
   }
   
   /**
    * @return EstraierPure_Node
    */
   function init_node($conf=null){
      static $node;
      if(!$node){
         if(!$conf) $conf=HE_tools::$conf;
         if(!$conf) $conf=Xorcapp::$inst->conf["est"];
         
         # php5 variant of estpure
         require_once 'xorc/div/EstraierPure-0.5.0/estraierpure.php5';

         // create and configure the node connecton object
         $node = new EstraierPure_Node;
         //$node->set_url('http://athlon64.fsij.org:1978/node/test');
         $node->set_url($conf['node']);
         $node->set_auth($conf['user'], $conf['passwd']);
      }
      return $node;
   }
   
   function clear_node(){
      $node=$this->init_node();
      $name=basename($node->url);
      #print "NAME:$name $node->url";
      $url=str_replace("node/".$name, "master", $node->url);
      #print " URL $url";
      list($user, $passwd)=explode(":", $node->auth);
      HE_Tools::_post($url,
         array("action"=>"nodeclr", "name"=>$name),
         array($user, $passwd));
   }
   
   function he_date($mydate){
      return str_replace("-", "/", $mydate);
   }
   
   function my_date($hedate){
      return str_replace("/", "-", $hedate);
   }
   
   function snippet_html($snippet){
		$html="";
		foreach(split("\n", trim(strip_tags($snippet))) as $l){
			if(!$l){
				$html.=" ... ";
				continue;
			}
			$lL=split("\t", $l);
			if(isset($lL[1])){
				$html.=sprintf('<strong class="kw">%s</strong>', $lL[0]);
			}else{
				$html.=htmlspecialchars($lL[0]);
			}
		}
		return $html." ...";
	}
	
   function _post($url, $data=array(), $auth=array()){
      require_once "HTTP/Request.php";
     # log_error("##### POST");
   #   log_error($url);
      # log_error($data);
      $req =& new HTTP_Request($url);
      $req->setMethod(HTTP_REQUEST_METHOD_POST);
      
      if(count($auth)){
         $req->setBasicAuth($auth[0], $auth[1]);
      }
      foreach($data as $k=>$v){
         $req->addPostData($k, $v);
      }
      
      if (!PEAR::isError($req->sendRequest())) {
           $response = $req->getResponseBody();
      } else {
           $response = "";
      }
		
	#	log_error($req);
      return $response;
   }
}



?>
