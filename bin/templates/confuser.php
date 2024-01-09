<?php
/* at first we include our app classes */
include("prepend.php");

/* now chekking if an ID was given */
$id=isset($_GET['id'])?$_GET['id']:(isset($_POST['id'])?$_POST['id']:false);

/* maybe someone will create a new object */
$new=isset($_GET['new'])?true:false;

/* we're in which viewmode? listmode or in editmode? */
$mode=($id || $new || XorcForm::was_submitted())?"edit":"list";

/* our message is empty - at first */
$msgL=array(1=>"Ok. Entry was saved",
	2=>"Ok. Entry was deleted"
	);
$msg="";

/* did we have a successful posting-and-redirect? */
if(isset($_GET['msg']) && isset($msgL[$_GET['msg']])){
	$msg=$msgL[$_GET['msg']];
}

/* a new object is created here */
$<model-var>=new <model-name>($id);

/* we define the fields in our form */
	$el=array(
<loop form-elements>
	"<name>"=>array(<definition>),
</loop>
	);
	
	$button=array(
		"reset"=>array("type"=>"reset", "display"=>"reset"),
		"save"=>array("type"=>"submit", "display"=>"save"),
		"delete"=>array("type"=>"submit", "display"=>"delete"),
	);

/* this is the controller logic */
if($mode=="edit"){
	$form=new XorcForm('<model-var>', array_merge($el, $button), array("action"=>$THISURL));
	$form->register_confirm("delete", "Do you really want to delete this entry?");
	if($form->action("save")){
		if($form->validate() && $form->validateMandatory()){
			$<model-var>->set($form->get());
			$<model-var>->save();
			$_app->redirect($THISURL."?msg=1");
		}else{
			/* maybe we have a failed CREATE attempt here */
			if(!$id){
				$form->hide(array("reset", "delete"));
			}
		}
	}elseif($form->action("reset")){
		$form->set($<model-var>->get());			
	}elseif($form->action("delete") && $form->is_confirmed("delete")){
		$<model-var>->delete();
		$_app->redirect($THISURL."?msg=2");
	}else{
	/* this is the first time for the editform so we populate objectdata or initialise default values*/
		if($new){
			/* defaultvalues for new objects? 
					like:
						$form->set("defaultkey", $defaultvalue);
					or:
						$form->set(array("def_key1"=>$def_val2, "dev_key2"=>$dev_val2));
			*/
			// we don't want reset and delete buttons here!
			$form->hide(array("reset", "delete"));
		}else{
			$form->set($<model-var>->get());
		}
	}
}

/* now comes the view part .. abit of html */
$page_title="$<model-name>";
$action_title=$mode;

include("html_top.php");

if($mode!="list"){

/* a little navigation here */
?><p>[<a href="<?=$THISURL?>">list</a>]</p>
<?

	$form->display_htmlcss();
}else{

/* a little navigation here */
?><p>[<a href="<?=$THISURL?>?new=1">new</a>]</p>
<?

	/* query the object table. return iterator */
	$it=$<model-var>->select();
	?>
	<table class="zeta">
	<? while($<model-var>=$it->next()){
		$c++;	$css=($c % 2)?"odd":"even";
	?>
	<tr class="<?=$css?>">
		<td><a href="<?=$THISURL?>?id=<?=$<model-var>->prop['<model-id>']?>">edit</a></td>
	<loop columns>
		<td><?=<value>?></td>
	</loop>
	</tr>
	<? } ?>
	</table>

<?

}

/* now that is the end my friend. html lights off. */
include("html_bottom.php");
?>