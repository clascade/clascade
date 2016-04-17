<?php

namespace Clascade\Util;

class CoreCache extends \Clascade\StaticProxy
{
	public static function genCache ()
	{
		return static::provider()->genCache();
	}
}
