<?php
$files=$argv[1];
$version=$argv[2];

$channel="pear.chef";

$BIN1=<<<EBIN
<file name="xorc.sh" role="script">
 <tasks:replace from="@PHP-BIN@" to="php_bin" type="pear-config" />
 <tasks:replace from="@BIN-DIR@" to="bin_dir" type="pear-config" />
 <tasks:replace from="@PEAR-DIR@" to="php_dir" type="pear-config" />
</file>
EBIN;

$BIN2=<<<EBIN
<phprelease>
 <filelist>
  <install as="xorc" name="xorc/bin/xorc.sh" />
 </filelist>
</phprelease>
EBIN;


$pfx="xorc/";

if(!$version){
    $version=file("VERSION");
    $version=trim($version[0]);
}
if(!$files || !$version) die("you *must* provide a filelist *and* a version\n");

$xml="";

if(basename($files)=="package2.xml"){
// stage 2
  $xml=file_get_contents($files);
  $xml=str_replace('pear.php.net<', "$channel<", $xml);
  $xml=preg_replace("!<file.*?xorc\.sh.*?/>!", $BIN1, $xml);  
  $xml=preg_replace("!<phprelease />!", $BIN2, $xml);
  $out=fopen($files, "w");
  fwrite($out, $xml);
  fclose($out);
  
}else{
// stage 1

    foreach(file($files) as $f){
            
    //    120      120 rw           bin/templates/confuser.php
        if(preg_match("/^\s+\w+\s+\w+\s+\w+\s+(\w.*)$/", $f, $mat)){
            $f=$mat[1];
            if(is_dir($f)) continue;
            $b=basename($f);
            if($b[0]=='.') continue;
    
            $xml.= sprintf('   <file role="php" name="%s%s" />%s', $pfx, $f, "\n");
        }
        else print "skipping ..$f..\n";
    }

    $tpl=join("", file("bin/package.xml.tpl"));

    $tpl = str_replace("%CHANNEL%", $channel, $tpl);
    $tpl = str_replace("%VERSION%", $version, $tpl);
    $tpl = str_replace("%FILES%", $xml, $tpl);


    // print $tpl;
    $out=fopen("../package.xml", "w");
    fwrite($out, $tpl);
    fclose($out);
}
?>