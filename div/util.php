<?php
/*
	convert iso to utf8 including right conversation
	of the undefined iso chars for german typografic signs
*/

global $cp1252_map;
$cp1252_map = array(
	"\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
	"\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
	"\xc2\x83" => "\xc6\x92",    /* LATIN SMALL LETTER F WITH HOOK */
	"\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
	"\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
	"\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
	"\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
	"\xc2\x88" => "\xcb\x86",    /* MODIFIER LETTER CIRCUMFLEX ACCENT */
	"\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
	"\xc2\x8a" => "\xc5\xa0",    /* LATIN CAPITAL LETTER S WITH CARON */
	"\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
	"\xc2\x8c" => "\xc5\x92",    /* LATIN CAPITAL LIGATURE OE */
	"\xc2\x8e" => "\xc5\xbd",    /* LATIN CAPITAL LETTER Z WITH CARON */
	"\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
	"\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
	"\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
	"\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
	"\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
	"\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
	"\xc2\x97" => "\xe2\x80\x94", /* EM DASH */

	"\xc2\x98" => "\xcb\x9c",    /* SMALL TILDE */
	"\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
	"\xc2\x9a" => "\xc5\xa1",    /* LATIN SMALL LETTER S WITH CARON */
	"\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
	"\xc2\x9c" => "\xc5\x93",    /* LATIN SMALL LIGATURE OE */
	"\xc2\x9e" => "\xc5\xbe",    /* LATIN SMALL LETTER Z WITH CARON */
	"\xc2\x9f" => "\xc5\xb8"      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
);

function isowintoutf8($t) {
	global $cp1252_map;
	$t = mb_convert_encoding($t, 'UTF-8', 'Windows-1252');
	return  strtr($t, $cp1252_map);
}

function utf8toisowin($t) {
	global $cp1252_map;
	$t = strtr($t, array_flip($cp1252_map));

	$t = mb_convert_encoding($t, 'Windows-1252', 'UTF-8');
	return $t;
}

function xorc_fetch_url($url, $proxy = null, $file = null, $verbose = false, &$hdrs = null) {
	if ($proxy) {
		$pp = parse_url($proxy);
		$host = $pp['host'];
		$port = $pp['port'];
		if ($pp['user']) {
			$auth = "Proxy-Authorization: Basic " . base64_encode($pp['user'] . ":" . $pp['pass']) . "\r\n";
		}
		$doc = $url;
	} else {
		$pp = parse_url($url);
		$host = $pp['host'];
		$port = $pp['port'];
		if (!$port) {
			if ($pp['scheme'] == 'https') $port = "443";
			else $port = "80";
		}
		$doc = $pp['path'];
		if ($pp['query']) $doc .= "?" . $pp['query'];
		# Authorization: BASIC SFRNTFdvcmxkOkludGVybmV0
		if ($pp['user']) {
			$auth = "Authorization: Basic " . base64_encode($pp['user'] . ":" . $pp['pass']) . "\r\n";
		}
	}
	$shost = $host;
	$scheme = $pp['scheme'];
	if ($scheme && $scheme != 'http') {
		if ($scheme == 'https') $scheme = 'ssl';
		$shost = $scheme . '://' . $shost;
	}
	$fp = fsockopen($shost, $port, $errno, $errstr, 30);
	if (!$fp) return false;
	if ($file) $out = fopen($file, "w");
	if ($verbose) print "GET $doc HTTP/1.0\r\nHost: $host\r\nevtl. write to file $file\n\n";
	fputs($fp, "GET $doc HTTP/1.0\r\nHost: $host\r\n");
	if ($auth) fputs($fp, $auth);
	fputs($fp, "\r\n\r\n");
	$header = false;
	$data = $headerdata = "";
	while (!feof($fp)) {
		$buffer = fread($fp, 4096);
		if (!$header && (strpos($buffer, "\r\n\r\n") !== false)) {
			$headerdata .= substr($buffer, 0, strpos($buffer, "\r\n\r\n"));
			$buffer = substr($buffer, strpos($buffer, "\r\n\r\n") + 4);
			$header = true;
		}
		if ($header) {
			if ($out) fwrite($out, $buffer);
			else $data .= $buffer;
		}
	}
	fclose($fp);
	if ($out) fclose($out);
	if (!(null === $hdrs)) $hdrs = xorc_parse_httpheaders($headerdata);
	return $data;
}

/* 
 * modified from php.net comment by luigi dot sexpistols at gmail dot com
 *    http://de.php.net/manual/de/function.http-parse-headers.php
*/
function xorc_parse_httpheaders($headers = false) {
	if ($headers === false) return false;
	$headers = str_replace("\r", "", $headers);
	$headers = explode("\n", $headers);
	foreach ($headers as $value) {
		$header = explode(": ", $value);
		if ($header[0] && !$header[1]) {
			$status = preg_split("/\s+/", $header[0]);
			$headerdata['status'] = $status[1];
			$headerdata['proto'] = $status[0];
			$headerdata['msg'] = $status[2];
		} elseif ($header[0] && $header[1]) {
			$headerdata[strtolower($header[0])] = $header[1];
		}
	}
	return $headerdata;
}

function addLinks($string) {
	$string = preg_replace(
		"/(?<!quot;|[=\"]|:\/\/)\b((\w+:\/\/|www\.).+?)" .
			"(?=\W*([<>\s]|$))/i",
		"<a href=\"$1\">$1</a>",
		$string
	);
	return preg_replace("/href=\"www/i", "href=\"http://www", $string); #}

	//$t = addLinks($t);
	return $string;
}

# wordpress
if (!function_exists('is_email')) {
	function is_email($email) {
		return (preg_match("/^[-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i", $email));
	}
}
