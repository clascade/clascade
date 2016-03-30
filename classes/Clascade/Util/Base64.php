<?php

namespace Clascade\Util;

/**
 * Alternative Base64 encoder/decoder.
 *
 * This class helps you perform variants of base64 encoding, where the
 * character set or padding are different from the standard base64.
 *
 * If you're just doing a standard base64, you can call PHP's built-in
 * functions instead of using this class.
 */

class Base64
{
	const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	
	/**
	 * Base64-encode a string.
	 *
	 * $pad may be a boolean indicating whether padding should be
	 * present, or a string representing the character to use for
	 * padding (defaulting to "=").
	 *
	 * $chars may be a string up to 64 characters long, representing
	 * the replacement character set. If the value is shorter than
	 * 64 characters, the replacement pairs will be aligned at the
	 * *end* of the character set. So, if $chars is "_:", the
	 * replacements will happen as follows:
	 *   "+" => "_"
	 *   "/" => ":"
	 */
	
	public static function encode ($data, $pad=null, $chars=null)
	{
		if ($pad === null)
		{
			$pad = true;
		}
		
		if ($chars === null)
		{
			$chars = '';
		}
		
		// Do the standard encode.
		
		$data = base64_encode($data);
		
		// Handle custom character replacements, if needed.
		
		$standard_chars = substr(static::CHARS, -strlen($chars));
		
		if ($pad === false || strlen($pad) === 0)
		{
			// No padding.
			
			$data = rtrim($data, '=');
		}
		elseif ($pad !== true && $pad !== '=') {
			// Custom pad character. Add it to our character
			// replacement rule.
			
			$standard_chars .= '=';
			$chars .= substr($pad, 0, 1); // substr to ensure it's only one byte long.
		}
		
		if (strlen($chars) > 0)
		{
			// We have characters to replace. Do so.
			
			$data = strtr($data, $standard_chars, $chars);
		}
		
		return $data;
	}
	
	public static function decode ($data, $pad=null, $chars=null)
	{
		if ($pad === null)
		{
			$pad = true;
		}
		
		if ($chars === null)
		{
			$chars = '';
		}
		
		// Handle custom character replacements, if needed.
		
		$standard_chars = substr(static::CHARS, -strlen($chars));
		
		if (!is_bool($pad) && $pad !== '' && $pad !== '=')
		{
			// Custom pad character. Add it to our character
			// replacement rule.
			
			$standard_chars .= substr($pad, 0, 1);
			$chars .= '=';
		}
		
		if (strlen($chars) > 0)
		{
			// We have characters to replace. Do so.
			
			$data = strtr($data, $chars, $standard_chars);
		}
		
		// Do the standard decode.
		
		$data = base64_decode($data);
		return $data;
	}
	
	public static function encodeUnpadded ($data, $chars=null)
	{
		return static::encode($data, false, $chars);
	}
	
	public static function decodeUnpadded ($data, $chars=null)
	{
		return static::decode($data, false, $chars);
	}
	
	// URL-friendly characters.
	
	public static function encodeNice ($data)
	{
		return static::encode($data, false, '-_');
	}
	
	public static function decodeNice ($data)
	{
		return static::decode($data, false, '-_');
	}
}
