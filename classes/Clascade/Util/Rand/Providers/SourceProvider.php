<?php

namespace Clascade\Util\Rand\Providers;
use Clascade\Exception\RandomException;

class SourceProvider
{
	public function getBytes ($length)
	{
		$length = (int) $length;
		
		if ($length <= 0)
		{
			return '';
		}
		
		if (function_exists('random_bytes'))
		{
			try
			{
				$bytes = random_bytes($length);
			}
			catch (\Exception $e)
			{
				throw new RandomException('Exception thrown by random_bytes().', 0, $e);
			}
		}
		elseif (function_exists('mcrypt_create_iv'))
		{
			$bytes = mcrypt_create_iv($length, \MCRYPT_DEV_URANDOM);
		}
		elseif (file_exists('/dev/arandom'))
		{
			$bytes = file_get_contents('/dev/arandom', false, null, -1, $length);
			
			if ($bytes === false)
			{
				throw new RandomException('Error reading from /dev/arandom.');
			}
		}
		elseif (file_exists('/dev/urandom'))
		{
			$bytes = file_get_contents('/dev/urandom', false, null, -1, $length);
			
			if ($bytes === false)
			{
				throw new RandomException('Error reading from /dev/urandom.');
			}
		}
		else
		{
			$bytes = false;
		}
		
		if ($bytes === false || strlen($bytes) != $length)
		{
			throw new RandomException('Failed to gather sufficient random data.');
		}
		
		return $bytes;
	}
}
