<?php

namespace Clascade\View;

class Registry extends \Clascade\StaticProxy
{
	public static function get ($view)
	{
		return static::provider()->get($view);
	}
	
	public static function set ($view, $handler)
	{
		return static::provider()->set($view, $handler);
	}
	
	public static function nonce ()
	{
		return static::provider()->nonce();
	}
}
