<?php
# require_once("/home/data/projekte/webtape/lib/phpQuery-0.9.5.252/phpQuery/phpQuery.php");
require_once(__DIR__ . "/phpQuery/phpQuery/phpQuery.php");
require_once(__DIR__ . "/../mvc/filesys_helper.php");

class JQ_Scraper {

	var $raw;
	var $html;
	var $top;
	var $bottom;
	var $dom;
	var $baseurl;
	var $base = array();

	var $cache_dir = false;
	var $cache_url = "";

	var $assets = array();
	var $assets_css = array();

	var $exit_append;
	var $rw_base;  # rewrite base

	var $replacements = array();

	var $opts = array("force_cache_kill_css" => true, "proxy" => null, 'verbose' => false);
	var $docID;

	var $rec = null;
	function __construct($url = null, $opts = array()) {
		$this->opts = array_merge($this->opts, $opts);
		#	   mb_internal_encoding("UTF-8");
		if ($url) $this->fetch($url, $this->opts);
	}

	function play_recipe($r, $dir, $opts) {
		if ($opts['verbose']) {
			print "\n*** LOAD+PLAY Recipe ($r from $dir) ***\n";
		}
		$rec = JQ_Scraper_Recipe::load($r, $dir, $opts);

		if ($opts['verbose']) {
			print_r($rec);
		}

		$rec->play($this, $opts);
		$this->rec = $rec;
	}

	function destination($ropts) {
		return $this->rec->destination($ropts);
	}

	function fetch($url, $opts = array()) {
		$opts = array_merge(array(
			'remove_xml' => true,
			'fix_header' => true,
			'charset' => 'UTF-8'
		), $opts);
		#	echo $url."\n";	   
		#	   print_r($opts); die();

		$hdrs = "";
		if (preg_match("!^https?://!", $url)) {
			$layout = xorc_fetch_url($url, $this->opts['proxy'], false, true, $hdrs);
			if (!$hdrs['status'] == 200) throw new Exception("Could not fetch from URL $url ({$hdrs['status']})\n");
		} else {
			if ($url == '-') $url = 'php://stdin';
			$layout = file_get_contents($url);
		}

		if (!$layout) throw new Exception("Empty Layout. Could not read from FILE $url\n");

		# remove BOM
		if (substr($layout, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
			$layout = substr($layout, 3);
		}

		if ($opts['remove_xml']) {
			$layout = preg_replace('/<\?xml.*?\?>/i', "", $layout);
		}

		if ($opts['iso2utf8']) {
			$layout = utf8_encode($layout);
			$layout = preg_replace('/<meta.*?charset.*?>/', "", $layout);
		}

		# fix header
		if ($opts['fix_header']) {
			$layout = str_replace("<head>", '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>', $layout);
		}

		if ($opts['verbose']) {
			print "*** CONTENT AFTER GET'ing ***\n";
			print $layout;
		}

		if ($opts['charset']) {
			$doc = phpQuery::newDocumentHTML($layout, $opts['charset']);
		} else {
			$doc = phpQuery::newDocumentHTML($layout);
		}
		$this->docID = $doc->getDocumentID();
		$this->find_base();
	}

	function rewrite_and_cache() {
		if ($this->cache_dir && ($this->opts['force_cache_kill'] || $this->opts['force_cache_kill_css'])) {
			# silly security check
			#     path too short
			if (strlen(dirname(__FILE__)) > strlen($this->cache_dir)) {
				throw new Exception("Path {$this->cache_dir} is too short to remove\n");
			} else {
				$filter = "*.css";
				if ($this->opts['force_cache_kill']) $filter = "*";
				$cmd = "rm -rf {$this->cache_dir}/{$filter}";
				print "##########\n## CACHE KILL $filter\n## $cmd\n##########\n";
				`$cmd`;
			}
		}

		$this->rewrite_urls();

		if ($this->cache_dir) {
			$this->rewrite_cache_styles();
		}
	}

	function insert_header($txt) {
		if (!$this->html) $this->html = $this->html();
		$this->html = str_replace("<!--$this->headmarker_bottom-->", $txt, $this->html);
	}

	function insert_header_top($txt) {
		if (!$this->html) $this->html = $this->html();
		$this->html = str_replace("<!--$this->headmarker_top-->", $txt, $this->html);
	}

	function insert_header_bottom($txt) {
		if (!$this->html) $this->html = $this->html();
		$this->html = str_replace("<!--$this->headmarker_bottom-->", $txt, $this->html);
	}

	function insert_navi($txt) {
		if (!$this->html) $this->html = $this->html();
		$this->html = str_replace("<!--$this->navimarker-->", $txt, $this->html);
	}

	function insert_exitcode() {
		if ($this->exit_append) {
			if (!$this->html) $this->html = $this->html();
			$this->html = str_replace('%24%24%24EXITCODE%24%24%24', $this->exit_append, $this->html);
		}
	}

	function split_top_bottom() {
		if (!$this->html) $this->html = $this->html();
		list($this->top, $this->bottom) = explode("<!--$this->splitmarker-->", $this->html, 2);
	}

	function clear_marker() {
		if (!$this->html) $this->html = $this->html();
		$this->html = preg_replace("/<!--XORC-[-A-Z]+-HERE-->/", "", $this->html);
		$this->top = preg_replace("/<!--XORC-[-A-Z]+-HERE-->/", "", $this->top);
		$this->bottom = preg_replace("/<!--XORC-[-A-Z]+-HERE-->/", "", $this->bottom);
	}

	function find_base() {
		if (!$this->baseurl) $this->set_base(pq("base")->attr("href"));
		pq("base")->remove();
	}

	function rewrite_urls() {

		foreach (pq("[action]") as $url) {
			$url = pq($url);
			$url->attr("action", $this->urlrewrite($url->attr("action")));
			print pq($url)->attr("src") . " -- " . pq($url)->attr("href") . " -- " . pq($url)->attr("action") . "\n";
		}

		foreach (pq("[src]") as $url) {
			$url = pq($url);
			$url->attr("src", $this->urlrewrite($url->attr("src")));
			print pq($url)->attr("src") . " -- " . pq($url)->attr("href") . " -- " . pq($url)->attr("action") . "\n";
		}

		foreach (pq("[href]") as $url) {
			$url = pq($url);

			$ourl = $url->attr("href");
			if (preg_match("/^mailto:/", $ourl)) continue;

			$newurl = $this->urlrewrite($ourl);

			if ($this->opts['href_exit_code']) {
				$base = $this->base['scheme'] . "://" . $this->base['host'];
				print "##### EXIT appending $newurl vs. $base\n";
				if (strpos($newurl, $base) === 0) {
					print "FOUND\n";
					if (strpos($newurl, "?")) $p = "&";
					else $p = "?";
					$newurl .= $p . '$$$EXITCODE$$$';
				}
			}

			$url->attr("href", $newurl);

			print pq($url)->attr("src") . " -- " . pq($url)->attr("href") . " -- " . pq($url)->attr("action") . "\n";
		}

		foreach (pq("style") as $style) {
			$style = pq($style);

			$text = preg_replace_callback(
				"!(((import\s+)?url)\(?['\"]?([-_/\.\w]+)['\"]?\))|((import)\s+['\"](.*?)['\"])!",
				array($this, "_urlcache_cb"),
				$style->text()
			);
			$style->text($text);
		}
	}

	function rewrite_cache_styles($orig = null, $cache = null) {
		if (is_null($orig)) {
			$start = $this->assets_css;
		} else {
			$start = array($orig => $cache);
		}

		print "REWRITE TASKS\n";
		print_r($start);

		foreach ($start as $orig => $cache) {

			print "###### CSS PARSING: $orig => $cache\n#####\n\n";

			$style = file_get_contents($this->cache_dir . "/" . $cache);

			$rw_base = parse_url($orig);
			$this->rw_base = $rw_base;
			#	print $style;
			# other styles rekursiv
			$style = preg_replace_callback(
				"!(((import\s+)?url)\(?['\"]?([-_/\.\w]+)['\"]?\))|((import)\s+['\"](.*?)['\"])!",
				array($this, "_urlcache_cb"),
				$style
			);

			# reset base and go on
			#	$this->rw_base=$rw_base;
			#	$style = preg_replace_callback("!(import)\s+['\"](.*?)['\"]!", array($this, "_urlcache_cb"), $style);

			# reset base
			$this->rw_base = $rw_base;

			# bg images
			#	$style = preg_replace_callback("!(url)\(['\"]?(.*?)['\"]?\)!", array($this, "_urlcache_cb"), $style);

			print "\n###### ENDE CSS PARSING: writing stylesheet $orig =>$cache\n#####\n\n";
			file_put_contents($this->cache_dir . "/" . $cache, $style);
		}
	}

	function _urlrewrite_cb($match) {
		return "import url(" . $this->urlrewrite($match[1]) . ")";
	}

	function _urlcache_cb($match) {
		print "++++ CSS-REWRITE {$this->rw_base}\n\n";
		print_r($match);
		if (trim($match[3]) == "import") $import = "import";
		else $import = "";
		return "$import url(" . $this->urlrewrite($match[4]) . ")";
	}

	function urlrewrite($url) {
		# javascript detection
		if (preg_match('!\(!', $url)) return $url;
		print "rewrite url $url .. ";

		# whitespace entfernen
		$url = preg_replace("/\s/", "", $url);

		$u = parse_url($url);

		# evtl. rekursives basis directory
		if ($this->rw_base) {
			$b = $this->rw_base;
			$base_is_file = true;
		} else {
			$b = $this->base;
			$base_is_file = false;
		}

		if (!$u['host']) {
			# in-page links
			if ($u['fragment']) return $url;

			$u['host'] = $b['host'];
			$u['scheme'] = $b['scheme'];

			print "O-PATH: {$u['path']}\n";
			# relative urls, ughh
			#if($this->pfx && $u['path']{0}!="/"){
			# bei lokalem caching
			#	$u['path']=$this->pfx."/".$u['path'];
			#}elseif($b['path'] && $u['path']{0}!="/"){

			# relative url?
			if ($b['path'] && $u['path'][0] != "/") {
				if ($base_is_file) {
					$basedir = dirname($b['path']);
				} else {
					$basedir = $b['path'];
				}
				#			   print "### RELATIVE PATH: ".$basedir."*".$u['path']." ==> ".unrealpath($basedir."/".$u['path'])."\n";
				#   $u['path']=$b['path']."/".$u['path'];
				$u['path'] = unrealpath($basedir . "/" . $u['path']);
			}
		}

		# jetzt haben wir die vollständige original URL

		# caching gewuenscht und asset erkannt?
		$rw = $this->cache_url($u);
		if (!$rw) {
			$rw = $this->_glue_url($u);
		}
		print "$rw\n";
		return $rw;
	}

	# parsed url
	function cache_url($u) {
		if (!$this->cache_dir) return false;

		if (is_string($u)) {
			$u = parse_url($u);
		}

		#print_r($u);

		if (preg_match("!\.(gif|jpg|png|css|js)$!", $u['path'], $mat)) {
			$orig = $this->_glue_url($u);
			$cache = $u['host'] . "/" . md5($orig) . "_" . basename($orig);
			$cache_url = $this->cache_url . "/" . $cache;
			$cache_file = $this->cache_dir . "/" . $cache;
			mkdirs(dirname($cache_file));
			#			print "fetching $orig to {$cache_file}\n";
			if (!file_exists($cache_file)) {
				$hdrs = array();
				#				print "u: ";print_r($u); print " - o: $orig\n"; die();
				xorc_fetch_url($orig, null, $cache_file, true, $hdrs);
				if ($hdrs['status'] == "404") {
					print "\n*****\n***** 404 w/ $orig\n*****\n";
				}
			}
			$this->assets[$orig] = $cache;
			if ($mat[1] == "css") {
				$this->assets_css[$orig] = $cache;
				# im zweiten schritt rekursiver durchlauf
				if ($this->rw_base) {
					print "\n\n:::::: FOUND CSS $orig \n\n";
					$this->rewrite_cache_styles($orig, $cache);
				}
			}
			return ($cache_url);
		} else {
			return false;
		}
	}

	function _glue_url($parsed) {
		#print "GLUE!\n";
		#print_r($this->base);
		#print_r($parsed);

		if (!is_array($parsed)) return false;
		$uri = isset($parsed['scheme']) ? $parsed['scheme'] . ':' . ((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';

		// z.b. //use.typekit.net/vgn1byw.js
		if (!$uri && $parsed['host']) {
			$uri = 'http://';
		}

		if ($this->opts['user']) {
			$uri .= $this->opts['user'] . ":" . $this->opts['pass'] . '@';
		} else {
			$uri .= isset($parsed['user']) ? $parsed['user'] . ($parsed['pass'] ? ':' . $parsed['pass'] : '') . '@' : '';
		}
		$uri .= isset($parsed['host']) ? $parsed['host'] : '';
		$uri .= isset($parsed['port']) ? ':' . $parsed['port'] : '';
		if (isset($parsed['path'])) {
			$uri .= (substr($parsed['path'], 0, 1) == '/') ? $parsed['path'] : '/' . $parsed['path'];
		}
		$uri .= isset($parsed['query']) ? '?' . $parsed['query'] : '';
		$uri .= isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
		return $uri;
	}


	function html() {
		$html = phpQuery::getDocument($this->docID);
		$html = phpQuery::markupToPHP($html);
		$html = $this->rec->after_scrape($html);
		return $html;
	}


	function set_base($url) {
		$this->baseurl = $url;
		$this->base = parse_url($this->baseurl);
	}

	function set_cache($dir, $url) {
		$this->cache_dir = $dir;
		$this->cache_url = $url;
	}

	function set_exitcode($exit) {
		$this->exit_append = $exit;
	}
}

class JQ_Scraper_Recipe {

	function play($master, $opts = array()) {
		// überschreiben!
	}

	# überschreiben mit str_replace o.ä. über html
	function after_scrape($html) {
		return $html;
	}

	# content scraping?
	#   ex: return "contact.html";
	function destination() {
		return false;
	}

	static function load($r, $dir) {
		$dir = self::dir($dir);
		require_once($dir . "/{$r}_recipe.class.php");
		$clas = "{$r}_recipe";
		return new $clas;
	}

	static function dir($dir) {
		if (!is_dir($dir)) {
			$dir = dirname($dir);
		}
		$dir .= "/scrape_recipes";
		return $dir;
	}

	static function list_all($dir) {
		$dir = self::dir($dir);

		$list = array();
		# print "$dir/*_recipe.class.php\n";
		foreach (glob("$dir/*_recipe.class.php") as $file) {
			#print $file;
			preg_match("!/([^/]*?)_recipe\.class\.php$!", $file, $mat);
			$list[] = $mat[1];
		}
		return $list;
	}

	static function install($dir) {
		$root = Xorcapp::$inst->approot;
		$var = $root . "/var";
		$view = $root . "/src/view";
		$dirs = array(
			$dir . "/scrape_recipes",
			$var . "/tmp",
			$var . "/layouts",
			$var . "/scraped_contents",
			$var . "/asset-cache",
		);
		$links = array(
			"../../var/layouts" => $root . "/src/view/layouts",
			"../var/asset-cache" => $root . "/public/asset-cache",
		);
		foreach ($dirs as $d) {
			`mkdir $d`;
		}
		foreach ($links as $l => $d) {
			`ln -s $l $d`;
		}
	}

	static function new_recipe($dir, $name) {
		require_once("xorc/div/naming.class.php");
		require_once("xorc/bin/scripts/_genlib.php");
		require_once("xorc/bin/scripts/_genlib2.php");

		$files = array();
		$tpl = resolve_template("scrape_recipe.class.php", array(
			'class-name' => name_for('model', $name),
		));

		$files[$name . "_recipe.class.php"] = $tpl;
		write_files($files, "$dir/scrape_recipes", "recipe class");
	}
}
