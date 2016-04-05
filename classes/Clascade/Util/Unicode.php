<?php

namespace Clascade\Util;

class Unicode
{
	/**
	 * Get the Unicode codepoint at a specific character index in a
	 * UTF-8 string.
	 */
	
	public static function codePointAt ($string, $char_offset=null, &$sequence_length=null)
	{
		if ($char_offset === null)
		{
			$char_offset = 0;
		}
		
		if ($char_offset != 0)
		{
			$string = Str::slice($string, $char_offset, 1);
		}
		
		return static::codePointAtByte($string, 0, $sequence_length);
	}
	
	/**
	 * Get the Unicode codepoint at a specific byte index in a UTF-8
	 * string.
	 */
	
	public static function codePointAtByte ($string, $byte_offset=null, &$sequence_length=null)
	{
		if ($byte_offset === null)
		{
			$byte_offset = 0;
		}
		
		if ($byte_offset < 0)
		{
			// $byte_offset is negative. Interpret it as the
			// number of bytes before the end of the string.
			
			$byte_offset = strlen($string) - $byte_offset;
			
			if ($byte_offset < 0)
			{
				// The string isn't long enough for this offset.
				
				$sequence_length = 0;
				return false;
			}
		}
		elseif ($byte_offset >= strlen($string))
		{
			// The first byte is missing.
			
			$sequence_length = 0;
			return false;
		}
		
		// First byte.
		
		$c = ord($string[$byte_offset]);
		
		if (($c & 0b10000000) == 0b00000000) // First valid sequence: .0000000
		{
			$sequence_length = 1;
			return $c;
		}
		elseif (($c & 0b11100000) == 0b11000000) // First valid sequence: ...00010 ..000000
		{
			$val = $c & 0b00011111;
			
			if ($val < 0b00010)
			{
				// Overlong (invalid) sequence.
				
				$sequence_length = 1;
				return false;
			}
			
			$sequence_length = 2;
		}
		elseif (($c & 0b11110000) == 0b11100000) // First valid sequence: ....0000 ..100000 ..000000
		{
			$val = $c & 0b00001111;
			$sequence_length = 3;
		}
		elseif (($c & 0b11111000) == 0b11110000) // First valid sequence: .....000 ..010000 ..000000 ..000000
		{
			$val = $c & 0b00000111;
			
			if ($val >= 0b101)
			{
				// Invalid codepoint (above 0x10ffff).
				
				$sequence_length = 1;
				return false;
			}
			
			$sequence_length = 4;
		}
		else
		{
			// Invalid codepoint (above 0x10ffff).
			
			$sequence_length = 1;
			return false;
		}
		
		++$byte_offset;
		
		// Continuation bytes.
		
		for ($i = 1; $i < $sequence_length; ++$i, ++$byte_offset)
		{
			if ($byte_offset >= strlen($string))
			{
				// The sequence was cut short by the end of the string.
				
				$sequence_length = $i;
				return false;
			}
			
			$c = ord($string[$byte_offset]);
			
			if (($c & 0b11000000) != 0b10000000)
			{
				// Unexpected non-continuation byte.
				
				$sequence_length = $i;
				return false;
			}
			
			$val <<= 6;
			$val |= $c & 0b00111111;
			
			if ($i == 1)
			{
				if ($sequence_length == 3)
				{
					if ($val < 0b0000100000 || ($val >= 0b1101100000 && $val < 0b1110000000))
					{
						// Overlong sequence or invalid codepoint (surrogate).
						
						$sequence_length = 1;
						return false;
					}
				}
				elseif ($sequence_length == 4)
				{
					if ($val < 0b000010000 || $val >= 0b100010000)
					{
						// Overlong sequence or invalid codepoint (above 0x10ffff).
						
						$sequence_length = 1;
						return false;
					}
				}
			}
		}
		
		return $val;
	}
	
	public static function charAtByte ($string, $byte_offset=null, &$sequence_length=null)
	{
		if (static::codePointAtByte($string, $byte_offset, $sequence_length) === false)
		{
			return false;
		}
		
		return substr($string, $byte_offset, $sequence_length);
	}
	
	/**
	 * Returns the byte index that corresponds to a given character
	 * position within a UTF-8 string.
	 *
	 * If $byte_offset is provided, character counting will begin at
	 * this position from the beginning of the string. The value
	 * should NOT be negative. Regardless of $byte_offset, the
	 * returned value will be the position from the start of the
	 * string.
	 */
	
	public static function bytePos ($string, $char_pos, $byte_offset=null)
	{
		$byte_offset = (int) $byte_offset;
		
		if ($byte_offset < 0)
		{
			return false;
		}
		
		if ($char_pos == 0)
		{
			return 0;
		}
		
		if ($char_pos < 0)
		{
			// Handle negative position.
			
			$string = strrev($string);
			$string = preg_replace_callback('/[\x80-\xbf]*[\x80-\xff]/S', function ($match)
			{
				return strrev($match[0]);
			}, $string);
			
			$byte_pos = static::bytePos($string, -$char_pos);
			
			if ($byte_pos === false)
			{
				// The position is before the start of the string.
				
				return false;
			}
			
			$byte_pos = strlen($string) - $byte_pos;
			
			if ($byte_pos < $byte_offset)
			{
				// The position is before the provided $byte_offset.
				
				return false;
			}
			
			return $byte_pos;
		}
		
		while ($char_pos > 0 && preg_match('/[\x80-\xff][\x80-\xbf]*/S', $string, $match, \PREG_OFFSET_CAPTURE, $byte_offset))
		{
			$codepoint = static::codePointAtByte($match[0][0], 0, $sequence_length);
			$char_pos -= $match[0][1] - $byte_offset;
			$byte_offset = $match[0][1];
			
			if ($char_pos <= 0)
			{
				// The desired character position is in one of the skipped ASCII bytes.
				
				break;
			}
			
			$char_pos -= strlen($match[0][0]) - $sequence_length + 1;
			$byte_offset += strlen($match[0][0]);
		}
		
		$byte_offset += $char_pos;
		
		if ($byte_offset > strlen($string))
		{
			// The string doesn't contain enough characters.
			
			return false;
		}
		
		return $byte_offset;
	}
	
	public static function charPos ($string, $byte_pos, $byte_offset=null)
	{
		$byte_offset = (int) $byte_offset;
		
		if ($byte_offset < 0 || $byte_pos > strlen($string))
		{
			return false;
		}
		
		if ($byte_pos === 0)
		{
			return 0;
		}
		
		if ($byte_pos < 0)
		{
			$byte_pos = strlen($string) - $byte_pos;
			
			if ($byte_pos < $byte_offset)
			{
				return false;
			}
		}
		
		$char_pos = 0;
		
		while ($byte_offset < $byte_pos && preg_match('/[\x80-\xff][\x80-\xbf]*/S', $string, $match, \PREG_OFFSET_CAPTURE, $byte_offset))
		{
			$codepoint = static::codePointAtByte($match[0][0], 0, $sequence_length);
			$char_pos += $match[0][1] - $byte_offset;
			$byte_offset = $match[0][1];
			
			if ($byte_offset >= $byte_pos)
			{
				// The desired byte position is in one of the skipped ASCII characters.
				
				break;
			}
			
			$char_pos += strlen($match[0][0]) - $sequence_length + 1;
			$byte_offset += strlen($match[0][0]);
		}
		
		$char_pos += $byte_pos - $byte_offset;
		return $char_pos;
	}
	
	public static function isContinuationByte ($string)
	{
		return ((ord($string) & 0b11000000) === 0b10000000);
	}
	
	public static function fromCodePoint ($codepoint)
	{
		if ($codepoint < 0x000080)
		{
			return chr($codepoint);
		}
		elseif ($codepoint < 0x000800)
		{
			return chr(($codepoint >> 6) + 0xc0).chr(($codepoint & 0x3f) + 128);
		}
		elseif ($codepoint < 0x010000)
		{
			return chr(($codepoint >> 12) + 0xe0).chr((($codepoint >> 6) & 0x3f) + 128).chr(($codepoint & 0x3f) + 128);
		}
		elseif ($codepoint < 0x200000)
		{
			return chr(($codepoint >> 18) + 0xf0).chr((($codepoint >> 12) & 0x3f) + 128).chr((($codepoint >> 6) & 0x3f) + 128).chr(($codepoint & 0x3f) + 128);
		}
		else
		{
			return false;
		}
	}
	
	public static function codePointToUTF16 ($codepoint, $raw=null)
	{
		if ($raw === null)
		{
			$raw = false;
		}
		
		if ($codepoint < 0xd800 || ($codepoint >= 0xe000 && $codepoint < 0xffff))
		{
			return ($raw ? pack('n', $codepoint) : Math::dechex($codepoint, 4));
		}
		
		if ($codepoint < 0xe000 || $codepoint >= 0x110000)
		{
			// Invalid codepoint.
			
			return false;
		}
		
		// Use a surrogate pair to encode.
		
		$codepoint -= 0x010000;
		$codepoint = ((($codepoint << 6) & 0x03ff0000) | ($codepoint & 0x03ff)) + 0xd800d800;
		return ($raw ? pack('n', $codepoint) : Math::dechex($codepoint, 8));
	}
	
	/**
	 * Replace all invalid UTF-8 sequences with the Unicode
	 * replacement character, resulting in a valid UTF-8 string.
	 */
	
	public static function replaceBadChars ($string)
	{
		return static::replaceUTF8SequenceCallback($string, '/[\x80-\xff][\x80-\xbf]*/S', function ($char, $codepoint)
		{
			return $char;
		});
	}
	
	/**
	 * Use a regex and callback to replace UTF-8 character sequences.
	 *
	 * This is an advanced function used under the hood of several
	 * other replacement functions. It can provide pretty good
	 * performance while also cleaning up any invalid UTF-8
	 * sequences according to best practices.
	 *
	 * $utf8_seq_pattern should be a specially-crafted regular
	 * expression that meets certain requirements:
	 *
	 * 1. A single match should never include more than one valid
	 *    UTF-8 character.
	 *
	 * 2. If a match contains a valid UTF-8 character, that
	 *    character must appear at the beginning of the match.
	 *
	 * 3. A match may begin with an invalid byte, and any number of
	 *    invalid UTF-8 continuation bytes may appear at the end of
	 *    any match.
	 *
	 * 4. In order for this function to clean up invalid bytes, all
	 *    such bytes must be matched by this pattern.
	 *
	 * The following pattern meets these requirements, and it
	 * matches all non-ASCII UTF-8 sequences (valid or not):
	 *
	 *   [\x80-\xff][\x80-\xbf]*
	 *
	 * Warning: Do NOT use the "u" modifier in your regular
	 * expression! Doing so will prevent proper cleanup of invalid
	 * bytes.
	 *
	 * When a match includes invalid UTF-8 data, this function will
	 * split the match and replace the invalid data with the
	 * standard Unicode "replacement character" (0xFFFD). The
	 * callback function will be called for the valid character at
	 * the start of the match (if present), and again for each
	 * replacement character that is generated.
	 *
	 * The callback function will be called with two parameters:
	 * a string containing the valid UTF-8 sequence of a character
	 * (which may be the 0xFFFD replacement character), and an
	 * integer representing the Unicode codepoint of that character.
	 * The callback should return the string to replace the
	 * character with.
	 */
	
	public static function replaceUTF8SequenceCallback ($string, $utf8_seq_pattern, $callback)
	{
		return preg_replace_callback($utf8_seq_pattern, function ($match) use ($callback)
		{
			$codepoint = static::codePointAtByte($match[0], 0, $sequence_length);
			
			if ($codepoint === false)
			{
				$replacement = '';
				--$sequence_length;
			}
			else
			{
				$replacement = call_user_func($callback, $match[0], $codepoint);
			}
			
			for ($i = strlen($match[0]) - $sequence_length; $i > 0; --$i)
			{
				$replacement .= call_user_func($callback, "\xef\xbf\xbd", 65533);
			}
			
			return $replacement;
		}, $string);
	}
	
	public static function mapHighCharsByConf ($string, $conf_path, $char_fallback=null, $overrides=null)
	{
		$conf_path = Str::finish($conf_path, '/');
		
		return static::replaceUTF8SequenceCallback($string, '/[\x80-\xff][\x80-\xbf]*/S', function ($char, $codepoint) use ($conf_path, $char_fallback, $overrides)
		{
			if (isset ($overrides[$codepoint]))
			{
				if (is_string($overrides[$codepoint]))
				{
					return $overrides[$codepoint];
				}
				else
				{
					$replacement = call_user_func($overrides[$codepoint], $char, $codepoint);
					
					if ($replacement !== null)
					{
						return $replacement;
					}
				}
			}
			
			// Determine the conf file for this codepoint.
			
			$conf_file = $conf_path.Math::dechex($codepoint >> 8, 4);
			
			// Attempt to map the codepoint to a replacement string.
			
			$replacement = conf("{$conf_file}.".($codepoint & 0xff));
			
			if ($replacement === null)
			{
				return $char_fallback === null ? $char : $char_fallback;
			}
			
			return $replacement;
		});
	}
}
