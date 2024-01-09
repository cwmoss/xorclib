<?php
// Include Projax class and intialize it
include ("../../pquery/pquery.php");
$pquery= new PQuery();

?>
<!--Include the Javascripts -->
<script src="../../pquery/js/jquery.js" type="text/javascript"></script>

<h1>Simple Effects Demos</h1>
<hr />
<p><strong>visual_effect</strong> - Animation.</p>
<div id='animatefade' style="height:100px ; width : 200px; background-color : #ff0000; display: block;">
<?=$pquery->link_to_function('Click to make height = 50',$pquery->visual_effect('animate','#animatefade',['height'=>'50']));?><br />
<?=$pquery->link_to_function('Click to make height = 100',$pquery->visual_effect('animate','#animatefade',['height'=>'100']));?>
</div>

<hr />
<p><strong>visual_effect</strong> - Hide  / Show.</p>
<?=$pquery->link_to_function('hide',$pquery->visual_effect('hide','#hideshow'));?>&nbsp;&nbsp;
<?=$pquery->link_to_function('show',$pquery->visual_effect('show','#hideshow'));?>
<div id='hideshow' style="width : 200px; background-color : #ff0000; display: block; ">
<br />
<br />
<br />
<br />
</div>

<hr />
<p><strong>visual_effect</strong> - fadeIn / fadeOut.</p>
<?=$pquery->link_to_function('fadeOut',$pquery->visual_effect('fadeOut','#fade'));?>&nbsp;&nbsp;
<?=$pquery->link_to_function('fadeIn',$pquery->visual_effect('fadeIn','#fade'));?>


<div id='fade' style="background-color : #ff0000; display: block;">
<br />
<br />
<br />
<br />
</div>

<hr />
<p><strong>visual_effect</strong> - slideToggle with callback.</p>
<?=$pquery->link_to_function('slidetoggle',$pquery->visual_effect('slideToggle','#toggle',['callback'=>'alert("Callback");']));?>
<div id='toggle' style=" width : 200px; background-color : #ff0000; ">
<br />
<br />
<br />
<br />
</div>



