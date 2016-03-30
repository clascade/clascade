<?php

namespace Clascade;

$methods = Router::findMethods($this->request_path);

if (empty ($methods))
{
	$this->notFound();
}

header("{$_SERVER['SERVER_PROTOCOL']} 405 Method Not Allowed");
header('Allow: '.implode(', ', $methods));
$this->render('pages/error/post-not-allowed');
