<?php

namespace Clascade\Auth\UserStore;
use Clascade\Auth\User;

/**
 * Transient UserStore.
 *
 * This UserStore behaves as if all requested users exist, and it will
 * persist metadata only for the life of the request.
 */

class Transient extends \Clascade\Auth\UserStore
{
	public $transient_users = [];
	
	public function getUserByAuthIdent ($auth_ident)
	{
		return $this->getUserByID($auth_ident);
	}
	
	public function getUserByID ($user_id)
	{
		$user_id = User::normalizeAuthIdent($user_id);
		$meta = (isset ($this->transient_users[$user_id]) ? $this->transient_users[$user_id] : null);
		return make('Clascade\Auth\User', $user_id, $meta, $user_id);
	}
	
	public function getID ($auth_ident)
	{
		return User::normalizeAuthIdent($auth_ident);
	}
	
	public function createUserRaw ($auth_ident, $meta)
	{
		$auth_ident = User::normalizeAuthIdent($auth_ident);
		
		if (isset ($this->transient_users[$auth_ident]))
		{
			return false;
		}
		
		$this->transient_users[$auth_ident] = $meta;
		return true;
	}
	
	public function writeMeta ($user_id, $meta)
	{
		$user_id = User::normalizeAuthIdent($user_id);
		
		foreach ($meta as $field => $value)
		{
			$this->transient_users[$user_id][$field] = $value;
		}
	}
}
