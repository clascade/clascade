<?php

namespace Clascade\Auth\CredentialSource;
use Clascade\Auth;

class Posix extends \Clascade\Auth\CredentialSource
{
	public function authenticate ()
	{
		$user_info = posix_getpwuid(posix_geteuid());
		
		if ($user_info === false)
		{
			return false;
		}
		
		$user = Auth::getStore()->getUserByAuthIdent("posix:{$user_info['name']}");
		
		if ($result === false)
		{
			return false;
		}
		
		Auth::setUser($user, $this);
		return true;
	}
}
