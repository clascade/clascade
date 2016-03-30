<?php

namespace Clascade\Auth;

class UserStore
{
	/**
	 * Fetch a User by their auth ident.
	 *
	 * An auth ident is how Clascade likes to identify users, but it
	 * may be different from how the UserStore natively identifies
	 * users.
	 */
	
	public function getUserByAuthIdent ($auth_ident)
	{
		return false;
	}
	
	/**
	 * Fetch a User by their UserStore-native ID.
	 */
	
	public function getUserByID ($user_id)
	{
		return false;
	}
	
	/**
	 * Get the UserStore's local user ID that corresponds to the
	 * supplied auth ident.
	 */
	
	public function getID ($auth_ident)
	{
		return false;
	}
	
	/**
	 * Create a new user account.
	 *
	 * Note: If you want to generate a reset key (i.e., an account
	 * activation link) for the new account, you should consider
	 * calling createUserForKey() instead.
	 */
	
	public function createUser ($auth_ident, $meta)
	{
		return false;
	}
	
	/**
	 * Create a new user and return a newly-generated reset key.
	 */
	
	public function createUserForKey ($auth_ident, $meta)
	{
		$this->begin();
		
		// Generate a unique reset key that can be used to
		// access the new user.
		
		$reset_key = $this->createResetKey();
		
		if ($reset_key === false)
		{
			// This user store doesn't support reset keys.
			
			$this->rollback();
			return false;
		}
		
		list ($k, $key_hash) = $reset_key;
		$meta['reset-key'] = $key_hash;
		
		// The reset key will be valid for the configured number of hours.
		
		$meta['reset-time'] = time() + conf('common.auth.activation-time-hours') * 60 * 60;
		
		if (!$this->createUser($auth_ident, $meta))
		{
			$this->rollback();
			return false;
		}
		
		$this->commit();
		return $k;
	}
	
	/**
	 * Store the given meta fields for the user, identified by the
	 * UserStore-native user ID.
	 *
	 * $meta should be an array of key/value pairs for all of the
	 * meta fields that should be updated or created for the user.
	 *
	 * If there are any existing meta keys that match keys in the
	 * $meta array, those existing fields will be overwritten.
	 *
	 * If there are existing meta keys that do *not* appear in the
	 * supplied $meta array, those fields will be left as they are
	 * and will not be deleted. To delete an existing meta field,
	 * you should set its value to null in the $meta array.
	 */
	
	public function writeMeta ($user_id, $meta)
	{
	}
	
	//== Transactions ==//
	
	public function begin ()
	{
		return false;
	}
	
	public function commit ()
	{
		return false;
	}
	
	public function rollback ()
	{
		return false;
	}
	
	public function inTransaction ()
	{
		return false;
	}
	
	//== Reset keys ==//
	
	public function getUserByResetKey ($key)
	{
		return false;
	}
	
	public function createResetKey ()
	{
		return false;
	}
	
	//== Auth tokens ==//
	
	public function getUserByAuthToken ($token)
	{
		return false;
	}
	
	public function createAuthToken ($user_id, $expires=null)
	{
		return false;
	}
	
	public function clearAuthTokens ($user_id)
	{
	}
}
