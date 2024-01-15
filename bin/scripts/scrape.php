<?php
require_once(XORC_LIB_PATH . "/xml/jq_scraper.class.php");

$rec_dir = dirname($tooldir); #."/scrape_recipes";

$recipe = $margs[0];
if (!$recipe) {
   new JQ_Scraper; # load classfile
   print "valid recipes:\n";
   foreach (JQ_Scraper_Recipe::list_all($rec_dir) as $r) {
      print("  $r\n");
   }
   exit;
} elseif ($recipe == "install") {
   JQ_Scraper_Recipe::install($rec_dir);
   exit;
} elseif ($recipe == "new") {
   $name = $margs[1];
   if (!$name) die("please provide a layout-name (recipe-name)\n");
   JQ_Scraper_Recipe::new_recipe($rec_dir, $name);
   exit;
}

array_shift($margs);
$ropts = array();

foreach ($margs as $arg) {
   #   print $arg."\n";

   list($key, $val) = explode("=", $arg, 2);
   if (!isset($val)) {
      $layouturl = $key;
   } elseif (preg_match("!^http://!", $key)) {
      $layouturl = $key . "=" . $val;
   } else {
      $ropts[$key] = $val;
   }
}
#print_r($ropts);

$name = $ropts['name'];
if (!$name) {
   $name = $recipe;
}

if ($opts['v']) {
   $verbose = 1;
   $ropts['verbose'] = 1;
}

if (!$layouturl) {
   $layouturl = xorc_ini("scrape.$recipe");
   if (!$layouturl) $layouturl = xorc_ini("scrape.url");
}

if (!$layouturl) {
   die("please provide a layouturl\n");
}

print "scraping page $layouturl\n";
print "options:\n";

foreach ($ropts as $k => $v) {
   print(" $k: $v\n");
}

$VAR = Xorcapp::$inst->approot . "/var";

$user = $pass = null;
if ($ropts['auth']) {
   list($user, $pass) = explode(":", $ropts['auth'], 2);
}

try {
   $sopts = array();
   if ($user) {
      $sopts['user'] = $user;
      $sopts['pass'] = $pass;
   }

   if ($ropts['kill'] == "no") {
      $sopts['force_cache_kill'] = false;
   } else {
      $sopts['force_cache_kill'] = true;
   }

   foreach (explode(" ", "proxy charset remove_xml fix_header iso2utf8 verbose") as $option) {
      if (isset($ropts[$option])) {
         $sopts[$option] = $ropts[$option];
      }
   }

   $master = new JQ_Scraper($layouturl, $sopts);
   # $master->set_base($layouturl);
   $master->play_recipe($recipe, $rec_dir, $ropts);

   $html = $master->html();
   print "OK.FIN.####\n";

   if ($verbose) {
      print "\n*** HTML after recipe ***\n";
      print $html;
   }

   if ($cdest = $master->destination($ropts)) {
      $tmp = "$VAR/tmp/$cdest";
      $dest = "$VAR/scraped_contents/$cdest";
   } else {
      $tmp = "$VAR/{$name}_layout.page.html";
      $dest = "$VAR/layouts/$name/_layout.page.html";
      $destdir = dirname($dest);
      if (!(file_exists($destdir) && is_dir($destdir))) {
         `mkdir $destdir`;
      }
   }

   print ">>> WRITING TMP: $tmp\n";
   file_put_contents($tmp, $html);

   if (filesize($tmp)) {
      print ">>> MOVING TO DESTINATION: $dest\n";
      rename($tmp, $dest);
   } else {
      print "!!! NOT MOVING TO DESTINATION: TEMPFILE $tmp is empty";
      unlink($tmp);
   }
} catch (Exception $e) {

   print "OPERATION CANCELLED\n";
   print $e->getMessage();
}
