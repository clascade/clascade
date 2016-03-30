<?php

namespace Clascade\Controllers;
use Clascade\Auth;

class Logout
{
	public function post ($page)
	{
		$redirect_url = Auth::endSession();
		
		if ($redirect_url === null)
		{
			$redirect_url = conf('common.urls.logout-dest');
		}
		
		redirect($redirect_url);
	}
}
