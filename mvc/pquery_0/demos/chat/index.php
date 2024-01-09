<?php

// Include Projax class and intialize it
define('CHAT_FILE','chat.txt');
define('MAX_FILE_LEN',100);
define('FILE_SHIFT',80);

include ("../../pquery/pquery.php");
$pquery= new PQuery();

session_start(); //Session 
$task=$_GET['task'] ?? ''; 

$fp=fopen(CHAT_FILE,"r");
$chat_data=(filesize(CHAT_FILE)>0)?fread($fp,filesize(CHAT_FILE)):'';
fclose($fp);
$chat_arr=explode("\n",$chat_data);

if( $task == 'add' ) 
{
	//check size 
	if(count($chat_arr)>MAX_FILE_LEN) {
		$chat_arr=array_slice($chat_arr,FILE_SHIFT);
		$fp=fopen(CHAT_FILE,"wa");
		fwrite($fp,implode("\n",$chat_arr)."\n");
		fclose($fp);
	}

	$msg=$_GET['message'];
	if(!( strlen((string) $msg)>1 && strlen((string) $msg)<200))return;

	$fp=fopen(CHAT_FILE,"a");
	fwrite($fp,$msg."\n");
	fclose($fp);
	$chat_arr[]=$msg;
 	$task='refresh';
}

if( $task == 'refresh' ) 
{
	if(!isset($_SESSION['last_id'])) {
		$_SESSION['last_id']=count($chat_arr); 
	} else if ( !isset($chat_arr[$_SESSION['last_id']]) ) {
		$_SESSION['last_id']=$_SESSION['last_id']-FILE_SHIFT; 
	}
	
	for($i=$_SESSION['last_id'];$i<count($chat_arr);$i++) {
		if(!empty($chat_arr[$i]))echo $chat_arr[$i]."\r";
	}
	$_SESSION['last_id']=$i-1;
	return;
}

?>

<script src="../../pquery/js/jquery.js" type="text/javascript"></script>

<p>Simple ajax powered chat<br /></p>
<p>
<?=$pquery->form_remote_tag(['url'=>'index.php?task=add', 'success'=>'$("#chatarea").val(response+$("#chatarea").val());$("#chatmsg").val("");']);?>
 
    <textarea id="chatarea" name="textarea" style="width:400px;" rows="8" ></textarea><br />
    <input id="chatmsg" type="text" name="message" maxlength="200">
    <input type="submit" name="Submit" value="Submit">
	<input type="button"  value="Clear" onClick='$("#chatarea").text("");'>
</form>
</p>
<script type="text/javascript">
<?=$pquery->periodically_call_remote(['url'=>'index.php?task=refresh', 'success'=>'$("#chatarea").val(response+$("#chatarea").val());', 'frequency'=>2]);?>;

</script>
