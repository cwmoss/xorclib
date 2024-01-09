<?php

class Xorc_Resource{
   
   var $_uri, $_auth;
   private $klas;
   private $was_loaded=false;
   private $prop;
   private $enums=array();
   
   var $assoc_single=array();
   var $assoc_many=array();
   
   static $setup=array();
   
   function find($id){
      $data=$this->_parse($this->_get("show/".$id));
      #print_r($data);
      
      $this->was_loaded=true;
      return $this->_rename($data);
   }
   
   function find_all($cond=array()){
      $data=$this->_parse($this->_get("index/", $cond));
      #print_r($data);
      
      $this->was_loaded=true;
      return $this->_rename($data);
   }
   
   function save(){
      if($this->id){
         $xml=$this->_build();
         print $xml;
         
         $resp=$this->_put($xml);
      }else{
         $resp=$this->_post($this->_build());
         $newobj=$this->_rename($this->_parse($resp));
         $this->prop=$newobj->prop;
      }
 #     var_dump($resp);
      
   }
   
   function delete(){
      $this->_delete("delete/".$this->id);
   }
   
   function set($arr){
      foreach($arr as $k=>$v){
         $this->$k=$v;
      }
   }
   
   function _parse($resp){
 #     print_r($resp); die();
      #if($resp)
      return $this->_from_xml($resp);
   }
   
   function _build(){
      return $this->_to_xml();
   }
   
   function setup($uri, $auth){
      $this->_uri=$uri;
      $this->_auth=$auth;
      $this->klas=str_replace("_res", "", strtolower(get_class($this)));
   }
   
   function __construct(){
      foreach($this as $k=>$v){
         if(is_array($v)){
    #        print "EN $k \n";
            $this->enums[]=$k;
         }
      }
   }
   function __get($k){
      return $this->prop[$k];
   }
   
   function __set($k, $v){
      $this->prop[$k]=$v;
   }
   
#   function __isset($k){return false;}
   
   function _rename($data){
      $root=$data['__root__'];
      unset($data['__root__']);
      
      # build array of objects
      $ret=array();
      if($root=="items"){
         print "building array\n";
         foreach($data as $k=>$v){
            $klas=$k."_res";
            foreach($v as $item){
               print "building obj $klas\n";
               $obj=new $klas;
               #$obj->set($this->_rename($item));
               #$ret[]=$obj;
               $ret[]=$obj->_rename($item);
            }
         }
         return $ret;
      }
      
      # build single object
      $klas=get_class($this);
      $ret=new $klas;
      foreach($data as $k=>$v){
         if(is_array($v) && isset($ret->{$k}) && is_array($ret->$k)){
            $assoc=array_keys($v);
            foreach($v[$assoc[0]] as $o){
               $klas=$assoc[0]."_res";
               print "building MANY obj $klas\n";
               $obj=new $klas;
               $ret->{$k}[]=$obj->_rename($o);
            }
         }elseif(is_array($v) && $ret->assoc_single[$k]){
            
      #      print "=============SINGLE\n";
            $klas=$k."_res";
            $obj=new $klas;
            print "building SINGLE obj $klas\n";
            $ret->{$k}=$obj->_rename($v[$k]);
         }else{
            $renamed_key=str_replace("-", "_", $k);
            $ret->{$renamed_key}=$v;
         }
      }
      return $ret;
   }
   
   function _to_xml(){
      $xml.='<?xml version="1.0" encoding="UTF-8"?>';
      $root=str_replace("_", "-", $this->klas);
      $xml.='<'.$root.'>';
      foreach($this->prop as $k=>$v){
         $k=str_replace("_", "-", $k);
         $xml.='<'.$k.'>'.htmlspecialchars($v, ENT_NOQUOTES).'</'.$k.'>';
      }
      $xml.='</'.$root.'>';
      return $xml;
   }
   
   function _from_xml($resp){
      var_dump($resp);die();
      if(!$resp) throw new Exception("NO DATA");
      
      $options = array("complexType" => "array",
      #   'parseAttributes'=>true,
         'attributesArray'=>false,
      #   "forceEnum"=>array_merge(array("items"), array_values($this->assoc_many)),
         "forceEnum"=>array_values($this->assoc_many),
      #   "tagMap"=>array("picture"=>"picture_res", "article"=>"article_res", "site"=>"site_res")
         );

      // create object
      $unserializer = new XML_Unserializer($options);

      // unserialize the document
      $result = $unserializer->unserialize($resp);   
#print_r($result);
      // dump the result
      $data = $unserializer->getUnserializedData();
      print_r($data);
      $data['__root__']=$unserializer->getRootName();
      return $data;
   }
   
   function _put($data, $uri=null){
      log_error("##### PUT");
      if(!$uri) $uri=$this->_uri;
      $uri=$uri."/"."save";

      $req =& new HTTP_Request($uri);
      $req->setMethod(HTTP_REQUEST_METHOD_PUT);
      $req->addHeader("ACCEPT", "application/xml;q=1");

      if(count($this->_auth)){
         $req->setBasicAuth($this->_auth[0], $this->_auth[1]);
      }
      
      $req->setBody($data);

#      foreach($data as $k=>$v){
#         $req->addPostData($k, $v);
#      }

      if (!PEAR::isError($req->sendRequest())) {
           $response = $req->getResponseBody();
      }else{
         log_error($req);
           $response = "";
      }
      return $response;
   }
   
   function _post($data, $uri=null){
      log_error("##### POST");
      if(!$uri) $uri=$this->_uri;
      $uri=$uri."/"."save";
      
      $req =& new HTTP_Request($uri);
      $req->setMethod(HTTP_REQUEST_METHOD_POST);
      $req->addHeader("ACCEPT", "application/xml;q=1");
      
      if(count($this->_auth)){
         $req->setBasicAuth($this->_auth[0], $this->_auth[1]);
      }

     # $req->addPostData("body_xml", $data);
      $req->setBody($data);
#      foreach($data as $k=>$v){
#         $req->addPostData($k, $v);
#      }
      
      if (!PEAR::isError($req->sendRequest())) {
         $code = $req->getResponseCode();
         if( $code > 400 ) throw new Exception("AUTH FAILED ".$req->getResponseBody(), $code);
           $response = $req->getResponseBody();
      }else{
         log_error($req);
           $response = "";
      }
      return $response;
   }
   
   function _delete($data, $uri=null){
      if(!$uri) $uri=$this->_uri;
      $uri=$uri."/".$data;
      $req =& new HTTP_Request($uri);
      $req->setMethod(HTTP_REQUEST_METHOD_DELETE);
      
      if(count($this->_auth)){
         $req->setBasicAuth($this->_auth[0], $this->_auth[1]);
      }
      
#      foreach($data as $k=>$v){
#         $req->addPostData($k, $v);
#      }
      
      if (!PEAR::isError($req->sendRequest())) {
           $response = $req->getResponseBody();
      } else {
         log_error($req);
           $response = "";
      }
      return $response;
   }
   
   function _get($meth, $data, $uri=null){
      if(!$uri) $uri=$this->_uri;
      $uri=$uri."/".$meth;
      $req =& new HTTP_Request($uri);
      $req->setMethod(HTTP_REQUEST_METHOD_GET);
      
      $req->clearPostData();
      
      # $req->addHeader("X-PHP-Version", phpversion());
      if(count($this->_auth)){
         $req->setBasicAuth($this->_auth[0], $this->_auth[1]);
      }
      
      foreach($data as $k=>$v){
         $req->addQueryString($k, $v);
      }
     # print_r($req);die();  
      $err = $req->sendRequest();
      
      
      if (!PEAR::isError($err)) {
         $code = $req->getResponseCode();
         #print "OK#".$req->getResponseCode()."#";die();
         if( $code > 400 ) throw new Exception("AUTH FAILED ".$req->getResponseBody(), $code);
         $response = $req->getResponseBody();
      } else {
         throw new Exception($err->getMessage());
         log_error($req);
           $response = "";
      }
      return $response;
   }
}

?>