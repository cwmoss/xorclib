<?php

include("XML/Serializer.php");

class Rest_search_server{
   var $opts;
   var $resp;
   var $obj;
   
   function rest_search_server(&$obj, $opts=array()){
      $this->obj =& $obj;
      $defopts=array("base"=>$_SERVER['SCRIPT_NAME'], "auth"=>true);
      $this->opts=array_merge($defopts, $opts);

      $accepts = explode(',', $_SERVER['HTTP_ACCEPT']);
      if(in_array("text/json", $accepts) || $_GET['alt']=='json'){
         $this->resp="json";
      }else{
         $this->resp="atom";
      }
      if($this->opts['src_enc']=="iso") include_once("xorc/div/util.php");
   }
   
   function search($q){
      $atom=array();
      $res=$this->obj->rest_search($q);
      foreach($res as $entry){
         $props=$entry->rest_properties();
         $atom[]=$this->_properties_convert($props, $this->resp);
      }
      #print_r($atom);
      $this->respond($atom);
   }
   
   function get_entry($id){
      $res=$this->obj->rest_entry($id);
#      print_r($res->rest_properties());
      if($res==false) $this->e404();
      else{
         $atom=$this->_properties_convert($res->rest_properties(), $this->resp);
         $this->respond($atom, false);
      }
   }
   
   function respond($data, $feed=true){
      if($this->resp=="atom"){
         header('Content-type: text/xml');
         #header('Content-type: application/atom+xml');
         if($feed) print $this->serialize_feed_atom($data);   
         else print $this->serialize_entry_atom($data);   
      }else{
         header('Content-type: text/javascript');
         print $this->to_json($data);
      }
   }
   
   function to_json($data){
      if(function_exists("json_encode")){
         return json_encode($data);
      }else{
         require_once "JSON/JSON.php";
         $json = new Services_JSON();
         return $json->encode($data);
      }
   }
   
   function serialize_entry_atom($entry){
      return $this->serialize($entry, "entry");
   }
   
   function serialize_feed_atom($feed){
      $atom = array(
          "title" => "Links search results",
          #"link"  => "http://www.php-tools.de",
          #"image" => array(
          #  "title" => "Example image",
          #  "url"   => "http://www.php-tools.de/image.gif",
          #  "link"  => "http://www.php-tools.de"
          #  ),
          #"_attributes" => array( "rdf:about" => "http://example.com/foobar.html" )
          );

      $atom=array_merge($atom, $feed);
      return $this->serialize($atom, "feed");
   }
   
   function serialize($data, $root){
      $rootattrs=array("xmlns"=>"http://www.w3.org/2005/Atom");
      if($this->opts['prefix_xmlns']){
         $rootattrs["xmlns:".$this->opts['prefix']]=$this->opts['prefix_xmlns'];
      }

      $options = array(
        "indent"          => "    ",
        "linebreak"       => "\n",
        "typeHints"       => false,
        "addDecl"         => true,
        "encoding"        => "UTF-8",
        "rootName"        => $root,
        "rootAttributes"  => $rootattrs,
        "defaultTagName"  => "entry",
        "attributesArray" => "_attributes"
        );

      $serializer = new XML_Serializer($options);
      $result = $serializer->serialize($data);

      if($result === true) return $serializer->getSerializedData();
      else return false;
   }

   function _properties_convert($props, $format){
      $atom=array();
     
      $prefix=$this->opts['prefix'];
      $defs=array("id", "title", "content", "author", "updated", "summary", "published",
         "category", "contributor", "rights");
      $domain=array_diff(array_keys($props), $defs);
      
      if($format=="json")
         $atom['link']=$this->opts['base']."/item/".$props["id"];
      else
         $atom["link"]=array("_attributes"=>array("rel"=>"view", 
            "href"=>$this->opts['base']."/item/".$props["id"]));
         
      foreach($defs as $def){
         if(isset($props[$def])){
            if($this->opts['src_enc']=="iso")
               $atom[$def]=isowintoutf8($props[$def]);
            else
               $atom[$def]=$props[$def];
         }
      }
      
      foreach($domain as $dom){
         if($this->opts['src_enc']=="iso"){
            $prop=isowintoutf8($props[$dom]);
         }else{
            $prop=$props[$dom];
         }
         if($format=="json")
            $atom["$prefix_$dom"]=$prop;
         else
            $atom["$prefix:$dom"]=$prop;
      }
      return $atom;
   }
      
   function _properties_to_atom($props){
      $atom=array();
      $prefix=$this->opts['prefix'];
      $defs=array("id", "title", "content", "author", "updated", "summary", "published",
         "category", "contributor", "rights");
      $domain=array_diff(array_keys($props), $defs);
      $atom["link"]=array("_attributes"=>array("rel"=>"view", 
         "href"=>$this->opts['base']."/item/".$props["id"]));
         
      foreach($defs as $def){
         if(isset($props[$def]))
            $atom[$def]=$props[$def];
      }
      
      foreach($domain as $dom){
         $atom["$prefix:$dom"]=$props[$dom];
      }
      
      return $atom;
   }
   
   function check_password($u, $p){
      return ($u=="robert");
   }
   
   function authorize(){
      if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])){
         if(!$this->check_password($_SERVER['PHP_AUTH_USER'],
            $_SERVER['PHP_AUTH_PW'])){
               $this->e401();
         }
      }else{
         $this->e401();
      }
      return true;
   }
   
   function dispatch(){
      if($this->opts['auth']) $this->authorize();
      $info=$_SERVER['PATH_INFO'];
      if(!$info) $info=$_SERVER['ORIG_PATH_INFO'];      # lighty rewrites
      if(!$info && function_exists("url")){
         # xorc controller variante
         $info=str_replace(url(), "", $_SERVER['REDIRECT_URL']);
      }
      # print_r($_SERVER);
      $r=split("/", $info);
      array_shift($r);
      
      if($r[0]=="search"){
         #print("suche nach $r[1]");
         if($this->opts['src_enc']=='iso') $q=utf8toisowin($_GET['q']);
         else $q=$_GET['q'];
         return $this->search($q);
      }elseif($r[0]=="item"){
         #print("hole item $r[1]");
         return $this->get_entry($r[1]);
      }else{
         $this->e400();
      }
   }
   
   function e404($msg="NOT FOUND"){
      header("HTTP/1.0 404 $msg");
      print("404 $msg");
      exit;
   }
   
   function e401($msg='SEARCH'){
      header('HTTP/1.0 401 Unauthorized');
      if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])){
        header('WWW-Authenticate: Basic realm="'.$msg.'"');
      }else{
         unset($_SERVER['PHP_AUTH_USER']);
         unset($_SERVER['PHP_AUTH_PW']);
      }
       
      #print_r($_SERVER);
      #print("401 Unauthorized for $msg");
      exit;
   }
   
   function e400($msg="BAD REQUEST"){
      header("HTTP/1.0 400 $msg");
      print("400 $msg");
      #print_r($_SERVER);
      exit;
   }
}


?>