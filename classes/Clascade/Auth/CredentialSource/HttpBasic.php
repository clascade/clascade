<?php

namespace Clascade\Auth\CredentialSource;
use Clascade\Auth;
use Clascade\Router;

class HttpBasic extends \Clascade\Auth\CredentialSource
{
	public function __construct ()
	{
		$path = request_path();
		$app_base = app_base();
		
		if ($path == "{$app_base}/!http-basic-logout")
		{
			// We're handling a logout process (see the desist
			// comments below for information about how this works).
			
			if (!isset ($_SERVER['PHP_AUTH_USER']))
			{
				// Send a 401 so the browser knows the page wants HTTP authentication.
				
				Router::loadErrorPage('unauthorized', $path);
			}
			elseif ($_SERVER['PHP_AUTH_USER'] != 'logout')
			{
				// Tell the browser to request the page again with the username "logout".
				
				$scheme = request_is_https() ? 'https' : 'http';
				
				redirect("{$scheme}://logout@{$_SERVER['HTTP_HOST']}{$app_base}/!http-basic-logout");
			}
			else
			{
				// Redirect to the logout destination page, implicitly
				// telling the browser that it's now logged in as "logout".
				
				redirect(conf('common.urls.logout-dest'));
			}
			
			exit;
		}
	}
	
	public function prompt ($page)
	{
		$page->unauthorized();
	}
	
	public function authenticate ()
	{
		if (!isset ($_SERVER['PHP_AUTH_USER']))
		{
			return false;
		}
		
		if ($_SERVER['PHP_AUTH_USER'] == 'logout')
		{
			// Since the user "logout" has special meaning
			// for our logout process, let's make sure it's
			// never treated as a valid user.
			
			return false;
		}
		
		$user = Auth::attemptPassword('email:'.strtolower($_SERVER['PHP_AUTH_USER']), $_SERVER['PHP_AUTH_PW']);
		
		if ($user !== false)
		{
			Auth::setUser($user, $this);
			$this->used_auth = true;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Stop HTTP Basic persistance.
	 *
	 * The process to desist is somewhat complex. The trick is to
	 * coerce the browser to try authenticating with an invalid
	 * username, and we'll pretend that the username was accepted.
	 *
	 * This causes the browser to think it's now logged in under
	 * that invalid username, which we'll never accept on real
	 * pages. When we reject it, the browser will prompt the user
	 * for a new username/password.
	 *
	 * In order to avoid confusing browser prompts, the process has
	 * to be as follows:
	 *
	 * 1. Redirect to the special /!http-basic-logout pseudo-page.
	 * 2. The browser requests the page, without providing a
	 *    username/password, to see if the page requires
	 *    authentication.
	 * 3. We tell the browser that it does.
	 * 4. The browser requests the page again, with the user's
	 *    current username/password.
	 * 5. We implicitly accept the username/password by responding
	 *    with a non-401. What we send is a redirect to the same
	 *    /!http-basic-logout page, but specifying a fake username
	 *    ("logout") in the URL.
	 * 6. Since the browser already knows and accepts that that page
	 *    wants authentication, it should request it again with the
	 *    "logout" username, without requiring user confirmation.
	 * 7. We implicitly accept that username by redirecting the user
	 *    to another page. Note: On the server end, the user is
	 *    *not* actually logged in.
	 * 8. The browser now thinks it's successfully logged in as
	 *    "logout". However, on any page except /!http-basic-logout,
	 *    we wouldn't accept that as a valid login. The next time we
	 *    require authentication, those credentials will be
	 *    rejected, and the browser will ask the user to provide a
	 *    new username/password.
	 */
	
	public function desist ($used_auth)
	{
		if ($used_auth)
		{
			return app_base().'/!http-basic-logout';
		}
		
		return true;
	}
}
