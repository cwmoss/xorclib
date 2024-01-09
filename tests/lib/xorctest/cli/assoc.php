<?php

$a=new Author(1);

print_r($a->posts);

foreach($a->posts as $p){
   print $p->title."\n";
};

print "erster:";
echo $a->posts[0]->title;
exit;
# echo $rs[0]->title;
echo "\nCOUNT#";
echo count($a->posts);

echo "\nHAS?";
echo $a->has_posts();
/*
$p=new Post(array("title"=>"xnasowas ...", "body"=>"nobody", "type"=>"Post"));

$a->posts[]=$p;

$p2=new Post(array("title"=>"2x nasowas ...", "body"=>"nobody", "type"=>"Post"));
$p2->save();

$a->posts[]=$p2;
*/

$p3=new Post(array("title"=>"3x nasowas ...", "body"=>"nobody", "type"=>"Post"));
$a2=new Author(array("name"=>"robze2"));
$a2->id=5;
$a2->posts[]=$p3;
$a2->save();

#$p3->save();

echo count($a->posts);
?>