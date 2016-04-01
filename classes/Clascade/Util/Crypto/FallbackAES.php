<?php

namespace Clascade\Util\Crypto;

class FallbackAES extends \Clascade\StaticProxy
{
	public static function encryptCBC ($key, $iv, $data)
	{
		return static::provider()->encryptCBC($key, $iv, $data);
	}
	
	public static function decryptCBC ($key, $iv, $data)
	{
		return static::provider()->decryptCBC($key, $iv, $data);
	}
}
