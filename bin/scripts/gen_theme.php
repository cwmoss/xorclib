<?php
include_once(XORC_LIB_PATH . "/div/naming.class.php");
include_once(__DIR__ . "/_genlib.php");
include_once(__DIR__ . "/_genlib2.php");

$path = getcwd();

$name = $margs[0] ?? null;
if (!$name) die("you must provide a theme name.\n") .

   # aus seltsamen gründen muss ich das hier doppelt aufrufen, 
   #     scheinbar weil ich auf $margs zugreife???
   $path = getcwd();


// $themes = $path . "/src";
$themes = xorcapp::$inst->view->basedir;
$subdir = "";
if (str_ends_with(rtrim($themes, "/"), "/src")) {
   $subdir = "src/";
}

$dirs = array(
   "$path/public/themes",
   "$themes/themes",
   "$themes/themes/$name",
   "$themes/themes/$name/public",
   "$themes/themes/$name/public/gfx",
   "$themes/themes/$name/public/js",
   "$themes/themes/$name/public/css",
   "$themes/themes/$name/view",

);

foreach ($dirs as $d) {
   print(">> creating directory $d");
   if (is_dir($d)) print(" .. exists\n");
   else {
      mkdir($d, 0775);
      print " .. OK\n";
   }
}


$symlinks = array(
   // "$themes/themes/$name/public" => "$path/public/themes/$name",	
   "../../{$subdir}themes/$name/public" => "$path/public/themes/$name",
);

print ">> making symlinks for theme assets\n";

foreach ($symlinks as $src => $dest) {
   if (!file_exists($dest))
      `ln -sf $src $dest`;
}
