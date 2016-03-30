<?php

namespace Clascade\Util\Rand;

class Source extends \Clascade\StaticProxy
{
	public static function getBytes ($length)
	{
		return static::provider()->getBytes($length);
	}
}
