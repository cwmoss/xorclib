<?php
// Include Projax class and intialize it
include ("../../pquery/pquery.php");
$pquery= new PQuery();

//our controlling variable
$task=$_GET['task'] ?? 'view';

switch($task)
{
	case "ajax": echo 'Ajax Form Submitted<br />Field :'.$_GET['field'];
	return ;
	case "ajaxlink": echo 'Ajax link Clicked';
	return ;
	case "ajaxlinkc": echo 'Ajax link Clicked (callback)';
	return ;
	case "ajaxtime": echo 'Time : '.date("r",time());
	return ;
}

?>
<!--Include the Javascripts -->
<script src="../../pquery/js/jquery.js" type="text/javascript"></script>

<h1>Simple Ajax Demo</h1>
<hr />
<p><strong>form_remote_tag</strong> - Ajax form submission.</p>
<p><?=$pquery->form_remote_tag(['url'=>'index.php?task=ajax', 'update'=>'#idtoupdate']);?>
Field : <input type="text" name="field" /><br />
<input type="submit" /> 
</form>
</p>
<p>
<div id="idtoupdate">Update ME(Ajax form)</div>
</p>

<hr />
<p><strong>link_to_remote</strong> - Ajax through a Link.</p>
<p><?=$pquery->link_to_remote("Ajax Link",['url'=>'index.php?task=ajaxlink', 'update'=>'#idtoupdate1']);?>
</p>
<p>
<div id="idtoupdate1">Update ME(Ajax Link)</div>
</p>

<hr />
<p><strong>link_to_remote</strong> - Ajax through a Link ( with callbacks ).</p>
<p><?=$pquery->link_to_remote("Ajax Link",['url'=>'index.php?task=ajaxlinkc', 'update'=>'#idtoupdate2', 'success'=>'alert("Ajax was successful");']);?>
</p>
<p>
<div id="idtoupdate2">Update ME(Ajax Link w callaback)</div>
</p>

<hr />
<!-- new Date().getTime() for ie caching problem ; -->
<p><strong>periodically_call_remote</strong> - Update a from peridically ( keep session alive ).</p>
<script type="text/javascript">
 $(document).ready(function() {
 	 <?=$pquery->periodically_call_remote(['url'=>'index.php?task=ajaxtime&rnd="+new Date().getTime()+"', 'update'=>'#idtime', 'frequency'=>1]);?> 	
  });
</script>
<p>
<div id="idtime">Ajax Time</div>
</p>


