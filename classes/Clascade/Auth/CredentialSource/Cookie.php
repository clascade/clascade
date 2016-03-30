<?php

namespace Clascade\Auth\CredentialSource;
use Clascade\Auth;
use Clascade\Response;

class Cookie extends \Clascade\Auth\CredentialSource
{
	public function prompt ($page)
	{
		$url = Response::createRedirectTo();
		redirect(conf('common.urls.login').'?'.rawurlencode(conf('common.field-names.redirect-to')).'='.urlencode($url));
	}
	
	public function authenticate ()
	{
		if (!isset ($_COOKIE['auth']))
		{
			return false;
		}
		
		$result = Auth::getStore()->getUserByAuthToken($_COOKIE['auth']);
		
		if ($result === false)
		{
			// Failed to authenticate from the cookie. Delete it.
			
			$this->desist();
			return false;
		}
		
		list ($user, $token) = $result;
		
		// The user has been successfully authenticated, and a
		// new token has been generated. Update the cookie with
		// the new token.
		
		$expire = time() + conf('common.auth.source.cookie.remember-me-time');
		setcookie('auth', $token, $expire, '/', '', request_is_https());
		
		Auth::setUser($user, $this);
		return true;
	}
	
	public function desist ()
	{
		if (isset ($_COOKIE['auth']))
		{
			unset ($_COOKIE['auth']);
			setcookie('auth', '', 1, '/', '', request_is_https());
		}
		
		return true;
	}
}
