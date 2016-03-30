<?php

namespace Clascade\Lang;

class Pack extends \Clascade\StaticProxy
{
	public static function get ($path, $default=null)
	{
		return static::provider()->get($path, $default);
	}
}
