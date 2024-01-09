<?php
/* 
..........................................................................
	NiceDate
	Displayfuntions for Date
..........................................................................
*/
class Nicedate {

	var $dow = array( 0=>"Sonntag", 1=>"Montag", 2=>"Dienstag", 3=>"Mittwoch",  
				4=>"Donnerstag", 5=>"Freitag", 6=>"Sonnabend" );
	var $niceL = array();
	var $heute;
	
	function Nicedate($datumzeit="") {
		$heute=getdate();
		$heute_i=$heute["wday"];
		
		for($i=1; $i>-7; $i--){
			//print $i;
			//print "mktime.".mktime(0,0,0,$heute["mon"],($heute["mday"]+i),$heute["year"]).".";
			$dat = date("Y-m-d", mktime(0,0,0,$heute["mon"],($heute["mday"]+$i),$heute["year"]));
			//print $dat;
			if($i==1) $this->niceL[$dat] = "Morgen";
			elseif($i==0) {
				$this->niceL[$dat] = "Heute";
				$this->heute = $dat;
				}
			elseif($i==-1) $this->niceL[$dat] = "Gestern";
			else $this->niceL[$dat] = $this->dow[($heute_i+7+$i)%7];				
		}
		
		if($datumzeit)
			return $this->get($datumzeit);
	}

	function get($datumzeit, $withTime="") {
		// eingabe im format yyyy-mm-dd hh:mm:ss
		if(!$datumzeit) return "--";
		$datum = substr($datumzeit,0,10);
		$zeit  = substr($datumzeit,11,5);
		if($withTime) $withTime = $zeit;
		
		if($r = $this->niceL[$datum]){
			if($r=="Heute" && $zeit)	
				return $zeit;
			return $r." ".$withTime;		
		}
		
		return substr($datum,8,2).".".substr($datum,5,2).".".substr($datum,0,4)." ".$withTime;
	}
	
	function getTime($datumzeit) {
		return $this->get($datumzeit, "withTime");
	}
	
	function getList(){
		return $this->niceL;
	}
	
	function days($from){    
		if(!is_numeric($from)) $from=strtotime($from);
		$to = time();
		$diff = (int) abs($to - $from);
		if($diff <= 3600){
			$mins = round($diff / 60);
			if($mins <= 1)
				$since = '1 Minute';
			else
				$since = sprintf( '%s Minuten', $mins);
		}elseif(($diff <= 86400) && ($diff > 3600)){
			$hours = round($diff / 3600);
			if($hours <= 1)
				$since = '1 Stunde';
			else 
				$since = sprintf( '%s Stunden', $hours );
		}elseif($diff >= 86400){
			$days = round($diff / 86400);
			if($days <= 1)
				$since = '1 day';
			else
				$since = sprintf( '%s Tagen', $days );
		}
		return "vor ".$since;
	}
}
?>