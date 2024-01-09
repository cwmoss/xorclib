<?php
include("XML/Unserializer.php");
require_once "HTTP/Request.php";
require_once "JSON/JSON.php";

class rest_search_client{
   var $proxy=null;

   function rest_search_client($url, $user, $pass, $json=false){
      $this->base=$url;
      $this->user=$user;
      $this->pass=$pass;
      $this->json=$json;
   }
   
   function search($q){
      $url=$this->base."/search?q=".
         urlencode($q);
      $resp = $this->request($url);
#      var_dump($resp);
      $resp = $this->condensed($resp, true);
#      var_dump($resp);
#      var_dump($this->to_json($resp));
#      var_dump($this->json);
      if($this->json) return($this->to_json($resp));
      return $resp;
   }
   
   function get($id){
      $url=$this->base."/item/".
         urlencode($id);
      $resp = $this->request($url);
      $resp = $this->condensed($resp);
      if($this->json) return($this->to_json($resp));
      return $resp;
   }
   
   function condensed($data, $force_array=false){
#      print_r($data);
#      var_dump($force_array);
      $res=array();
      if($data['entry']){
         return $data['entry'];
      }elseif($force_array){
         return $res;
      }else{
         return $data;
      }
   }
   
   function to_json($data){
      $json = new Services_JSON();
      return $json->encode($data);
   }
   
   function request($url){
      $req =& new HTTP_Request($url);
      $req->setBasicAuth($this->user, $this->pass);
		
		if($this->proxy){
			$req->setProxy($this->proxy['host'], $this->proxy['port']);
		}
		
      $response = $req->sendRequest();

      if(PEAR::isError($response)){
          echo $response->getMessage();
      } else {
          $data = $req->getResponseBody();
      }
      
      
      $options = array("complexType" => "array",
         'parseAttributes'=>true,
         'attributesArray'=>false,
         "forceEnum"=>array("entry"),
         'tagMap'=>array(
            "lisa:url"=>"lisa_url",
            "lisa:location"=>"lisa_location",
            "lisa:alturl"=>"lisa_alturl",
            "lisa:talentid"=>"lisa_alturl"
            )
         );

      // create object
      $unserializer = &new XML_Unserializer($options);

      // unserialize the document
      $result = $unserializer->unserialize($data);   
#print_r($result);
      // dump the result
      return $unserializer->getUnserializedData();
      
   }
}

?>