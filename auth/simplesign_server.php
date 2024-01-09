<?php //vim: foldmethod=marker
//require_once("OAuth.php");

class SimpleSign_OAuthServer extends OAuthServer {
   function __construct($p){
      parent::__construct($p);
      $this->timestamp_threshold = 20; # 20seconds
      $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
      $this->add_signature_method($sha1_method);
   }
   
   function lookup_consumer($key){
      return $this->data_store->lookup_consumer($key);
   }
}

/**
 * A mock store for testing
 */
class SimpleSign_OAuthDataStore extends OAuthDataStore {/*{{{*/

    private $db;
    
    function __construct($cfile=null) {/*{{{*/
       if(!$cfile) $this->db=dirname(__FILE__)."/consumers";
       else $this->db=$cfile;
       log_error($cfile);
    }/*}}}*/

    function lookup_consumer($consumer_key) {/*{{{*/
        log_error("LOOK-up: $consumer_key");
       # kein consumerfile, sondern ein array mit key=>val zeilen
       if(is_array($this->db)){
          if(isset($this->db[$consumer_key])){
             return new OAuthConsumer($consumer_key, $this->db[$consumer_key], NULL);
          }
       }else{
          foreach(file($this->db) as $con){
             if(strpos($con, $consumer_key)===0){
                list($key, $secret)=preg_split("/\s+/", trim($con));
                return new OAuthConsumer($key, $secret, NULL);
             }
          }
      }
        return NULL;
    }/*}}}*/

    function lookup_token($consumer, $token_type, $token) {/*{{{*/
        return NULL;
    }/*}}}*/

    function lookup_nonce($consumer, $token, $nonce, $timestamp) {/*{{{*/
        return NULL;
    }/*}}}*/

    function new_request_token($consumer, $callback = null) {/*{{{*/
        return $consumer->key;
    }/*}}}*/

    function new_access_token($token, $consumer, $verifier = null) {/*{{{*/
        return $consumer->key;
    }/*}}}*/
}/*}}}*/
?>
