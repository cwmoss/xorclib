<?php
require_once 'XML/FastCreate.php';

class Xorc_View_Fastcreate{
	var $auto=array("top", "bottom");
	var $R;
	
	function init_renderer($trans=array()){
	   $this->R = XML_FastCreate::factory('Text',
          array(
              // Use the XHTML 1.0 Strict Doctype
              'doctype'   => XML_FASTCREATE_DOCTYPE_XHTML_1_0_STRICT,
              'translate' => $trans
             # 'indent'     => true,
          )
      );
	}
	
	function out(){
	   $this->R->toXML();
	}
	
	function _translate(){return array();}
	
   function render($view="", $params=array()){
     
      if(!$view){
         $k=XorcApp::$inst->ctrl_name;
         $m=XorcApp::$inst->act;
      }elseif($view{0}=="/"){
         $view=substr($view, 1);
      }else{
         $k=XorcApp::$inst->ctrl_name;
         $m=$view;
      }
      #print "#$k#$m#";
      $k.="_V";
      $this->_v=new $k;
      $this->_v->init_renderer($this->_v->_translate());
      $out=$this->_import($params);
      
      $this->_v->$m();
      
      return;
      
      if(XorcApp::$inst->ctrl->_page) $out=XorcApp::$inst->ctrl->_page->return_string();
      
      foreach(XorcApp::$inst->ctrl->_post as $p){
         $out=$p($out);
      }
      return $out;
   }

	function render_part($view="", $params=array()){
	   XorcApp::$inst->log("VIEWPART $view");
		$out=$this->_include("_$view.html", $params);
        return $out;
	}
    	
	function render_page(){
	   $this->_v->layout();
	   $this->_v->out();
	   return;
		$c =& XorcApp::$inst->ctrl;
		$c->content =& XorcApp::$inst->out;
		$charset=XorcApp::$inst->conf['general']['charset'];
		if(!$charset) $charset="UTF-8";
		header("Content-type: text/html; charset=$charset");
// print "RENDER PAGE "; print_r($c);
      $layout=$c->layout();
      if($layout) $layout="_$layout";
      XorcApp::$inst->log("LAYOUT $layout");
		foreach($this->auto as $auto){
			if($c->auto($auto) && file_exists(XorcApp::$inst->base."/view/_layout{$layout}.$auto.html"))
				$c->layout[$auto]=$this->_include("_layout{$layout}.$auto.html");
		}
		if($c->auto('page') && file_exists(XorcApp::$inst->base."/view/_layout{$layout}.page.html")) 
			return $this->_include("_layout{$layout}.page.html");
		else return $c->layout['top'].$c->content.$c->layout['bottom'];
	}
	
	function _import($params=array()){		
		foreach(XorcApp::$inst->ctrl as $key=>$val){
		   if(!isset($this->_v->$key)) $this->_v->$key=$val;
		}
		foreach($params as $key=>$val){
		   $this->_v->$key=$val;
		}
//		ob_start();
//		include($file);
//		$out=ob_get_clean();
//		print $out;

//		return $out;
	}
}
?>