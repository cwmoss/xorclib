<?php

/*
	XorcHelper
*/

class XH {


	function countrycodes($lang = "de", $max = 24, $sort = true) {
		$ccL = array();
		$cc = parse_ini_file(dirname(__FILE__) . "/country-iso3166-{$lang}.ini");
		foreach ($cc as $c => $v) {
			$c = str_replace("$lang.", "", $c);
			$ccL[$c] = mb_substr($v, 0, $max, "UTF-8");
		}

		$cc = array_map(function ($v) use ($max) {
			return mb_substr($v, 0, $max, 'UTF-8');
		}, $ccL);

		if ($sort) asort($cc, SORT_LOCALE_STRING);
		return $cc;
	}
	function countrycodes_spec($lang = "de", $max = 24, $sort = true) {
		$ccL = array();
		$cc = parse_ini_file(dirname(__FILE__) . "/country-iso3166-{$lang}.ini");
		foreach ($cc as $c => $v) {
			$c = str_replace("$lang.", "", $c);
			$ccL[$c] = mb_substr($v, 0, $max, "UTF-8");
		}

		$cc = array_map(function ($v) use ($max) {
			return mb_substr($v, 0, $max, 'UTF-8');
		}, $ccL);

		if ($sort) asort($cc, SORT_LOCALE_STRING);
		#print_r($cc);
		$front = array("de", "at", "ch", "nl", "it");
		$d = array();
		foreach ($front as $v) {
			$d[$v] = $cc[$v];
			unset($cc[$v]);
		}
		return ($d + $cc);
	}

	function countrycodes_utf8($lang = "de", $max = 24, $sort = true) {
		$codes = self::countrycodes($lang, $max, $sort);
		foreach ($codes as $key => $code)
			$codes[$key] = isowintoutf8($code);
		return $codes;
	}

	function langcodes($lang = "de", $max = 24, $sort = true) {
		$lcL = array();
		$lc = parse_ini_file(dirname(__FILE__) . "/lang-iso639-1-{$lang}.ini");
		foreach ($lc as $c => $v) {
			$c = str_replace("de.", "", $c);
			$lcL[$c] = $v;
		}

		$lc = array_map(function ($v) use ($max) {
			return mb_substr($v, 0, $max, 'UTF-8');
		}, $lcL);
		if ($sort) asort($lc);
		return $lc;
	}

	function alpharegister($url = "", $pos = "", $addparms = array(), $step = 1, $noalpha = 1) {
		if (!$url) $url = $_SERVER['PHP_SELF'];
		if (!$pos) $pos = $_GET['alphareg'];
		if (!$pos) $pos = $_POST['alphareg'];
		$reg = "";
		$parms = XH::url("", $addparms);
		if ($parms) $parms = "&$parms";

		$c = -1;

		if (true) {
			$act = ($pos == "all") ? " class=\"active\"" : "";
			$reg .= "<li$act><a href=\"$url?all=1$parms\" class=\"alphareg\">" .
				"alle</a></li>";
		}

		if ($noalpha) {
			$act = ($pos == "-") ? " class=\"active\"" : "";
			$reg .= "<li$act><a href=\"$url?alphareg=-$parms\" class=\"alphareg\">" .
				"0</a></li>";
		}
		foreach (range('a', 'z') as $p) {
			$c++;
			if ($c % $step) continue;
			$letter = $p;
			$next = chr(ord($letter) + $step - 1);
			if ($next > "z") $next = "z";
			$display = ($letter == $next) ? $letter : "$letter-$next";

			$act = ($pos == $letter) ? " class=\"active\"" : "";
			$reg .= "<li$act><a href=\"$url?alphareg=$letter$parms\" class=\"alphareg\">" .
				"$display</a></li>";
		}
		return $reg;
	}

	function alpharegister_sql($pos, $field) {
		switch ($pos) {
			case '-':
				$char = '^a-zäöü';
				break;
			case 'u':
				$char = 'üu';
				break;
			case 'a':
				$char = 'äa';
				break;
			case 'o':
				$char = 'öo';
				break;
			default:
				$char = $pos;
		}
		$regex = sprintf(
			"%s ~* '^[%s]'",
			$field,
			$char
		);
		return $regex;
	}

	function pager($pager, $url = "", $addparms = array(), $slide = 6) {
		if (!$url) $url = $_SERVER['PHP_SELF'];
		$parms = XH::url("", $addparms);
		if ($parms) $parms = "&$parms";

		//        if($pager['totalpages']==1) return "";
		$html = "";
		$html .= "<ul class=\"pager\"><li class=\"descr\"><strong>{$pager['total']}</strong> Ergebnisse. Seite {$pager['this']} von {$pager['totalpages']}</li>";

		if ($pager['less']) {
			$prevurl = "$url?page={$pager['prev']}$parms";
			$firsturl = "$url?page=1$parms";
			$html .= "<li class=\"arrow\"><a href=\"{$firsturl}\">&lt;&lt;</a></li>" .
				"<li class=\"arrow\"><a href=\"{$prevurl}\">&lt;</a></li>";
		}

		if ($slide) {
			$start = $pager['this'] - $slide;
			if ($start < 1) $start = 1;
			$end = $pager['this'] + $slide;
			if ($end > $pager['totalpages']) $end = $pager['totalpages'];
		} else {
			$start = 1;
			$end = $pager['totalpages'];
		}
		for ($i = $start; $i <= $end; $i++) {
			$act = ($i == $pager['this']) ? ' class="active"' : '';
			$purl = "$url?page={$i}$parms";
			//<a href=\"{$this->longurl}?page={$i}$parm\">$i</a>
			$html .= "<li$act><a href=\"$purl\">$i</a></li>";
		}
		if ($pager['more']) {
			$nexturl = "$url?page={$pager['next']}$parms";
			$lasturl = "$url?page={$pager['totalpages']}$parms";
			$html .= "<li class=\"arrow\"><a href=\"{$nexturl}\">&gt;</a></li>" .
				"<li class=\"arrow\"><a href=\"{$lasturl}\">&gt;&gt;</a></li>";
		}
		$html .= "</ul>\n";
		return $html;
	}

	function url($url, $parms = array()) {
		$p = array();
		if ($parms) {
			foreach ($parms as $k => $v) {
				if (isset($v))
					$p[] = "$k=" . urlencode($v);
			}
			$parms = @join("&", $p);
		}
		if ($parms)
			return ($url) ? "$url?$parms" : $parms;
		return "$url";
	}
}
