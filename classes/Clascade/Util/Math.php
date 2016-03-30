<?php

namespace Clascade\Util;

class Math
{
	public static function clamp ($number, $min, $max)
	{
		if ($number < $min)
		{
			return $min;
		}
		
		if ($number > $max)
		{
			return $max;
		}
		
		return $number;
	}
	
	public static function dechex ($decimal_number, $num_digits=null)
	{
		$hex = dechex($decimal_number);
		
		if ($num_digits === null)
		{
			$num_digits = static::ceil(strlen($hex), 2);
		}
		
		$hex = Str::padLeft($hex, $num_digits, '0');
		return $hex;
	}
	
	public static function mod ($x, $y)
	{
		if (is_int($x) && is_int($y))
		{
			return $x % $y;
		}
		else
		{
			return fmod($x, $y);
		}
	}
	
	public static function normalizeAngle ($angle, $size=null)
	{
		if ($size === null)
		{
			$size = \M_PI * 2;
		}
		
		return static::mod($angle, $size) + ($angle < 0 ? $size : 0);
	}
	
	public static function sign ($number)
	{
		if ($number > 0)
		{
			return 1;
		}
		elseif ($number < 0)
		{
			return -1;
		}
		else
		{
			return 0;
		}
	}
	
	//== Rounding ==//
	
	public static function floor ($number, $to_multiple_of=null)
	{
		if ($to_multiple_of === null)
		{
			$to_multiple_of = 1;
		}
		else
		{
			$to_multiple_of = abs($to_multiple_of);
		}
		
		$mod = static::mod($number, $to_multiple_of);
		
		if ($mod < 0)
		{
			$mod += $to_multiple_of;
		}
		
		return $number - $mod;
	}
	
	public static function ceil ($number, $to_multiple_of=null)
	{
		if ($to_multiple_of === null)
		{
			$to_multiple_of = 1;
		}
		else
		{
			$to_multiple_of = abs($to_multiple_of);
		}
		
		$mod = static::mod($number, $to_multiple_of);
		
		if ($mod < 0)
		{
			$mod += $to_multiple_of;
		}
		
		return $number + ($to_multiple_of - $mod);
	}
	
	public static function round ($number, $to_multiple_of=null)
	{
		if ($to_multiple_of === null)
		{
			$to_multiple_of = 1;
		}
		else
		{
			$to_multiple_of = abs($to_multiple_of);
		}
		
		$mod = static::mod($number, $to_multiple_of);
		
		if ($number < 0)
		{
			$mod += $to_multiple_of;
		}
		
		$half = $to_multiple_of / 2;
		
		if ($mod > $half || ($mod == $half && $number >= 0))
		{
			return $number + ($to_multiple_of - $mod);
		}
		
		return $number - $mod;
	}
	
	//== Parity ==//
	
	public static function isEven ($number)
	{
		return (static::mod($number, 2) == 0);
	}
	
	public static function isOdd ($number)
	{
		return (static::mod($number, 2) == 1);
	}
}
