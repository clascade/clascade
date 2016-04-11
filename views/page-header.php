<?php

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="<?=page()->langAttr() ?>">
	<head>
		<title><?=$title ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" type="text/css" href="/assets/styles/main.css">
		<?=page()->headElements() ?>
	</head>
	<body>
	<?=page()->reportStatus() ?>
