<?php

namespace Clascade\Middleware;
use Clascade\Auth;

class AuthInitializer
{
	public function handle ()
	{
		Auth::init();
	}
}
