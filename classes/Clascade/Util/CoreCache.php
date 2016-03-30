<?php

namespace Clascade\Util;

class CoreCache extends \Clascade\StaticProxy
{
	public static function findCachablePaths ()
	{
		return static::provider()->findCachablePaths();
	}
	
	public static function collectCachablePaths ($base, $rel_path, &$cache, $layer_type=null, $section=null)
	{
		return static::provider()->collectCachablePaths($base, $rel_path, $cache, $layer_type, $section);
	}
}
