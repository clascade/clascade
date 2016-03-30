<?php

namespace Clascade;

class Response extends StaticProxy
{
	public static function redirect ($location, $status=null)
	{
		return static::provider()->redirect($location, $status);
	}
	
	public static function createRedirectTo ($url=null)
	{
		return static::provider()->createRedirectTo($url);
	}
	
	public static function sendFile ($path)
	{
		return static::provider()->sendFile($path);
	}
}
