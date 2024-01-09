<?

#phpinfo();
#require_once("lessphp/lessc.inc.php");
#require_once("lessphp-git-top/lessc.inc.php");
require_once("lessphp-0.4.0/lessc.inc.php");
#require_once("/home/share/xorc2/lib/ext/lessphp/lessc.inc.php");

$input = $_SERVER["PATH_TRANSLATED"];

# $lc = new lessc($input);
$lc = new lessc;

if($_GET['compress']==1){
   $lc->setFormatter("compressed");
}
try{
   header("Content-Type: text/css");
#   print $lc->parse();
   print $lc->compileFile($input);
# print "/* lesshandler: ".__FILE__." */";
} catch (exception $ex){
   print "LESSC FEHLER:";
   print $ex->getMessage();
}


?>
