<?php

namespace Clascade\Util;

class Escape
{
	public static function glob ($string)
	{
		return strtr($string,
		[
			'\\' => '\\\\',
			'*' => '\\*',
			'?' => '\\?',
			'[' => '\\[',
		]);
	}
	
	/**
	 * Escape a string of text for use within an HTML element or
	 * properly-quoted attribute value.
	 */
	
	public static function html ($string)
	{
		$string = htmlspecialchars($string, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_DISALLOWED, 'UTF-8');
		
		// Escape some extra characters as a last-ditch defense
		// in case this function was mistakenly used in the
		// wrong context.
		//
		// Warning: You should *never* deliberately rely on this
		// function in the wrong context!
		//
		// Even with these extra replacements, there are many
		// cases where XSS attacks will still be possible if you
		// rely on this function inappropriately. For example,
		// the extra replacements do nothing to protect against
		// XSS in the context of an event attribute value or a
		// style attribute value. In these cases, you should
		// escape user input with escapeJS or escapeCSS instead.
		
		$string = strtr($string,
		[
			'(' => '&#40;',
			')' => '&#41;',
			'+' => '&#43;',
			'=' => '&#61;',
			'[' => '&#91;',
			'\\' => '&#92;',
			']' => '&#93;',
			'`' => '&#96;',
			'{' => '&#123;',
			'}' => '&#125;',
		]);
		
		return $string;
	}
	
	/**
	 * Escape a string of text for use within an unquoted HTML
	 * attribute value.
	 *
	 * For properly quoted attribute values, escapeHTML() will work
	 * just as well.
	 */
	
	public static function htmlAttr ($string)
	{
		return Unicode::replaceUTF8SequenceCallback($string, '/[\x80-\xff][\x80-\xbf]*|[^a-zA-Z0-9,\-._]/', function ($char, $codepoint)
		{
			switch ($char)
			{
			case '&': return '&amp;';
			case '"': return '&quot;';
			case '<': return '&lt;';
			case '>': return '&gt;';
			
			default:
				if (($codepoint < 0x20 && $codepoint != 0x09 && $codepoint != 0x0a && $codepoint != 0x0c) || ($codepoint >= 0x7f && $codepoint < 0xa0))
				{
					// Invalid character reference.
					
					return '&#65533;';
				}
				
				return "&#{$codepoint};";
			}
		});
	}
	
	/**
	 * Escape string to be treated as text within a JavaScript
	 * string.
	 *
	 * The output of this function will never contain any HTML
	 * special characters, so you don't need to put it through
	 * escapeHTML().
	 */
	
	public static function js ($string)
	{
		return Unicode::replaceUTF8SequenceCallback($string, '/[\x80-\xff][\x80-\xbf]*|[^a-zA-Z0-9,._]/', function ($char, $codepoint)
		{
			switch ($char)
			{
			case "\0": return '\\0';
			case "\x08": return '\\b';
			case "\f": return '\\f';
			case "\n": return '\\n';
			case "\r": return '\\r';
			case "\t": return '\\t';
			case '\\': return '\\\\';
			default:
				if ($codepoint <= 127)
				{
					return '\\x'.hex($codepoint, 2);
				}
				
				// Convert the codepoint to a UTF-16 sequence, in hex.
				//
				// Note: This should never return false, because the
				// replaceUTF8SequenceCallback function guarantees
				// valid codepoints.
				
				$hex = Unicode::codePointToUTF16($codepoint);
				
				if (strlen($hex) == 4)
				{
					return '\\u'.$hex;
				}
				else
				{
					return '\\u'.substr($hex, 0, 4).'\\u'.substr($hex, 4);
				}
			}
		});
	}
	
	/**
	 * Escape a trusted string of JavaScript code for use within an
	 * HTML script element.
	 *
	 * WARNING: If your entire value is just going into a
	 * JavaScript string, you should use escapeJS instead!
	 *
	 * Since the body of a script element isn't parsed as markup,
	 * you shouldn't put the output through escapeHTML().
	 */
	
	public static function scriptBody ($string)
	{
		return str_replace('</', '\\x3c/', $string);
	}
	
	/**
	 * Escape a string of text for use within a CSS value.
	 *
	 * The output of this function will never contain any HTML
	 * special characters, so you don't need to put it through
	 * escapeHTML().
	 */
	
	public static function css ($string)
	{
		return Unicode::replaceUTF8SequenceCallback($string, '/[\x80-\xff][\x80-\xbf]*|[^a-zA-Z0-9]/', function ($char, $codepoint)
		{
			if ($char == '\\')
			{
				return '\\\\';
			}
			
			return '\\'.hex($codepoint, 6);
		});
	}
	
	public static function template ($string, $extra_chars=null)
	{
		if (is_string($extra_chars))
		{
			$extra_chars = str_split($extra_chars, 1);
		}
		
		$replacements = ['%{' => '%{%25}{'];
		
		if ($extra_chars !== null)
		{
			foreach ($extra_chars as $char)
			{
				$replacements[$char] = '%{'.rawurlencode($char).'}';
			}
		}
		
		return strtr($string, $replacements);
	}
	
	public static function translation ($string)
	{
		return static::escapeTemplate($string, '|{[(');
	}
	
	/**
	 * Escape a string of text for use within a single path
	 * component, parameter name, or parameter value within a URL.
	 *
	 * The output of this function will never contain any HTML
	 * special characters, so you don't need to put it through
	 * escapeHTML().
	 */
	
	public static function url ($string)
	{
		return rawurlencode($string);
	}
	
	//== Sanitizers (lossy) ==//
	
	/**
	 * Strip characters that aren't allowed in an HTML attribute
	 * name.
	 */
	
	public static function sanitizeAttributeName ($string)
	{
		$string = Unicode::replaceBadChars($string);
		return preg_replace('/[ \t\n\f\r\0"\'>\/=\p{Cc}]/u', '', $string);
	}
}
