<?php

namespace Clascade\Auth\CredentialSource;
use Clascade\Auth;
use Clascade\Util\Str;

class Shibboleth extends \Clascade\Auth\CredentialSource
{
	public function authenticate ()
	{
		$vars = $this->getRequestArray();
		
		if (!isset ($vars['eppn']))
		{
			return false;
		}
		
		// Check whether the user has an account yet.
		
		$auth_ident = 'eppn:'.Str::lowerAscii($vars['eppn']);
		$store = Auth::getStore();
		$user = $store->getUserByAuthIdent($auth_ident);
		$new_meta = $this->getMetaFromRequest($vars);
		
		if ($user === false)
		{
			// No account exists. We'll automatically create one,
			// using the information provided in the request.
			
			$store->begin();
			
			if ($store->createUser($auth_ident, $new_meta))
			{
				// Get the newly-created user.
				
				$user = $store->getUserByAuthIdent($auth_ident);
			}
			
			if ($user === false)
			{
				// Something went wrong. Roll back.
				
				$store->rollback();
				return false;
			}
			
			$store->commit();
		}
		else
		{
			// The user already has an account. Let's check whether
			// we've received new profile information to update any
			// missing account fields.
			
			$updated = false;
			
			if (!isset ($user['email']) && isset ($new_meta['email']))
			{
				$user['email'] = $new_meta['mail'];
				$updated = true;
			}
			
			if ((!isset ($user['display-name']) || $user['display-name'] == $user['eppn']) && $new_meta['display-name'] != $user['eppn'])
			{
				$user['display-name'] = $new_meta['display-name'];
				$updated = true;
			}
			
			if ($updated)
			{
				$user->save('email', 'display-name');
			}
		}
		
		Auth::setUser($user, $this);
		return true;
	}
	
	public function desist ()
	{
		// If we're still getting the credentials from Shibboleth,
		// we'll have to lock the session to prevent the user from
		// getting automatically logged back in during subsequent
		// requests.
		
		$vars = $this->getRequestArray();
		return !isset ($vars['eppn']);
	}
	
	//== Local methods ==//
	
	public function getRequestArray ()
	{
		return $_SERVER;
	}
	
	public function getMetaFromRequest ($vars)
	{
		$meta = [];
		
		if (isset ($vars['mail']) && filter_var($vars['mail'], \FILTER_VALIDATE_EMAIL))
		{
			$meta['email'] = $vars['mail'];
		}
		
		if (isset ($vars['displayName']) && $vars['displayName'] != '')
		{
			$meta['display-name'] = $vars['displayName'];
		}
		else
		{
			$meta['display-name'] = $vars['eppn'];
		}
		
		return $meta;
	}
}
