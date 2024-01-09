<?php

// require_once("Console/Getopt.php");

// now comes the fun part:)

class Ezoptions extends Console_Getopt {

	var $intro;
	var $cdir = array();
	var $help = array();
	var $xcmds = "";
	var $cmd;
	var $xcmd;

	function parse($defs, $intro = "", $cd = "", $args = "") {
		$this->intro = $intro;
		if ($cd) {
			$this->cdir[] = $cd;
		}
		$this->cdir[] = dirname(dirname(__FILE__)) . "/bin/scripts";

		$shortopts = "";
		$longopts = array();
		$helps = array();
		$map_parmlist = array();
		$indent = "\n       ";
		$defs[] = array('h', 'help', 'dieser hilfetext');
		foreach ($defs as $key => $dV) {
			list($d['s'], $d['l'], $d['h'], $d['PARM'], $d['OPTPARM']) = $dV + [3 => "", 4 => ""];
			$defname = is_integer($key) ? ($d['s'] ? $d['s'] : $d['l']) : $key;
			$long = "";
			$help = " ";

			if ($d['PARM']) $helpp = strtoupper($d['PARM']);
			elseif ($d['OPTPARM']) $helpp = '[' . strtoupper($d['OPTPARM']) . ']';
			else $helpp = '';

			if ($d['s']) {
				$shortopts .= $d['s'];
				if ($d['PARM']) $shortopts .= ':';
				elseif ($d['OPTPARM']) $shortopts .= '::';
				$help .= '-' . $d['s'];
				$map_parmlist[$d['s']] = $defname;
			} else {
				$help .= '  ';
			}
			if ($d['l']) {
				$long = $d['l'];
				$help .= $d['s'] ? ', ' : '  ';
				$help .= '--' . $d['l'];
				if ($d['PARM']) {
					$long .= '=';
					$help .= '=';
				} elseif ($d['OPTPARM']) {
					$long .= '==';
					$help .= '=';
				}
				$map_parmlist["--" . $d['l']] = $defname;
				$longopts[] = $long;
			}
			$help .= $helpp;	//sprechender parametername fuer hilfe --use-date=DATUM
			if (!$d['h']) $d['h'] = 'sorry, helptext missing.';
			$help .= $indent . wordwrap($d['h'], 66, $indent);
			$helps[] = $help;
		}
		$this->help = $helps;
		if (!$args) {
			$args = $this->readPHPArgv();
			//			array_shift($args);
		}

		$options = $this->getopt($args, $shortopts, $longopts);
		if (is_object($options)) die($options->message . "\n");

		$this->cmd = $this->get_commands($this->cdir);

		//		print_r($this->cmd);

		$newopt = array();
		foreach ($options[0] as $k => $v) {
			if (!$map_parmlist[$v[0]]) {
				die("Unbekannte option -- {$v[0]}\n");
			}
			$newopt[$map_parmlist[$v[0]]] = (gettype($v[1]) == 'string' && $v[1] != '') ? $v[1] : true;
		}

		if ($newopt['h'] ?? false) {
			$this->help($options[1]);
			exit;
		}


		$cmd = $this->command_file($options[1]);

		if (!$cmd) {
			print "Unbekanntes Kommando: " . join(" ", $options[1]) . "\n";
			print "\nCommands:\n";
			print $this->command_help();
			die();
		}
		$newopt['_COMMAND_'] = $cmd;


		$newopt['_REMAINING_'] = $options[1];
		return $newopt;
	}

	function help($remain = array()) {
		if ($this->cmd && $cmd = $this->is_command($remain)) {
			print("$cmd\n" . str_repeat("=", strlen($cmd)) . "\n");
			print $this->command_help($cmd);
		} else {
			print "\n" . wordwrap($this->intro, 68) . "\n";
			print "\nOptions:\n" . join("\n", $this->help) . "\n";
			if ($this->cmd) {
				print "\nCommands:\n";
				print $this->command_help();
			}
		}
	}

	function get_commands($dirs) {
		$cmd = array();
		foreach ($dirs as $dir) {
			$cmd[$dir] = array();
			foreach (glob("$dir/*.php") as $c) {
				$c = basename($c, ".php");
				if ($c[0] == "_") continue;
				$c = str_replace("_", " ", $c);
				$cmd[$dir][$c] = true;
			}
		}
		return $cmd;
	}

	function is_command(&$parms) {
		if ($parms) {
			foreach ($this->cdir as $dir) {
				if (($parms[1] ?? false) && isset($this->cmd[$dir]["{$parms[0]} {$parms[1]}"])) {
					$cmd = "$dir/{$parms[0]} {$parms[1]}";
					array_shift($parms);
					array_shift($parms);
					return $cmd;
				} elseif (isset($this->cmd[$dir][$parms[0]])) {
					$cmd = "$dir/{$parms[0]}";
					array_shift($parms);
					return $cmd;
				}
			}
		}
		return false;
	}

	function find_command($cmd) {
		foreach ($this->cdir as $dir) {
			if (isset($this->cmd[$dir][$cmd])) return $dir;
		}
	}

	function command_file(&$parms) {
		if ($cmd = $this->is_command($parms)) {
			return str_replace(" ", "_", $cmd) . ".php";
		} else {
			return false;
		}
	}

	function command_help($command = "") {
		if ($command) {
			$dir = $this->find_command($command);
			$h = $this->command_parse_help("$dir/" . str_replace(" ", "_", $command) . ".php", true) . "\n";
			// print(wordwrap($h, 50, "\n   "));
			print $h;
		} else {
			$visited = array();
			foreach ($this->cmd as $dir => $cmds) {
				foreach ($cmds as $command => $dd) {
					if (isset($visited[$command])) continue;
					$visited[$command] = true;
					$h = " $command, " . $this->command_parse_help("$dir/" .
						str_replace(" ", "_", $command) . ".php", false) . "\n";
					print($h);
				}
			}
		}
	}

	function command_parse_help($file, $long = false) {
		$c = join("", file($file));
		$ok = preg_match("!/\** help\!(.*)\*/!is", $c, $mat);
		if ($ok) {
			$h = preg_split("!\n\n!", $mat[1], 2);
			if (!$long) return trim($h[0]);
			else return $h[0] . "\n" . $h[1];
		}
		return "";
	}
}

if (__FILE__ == $argv[0]) {
	$con  = new EzOptions;
	$opts = $con->parse(
		array(
			'h' => array('h', 'help', 'dieser hilfetext'),
			'd' => array('d', 'datum', 'verwende ein bestimmtes datum, andernfalls wird das aktuelle Tagesdatum genommen', 'date'),
			't' => array('t', 'time', 'mit uhrzeit'),
			'v' => array('v', '', 'verbose (sei gespraechig)')
		),
		"this is a little gaga-wa-dodoo script to show how the new fancy getoptions interface works.\n\t\t-rw, 2002-07-30"
	);
	if ($opts['h']) {
		$con->help();
	} else {
		if ($opts['v']) print_r($opts);
		print date(($opts['d'] ? $opts['d'] : 'Y-m-d') . ($opts['t'] ? 'H:i:s' : '')) . "\n";
	}
}
