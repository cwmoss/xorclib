<?php
/*

   PROJEKT="chattychan"
   SVNADDI="svn/chattychan"

   svn import -m'erster import' public http://svn.20sec.de/$SVNADDI/trunk/public
   svn import -m'erster import' src http://svn.20sec.de/$SVNADDI/trunk/src
   svn import -m'erster import' doc http://svn.20sec.de/$SVNADDI/trunk/doc
   svn import -m'erster import' bin http://svn.20sec.de/$SVNADDI/trunk/bin
   svn import -m'erster import' conf http://svn.20sec.de/$SVNADDI/trunk/conf
   svn import -m'erster import' db http://svn.20sec.de/$SVNADDI/trunk/db
   svn import -m'erster import' lib/$PROJEKT http://svn.20sec.de/$SVNADDI/trunk/lib/$PROJEKT

   mkdir before-import
   mv public src doc bin conf db lib before-import/

   svn co http://svn.20sec.de/$SVNADDI/trunk/ ./


   svn mv conf/${PROJEKT}_dev.ini conf/${PROJEKT}_dist.ini
   svn mv public/.htaccess public/dot.htaccess
   svn ci -m'confi+htaccess'
   cp conf/${PROJEKT}_dist.ini conf/${PROJEKT}_dev.ini
   cp public/dot.htaccess public/.htaccess
   
*/

$repo=$margs[0];
if(!$repo) $repo = xorc_ini("svn.url");
if(!$repo) die("please provide svn repository.\n");

$name=strtolower(get_class(Xorcapp::$inst));

$dir=array(
   "public", "src", "doc", "bin", "conf", "db", "lib/$name"
   );

`mv conf/{$name}_dev.ini conf/{$name}_dist.ini`;
`mv public/.htaccess conf/dot.htaccess`;

foreach($dir as $d){
   `svn import -m 'import first version' $d $repo/trunk/$d`;
}

`mkdir before-import`;

$dirs=join(" ", $dir);

`mv public src doc bin conf db lib before-import`;

`svn co $repo/trunk/ ./`;

`cp conf/{$name}_dist.ini conf/{$name}_dev.ini`;
`cp conf/dot.htaccess public/.htaccess`;



?>