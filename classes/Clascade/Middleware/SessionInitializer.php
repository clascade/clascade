<?php

namespace Clascade\Middleware;
use Clascade\Router;
use Clascade\Session;

class SessionInitializer
{
	public function handle ()
	{
		if (!Session::init())
		{
			Router::sessionError();
			return false;
		}
	}
}