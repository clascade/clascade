<?php

namespace Clascade\Auth;

class CredentialSource
{
	/**
	 * Attempt to direct the client to provide authentication
	 * information.
	 *
	 * This will typically be achieved using a complete HTTP
	 * response, such as a redirect to a login page. If a solution
	 * is found, this function should throw either a
	 * RedirectionException or a PageLoadedException. The redirect()
	 * and $page->errorPage() functions do so automatically.
	 */
	
	public function prompt ($page)
	{
	}
	
	/**
	 * Attempt to authenticate the user from information present in
	 * the current request.
	 *
	 * This returns a boolean indicating whether the user was
	 * successfully authenticated.
	 */
	
	public function authenticate ()
	{
		return false;
	}
	
	/**
	 * Stop this source from automatically reauthenticating the user
	 * on subsequent requests.
	 *
	 * This function will return a boolean or a string.
	 *
	 * If it returns a boolean, this indicates whether it has
	 * successfully desisted. If it returns false, that indicates
	 * that the session may need to be locked to prevent automatic
	 * reauthentication.
	 *
	 * If it returns a string, this will be a URL that the user must
	 * visit to complete the desist. This URL will typically be fed
	 * to the redirect() function. If more than one credential
	 * source requires a redirect, this may cause the session to be
	 * locked instead.
	 *
	 * When called normally, this function will be passed a
	 * $used_auth parameter indicating whether this credential
	 * source was responsible for the user's current authentication.
	 */
	
	public function desist ($used_auth)
	{
		return false;
	}
}
