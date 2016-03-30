<?php

namespace Clascade\Auth;
use Clascade\Auth;

class User implements \ArrayAccess
{
	public $auth_ident;
	public $auth_type;
	public $auth_name;
	public $id;
	public $meta;
	
	public function __construct ($auth_ident, $meta=null, $id=null)
	{
		$auth_ident = static::normalizeAuthIdent($auth_ident);
		$meta = (array) $meta;
		
		$this->auth_ident = $auth_ident;
		$this->id = ($id !== false ? $id : null);
		$this->meta = $meta;
		
		list ($this->auth_type, $this->auth_name) = explode(':', $auth_ident, 2);
	}
	
	public function isGuest ()
	{
		return (empty ($this->auth_type) || $this->auth_type == 'unauthenticated');
	}
	
	public function getID ()
	{
		if ($this->id === null)
		{
			$this->id = Auth::getStore()->getID($this->auth_ident);
		}
		
		return $this->id;
	}
	
	/**
	 * Return a meta value.
	 */
	
	public function get ($key)
	{
		if (array_key_exists($key, $this->meta))
		{
			return $this->meta[$key];
		}
		
		return null;
	}
	
	/**
	 * Save meta values.
	 *
	 * If no arguments are given, this will save all meta values.
	 *
	 * If a list of field names is given, only those fields will be
	 * saved. Any fields not listed will be unaffected. You may
	 * provide the list as an array or as multiple arguments.
	 */
	
	public function save ($fields=null)
	{
		if ($fields === null)
		{
			$updates = $this->meta;
		}
		else
		{
			$updates = [];
			
			foreach (func_get_args() as $field_names)
			{
				foreach ((array) $field_names as $field_name)
				{
					if (array_key_exists($field_name, $this->meta))
					{
						$value = $this->meta[$field_name];
						$updates[$field_name] = ($value === null ? null : (string) $value);
					}
				}
			}
		}
		
		Auth::getStore()->writeMeta($this->getID(), $updates);
		
		if (user()->auth_ident == $this->auth_ident)
		{
			// This applies to the current session's user.
			// Update the session's cache.
			
			session_set('clascade.auth.user.meta', $this->meta);
		}
	}
	
	//== ArrayAccess ==//
	
	public function offsetExists ($offset)
	{
		return array_key_exists($offset, $this->meta);
	}
	
	public function offsetGet ($offset)
	{
		return $this->get($offset);
	}
	
	public function offsetSet ($offset, $value)
	{
		$this->meta[$offset] = ($value === null ? null : (string) $value);
	}
	
	public function offsetUnset ($offset)
	{
		if (array_key_exists($offset, $this->meta))
		{
			unset ($this->meta[$offset]);
		}
	}
	
	public static function normalizeAuthIdent ($auth_ident)
	{
		$auth_ident = preg_replace('/[\x00-\x1f\x7f]/', '', $auth_ident);
		$auth_ident = trim($auth_ident, ' ');
		$auth_ident = u_strtolower($auth_ident);
		return $auth_ident;
	}
}
