<?php
$src=$margs[0];
$dest=$margs[1];

if(!$src) die("please provide source yaml file.\n");
if(!$dest){
   $info = pathinfo($src);
   $dest = $info['dirname']."/".$info['filename'].".db";
}

#XorcApp::use_yaml();
use Symfony\Component\Yaml\Yaml;

if(!class_exists('Symfony\Component\Yaml\Yaml')){
   $old = true;
   require_once(XORC_LIB_PATH."/text/sym-yaml/lib/sfYaml.php");
}else{
   $old = false;
}

#use Symfony\Component\Yaml\Parser;
#use Symfony\Component\Yaml\Dumper;

#$yaml = new Parser();
#$dumper = new Dumper();

print "converting $src ==> $dest\n";

#$data = Spyc::YAMLLoad(file_get_contents($src));
#$data = $yaml->parse(file_get_contents($src));

$data = parse_files($src);

file_put_contents($dest, serialize($data));

function parse_files($src){

   $docs = explode('------', file_get_contents($src));

   $data = array();

   if(count($docs) > 1){
      foreach($docs as $doc){
         // print "doc: $doc";
         if(!$doc) continue;
         list($name, $content) = explode("\n", $doc, 2);
         if(!trim($content)){
            $content = $name;
            $name = '__sys';
         } 
         $data[trim($name)] = parse($content);
         if($data['__sys']['import']){
            $import = $data['__sys']['import'];
            $data = merge_docs(parse_files(dirname($src).'/'.$import), $data);
         }
      }
   }else{
      $data = parse($docs[0]);
   }

   return $data;
}

function merge_docs($f1, $f2){
   $merge = [];
   $docs = array_unique(array_merge(array_keys($f1), array_keys($f2)));
   foreach($docs as $doc){
      $d1 = $f1[$doc]?:[];
      $d2 = $f2[$doc]?:[];
      $merge[$doc] = array_merge($d1, $d2);
   }
   return $merge;
}

function parse($content){
   if($old) $data = sfYaml::load($content);
   else $data = Yaml::parse($content);

   #print_r($data);

   if($data['$$templates$$']){
      print "  \$\$templates\$\$ found.\n";
      $tpldata = array();
      $tpl = $data['$$templates$$'];
      $yaml = "";
      foreach($tpl as $t){

         print "  - evaluating template \"{$t['name']}\"\n";

         #$y = Spyc::YAMLDump($t['tpl']);
         if($old) $y = sfYaml::dump($t['tpl']);
         else $y = Yaml::dump($t['tpl']);

         foreach(range(0, $t['iteration']-1) as $i){
            $yi = $y;
            foreach($t['vars'] as $v=>$arr){
               $yi=str_replace("<$v>", $arr[$i], $yi);
            }
            $yaml.=$yi;
         }
      }
      // print "    RESULT\n---\n".$yaml."\n---\n";
      unset($data['$$templates$$']);
      #$data = array_merge($data, Spyc::YAMLLoad($yaml));

      if($old) $datat = sfYaml::load($yaml);
      else $datat = Yaml::parse($yaml);
      
      $data = array_merge($data, $datat);
   }
   
   return $data;
}




/** help!
converts a yaml file to serialized php data
need: source file
optional: destination file

if no destination file is given source file ending will be removed and replaced with ".db"
ex: names.yaml ==> names.db
*/
?>