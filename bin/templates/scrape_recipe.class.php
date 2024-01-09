<?php

class <class-name>_recipe extends JQ_Scraper_Recipe{
   
   
   function play($master, $opts=array()){
      
      $name = $opts['name'];
      
      
      $base = "http://scrape.server.de/public";
      
      $master->set_base($base);
      
      $master->set_cache(Xorcapp::$inst->approot."/var/asset-cache/$name", Xorcapp::$inst->base."/asset-cache/$name");
 
      $master->rewrite_and_cache();

#      pq("div.content_regular")->php('print render_part("content_prepend");print $content; print render_part("content_append");');
#      pq("title")->php('print $title');
#      pq("h1")->php('print $title');

#      pq("div.content_teaser_column")->php('print render_part("sidebar");');
    #  pq("#navi_main_list_rightborder")->php('print render_part("navigation");');
#      pq("#navi_main_list_rightborder")->html('<h1>$title</h1>');
#      pq("#navi_main_list_rightborder")->appendPHP('print render_part("userinfo");');
      
   #   pq("#content")->before(pq("div.content_teaser_column"));
   #   pq("#navi_secondary")->remove();
#      pq("#navi_secondary")->php('print render_part("navigation");');
      
   #   pq("#navi_main_logo")->after("<h1>X-Bereich</h1>");
#      pq("#navi_main_logo")->remove();
      
#      pq("body")->appendPHP('print render_part("footer_append");');
   	
#   	pq("head")->prependPHP('print render_part("header_prepend");');
#      pq("head")->appendPHP('print render_part("header_append");print css_tag("css/app.css");');
   }
   
}

?>