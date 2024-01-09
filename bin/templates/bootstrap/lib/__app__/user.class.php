<?php

class User extends xorcstore_ar{
   use validatable;
   
   public $reminder_email;
   public $reset_token;
   
   public $passwd_old;
   public $passwd_current;
   public $passwd_confirmation;
   
	function before_create(){
		# TODO: warum geht das nicht automatisch?
		if(!$this->created_at) $this->created_at = date('Y-m-d H:i:s');
	}
	
	function before_save(){
	   if(!$this->token) $this->make_token();
	   if(!$this->passwd) $this->passwd='';  
      if(!$this->status) $this->status=0;
    }

    function make_token(){
       $this->token=$this->generate_token();
       $this->token_expires_at=date('Y-m-d H:i:s', (time()+(24*3600)));
    }
    
    function xcheck_token($t){
      if($this->token == $t){
	      if($this->token_expires_at > date('Y-m-d H:i:s')){
	         return true;
	      }else{
	         return 'TOKEN ist bereits verfallen.';
	      }
	   }else{
	      return("Falsches TOKEN.");
	   }
    }
    
    function generate_token(){
        return md5(uniqid(rand()));
    }
    
    function after_login(){
        $this->update_attr('login_at', date("Y-m-d H:i:s"));
        $this->update_attr('login_from', $_SERVER['REMOTE_ADDR']);
        $this->increase("login_count");
    }
    

    function name_with_realname(){
        $name=$this->uname;
        if($this->fname || $this->lname){
            $name.=" aka ".$this->fname." ".$this->lname;
        }
        return $name;
    }
    
    function is_activated(){
        return ($this->status?true:false);
    }
    
	function is_blocked(){
		return ($this->status==9);
	}
    function confirmed(){
       $this->update_attr("status", "1");
		$this->update_attr("confirmed_at", date('Y-m-d H:i:s'));
    }
    
    // static call
    function generate_password(){
        require_once "Text/Password.php";
        return Text_Password::create(6);
    }
    
    function password_update($p){
       log_error("password_update /$p/");
       $len=16; $strong=null;
       $salt = substr(base64_encode(openssl_random_pseudo_bytes($len, $strong)), 0, 16);
       // sha256 
       $c = crypt($p, '$5$rounds=5000$'.$salt);
       $passwd=$salt.(str_replace('$5$rounds=5000$'.$salt.'$', '', $c));
       $this->update_attr("passwd", $passwd);
    }
   
    function password_match($p, $with=null){
       if(is_null($with)) $with=$this->passwd;
       $salt = substr($with, 0, 16);
       $pwd = substr($with, 16);
       $fix = '$5$rounds=5000$'.$salt.'$'.$pwd;
       return ($fix==crypt($p, $fix));
    }
    
    // via validator
    function check_password($e, $opts){
       log_error("check password ~{$this->passwd_old}~ vs {$this->passwd_current}");
       return $this->password_match($this->passwd_old, $this->passwd_current);
    }
    
    function check_email_exists($e, $opts){
       $cid = the_contest()->id;
       if($this->find_first(array('conditions'=>array('contest_id'=>$cid, 'email'=>$this->reminder_email)))) return true;
       else return false;
    }
    
    function check_token($e, $opts=array()){
       if($this->find_by_token($this->token)) return true;
       else return false;
    }
    
    // kann auch direkt verwendet werden
    function check_reset_token($e=null, $opts=array()){
       if($this->reset_token == $this->token) return true;
       else return false;
    }
    
    function get_authorline(){
		 return $this->uname;
       return trim($this->fname." ".$this->lname);
    }
    
	function get_realname(){
		return $this->fname." ".$this->lname;
	}
	function get_address(){
		$addr = array($this->street.' '.$this->number, 
			$this->postalcode.' '.$this->city
			);
		return join(', ', $addr);
	}
   function validatable_fields($event='save'){
      log_error("######### VALidate EVENT $event +++++++");
      if($event=='change_password'){
         $f='passwd_old passwd';
      }elseif($event=='remind_password'){
         $f='reminder_email';
      }elseif($event=='confirm'){
         $f='token';
      }elseif($event=='reset_password'){
         $f='passwd';
      }else{
         return $this->validatable_fields_default();
      }
      return explode(' ', $f);
   }

   function voting_stats($contest){
      return voting::stats_by_user($contest, $this);
   }
   
   function vote($contest, $image, $vote){
      return voting::vote($contest, $this, $image, $vote);
   } 
   
   function vote_revoke($contest, $image){
      return voting::vote_revoke($contest, $this, $image);
   }
   
	function vote_get($contest, $image){
		return voting::vote_get($contest, $this, $image);
	}
	
   function get_name(){
      return join(' ', array_filter([$this->fname, $this->lname]));
   }
   
	function get_is_admin(){
		return $this->role=='adm';
	}
	
	function get_is_jury(){
		return $this->role=='jur';
	}
	
	function search($q, $page=1, $limit=100){
		$qc = array("%$q%", 'like');
		$cond = array(
			$this->condition_to_sql(array('email'=>$qc)),
			$this->condition_to_sql(array('fname'=>$qc)),
			$this->condition_to_sql(array('lname'=>$qc))
		);
		$cond = "( ".join(' OR ', $cond).' ) ';
		return self::i()->find_all(array('conditions'=>$cond, 'page'=>$page, 'limit'=>$limit));
	}
   function has_many(){
      return array(
         "images"=>array("fkey"=>"user_id", "class"=>"image")
         );
   }
    
    public function define_schema(){
       return array('table'=>'users');
    }

    static function roles(){
       return ['usr'=>'User', 'adm'=>'Admin'];
    }
    
 	 static function i(){return new self;}
}

?>