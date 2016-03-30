<?php

namespace Clascade;

class Conf extends StaticProxy
{
	public static function exists ($path)
	{
		return static::provider()->exists($path);
	}
	
	public static function get ($path, $default=null)
	{
		return static::provider()->get($path, $default);
	}
	
	public static function set ($path, $value)
	{
		return static::provider()->set($path, $value);
	}
	
	public static function delete ($path)
	{
		return static::provider()->delete($path);
	}
	
	public static function reset ($path)
	{
		return static::provider()->reset($path);
	}
}
