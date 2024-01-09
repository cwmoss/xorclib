<?php
/*************************************************************************
written by vaibhav ambavale
contact vaibhav_ambavale@yahoo.co.in
*************************************************************************/
class SwishE
{
	var $swish;
	var $createscript;
	var $search_config;
	var $search_index;
	var $search_query;
	var $attr_base="rel url title size";
	var $attr_user="";
	
	/**
	 * Description this will be used for highlighting text.  
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $highlight_element=array();
	/**
	 * Description this will hold command to execute swish-e
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $cmd;
	
	/**
	 * Description this will tell from which record swish-e should return results.
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $startat;
	
	/**
	 * Description :this tells how many results should be returned.
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $no_of_results;

	/**
	 * Description :this will have total no of results returned by swish-e
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $num_results;
	
	/**
	 * Description : this array will have relavance of each result.
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $relevance=array();
	
	/**
	 * Description :
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $result_url=array();
	
	/**
	 * Description
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $result_title=array();
	
	/**
	 * Description
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $file_size=array();
	
	/**
	 * Description
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $link=array();
	
	/**
	 * Description
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $description=array();
	var $attr=array();
	var $errorMessage;
	
	/**
	 * Description
	 * @var       
	 * @since     1.0
	 * @access    private
	 */
	var $search_element=array();
	
	var $res=array();
	/**
	 * Short description. constructor
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function SwishE($start=0)
	{

			$this->swish="";
			$this->search_index="";
		    $this->search_query="";
			$this->cmd="";
			$this->num_results=0;
			//$this->relevance=0;
			$this->result_url=array();
			$this->result_title=array();
			$this->file_size=array();
			$this->link=array();
			$this->description=array();
			$this->errorMessage="";
			$this->search_element=array();
			$this->highlight_element=array();
			$this->startat=$start;
			$this->no_of_results = 20;
			     
	} // end func

	function setup($prop){
		$this->swish=$prop['bin'];
		$this->search_index=$prop['idx'];
		if($prop['idx.today']){
		   $today=$prop['idx'].".".$prop['idx.today'];
#		   if(file_exists($today)) $this->search_index.=" ".$today;
		}
		$this->search_config=$prop['conf'];
		$this->createscript=$prop['create'];
		$this->createscriptparms=$prop['createparms'];
		$this->order=$prop['order'];
	}
	
	function set_pages($page="", $maxpp=20, $offset=null){
		$this->no_of_results = $maxpp;
		if($offset===null){
			$this->startat=$page?(($page-1)*$this->no_of_results+0):"0";
		}else{
			$this->startat=$offset;
		}
	}
	/**
	 * Short description. 
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/

	function setSearchQuery($query)
	{
	    $this->search_query=$query;
	} // end func
	
	
	/**

	 * Short description. 
	 *
	 * Detail description tells where swish-e executable is stored
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function setSwish($swish)
	{
	    $this->swish=$swish;
	} // end func

	
	/**
	 * Short description. 
	 *
	 * Detail description sets the path of the index file
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function setIndex($index)
	{
	    $this->search_index=$index;
	} // end func

	
	/**
	 * Short description. this will splitup each word out
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	
	function getWordsOut()
	{
	    $formated_query=str_replace("*"," ",$this->search_query); /*replace wildcard caracter by space*/
		$this->search_element=explode(" ",trim($formated_query)); /*separate words in search query */
		
		for($i=0;$i<count($this->search_element);$i++){
			$this->highlight_element[$i]=$this->search_element[$i]."[^ ]* ";
			//echo "(".$search_element1[$i].")";
			$this->search_element[$i]=ereg_replace("\*.*","",$this->search_element[$i]);
			//echo $search_element[$i];
		}
	} // end func
	
	/**
	 * Short description. this will buildup command to be executed for search 
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function getCommand()
	{
	    $this->cmd=$this->swish." -w '".$this->search_query."'".
		 	" -f ".$this->search_index.
		 	" -b ".$this->startat;
			
		if($this->no_of_results > 0)
			$this->cmd.=" -m ".$this->no_of_results;
		
		$ord=$this->order?"-s $this->order":"";
		
		$this->cmd.=" -p {$this->attr_user} $ord -d @@@@";
//			" -p {$this->attr_user} -s date desc -d @@@@";
	} // end func

	
	/**
	 * Short description. this will return no of results 
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function getNoofResults($pp)
	{
		while($nline=@fgets($pp,1024)){
	//		print "nline: $nline";
			if(preg_match("/^# Number of hits: (.+)$/i", $nline, $mat)){
				$this->num_results=trim($mat[1]);
				return;
			}
		
		}
		

	} // end func

	
	/**
	 * Short description. sets array of description of each hit.
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function getDescription($page_requested)
	{
	   $contents = join("", file($page_requested));
		$contents=strip_tags($contents);
		$temp=strtolower($contents);
		$needle=strtolower($this->search_element[0]);
		$ind=strpos($temp,$needle);
		if ($ind<25) {
			$description=substr($contents,0,200);    
		}else {
			$description=substr($contents,$ind-25,200);    
		}
		//$search_string="";
		for($i=0;$i<count($this->highlight_element);$i++){
			//$search_string.=$search_element1[$i];
			$this->description[]=eregi_replace("(".$this->highlight_element[$i].")"," <b>\\1</b> ",$description);
		}
		//echo $search_string;
		//$description=eregi_replace("(".$search_string.")"," <b>\\1</b> ",$description);

	} // end func

	
	/**
	 * Short description. sets array of attributes like file size, relevance, title and link  
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function getAttributes($line)
	{	   
		$attr=array();
		$attr_names=preg_split("/\s+/", $this->attr_base." ".$this->attr_user);
		$attr_raw=split("@@@@",$line); 
		
		if(!is_numeric($attr_raw[0])) return;
		
		foreach($attr_raw as $k=>$v){
			$attr[$attr_names[$k]]=trim($v);
		}
		
	//	print_r($attr_raw);
	//	print_r($attr);
		
		$this->relevance[]=$attr['rel']/10;/* format relevance as a percentage for search results */
		$this->result_title[]=$attr['title'];

		$url=parse_url($attr['url']);/* split the URL into an array of its components */

		$link=$url["path"];/* assign the web link to the path component to return a relative URL */

		$page_requested=$link;   /* return the full path of the file on the web server */

	//	$this->getDescription($page_requested);
				
		if($url["query"]){
			$this->link[]=$link."?".$url["query"];
		}else {
		    $this->link[]=$link;
		}/* if the URL contains a query string, append the URL with that query string */	
		
		$this->attr[]=$attr;
	} // end func

	/**
	 * Short description. this will create new process where command in $cmd will be executed
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function executeCommand()
	{
	   // print $this->cmd;
	    $pp=popen($this->cmd,"r") or die ("The search request generated an error.Please try again.");

		$this->getNoofResults($pp);

		while($line=@fgets($pp,4096)){				
				if(!preg_match("/^#/", $line)) $this->getAttributes($line);
		}//end of while
		pclose($pp);/* close the shell pipe */
	} // end func

	
	/**
	 * Short description. this will sequence all the functions. 
	 *
	 * Detail description
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     public
	 * @return     void
	 * @update     date time
	*/
	function execute($debug=false)
	{
	   $this->preProcess();
		$this->getWordsOut();
		$this->getCommand();
		if($debug) print "executing: $this->cmd";
		log_db_error("### SWISH: ".$this->cmd);
		$this->executeCommand();
	} // end func
	
	function get_pager(){
		$p=array();
		$page=$this->startat/$this->no_of_results+1;
		$maxpp=$this->no_of_results;
		$p['this']=$page;
		$p['maxpp']=$maxpp;
		$p['total']=$this->num_results;
//		print "TOTAL:{$p['total']}~maxpp:$maxpp~";
		$p['totalpages']=ceil($p['total']/$maxpp);
		if(!$p['totalpages']) $p['totalpages']=1;
		$p['real']=($page*$maxpp<$p['total'])?$maxpp:($p['total']-($page-1)*$maxpp);
		$p['less']=($page==1)?false:true;
		$p['prev']=($page==1)?$page:$page-1;
		$p['more']=($page==$p['totalpages'])?false:true;
		$p['next']=($page==$p['totalpages'])?$page:$page+1;
		$p['first']=$p['total']?($page-1)*$maxpp+1:0;
		$p['last']=$p['total']?$p['first']+$p['real']-1:0;
		return $p;
	}
	
	/**
	 * Short description. processes input query string
	 *
	 * Detail description this will filter out any shell command, backslashes, quotes
	 * @param      none
	 * @global     none
	 * @since      1.0
	 * @access     private
	 * @return     void
	 * @update     date time
	*/
	function preProcess()
	{
		$this->search_query=EscapeShellCmd($this->search_query);/* escape potentially malicious shell commands */
		$this->search_query=stripslashes($this->search_query);/* remove backslashes from search query */
		$this->search_query=ereg_replace('("|\')','',$this->search_query);
		/* remove quotes from search query */
		
	} // end func
	
	
}

class SwishE_Iterator{

	var $swish;
	var $clas;
	
	function SwishE_Iterator(&$sw_obj, $clas="SwishE_Iterator_Item"){
		$this->swish=$sw_obj;
		$this->clas=$clas;
		reset($this->swish->attr);
	}
	
	function next(){
		if(list($k, $v)=each($this->swish->attr)){
			$o=new $this->clas($v);
			return $o;
		}else{
			return false;
		}
	}
}

class SwishE_Iterator_Item{
	var $prop;
	
	function SwishE_Iterator_item($p){
		$this->prop=$p;
	}
	
	function describe(){
		return $this->prop['swishdescription'];
	}
	
	function fullname(){
		return $this->prop['title'];
	}
	
	function id(){
		preg_match("/id=(\d+)/", $this->prop['url'], $m);
		return $m[1];
	}
}
?>