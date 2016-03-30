<?php

namespace Clascade\Middleware;
use Clascade\Router;

class SimpleAuth
{
	public function handle ()
	{
		Router::get(conf('common.urls.login'), 'Clascade\Controllers\Login');
		Router::post(conf('common.urls.login'), 'Clascade\Controllers\Login');
		
		Router::post(conf('common.urls.logout'), 'Clascade\Controllers\Logout');
		
		Router::get(conf('common.urls.reset-password'), 'Clascade\Controllers\ResetPassword');
		Router::post(conf('common.urls.reset-password'), 'Clascade\Controllers\ResetPassword');
	}
}
