<?php

namespace Clascade\Controllers;
use Clascade\Auth;

class Login
{
	public function get ($page)
	{
		$page->render('pages/login',
		[
			'redirect-to' => request_redirect_to(conf('general.login-dest')),
			'email' => $page->fieldValue('email'),
			'password' => $page->fieldValue('password'),
			'remember' => $page->fieldValue('remember'),
			'reset-password-url' => conf('common.urls.reset-password'),
		]);
	}
	
	public function post ($page)
	{
		$v = $page->validate('login');
		
		if ($v['remember'])
		{
			// Create an auth token and set a cookie so the
			// user will be remembered in subsequent sessions.
			
			$expire_time = time() + conf('common.auth.source.cookie.remember-me-time');
			$auth_token = Auth::getStore()->createAuthToken($v['user']->id, $expire_time);
			
			if ($auth_token !== false)
			{
				setcookie(conf('common.auth.source.cookie.name'), $auth_token, $expire_time, conf('common.urls.app-base').'/', '', request_is_https());
			}
		}
		
		// Log the user in and redirect.
		
		Auth::setUser($v['user'], 'Clascade\AuthSource\AuthSourceCookie');
		redirect(request_redirect_to(conf('common.urls.login-dest')));
	}
}
