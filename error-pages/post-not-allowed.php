<?php

namespace Clascade;

$methods = Router::findMethods($this->request_path);

if (empty ($methods))
{
	$this->notFound();
}

return view('pages/error/post-not-allowed',
[
	'methods' => $methods,
]);
