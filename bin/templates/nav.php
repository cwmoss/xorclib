<?php

$APP_NAV=array(
<loop navigation>
	"<url>.php" => "<description>",
</loop>
);

print("<div id=\"navcontainer\">\n\t<ul id=\"navlist\">");
foreach($APP_NAV as $url=>$descr){
	$licss=$acss="";
	if($url==basename($THISURL)){
		$licss=' id="active"';
		$acss=' id="current"';
	}
	printf('<li%s><a href="%s"%s>%s</a></li>',
		$licss, $url, $acss, $descr);
}
print("</ul>\n</div>\n\n");

?>