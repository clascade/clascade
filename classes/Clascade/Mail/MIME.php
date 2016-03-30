<?php

namespace Clascade\Mail;

class MIME
{
	public static function quotePrintable ($string)
	{
		// Encode non-printable characters.
		
		$string = preg_replace_callback('/[^ \t\x21-\x3c\x3e-\x7e]/', function ($match)
		{
			$hex = hex(ord($match[0]), 2);
			$hex = strtoupper($hex);
			return '='.$hex;
		}, $string);
		
		// Encode horizontal whitespace at the end of the data, if present.
		
		$last_char = substr($string, -1);
		
		if ($last_char == ' ')
		{
			$string = substr($string, 0, -1).'=20';
		}
		elseif ($last_char == "\t")
		{
			$string = substr($string, 0, -1).'=09';
		}
		
		// Wrap to 76 columns with "=".
		
		$string = preg_replace('/.{1,75}(?<!=|=.)/s', "\$0=\r\n", $string);
		$string = substr($string, 0, -3);
		
		return $string;
	}
	
	public static function createBoundary ()
	{
		return '----=_'.bin2hex(rand_bytes(32));
	}
}
