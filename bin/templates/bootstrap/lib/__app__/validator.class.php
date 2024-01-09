<?php
/**
 * validator
 *
 * die klasse nimmt valierungsregeln entgegen und prüft gegen ein Nostore/ AR Objekt
 *
 * regeln werden in einer yaml datei deklariert und dann in php-serialisiert
 * $ bin/credit yaml-to-db conf/ols-validations.yaml
 *
 * beispiel für regeln in yaml notation:
 *    hausnr:
 *      mand: Hausnummer darf nicht leer sein
 *      format:
 *        regex: ^[-A-Za-z\d\säöüÄÖÜß]{1,5}$
 *    fa_plz:
 *      condition: :check_voradresse
 *      mand: PLZ der Voradresse darf nicht leer sein
 *      plz:
 *        msg: Die Postleitzahl der Voradresse ist ungültig
 *   abloesekonto1:
 *      condition: $abloese1_y == 1
 *      konto:
 *      mand: Bitte geben Sie die Kontonummer Ihres abzulösenden Kredits an.
 *
 * - jedes element kann mehrere regeln bekommen
 * - jede regel kann eine fehlermeldung (msg) haben. falls keine weiteren parameter
 *   notwendig sind kann die msg direkt als wert der regel notiert werden
 * - falls keine msg deklariert ist, werden die AR default messages bzw. default messages,
 *   die in den jeweiligen regelfunktionen definiert sind angewendet
 * - jede regel kann eine bedingung haben. nur wenn die bedingung zu `true` evaluiert,
 *   wird die regel angewendet
 * - ein element kann eine globale bedingung erhalten, dann gilt diese bedingung für
 *   alle regeln des elements
 * - bedingungen, die mit `:` beginnen, werden als methodenaufruf an das zu prüfende
 *   objekt delegiert
 * - alle anderen bedingungen werden via evaluator klasse evaluiert
 * - die regeln werden mit den in dieser klasse implementierten regelfunktionen geprüft
 *   dabei gilt: funktion = "v_{regelname}", wenn möglich, werden die regeln zu
 *   standard-AR_validations delegiert
 *
 * beispiel einbindung:
 *    // validator wird mit dem AR Objekt und den zu prüfenden regeln geladen
 *    // default für regeln: ols-validations.db
 *    $validator = Validator::init($antrag, 'api-validations.db');
 *    // die zu prüfenden attribute werden als liste übergeben
 *    $elements = array('req_amount', 'req_duration', 'reg_rate_amount');
 *    $validator->validate($elements);
 *    // fehler werden wie üblich im errors objekt festgehalten
 *    count($antrag->errors);
 *
 * @package ols
 * @author Robert Wagner
 * @see conf/ols-validations.yaml
 **/

define("CHAR_DIA_OK", "ŠšŽžŒœŸÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ");

class Validator{
   public static $v=null;
   public $o;

   public $ar_v;

   // default messages
   public static $msg = array(
      'min' => '{name} ist zu klein (min. {val})',
      'max' => '{name} ist zu groß (max. {val})'
   );
   
   public $trans_js = array("mand"=>"required", 'accept'=>'required', 'minval'=>'min', 
      'maxval'=>'max', 'min'=>'minlength', 'max'=>'maxlength');
      
   #public static $msg = array();
   
   static function init($o=null, $file=null){
      $v = new Validator;
      self::load($file);

      if($o) $v->o=$o;
      $v->ar_v = new Xorcstore_Validation($o);

      return $v;
   }

   static function load($file=null){
      if(self::$v) return;
      self::reload($file);
   }
   
   static function reload($file=null){
      if(!$file) $file = xorc_ini('validation.file');
      if(!$file) $file = 'validations.db';
      if($file[0]!='/') $file=Xorcapp::$inst->approot."/conf/".$file;
      log_error("### VALIDATOR now using $file ###");
      self::$v = unserialize(file_get_contents($file));
   }

   static function update_messages($msg){
      self::$msg = array_merge(self::$msg, $msg);
   }
   
   function validate($els, $ev=null){
      #log_error($els);
      if(is_string($els)){
         $els = explode(" ", $els);
      }
      if(!$ev) $ev='save';
      log_error("*** validate elements start [$ev] ***");
      log_error($els);
      foreach($els as $e){
         $this->validate_element($e, $ev);
      }
      log_error("*** validate elements finished ***");
   }

   function report_rules($els){
      $tab=array();
      $tab[]=explode(" ", "name COND MAND m-msg format f-msg max max-msg LIST sonstige");
      foreach($els as $e){
         $checks = self::$v[$e];
         $row=array(
            'name'=>$e, 'condition'=>($checks['condition']?"IF ".$checks['condition']:"-"),
            'mand'=>'', 'm-msg'=>'', 'format'=>'', 'f-msg'=>'',
            'max'=>'', 'max-msg'=>'', 'liste'=>'',
            'other'=>''
            );
         if($checks['condition'][0]==':'){
            $row['condition'] .= "\n".Documentor::strip_comment($this->o, trim($checks['condition'], ":"));
         }elseif($checks['condition']){
            unset($checks['condition']);
         }
         $other=array();

         if($checks) foreach($checks as $check=>$opts){
            if(!is_array($opts)){
               // als verkürzung kann die fehlermessage direkt an der
               //     validierungsregel mitgegeben werden
               if($opts) $opts=array("msg"=>$opts);
               else $opts=array();
            }
            if($check=='mand'){
               $row['mand']="X";
               if($opts['condition']){
                  $row['mand'].=" (IF {$opts['condition']})";
               }
               if($opts['msg']) $row['m-msg']=$opts['msg'];
            }elseif($check=='format'){
               $row['format']=$opts['regex'];
               if($opts['condition']){
                  $row['format'].=" (IF {$opts['condition']})";
               }
               if($opts['msg']) $row['f-msg']=$opts['msg'];
            }elseif($check=='max'){
               $row['max']=$opts['val'];
               $row['max-msg']=$opts['msg'];
            }elseif($check=='inlist'){
               $row['liste']='X';
            }else{
               // sonstige
               $oth=array();
               if($opts['condition']){
                  $oth[]="$check (IF {$opts['condition']})";
               }else{
                  $oth[]=$check;
               }
            #   if(substr($check, -2)=='()'){
               if($check[0]==':'){
                  $oth[] = "# ".Documentor::strip_comment($this->o, trim($check, ":"));
               }
               foreach($opts as $okey=>$oval){
                  if($okey=='condition' || $okey=='msg') continue;
                  $oth[]=" ".strtoupper($okey).": ".$oval;
               }
               if($opts['msg']) $oth[] = " MSG: ".$opts['msg'];
               $other[]=join("\n", $oth);
            }
         }
         $row['other']=join("\n\n", array_values($other));
         $tab[]=$row;
      }
      return $tab;
   }

   function validate_element($e, $event){
      // datumselemente
      // $dates=array("geburtsdatum", "seit", "bv_seit", "fa_seit", "aa_seit");

      log_error("[V/START] e: $e ({$this->o->$e})");

      $e=preg_replace("/_dd$/", "", $e);

      if(!self::$v[$e]) return;
      log_error("[V] element: $e");
      $checks = self::$v[$e];

      // top level event condition
      if(!self::match_event($event, @$checks['on'])) return true; 
      else unset($checks['on']);
      
      // top level condition
      //    betrifft alle checks
      log_error("CONDITON?");
      log_error($checks['condition']);
      if($checks['condition']){
         log_error("[V] e: $e, condition: {$checks['condition']}");
         if(!Evaluator::check($checks['condition'], $this->o, $e)){
            log_error("[V] condition FAILED ==> SKIP");
            return true;
         }else{
            unset($checks['condition']);
         }
      }

      foreach($checks as $check=>$opts){
         if(!is_array($opts)){
            // als verkürzung kann die fehlermessage direkt an der
            //     validierungsregel mitgegeben werden
            if($opts) $opts=array("msg"=>$opts);
            else $opts=array();
         }
         log_error("[V] e: $e, check: $check");

         // einzelcheck event condition
         if(!self::match_event($event, @$opts['on'])) continue; 
         else unset($opts['on']);
         
         // einzelcheck kann auch an eine condition gebunden sein
         if($opts['condition']){
            log_error("[V] e: $e, condition singlecheck: {$opts['condition']}");
            if(!Evaluator::check($opts['condition'], $this->o, $e)){
               log_error("[V] condition singlecheck FAILED ==> SKIP");
               continue;
            }else{
               unset($opts['condition']);
            }
         }

         if($check[0]=='.'){
            // der check wird an eine objektfunktion delegiert
            $opts['val'] = trim($check, ".");
            $err = $this->v_method($e, $opts);
         }else{
            $err = call_user_func_array(array($this, "v_{$check}"), array($e, $opts));
         }
         if($err!==true){
            $this->o->errors->add($err);
            log_error("[V] e: $e FAILED [{$err->msg}]");
         }else{
            log_error("[V] e: $e OK ({$this->o->$e})");
         }
      }
   }

   static function match_event($event, $prop=""){
      #log_error("validation $prop MATCHES/1 $event ???");
      // catch all
      if(!$prop) return true;
      #if(!$prop && $event!='save') return false;
      #if(!$prop && $event=='save') return true;
      #log_error("validation $prop MATCHES $event ???");
      if(!is_array($prop)) $prop = explode(',', $prop);
      #log_error($prop);
      #log_error(in_array($event, $prop));
      return in_array($event, $prop);
   }
   
   /*
      das ist ein ersatz für die alte builder#check_mand funktion
      sie bekommt nur dann true, wenn ein bedingungsloser fall vorliegt
   */
   static function check_mand($e){
      if(!self::$v) self::load();
      # print_r(self::$v[$e]);
      $def = self::$v[$e];
      log_error("[V] $e is_mandatory?");
      log_error($def);

      if(!$def || !is_array($def)) return false;

      if((key_exists('mand', $def) || key_exists('mand_datum', $def)) &&
         !is_array($def['mand']) && !key_exists('condition', $def)
         ) return true;
      return false;
   }

   function mand_class($e){
      $class = self::check_mand($e)?"required":"";
      return $class;
   }

   /*
      hilfsfunktion, damit ein max-length attribut an <input type=text> tags geschrieben werden kann
   */
   static function check_max_length($e){
      if(!self::$v) self::load();
      # print_r(self::$v[$e]);
      $def = self::$v[$e];
      #log_error("[V] $e is_maxlen?");
      #log_error($def);

      if(!$def || !is_array($def)){
         /*
          sonderfall datums-teile
         */
         if(preg_match("/_(dd|mm|yy)$/", $e, $mat)){
            $max=array("dd"=>2, "mm"=>2, "yy"=>4);
            return $max[$mat[1]];
         }else{
            return false;
         }
      }

      if(key_exists('max', $def) && !key_exists('condition', $def)){
         return $def['max']['val'];
      }

      return false;
   }

   function check_attrs($e, $sub=""){
      $attrs=array();
      $checks = self::$v[$e];
      if($checks['mand']){
         $req = "true";
         if(is_array($checks['mand']) && $checks['mand']['js-condition']) $req = $checks['mand']['js-condition'];
         $attrs['required']=$req;
         unset($checks['mand']);
         if($req=="true") unset($attrs['required']);
      }
      foreach($checks as $chk=>$parms){
         if($chk == "totalphone") continue;
         if(is_array($parms)) $val = current($parms);
         else{
            if($parms) $val = $parms;
            else $val = 1;
         }
     #    if($chk=="format") $val=addcslashes($parms['regex'], '\\');
     #    if($chk=="format") $val=$parms['regex'];
         $attrs[$chk] = $val;
      }
    #  print_r($attrs);
      if(is_array($sub)){
         $attrs = array_merge($sub, $attrs);
      }
      return $attrs;
   }

   function js_all_messages($els, $wrap=""){
      $trans = array("mand"=>"required", 'accept'=>'acceptcheckbox', 'minval'=>'min', 'maxval'=>'max', 'min'=>'minlength', 'max'=>'maxlength', 'confirm'=>'equalTo');
      $messages=array();
      $more_els=array("plz"=>"ort", "fa_plz"=>"fa_ort");
      foreach($more_els as $e=>$more){
         if(in_array($e, $els)){
            if(!is_array($more)) $more=array($more);
            $els=array_merge($els, $more);
         }
      }
#      log_error("ELEMENT-MESSAGES FOR:");
#      log_error($els);

      foreach($els as $e){
         # einige elementnamen muessen im javascript anders heissen
         #     zzt. zusammengesetzte datumsfelder
         $e2=preg_replace("/_dd$/", "", $e);
         if($e2!=$e) $js_e = $e2."_yy";
         else $js_e = $e;

         $e = $e2;
         ###
         
         $checks = self::$v[$e];
         if(!$checks) continue;

         foreach($checks as $check => $opts){
            if($check=="condition") continue;

            $msg="";
            if(!is_array($opts)){
               $opts = array('msg'=>$opts);
            }
            if(method_exists($this, 'jsm_'.$check)){
               $opts['msg'] = call_user_func_array(array($this, "jsm_{$check}"), array($e, $opts));
            }
            $js_check=$check;
            if($trans[$check]) $js_check = $trans[$check];
            if($check=='confirm' && $opts['msg']){
               $messages[$e.'_confirmation'][$js_check]=$opts['msg'];
               continue;
            }
            if($opts['msg']) $messages[$e][$js_check]=$opts['msg'];
         }
      }
      
      $msgok=array();
      foreach($messages as $k=>$m){
         if($wrap) $k = sprintf($wrap, $k);
         $msgok[$k]=$m;
      }
      return json_encode($msgok);
   }

   function js_all_rules($els, $wrap=""){
      $trans = array("mand"=>"required", 'accept'=>'acceptcheckbox', 'minval'=>'min', 'maxval'=>'max', 'min'=>'minlength', 'max'=>'maxlength');
      $js_allowed=explode(' ', 'format mand accept confirm min max minval maxval email');

      $r=array();
      $more_els=array("plz"=>"ort", "fa_plz"=>"fa_ort");
      foreach($more_els as $e=>$more){
         if(in_array($e, $els)){
            if(!is_array($more)) $more=array($more);
            $els=array_merge($els, $more);
         }
      }
      log_error("JS-ALL-RULEZ: ELEMENT-MESSAGES FOR:");
      log_error($els);

      foreach($els as $e){
         # einige elementnamen muessen im javascript anders heissen
         #     zzt. zusammengesetzte datumsfelder
         $e2=preg_replace("/_dd$/", "", $e);
         if($e2!=$e) $js_e = $e2."_yy";
         else $js_e = $e;

         if($e=='geburtsdatum') $js_e='geburtsdatum_yy';

         $e = $e2;
         ###
    #     log_error("# $e");
         $checks = self::$v[$e];
   #      log_error($checks);

         if(!$checks) continue;
         $js_chk=array();

         foreach($checks as $check => $opts){
            if(!in_array($check, $js_allowed)) continue;

            $cond = null;
            $cond = $checks['condition'];
            if(!$cond && is_array($opts)) $cond = $opts['condition'];
            if($cond){
               log_error("COND. FOUND on $e ($check)");
               $cond = Evaluator::check_js($cond, $this->o, $e);
               // js-condition kann nicht erstellt werden
               if($cond===false) continue;
            }

            if($check=="confirm"){
               log_error("### JS-CONFIRM {$js_e}");
               if($wrap) $e = sprintf($wrap, "{$js_e}_confirmation");
               $r[$e]=array("equalTo"=>"#{$js_e}");
               continue;
            }

            $js_o="";
            if(is_array($opts)){
               unset($opts['msg']);
               // min, max, etc
               if($opts['val']) $opts=$opts['val'];
            }elseif(is_string($opts)){
               $opts=true;
            }

			/*	if($check=='accept'){
					$opts=array(1,1);
				}
			*/
            $js_o = $opts?$opts:true;

            if($check=="mand") $js_o=true;
            if($cond){
               if(!is_array($js_o)) $js_o = array();
               $js_o['depends'] = $cond;
            }

            log_error("###ENDE### $check");
         #   log_error($js_o);

            if($trans[$check]) $check = $trans[$check];
            if($js_o) $js_chk[$check]=$js_o;
         }


         if($js_o){
            if($wrap) $e = sprintf($wrap, $js_e);
            $r[$e] = $js_chk;
         }
      }
      return json_encode_w_functions($r);
   }

   function js_validate($e){
      $rules=array();
      $messages=array();
      $checks = self::$v[$e];

      if($checks['mand']){
         $req = true;
         $msg = "";
         if(is_array($checks['mand'])){
            if(isset($checks['mand']['js-condition'])) $req = $checks['mand']['js-condition'];
            if(isset($checks['mand']['msg'])) $msg = $checks['mand']['msg'];
         }else{
            $msg = $checks['mand'];
         }
         $rules['required']=$req;
         if($msg) $messages['required']=$msg;
      }
#      print_r($rules);
      return json_encode(array("rules"=>$rules, "messages"=>$messages));
   }

   function get_message($def, $e, $opts, $vals=array()){
      $rep=array('{name}'=>$opts['name'],
         '{yourval}'=> h($opts['__'])
         );
      foreach($vals as $k=>$v){
         $rep['{'.$k.'}'] = $v;
      }
      if(!$rep['{name}']) $rep['{name}'] = f($this->o, $e)->title;
      if(!$rep['{name}']) $rep['{name}'] = ucfirst($e);
      $msg = $opts['msg_'.$def];
      if(!$msg) $msg = $opts['msg'];
      if(!$msg) $msg = self::$msg[$def];
      return str_replace(array_keys($rep), $rep, $msg);
   }

   function get_message_js($def, $e, $opts, $vals=array()){
      $rep=array('{name}'=>$opts['name']);
      $i=0;
      foreach($vals as $k=>$v){
         $rep['{'.$k.'}'] = '{'.$i.'}';
         $i++;
      }
      if(!$rep['{name}']) $rep['{name}'] = f($this->o, $e)->title;
      if(!$rep['{name}']) $rep['{name}'] = ucfirst($e);
      log_error($rep);
      $msg = $opts['msg_'.$def];
      if(!$msg) $msg = $opts['msg'];
      if(!$msg) $msg = self::$msg[$def];
      return str_replace(array_keys($rep), $rep, $msg);
   }
   
   function v_method($e, $opts=array()){
      $v=$this->o->$e;
      if(!$v) return true;
      $m = $opts['val'];
      $res = call_user_func_array(array($this->o, $m), array($e, $opts));
      if($res === true){
         // errors werden in custom function gesetzt
         return true;
      }elseif(is_string($res)){
         $opts['msg'] = $res;
         return new Xorcstore_Error($e, $this->get_message('', $e, $opts));
      }elseif($res===false || is_null($res)){
         return new Xorcstore_Error($e, $this->get_message('', $e, $opts));
      }else{
         return $res;
      }
   }
   
   function v_mand($e, $opts=array()){
      $v=$this->o->$e;
      if(!trim($v) && trim($v)!=="0"){
         log_error('FAIL MAND CHECK '.$e);
         return new Xorcstore_Error($e, $this->get_message('empty', $e, $opts));
      } 
      log_error('OK MAND CHECK '.$v);
      return true;
   }

   function jsm_mand($e, $opts){
      return $this->get_message_js('empty', $e, $opts);
   }
   
   function v_inlist($e, $opts=array()){
      $v=$this->o->$e;
      // wir sind hier sehr lasch und erlauben null/ ""/ 0
      //    falls unerwünscht -- mand check setzen
      if(!$v) return true;
      $list = $opts['func'];
      if($list){
         $list = explode('::', $list);
         $list = call_user_func_array(array($list[0], trim($list[1], '()')), array($e));
         $list = array_keys($list);
      }else{
         $list = $opts['list'];
      }
      log_error("LISTE:"); log_error($list);
      $opts['in'] = $list;
      # TODO
      $opts['between'] = null;
      if($opts['exclude']==1 &&
         ($opts['in'] && in_array($v, $opts['in'])) ||
            ($opts['between'] && ($v>=$opts['between'][0] && $v<=$opts['between'][1]))){
         return new xorcstore_error($e, $this->get_message('exclusion', $e, $opts));
      }else{
         if(!$opts['exclude'] &&
         ($opts['in'] && !in_array($v, $opts['in'])) ||
            ($opts['between'] && ($v<$opts['between'][0] || $v>$opts['between'][1]))){
               return new xorcstore_error($e, $this->get_message('inclusion', $e, $opts));
         }
      }
      return true;
   }
   
   function jsm_min($e, $opts){
      return $this->get_message_js('too_short', $e, $opts, array('val'=>$opts['val']));
   }
   function v_min($e, $opts=array()){
      $opts['minimum']=$opts['val'];
      return $this->v_length($e, $opts);
   }

   function jsm_max($e, $opts){
      return $this->get_message_js('too_long', $e, $opts, array('val'=>$opts['val']));
   }
   
   function v_max($e, $opts=array()){
      $opts['maximum']=$opts['val'];
      return $this->v_length($e, $opts);
   }

   function jsm_len($e, $opts){
      return $this->get_message_js('wrong_length', $e, $opts, array('val'=>$opts['val']));
   }
   
   function v_len($e, $opts=array()){
      $opts['is']=$opts['val'];
      return $this->v_length($e, $opts);
   }
   
   function v_length($e, $opts=array()){
      $v=$this->o->$e;
      if($opts['allow_null'] && is_null($v)) return true;
      
      if(function_exists('mb_strlen')){
         $v = str_replace("\r\n", "\n", $v);
         $v = str_replace("\r", "\n", $v);
         $len=mb_strlen($v, "utf-8");
      }else{
         $len=strlen($v);
      }
      $min=$max=null;
      if($opts['between']) list($min, $max)=$opts['between'];
      if(isset($opts['maximum'])) $max=$opts['maximum'];
      if(isset($opts['minimum'])) $min=$opts['minimum'];
      
      if($opts['is'] && $len!=$opts['is']){
         return new xorcstore_error($e, $this->get_message('wrong_length', $e, $opts, array('val'=>$opts['is'])));
      }elseif(!is_null($min) && ($len<$min || is_null($v))){
         return new xorcstore_error($e, $this->get_message('too_short', $e, $opts, array('val'=>$opts['minimum'])));
      }elseif(!is_null($max) && ($len>$max || is_null($v))){
         return new xorcstore_error($e, $this->get_message('too_long', $e, $opts, array('val'=>$opts['maximum'])));
      }
      return true;
   }

   function jsm_confirm($e, $opts){
      return $this->get_message_js('confirmation', $e, $opts);
   }
   function v_confirm($e, $opts){
      $v=$this->o->$e;
      if(!$v) return true;
      $confirm=$e."_confirmation";
      if(is_null($this->o->$confirm)) return true;

      if($v!=$this->o->$confirm){
         return new xorcstore_error($e, $this->get_message('confirmation', $e, $opts));
      }
      return true;
   }

   function v_accept($e, $opts){
      $v=$this->o->$e;
      if(is_null($v)) return true;
      if(!$opts['accept']) $opts['accept'] = 1;
      if($v!=$opts['accept']){
         return new xorcstore_error($e, $this->get_message('accepted', $e, $opts));
      }
      return true;
   }

   function v_unique($e, $opts){
      $v=$this->o->$e;
      if(!$v) return true;

      $scopeL=array($e=>$v);
      if($opts['scope']){
         if(!is_array($opts['scope'])) $opts['scope']=array($opts['scope']);
         foreach($opts['scope'] as $scope){$scopeL[$scope]=$this->o->$scope;}
      }

      if(!$this->o->is_new_record()){$scopeL[]="id != ".$this->o->id_quoted();}

      $found=$this->o->find_first(array("conditions"=>$scopeL));
      if($found) return new xorcstore_error($e, $this->get_message('taken', $e, $opts));
      return true;
   }


   function v_format($e, $opts){
      $v=$this->o->$e;
      if(!$v) return true;
      
      $f = $opts['regex'];
      $f = str_replace('CHAR_DIA_OK', CHAR_DIA_OK, $f);

      if(!preg_match("/$f/", $v)){
         return new xorcstore_error($e, $this->get_message('invalid', $e, $opts));
      }
      return true;
   }

	function v_email($e, $opts=array()){
		$opts['regex']="^[^ ]+@[^ ]+\.[^ ]+$";
		return $this->v_format($e, $opts);
	}

   function v_plz($e, $opts=array()){
      $opts['regex']="^[0-9]{5}$";
      return $this->v_format($e, $opts);
   }
   
   function v_konto($e, $opts=array()){
      $opts['regex']="^\d{2,10}$";
      return $this->v_format($e, $opts);
   }

   function v_blz($e, $opts=array()){
      $opts['regex']="^\d{8}$";
      return $this->v_format($e, $opts);
   }
   
   function v_iban($e, $opts=array()){
      $opts['regex']="^(DE|de)\d{20}$";
      return $this->v_format($e, $opts);
   }
   
   function v_number($e, $opts=array()){
      $v=$this->o->$e;
      if($opts['allow_null'] && (is_null($v) || (is_string($v) && !trim($v)))) return true;
      
      $opts['modify_before_check'] = 'to_float';
      if($opts['modify_before_check']){
         $func = $opts['modify_before_check'];
         $v = $func($v);
      }
      
      if(!is_numeric($v) || ($opts['only_integer'] && (is_float($v) || !preg_match("/^[-+]?\d+$/", $v)))){
         return new xorcstore_error($e, $this->get_message('not_a_number', $e, $opts));
      }  
   }

   function v_minval($e, $opts=array()){
      $v=$this->o->$e;
      if(!$v) return true;
      $min = $opts['val'];
      if($v < $min){
         return new xorcstore_error($e, $this->get_message('min', $e, $opts, array('val'=>$min)));
      }
      return true;
   }
   
   function v_maxval($e, $opts=array()){
      $v=$this->o->$e;
      if(!$v) return true;
      $min = $opts['val'];
      if($v > $min){
         return new xorcstore_error($e, $this->get_message('max', $e, $opts, array('val'=>$max)));
      }
      return true;
   }
   
   /**
    * validiert, ob ein feld leer ist
    *   quasi das gegenteil von mandatory
    *
    * @param string $e
    * @param string $opts
    * @return void
    * @author Robert Wagner
    */
   function v_empty($e, $opts=array()){
      if($this->o->$e){
         return new Xorcstore_Error($e, $opts['msg']);
      }
      return true;
   }



   function v_datum($e, $opts=array()){
	   $err = $this->_check_and_clean_date_in_object($this->o, $e, $opts);

      if(!$err){
         return true;
      }else{
         $msg = join("<br>\n", array_map(function($e){return $e->msg;}, $err));

         return new Xorcstore_Error($e."_dd", $msg);
      }
   }

   function v_mand_datum($e, $opts){
      $opts=array_merge(array("msg"=>Xorcstore_Validation::$msg['empty'], "name"=>ucfirst($e)), $opts);
      $els=array($e."_mm", $e."_yy");
      if($e=="geburtsdatum") $els[]=$e."_dd";
      log_error($els);
      foreach($els as $part){
         if(!$this->o->$part) return new Xorcstore_Error($e."_dd", sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }



   function v_nonconfirm($e, $opts){
      $field = $opts['field'];
		log_error("CONFIRM $e vs $field");
		if(!$this->o->$e) return true;

	#	log_error($opts);
      if($this->o->$e == $this->o->$field){
         return new Xorcstore_Error($e, sprintf($opts['msg'], $opts['name']));
      }
      return true;
   }


   function _check_and_clean_date_in_object($o, $el, $opts=array()){
	   $y=$el."_yy"; $m=$el."_mm"; $d=$el."_dd";
	   $inp=$el."_inp";
	   // soll der tag mit berücksichtigt werden?
	   $day = $opts['day'];
	   log_error("CHECKDATE: $el ($day)");
	   log_error($opts);

	   if($opts['single-field']){
	      $error_el = $el;
	      $input_exists = ($o->$el);

	      $d = explode(".", $o->$el, 3);
         $datp = htmlspecialchars($d[2]."-".$d[1]."-".$d[0]);
         $dat  = sprintf("%04d-%02d-%02d", $d[2], $d[1], $d[0]);
	   }else{
	      $error_el = $el."_dd";
	      $input_exists = ($o->$y || $o->$m || $o->$d);

	      if(!$day){
	         $o->$d="01";
	         $datp=htmlspecialchars($o->$y."-".$o->$m);
	      }else{
	         $datp=htmlspecialchars($o->$y."-".$o->$m."-".$o->$d);
	      }
   		$dat=sprintf("%04d-%02d-%02d", $o->$y, $o->$m, $o->$d);
	   }

	   $errors=array();

	   if($input_exists){

   		// formaler test
   		if($dat != $datp){
   		   log_error("++ DAT-FORMAT $dat VS $datp");
   		   $errors[] = new Xorcstore_Error($error_el, "Datumsformat ungültig.");
   		   return $errors;
   		}

   		$lab=$opts['name'];

   		// formal alles ok.
   		list($yy, $mm, $dd) = explode("-", $dat);

   		if(@checkdate($mm, $dd, $yy)){
   			$o->$inp=sprintf("%02d.%02d.%04d", $dd, $mm, $yy);
   			$now=date("Y-m-d");
   			if($opts['past'] && ($now < $dat)){
   			   $errors[] = new Xorcstore_Error($error_el, "Datum darf nicht in der Zukunft liegen: ".$lab." ($datp)");
   			   $o->$inp=null;
   			}elseif($el!="geburtsdatum" && $o->geburtsdatum_inp){
   			   $gebdat=my_date($o->geburtsdatum_inp);
   			   log_error("############ GEB vs. DAT (OTHER): $gebdat vs. $dat");
   			   if(substr($dat, 0, 7) < substr($gebdat, 0, 7)){
   			      $errors[] = new Xorcstore_Error($error_el, "Datum darf nicht vor Ihrem Geburtsdatum liegen: ".$lab." ($datp)");
   			      $o->$inp=null;
   			   }
   			}elseif($el=="geburtsdatum" && $o->geburtsdatum_inp){
   			   $gebdat=my_date($o->geburtsdatum_inp);
   			   log_error("############ GEB vs. DAT (GEBDAT): $gebdat vs. $dat");
   			   if(substr($gebdat, 0, 4) == date("Y")){
   			      $errors[] = new Xorcstore_Error($error_el, "Sie haben sich bei Ihrem Geburtsjahr vertippt: (".date("Y").")");
   			      $o->$inp=null;
   			   }
   			}
   			if($opts['future'] && ($now >= $dat)){
   			   $errors[] = new Xorcstore_Error($error_el, "Datum muss in der Zukunft liegen: ".$lab." ($datp)");
   			   $o->$inp=null;
   			}
   			if($opts['same-year'] && (date("Y") != $yy)){
   			   $errors[] = new Xorcstore_Error($error_el, $opts['msg-same-year']);
   			   $o->$inp=null;
   			}
   			if($opts['min-age'] && (strtotime("-{$opts['min-age']} years") < strtotime($dat))){
   			   $errors[] = new Xorcstore_Error($error_el, $opts['msg-min-age']);
   			}
   		}else{
   		   # print_r($o);
   		   $o->$inp=null;
   		#	$errors[] = new Xorcstore_Error($el."_dd", "Datum ungültig. ".$lab." ($datp)");
   		   $errors[] = new Xorcstore_Error($error_el, "Datum ungültig.");
   		}
   	}else{
   	   $o->$inp=null;
   	}
   	return $errors;
	}



}

?>