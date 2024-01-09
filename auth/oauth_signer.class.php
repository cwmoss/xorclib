<?
require_once("xorc/auth/oauth2/OAuth.php");

class OAuth_Signer{
   private static $server=false;
   
   static function sign($key, $secret=null, $url, $parms=array(), $meth="GET", $consumerfile=null){
      if(is_null($secret) && !self::$server){
         require_once("simplesign_server.php");
         self::$server=new Simplesign_OAuthServer(new Simplesign_OAuthDataStore($consumerfile));
      }
      if(is_null($secret)){
         $cons=self::$server->lookup_consumer($key);
         $secret=$cons->secret;
      }
      $consum = new OAuthConsumer($key, $secret, NULL);
      $sig_method = new OAuthSignatureMethod_HMAC_SHA1();
      $req = OAuthRequest::from_consumer_and_token($consum, NULL, $meth, $url, $parms);
      $req->sign_request($sig_method, $consum, NULL);
      return $req;
   }
   
   static function check($consumerfile){
      if(!self::$server){
         require_once("simplesign_server.php");
         self::$server=new Simplesign_OAuthServer(new Simplesign_OAuthDataStore($consumerfile));
      }
      
      try {
        $req = OAuthRequest::from_request();
        log_error($req);
        $token = self::$server->fetch_request_token($req);
        log_error("TOKEN: $token");
        return $req->get_parameters();
      } catch (OAuthException $e) {
        return $e->getMessage();
      }
   }
}


if(!function_exists('hash_hmac')){
   function hash_hmac($algo, $data, $key, $raw_output = false)
   {
       $algo = strtolower($algo);
       $pack = 'H'.strlen($algo('test'));
       $size = 64;
       $opad = str_repeat(chr(0x5C), $size);
       $ipad = str_repeat(chr(0x36), $size);

       if (strlen($key) > $size) {
           $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
       } else {
           $key = str_pad($key, $size, chr(0x00));
       }

       for ($i = 0; $i < strlen($key) - 1; $i++) {
           $opad[$i] = $opad[$i] ^ $key[$i];
           $ipad[$i] = $ipad[$i] ^ $key[$i];
       }

       $output = $algo($opad.pack($pack, $algo($ipad.$data)));

       return ($raw_output) ? pack($pack, $output) : $output;
   }
}


?>