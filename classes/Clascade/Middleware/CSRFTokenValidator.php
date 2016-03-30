<?php

namespace Clascade\Middleware;
use Clascade\Router;
use Clascade\Session;

class CSRFTokenValidator
{
	public function handle ()
	{
		if (!Session::validateCSRFToken($_SERVER['REQUEST_METHOD']))
		{
			Router::sessionError();
			return false;
		}
	}
}