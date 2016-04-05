<?php

namespace Clascade\Util;
use Clascade\Lang;

class Str
{
	//== Basic UTF-8 string functions ==//
	
	// NOTICE: Sometimes you need to operate on UTF-8 characters,
	// and sometimes you need to operate on raw bytes. UTF-8 string
	// functions are not appropriate for all use cases. Know what
	// kind of data you're working with, and use the correct tool
	// for the job.
	
	/**
	 * Return the number of characters in a UTF-8 string.
	 */
	
	public static function length ($string)
	{
		$byte_offset = 0;
		$count = 0;
		
		while (preg_match('/[\x80-\xff][\x80-\xbf]*/S', $string, $match, \PREG_OFFSET_CAPTURE, $byte_offset))
		{
			$codepoint = Unicode::codePointAtByte($match[0][0], 0, $sequence_length);
			$count += $match[0][1] - $byte_offset + strlen($match[0][0]) - $sequence_length + 1;
			$byte_offset = $match[0][1] + strlen($match[0][0]);
		}
		
		$count += strlen($string) - $byte_offset;
		return $count;
	}
	
	/**
	 * Return part of a UTF-8 string.
	 */
	
	public static function slice ($string, $start, $length=null)
	{
		$byte_start = Unicode::bytePos($string, $start);
		
		if ($byte_start === false)
		{
			return false;
		}
		
		if ($length === null)
		{
			$byte_end = strlen($string);
		}
		else
		{
			$byte_end = Unicode::bytePos($string, $length, $length >= 0 ? $byte_start : null);
			
			if ($byte_end === false)
			{
				if ($length < 0)
				{
					// The position is before the start of the string.
					
					return false;
				}
				else
				{
					// The position is beyond the end of $string.
					
					$byte_end = strlen($string);
				}
			}
			elseif ($byte_end < $byte_start)
			{
				if ($start < 0)
				{
					return '';
				}
				else
				{
					return false;
				}
			}
		}
		
		return substr($string, $byte_start, $byte_end - $byte_start);
	}
	
	public static function charAt ($string, $pos)
	{
		return static::slice($string, $pos, 1);
	}
	
	/**
	 * Return the position (in characters) of the first occurrence
	 * of a substring in a UTF-8 string.
	 */
	
	public static function indexOf ($haystack, $needle, $offset=null)
	{
		if (Unicode::isContinuationByte($needle))
		{
			return false;
		}
		
		$offset = ($offset === null ? 0 : Unicode::bytePos($haystack, $offset));
		$pos = strpos($haystack, $needle, $offset);
		
		if ($pos === false)
		{
			return false;
		}
		
		return Unicode::charPos($haystack, $pos);
	}
	
	/**
	 * Return the position (in characters) of the last occurrence
	 * of a substring in a UTF-8 string.
	 */
	
	public static function lastIndexOf ($haystack, $needle, $offset=null)
	{
		if (Unicode::isContinuationByte($needle))
		{
			return false;
		}
		
		if ($offset === null)
		{
			$byte_offset = 0;
		}
		else
		{
			$byte_offset = Unicode::bytePos($haystack, $offset);
			
			if ($byte_offset === false)
			{
				return false;
			}
			
			if ($offset < 0)
			{
				$byte_offset -= strlen($haystack);
			}
		}
		
		$pos = strrpos($haystack, $needle, $byte_offset);
		
		if ($pos === false)
		{
			return false;
		}
		
		return Unicode::charPos($haystack, $pos);
	}
	
	/**
	 * Returns whether a string contains a substring.
	 *
	 * This function is suitable for both UTF-8 strings and byte
	 * strings.
	 */
	
	public static function contains ($haystack, $needle)
	{
		return (strpos($haystack, $needle) !== false);
	}
	
	/**
	 * Returns whether a string starts with a substring.
	 *
	 * This function is suitable for both UTF-8 strings and byte
	 * strings.
	 */
	
	public static function startsWith ($haystack, $needle)
	{
		if (strlen($needle) == 0)
		{
			return true;
		}
		
		return (substr($haystack, 0, strlen($needle)) === $needle);
	}
	
	/**
	 * Returns whether a string ends with a substring.
	 *
	 * This function is suitable for both UTF-8 strings and byte
	 * strings.
	 */
	
	public static function endsWith ($haystack, $needle)
	{
		if (strlen($needle) == 0)
		{
			return true;
		}
		
		return (substr($haystack, -strlen($needle)) === $needle);
	}
	
	/**
	 * Split a UTF-8 string into an array of strings, each
	 * $split_length characters long.
	 *
	 * The last element might be fewer characters.
	 */
	
	public static function chunk ($string, $split_length=null)
	{
		if ($split_length === null)
		{
			$split_length = 1;
		}
		
		$chunks = [];
		$byte_pos = 0;
		$char = Unicode::charAtByte($string, $byte_pos, $sequence_length);
		$key = -1;
		$key_col = $split_length;
		
		while ($sequence_length > 0)
		{
			if ($key_col >= $split_length)
			{
				// Begin the next chunk.
				
				++$key;
				$key_col = 1;
				$chunks[$key] = $char;
			}
			else
			{
				// Append the character to the current chunk.
				
				++$key_col;
				$chunks[$key] .= $char;
			}
			
			// Get the next character.
			
			$byte_pos += $sequence_length;
			$char = Unicode::charAtByte($string, $byte_pos, $sequence_length);
		}
		
		return $chunks;
	}
	
	/**
	 * Call a function for each character in the UTF-8 string.
	 */
	
	public static function apply ($string, $callback)
	{
		$byte_pos = 0;
		$char_pos = 0;
		$char = Unicode::charAtByte($string, $byte_pos, $sequence_length);
		
		while ($sequence_length > 0)
		{
			if (call_user_func($callback, $char, $char_pos, $byte_pos, $sequence_length) === false)
			{
				break;
			}
			
			$byte_pos += $sequence_length;
			++$char_pos;
			$char = Unicode::charAtByte($string, $byte_pos, $sequence_length);
		}
	}
	
	/**
	 * Returns whether two strings are equal, in a manner that
	 * guards against timing attacks.
	 *
	 * Important: If it's possible for the two strings to have
	 * different lengths, then the parameter order matters! The time
	 * this function takes to complete depends on the length of the
	 * second parameter. So, if you want to avoid leaking the size
	 * of sensitive data, you should put that sensitive data in the
	 * first parameter.
	 *
	 * If $known_string has a length of 0, this function will short-
	 * circuit, and the fact that it has a length of 0 will be
	 * leaked. This is still better than other implementations that
	 * always leak information about whether the lengths are equal.
	 */
	
	public static function equals ($known_string, $user_string)
	{
		$known_string = (string) $known_string;
		$known_len = strlen($known_string);
		
		$user_string = (string) $user_string;
		$user_len = strlen($user_string);
		
		// Record an error if the lengths of the strings differ,
		// but continue doing the same work we we would have
		// done if they had been the same.
		
		$error = $known_len ^ $user_len;
		
		// If $known_len is 0, we have no choice but to short-
		// circuit. The return value will still properly
		// indicate whether the strings were equal.
		
		if ($known_len > 0)
		{
			// We will iterate through each byte in $user_string. If
			// the byte differs from the corresponding byte in
			// $known_string, bits in $error will get set to 1.
			//
			// If $known_string is shorter than $user_string, $i
			// will end up beyond the end of $known_string. So, when
			// looking up the corresponding byte in $known_string,
			// we mod $i by the length of $known_string. Mod should
			// be a constant time operation.
			
			for ($i = $user_len - 1; $i >= 0; --$i)
			{
				$error |= ord($known_string[$i % $known_len]) ^ ord($user_string[$i]);
			}
		}
		
		return ($error === 0);
	}
	
	public static function begin ($string, $prefix)
	{
		if (static::startsWith($string, $prefix))
		{
			return $string;
		}
		
		return $prefix.$string;
	}
	
	public static function finish ($string, $suffix)
	{
		if (static::endsWith($string, $suffix))
		{
			return $string;
		}
		
		return $string.$suffix;
	}
	
	public static function incSuffix ($string, $amount=null)
	{
		if ($amount === null)
		{
			$amount = 1;
		}
		
		if (preg_match('/\d+$/', $string, $match))
		{
			$suffix = $match[0];
			$string = substr($string, 0, -strlen($suffix));
		}
		else
		{
			$suffix = 0;
		}
		
		$suffix += $amount;
		return $string.($suffix >= 0 ? $suffix : '0');
	}
	
	public static function decSuffix ($string, $amount=null)
	{
		if ($amount === null)
		{
			$amount = 1;
		}
		
		return static::incSuffix($string, -$amount);
	}
	
	//== Letter case =//
	
	/**
	 * Uppercase a UTF-8 string.
	 */
	
	public static function upper ($string)
	{
		$string = strtoupper($string);
		return Unicode::mapHighCharsByConf($string, 'unicode/to-upper');
	}
	
	/**
	 * Lowercase a UTF-8 string.
	 */
	
	public static function lower ($string)
	{
		$found_upper_sigma = false;
		$string = strtolower($string);
		$string = Unicode::mapHighCharsByConf($string, 'unicode/to-lower', null,
		[
			0x03a3 => function ($char) use (&$found_upper_sigma)
			{
				$found_upper_sigma = true;
				return $char;
			},
		]);
		
		if ($found_upper_sigma)
		{
			// Handle final sigmas.
			
			$string = preg_replace('/(?<=[\pL\pN])\x{03a3}(?=[^\pL\pN]|$)/u', "\xcf\x82", $string);
			$string = str_replace("\xce\xa3", "\xcf\x83", $string);
		}
		
		return $string;
	}
	
	/**
	 * Title case the first character in each word of a UTF-8
	 * string, except certain words.
	 *
	 * This function doesn't change any existing uppercase
	 * characters to lowercase. If you need it to, you might want to
	 * call Str::lower() on the string before calling this.
	 */
	
	public static function title ($string, $exceptions=null)
	{
		if ($exceptions === null)
		{
			$exceptions = conf('title-case-exceptions.words');
		}
		
		$string = static::titleWords($string, $exceptions);
		$string = static::ucFirst($string);
		return $string;
	}
	
	/**
	 * Make each character in a UTF-8 string title case.
	 *
	 * Note: If you're trying to title case a string of text, you
	 * probably want to use Str::title() or Str::titleWords()
	 * instead.
	 *
	 * For most characters, this is the same a Str::upper(). There
	 * are some exceptions, such as Unicode characters that consist
	 * of multiple letters within one character. In these cases, the
	 * character will be replaced by a title cased version of that
	 * character (where the first letter is uppercase and the rest
	 * are lowercase).
	 *
	 * This function is most commonly used on a single character.
	 * For example. Str::titleWords() calls this on the first
	 * character of each word.
	 */
	
	public static function titleChars ($string)
	{
		$string = strtoupper($string);
		return Unicode::mapHighCharsByConf($string, 'unicode/to-title');
	}
	
	/**
	 * Lowercase the first character of a UTF-8 string.
	 */
	
	public static function lcFirst ($string)
	{
		$first_char = static::slice($string, 0, 1);
		return static::lower($first_char).substr($string, strlen($first_char));
	}
	
	/**
	 * Uppercase the first character of a UTF-8 string.
	 */
	
	public static function ucFirst ($string)
	{
		$first_char = static::slice($string, 0, 1);
		return static::upper($first_char).substr($string, strlen($first_char));
	}
	
	/**
	 * Title case the first character in each word of a UTF-8
	 * string.
	 *
	 * For the purposes of this function, a word begins with the
	 * first letter or number to appear in the string or after a
	 * prior whitespace or Unicode separator character. Note that
	 * this logic differs from the Unicode standard for word breaks,
	 * which is more complex.
	 *
	 * This function doesn't change any existing uppercase
	 * characters to lowercase. If you need it to, you might want to
	 * call Str::lower() on the string before calling this.
	 */
	
	public static function titleWords ($string, $exceptions=null)
	{
		$string = Unicode::replaceBadChars($string);
		
		if ($exceptions !== null)
		{
			if (!is_array($exceptions))
			{
				$exceptions = preg_split('/\s*,\s*/', $exceptions);
			}
			
			if (empty ($exceptions))
			{
				$exceptions = '';
			}
			else
			{
				foreach ($exceptions as $key => $value)
				{
					$exceptions[$key] = preg_quote($value, '/');
				}
				
				$exceptions = '(?!(?:'.implode('|', $exceptions).')(?:[^\pL\pN]|$))';
			}
		}
		
		return preg_replace_callback('/((?:^|[\x09-\x0d\pZ])[^\pL\pN]*)'.$exceptions.'([\pL\pN])/u', function ($match)
		{
			$codepoint = Unicode::codePointAtByte($match[2]);
			
			// Determine the conf file for this codepoint.
			
			$conf_file = 'unicode/to-title/'.Math::dechex($codepoint >> 8, 4);
			
			// Attempt to map the codepoint to a replacement string.
			
			$replacement = conf("{$conf_file}.".($codepoint & 0xff));
			
			if ($replacement === null)
			{
				return $match[0];
			}
			
			return $match[1].$replacement;
		}, $string);
	}
	
	//== More reliable alternatives to ctype_* functions ==//
	
	/**
	 * Return whether the string consists entirely of one or more
	 * alphanumeric characters.
	 */
	
	public static function isAlnum ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/[^\pL\p{Nd}]/u' : '/[^0-9A-Za-z]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * letters.
	 */
	
	public static function isAlpha ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/\PL/u' : '/[^A-Za-z]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * ASCII characters.
	 */
	
	public static function isAscii ($string)
	{
		$pattern = '/[^\x00-\x7f]/';
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * control characters.
	 */
	
	public static function isCtrl ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/\P{Cc}/u' : '/[^\x00-\x1f\x7f]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * decimal digits.
	 */
	
	public static function isDigit ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/\P{Nd}/u' : '/[^0-9]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * printable characters, excluding space.
	 */
	
	public static function isGraph ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/[^\pL\pN\pP\pS]/u' : '/[^\x21-\x7e]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * lowercase letters.
	 */
	
	public static function isLower ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/^\P{Ll}+$/u' : '/[^a-z]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * printable characters, including space.
	 */
	
	public static function isPrint ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/[^\pL\pN\pP\pS\p{Zs}]/u' : '/[^\x20-\x7e]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * punctuation characters.
	 */
	
	public static function isPunct ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/\PP/u' : '/[^\x21-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * whitespace characters.
	 */
	
	public static function isSpace ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/[^\x09-\x0d\pZ]/u' : '/[^\x09-\x0d ]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * uppercase letters.
	 */
	
	public static function isUpper ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/\P{Lu}/u' : '/[^A-Z]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of one or more
	 * hexadecimal digits.
	 */
	
	public static function isXdigit ($string, $include_unicode_properties=null)
	{
		$pattern = ($include_unicode_properties ? '/[^0-9A-Fa-f\x{ff10}-\x{ff19}\x{ff21}-\x{ff26}\x{ff41}-\x{ff46}]/u' : '/[^0-9A-Fa-f]/');
		return strlen($string) > 0 && !preg_match($pattern, $string);
	}
	
	/**
	 * Return whether the string consists entirely of zero or more
	 * horizontal whitespace characters.
	 *
	 * Note: Unlike other is* functions, this will also return true
	 * if the string has a length of 0.
	 */
	
	public static function isBlank ($string, $include_unicode_properties=null)
	{
		if ($include_unicode_properties)
		{
			return !preg_match('/[^\t\p{Zs}]/u', $string);
		}
		else
		{
			return strspn($string, "\t ") === strlen($string);
		}
	}
	
	public static function isSimpleInt ($string, $allow_negative=null)
	{
		$sign = ($allow_negative ? '-?' : '');
		return (bool) preg_match("/^{$sign}(?:0|[1-9][0-9]*)\$/", $string);
	}
	
	public static function isSimpleNumber ($string, $allow_negative=null)
	{
		$sign = ($allow_negative ? '-?' : '');
		return (bool) preg_match("/^{$sign}(?:(?:0|[1-9][0-9]*)(?:\.[0-9]+)?|\.[0-9]+)\$/", $string);
	}
	
	public static function isEmail ($string)
	{
		return (bool) filter_var($string, \FILTER_VALIDATE_EMAIL);
	}
	
	//== Padding ==//
	
	public static function padBoth ($string, $pad_length, $pad_string=null)
	{
		return str_pad($string, $pad_length, ($pad_string === null ? ' ' : $pad_string), \STR_PAD_BOTH);
	}
	
	public static function padLeft ($string, $pad_length, $pad_string=null)
	{
		return str_pad($string, $pad_length, ($pad_string === null ? ' ' : $pad_string), \STR_PAD_LEFT);
	}
	
	public static function padRight ($string, $pad_length, $pad_string=null)
	{
		return str_pad($string, $pad_length, ($pad_string === null ? ' ' : $pad_string), \STR_PAD_RIGHT);
	}
	
	//== Identifier style conversion ==//
	
	public static function camel ($string, $delimiter=null)
	{
		if ($delimiter === null)
		{
			$delimiter = '[\s_-]';
		}
		elseif (is_array($delimiter))
		{
			foreach ($delimiter as $key => $value)
			{
				$delimiter[$key] = preg_quote($value, '/');
			}
			
			$delimiter = implode('|', $delimiter);
		}
		else
		{
			$delimiter = preg_quote($delimiter, '/');
		}
		
		return preg_replace_callback("/(?:{$delimiter})+(.?)/u", function ($match)
		{
			return static::upper($match[1]);
		}, $string);
	}
	
	public static function studly ($string, $delimiter=null)
	{
		if ($delimiter === null)
		{
			$delimiter = '[\s_-]';
		}
		elseif (is_array($delimiter))
		{
			foreach ($delimiter as $key => $value)
			{
				$delimiter[$key] = preg_quote($value, '/');
			}
			
			$delimiter = implode('|', $delimiter);
		}
		else
		{
			$delimiter = preg_quote($delimiter, '/');
		}
		
		return preg_replace_callback("/(?:^|(?:{$delimiter})+)(.?)/u", function ($match)
		{
			return static::upper($match[1]);
		}, $string);
	}
	
	public static function snake ($string, $delimiter=null)
	{
		if ($delimiter === null)
		{
			$delimiter = '_';
		}
		
		return preg_replace_callback('/\p{Lu}/', function ($match) use ($delimiter)
		{
			return $delimiter.static::lower($match[0]);
		}, $string);
	}
	
	public static function snakeFromStudly ($string, $delimiter=null)
	{
		return static::snake(static::lcFirst($string), $delimiter);
	}
	
	//== Binary arithmetic ==//
	
	public static function incBin ($string, $amount=null, $flags=null, &$overflow=null)
	{
		if ($amount === null)
		{
			$amount = 1;
		}
		else
		{
			$amount = (int) $amount;
		}
		
		$constant_time = (isset ($flags['constant-time']) && $flags['constant-time']);
		
		// Set the loop parameters based on the desired endianness.
		
		if (isset ($flags['little-endian']) && $flags['little-endian'])
		{
			$i = 0;
			$end = strlen($string);
			$delta = 1;
		}
		else
		{
			$i = strlen($string) - 1;
			$end = -1;
			$delta = -1;
		}
		
		for (; $i != $end; $i += $delta)
		{
			$amount += ord($string[$i]);
			$string[$i] = chr($amount & 0xff);
			$amount >>= 8;
			
			if (!$constant_time && $amount == 0)
			{
				break;
			}
		}
		
		$overflow = $amount;
		return $string;
	}
	
	public static function decBin ($string, $amount=null, $flags=null, &$overflow=null)
	{
		return static::incBin($string, ($amount === null ? -1 : -$amount), $flags, $overflow);
	}
	
	//== Miscellaneous ==//
	
	public static function plural ($singular, $num=null, $plural=null, $lang=null)
	{
		if ($num === null)
		{
			$num = 'other';
		}
		elseif (is_array($num) || $num instanceof \Countable)
		{
			$num = count($num);
		}
		
		if ($lang === null)
		{
			$lang = lang();
		}
		
		if ($plural === null)
		{
			$choices = Lang::getPluralChoicesByWord($singular, $lang);
			$translation = Lang::selectTranslation($choices, $num, $lang);
			
			// Attempt to restore the original case.
			
			if (!preg_match('/\p{Ll}/u', $singular))
			{
				// The original had no lowercase characters.
				
				$translation = static::upper($translation);
			}
			elseif (preg_match('/^\p{Lu}/u', $singular))
			{
				// The first character is uppercase.
				
				$translation = static::ucFirst($translation);
			}
			
			return $translation;
		}
		
		$choices = Escape::translation($singular);
		$plural = (array) $plural;
		
		foreach ($plural as $value)
		{
			$choices .= '|'.Escape::translation($value);
		}
		
		return Lang::selectTranslation($choices, $num, $lang);
	}
	
	/**
	 * Transliterate a UTF-8 string into the ASCII-compatible
	 * character set.
	 *
	 * This function only supports character-by-character
	 * transliteration. Individual Unicode characters are mapped to
	 * ASCII strings without any context-specific rules.
	 *
	 * If no transliteration rule is defined for a particular
	 * character, $char_fallback will be used instead. This defaults
	 * to "?", but you can set it to an empty string if you want
	 * these characters to just be ignored.
	 *
	 * Invalid UTF-8 bytes in the input string are silently ignored.
	 */
	
	public static function ascii ($string, $char_fallback=null)
	{
		if ($char_fallback === null)
		{
			$char_fallback = '?';
		}
		
		return Unicode::mapHighCharsByConf($string, 'unicode/to-ascii', $char_fallback);
	}
	
	/**
	 * Generate a URL-friendly representation ("slug") of a string.
	 *
	 * The result will normally be a string containing only lower-
	 * case letters (a-z), digits (0-9), and possibly single hyphens
	 * ("-") between other characters. If you supply a $default or
	 * $number_prefix parameter, these will be used without
	 * filtering. If you supply a different $separator string, that
	 * will be used instead of hyphens.
	 *
	 * An attempt will be made to transliterate non-ASCII Unicode
	 * characters into the [a-z0-9-] character set. The default
	 * values for $default and $number_prefix will ensure that the
	 * result is non-empty and always begins with a letter.
	 *
	 * By default, the result will be limited to 60 characters,
	 * unless the $default value is used and it is longer than 60
	 * characters. The limit can be changed by setting $max_length
	 * to a different value. A value of -1 means no limit.
	 */
	
	public static function slug ($string, $separator=null, $max_length=null, $default=null, $number_prefix=null)
	{
		if ($separator === null)
		{
			$separator = '-';
		}
		
		$working_separator = str_repeat('-', strlen($separator));
		
		if ($max_length === null)
		{
			$max_length = 60;
		}
		
		if ($default === null)
		{
			$default = 'p';
		}
		
		if ($number_prefix === null)
		{
			$number_prefix = "{$default}{$working_separator}";
		}
		
		$string = static::ascii($string);
		$string = strtolower($string);
		$string = preg_replace('/[^a-z0-9_\-\s]+/', '', $string);
		$string = preg_replace('/[^a-z0-9]+/', $working_separator, $string);
		$string = ltrim($string, '-');
		
		if ($string == '')
		{
			return $default;
		}
		
		if (static::isDigit($string))
		{
			$string = "{$number_prefix}{$string}";
		}
		
		if ($max_length != -1)
		{
			$string = substr($string, 0, $max_length);
		}
		
		$string = rtrim($string, '-');
		
		if ($separator != '-')
		{
			$string = str_replace($working_separator, $separator, $string);
		}
		
		return $string;
	}
	
	public static function template ($template, $context_vars=null, $allow_conf_refs=null)
	{
		if ($allow_conf_refs === null)
		{
			$allow_conf_refs = true;
		}
		
		$context_vars = (array) $context_vars;
		$out = '';
		$len = strlen($template);
		$pos = 0;
		
		while ($pos < $len)
		{
			$next_pos = strpos($template, '%{', $pos);
			
			if ($next_pos === false)
			{
				// No more variables remaining. Copy the
				// rest of the template to the output.
				
				$out .= substr($template, $pos);
				break;
			}
			
			// Copy everything up to the next "%{".
			
			$out .= substr($template, $pos, $next_pos - $pos);
			$pos = $next_pos + 2;
			
			// Read the name.
			
			$seg_len = strcspn($template, '}', $pos);
			$var_name = substr($template, $pos, $seg_len);
			$pos += $seg_len + 1;
			
			if (substr($var_name, 0, 1) == '%')
			{
				// Treat this as URL-encoded text.
				
				$out .= rawurldecode($var_name);
			}
			elseif ($allow_conf_refs && strpos($var_name, '.') !== false)
			{
				// This is a reference to a conf value.
				// Check whether we're allowed to access this value.
				
				$permitted = true;
				
				if (conf('common.util.template.use-whitelist'))
				{
					$permitted = false;
					
					foreach ((array) conf('common.util.template.whitelist') as $conf)
					{
						if (static::startsWith("{$var_name}.", "{$conf}."))
						{
							$permitted = true;
							break;
						}
					}
				}
				
				foreach ((array) conf('common.util.template.blacklist') as $conf)
				{
					if (static::startsWith("{$var_name}.", "{$conf}."))
					{
						$permitted = false;
						break;
					}
				}
				
				if ($permitted)
				{
					$out .= (string) conf($var_name);
				}
				else
				{
					// Templates aren't allowed to access this area of
					// the configuration. Leave the tag unprocessed.
					
					$out .= substr($template, $pos - 3 - $seg_len, $seg_len + 3);
				}
			}
			elseif (array_key_exists($var_name, $context_vars))
			{
				// This is a reference to a context variable.
				
				$out .= $context_vars[$var_name];
			}
			else
			{
				// No match. Include the tag in the output.
				
				$out .= substr($template, $pos - 3 - $seg_len, $seg_len + 3);
			}
		}
		
		return $out;
	}
	
	/**
	 * Shorten a string to a specified maximum length with a "...",
	 * trying to break at a word boundary if possible.
	 */
	
	public static function limit ($string, $max_length=null, $terminator=null, $word_break_search_length=null, $trim_input=null)
	{
		if ($max_length === null)
		{
			$max_length = 100;
		}
		
		if ($terminator === null)
		{
			$terminator = ' ...';
		}
		
		if ($word_break_search_length === null)
		{
			$word_break_search_length = 50;
		}
		else
		{
			$word_break_search_length = (int) max(0, $word_break_search_length);
		}
		
		if ($trim_input === null || $trim_input)
		{
			$string = trim($string);
		}
		
		if (strlen($string) <= $max_length)
		{
			return $string;
		}
		
		preg_match('/^.{0,'.($max_length - $word_break_search_length - strlen($terminator)).'}(?:.{0,'.$word_break_search_length.'}(?=\s)|.{'.$word_break_search_length.'})/s', $string, $match);
		
		return $match[0].$terminator;
	}
	
	public static function diff ($old_data, $new_data, $params=null)
	{
		$diff = new Diff($old_data, $new_data, $params);
		return $diff->getDiff();
	}
	
	public static function patch ($data, $patch, $is_reverse=null)
	{
		$patcher = new Patcher();
		return $patcher->patch($data, $patch, $is_reverse);
	}
	
	/**
	 * Returns a string $length characters long, containing random
	 * UTF-8 characters from the set specified by the $chars string.
	 *
	 * This function pulls from a cryptographically secure source,
	 * and it does so efficiently with minimal wasted bits, while
	 * producing output with a uniform distribution.
	 *
	 * If $chars is provided as an array of strings, this function
	 * will randomly select whole strings from the array instead of
	 * characters from a string. Each selected string will only
	 * count as one "character" toward the $length.
	 *
	 * If $byte_mode is true, random bytes will be selected instead
	 * of random UTF-8 characters. This setting is ignored if $chars
	 * is provided as an array.
	 */
	
	public static function rand ($length, $chars=null, $byte_mode=null)
	{
		if ($chars === null)
		{
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
		}
		
		if ($byte_mode === null)
		{
			$byte_mode = false;
		}
		
		if (!is_array($chars))
		{
			if ($byte_mode)
			{
				$chars = str_split($chars);
			}
			else
			{
				$chars = static::chunk($chars);
			}
		}
		
		$num_chars = count($chars);
		
		// For each character, we want to roll a random number
		// in the range 0..num_chars-1.
		//
		// We pull random numbers in groups of 8 bits. If
		// num_chars is less than 256, it's possible to pull a
		// random number that is too large. In that case, we
		// can modulo it by num_chars to get a value in the
		// desired range.
		//
		// *However* this is only safe to do within a certain
		// range of values. For example, if num_chars is 10, and
		// we've pulled one random byte with a value of 252,
		// then only values 250..255 are possible in this modulo
		// range, meaning we don't have a uniform distribution
		// of possible values. Values 0..5 would be slightly
		// more common than 6..9.
		//
		// In this case, we must pull more bytes until the
		// quotient + num_chars - 1 is within the range of
		// possible values. Expressed differently, the random
		// value must be less than the number of possible
		// values minus its modulo of num_chars.
		
		$result = '';
		$rand_value = 0;
		$num_possible_values = 1;
		$chars_produced = 0;
		
		while ($chars_produced < $length)
		{
			// Get a random byte.
			
			$rand_byte = ord(Rand::getBytes(1));
			
			// Shift it into our value.
			
			$rand_value = ($rand_value << 8) + $rand_byte;
			
			// We've just multiplied the number of
			// possible values by 256.
			
			$num_possible_values <<= 8;
			
			do
			{
				$progressed_char = false;
				
				// Determine the range of values that can be moduloed with
				// num_chars while maintaining a uniform distribution.
				
				$uniformity_ceiling = $num_possible_values - ($num_possible_values % $num_chars);
				
				if ($rand_value < $uniformity_ceiling)
				{
					// We've found a value we can use to produce a character.
					
					$result .= $chars[$rand_value % $num_chars];
					++$chars_produced;
					
					if ($chars_produced < $length)
					{
						// We still have more characters to generate. Let's
						// shift out what we used for the last character and
						// resume with what we have left over.
						
						$rand_value = floor($rand_value / $num_chars);
						$num_possible_values = floor($num_possible_values / $num_chars);
						$progressed_char = true;
					}
				}
				else
				{
					// We can subtract the uniformity_ceiling without
					// affecting the results. This keeps our integers
					// within a fixed range.
					
					// Note: Both values are currently greater than
					// uniformity_ceiling, so they won't end up negative.
					
					$rand_value -= $uniformity_ceiling;
					$num_possible_values -= $uniformity_ceiling;
				}
			}
			while ($progressed_char);
		}
		
		return $result;
	}
}
