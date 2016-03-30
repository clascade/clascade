<?php

namespace Clascade;

class Request extends StaticProxy
{
	public static function handle ()
	{
		return static::provider()->handle();
	}
	
	public static function isHTTPS ()
	{
		return static::provider()->isHTTPS();
	}
	
	public static function method ()
	{
		return static::provider()->method();
	}
	
	public static function urlBase ($userinfo=null)
	{
		return static::provider()->urlBase($userinfo);
	}
	
	public static function url ()
	{
		return static::provider()->url();
	}
	
	public static function path ($request_uri=null)
	{
		return static::provider()->path($request_uri);
	}
	
	public static function query ()
	{
		return static::provider()->query();
	}
	
	public static function getRedirectTo ($default=null, $params=null)
	{
		return static::provider()->getRedirectTo($default, $params);
	}
}
