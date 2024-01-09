<?php

class Single_Instance_Script{
	/*
		this should run since php 4.3
	*/
	
	var $pidfile;
	
	function single_instance_script($pid=null){
		if(is_null($pid)){
			$pid=$_SERVER['argv'][0];
		}
		if($pid[0]!="/"){
			$pid=getcwd()."/".$pid;
		}
		$pi=pathinfo($pid);
		if($pi['extension']!="pid") $pid.=".pid";
		
		$this->pidfile=$pidfile=$pid;
#	print "PIDFILE: $this->pidfile\n";
		$start=false;
		$prevPID = @file_get_contents($pidfile);
		if($prevPID !== FALSE){
			if(function_exists("posix_kill")){
				// kill test is possible to avoid crashed programs unerased PID files
				if(posix_kill(trim($prevPID), 0)){
					$start=false;
				}else{
					$start=true;
				}
		   }else{
				$start=false;
			}
		}else{
			$start=true;
		}

		if(!$start) die("Another Script is running. ($prevPID)\n");
		$this->_write_pid();
		register_shutdown_function(array($this, "clean_exit"));
	}

	function _write_pid(){
		$pid=getmypid();
		$fh=fopen($this->pidfile, "w");
		if($fh===false) die("Could not write PID file ($this->pidfile)\n");
		fwrite($fh, $pid);
		fclose($fh);
	}
	
	function clean_exit(){
		$ok=@unlink($this->pidfile);
		if(!$ok) die("Could not delete PID file ($this->pidfile)\n");
	}
}
?>