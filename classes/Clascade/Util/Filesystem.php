<?php

namespace Clascade\Util;

class Filesystem extends \Clascade\StaticProxy
{
	public function extension ($path)
	{
		return static::provider()->extension($path);
	}
	
	public static function contentType ($path, $default=null)
	{
		return static::provider()->contentType($path, $default);
	}
	
	public static function sniffType ($data, $default=null)
	{
		return static::provider()->sniffType($data, $default);
	}
}
