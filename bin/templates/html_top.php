<html>
	<head>
		<title><?=$app_title?> &gt;&gt; <?=$page_title?> &gt;&gt; <?=$action_title?></title>
		<link rel="stylesheet" href="xorcform.css" type="text/css">
		<link rel="stylesheet" href="app.css" type="text/css">
	</head>
	<body>
		<?php include("nav.php"); ?>
		<h2><?=$page_title?></h2>
		<?if($msg){?><p class="msg"><?=$msg?></p><?}?>
