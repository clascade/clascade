<?php

namespace Clascade;

class Session extends StaticProxy
{
	public static function getProviderClass ()
	{
		return 'Clascade\Session\SessionProvider';
	}
	
	public static function exists ($path=null)
	{
		return static::provider()->exists($path);
	}
	
	public static function get ($path=null, $default=null)
	{
		return static::provider()->get($path, $default);
	}
	
	public static function set ($path=null, $value=null)
	{
		return static::provider()->set($path, $value);
	}
	
	public static function delete ($path=null)
	{
		return static::provider()->delete($path);
	}
	
	public static function getKey ()
	{
		return static::provider()->getKey();
	}
	
	public static function lock ()
	{
		return static::provider()->lock();
	}
	
	public static function isLocked ()
	{
		return static::provider()->isLocked();
	}
	
	public static function clear ()
	{
		return static::provider()->clear();
	}
	
	public static function createNew ()
	{
		return static::provider()->createNew();
	}
	
	public static function init ()
	{
		return static::provider()->init();
	}
	
	public static function close ()
	{
		return static::provider()->close();
	}
	
	public static function regen ()
	{
		return static::provider()->regen();
	}
	
	public static function csrfToken ()
	{
		return static::provider()->csrfToken();
	}
	
	public static function validateCSRFToken ($request_method)
	{
		return static::provider()->validateCSRFToken($request_method);
	}
}
