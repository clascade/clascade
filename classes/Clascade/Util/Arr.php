<?php

namespace Clascade\Util;

class Arr
{
	public static function exists ($array, $path=null)
	{
		if ($path === null)
		{
			return (is_array($array) ? $array : null);
		}
		
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		for ($i = 0; $i < count($path); ++$i)
		{
			$key = $path[$i];
			
			if ($key == '' || !is_array($array) || !array_key_exists($key, $array))
			{
				return false;
			}
			
			$array = &$array[$key];
		}
		
		return true;
	}
	
	public static function get ($array, $path=null, $default=null)
	{
		if ($path === null)
		{
			return (is_array($array) ? $array : null);
		}
		
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		for ($i = 0; $i < count($path); ++$i)
		{
			$key = $path[$i];
			
			if ($key == '' || !is_array($array) || !array_key_exists($key, $array))
			{
				return $default;
			}
			
			$array = &$array[$key];
		}
		
		return $array;
	}
	
	public static function set (&$array, $path=null, $value=null)
	{
		if ($path === null)
		{
			$array = $value;
			return;
		}
		
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		for ($i = 0; $i < count($path) - 1; ++$i)
		{
			$key = $path[$i];
			
			if ($key == '')
			{
				$new = [];
				$array[] = &$new;
				$array = &$new;
				unset ($new);
			}
			else
			{
				if (!array_key_exists($key, $array) || !is_array($array[$key]))
				{
					$array[$key] = [];
				}
				
				$array = &$array[$key];
			}
		}
		
		$key = $path[$i];
		
		if ($key == '')
		{
			$array[] = $value;
		}
		else
		{
			$array[$path[$i]] = $value;
		}
	}
	
	public static function delete (&$array, $path=null)
	{
		if ($path === null)
		{
			$array = null;
			return true;
		}
		
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		for ($i = 0; $i < count($path) - 1; ++$i)
		{
			$key = $path[$i];
			
			if ($key == '' || !array_key_exists($key, $array) || !is_array($array[$key]))
			{
				return false;
			}
			
			$array = &$array[$key];
		}
		
		$key = $path[$i];
		
		if ($key == '')
		{
			return false;
		}
		else
		{
			unset ($array[$key]);
		}
		
		return true;
	}
	
	public static function first ($array)
	{
		return static::valueAtIndex($array, 0);
	}
	
	public static function firstKey ($array)
	{
		return static::keyAtIndex($array, 0);
	}
	
	public static function last ($array)
	{
		return static::valueAtIndex($array, -1);
	}
	
	public static function lastKey ($array)
	{
		return static::keyAtIndex($array, -1);
	}
	
	public static function valueAtIndex ($array, $index)
	{
		if (empty ($array))
		{
			return null;
		}
		
		$array = array_slice($array, $index, 1);
		return reset($array);
	}
	
	public static function keyAtIndex ($array, $index)
	{
		if (empty ($array))
		{
			return null;
		}
		
		$array = array_slice($array, $index, 1, true);
		return key($array);
	}
	
	/**
	 * Return a random value from an array.
	 *
	 * This function uses a cryptographically secure PRNG. It
	 * supports arrays with both numeric and string keys.
	 */
	
	public static function rand ($array)
	{
		$index = Rand::getInt(0, count($array) - 1);
		return static::valueAtIndex($array, $index);
	}
	
	/**
	 * Return a random key from an array.
	 *
	 * This function uses a cryptographically secure PRNG. It
	 * supports arrays with both numeric and string keys.
	 */
	
	public static function randKey ($array)
	{
		$index = Rand::getInt(0, count($array) - 1);
		return static::keyAtIndex($array, $index);
	}
	
	/**
	 * Return a random key from an array, weighted by values.
	 *
	 * This method expects the array values to be numeric. Each
	 * value represents the relative probability that its key will
	 * be picked. For example, a value of 6 will be twice as likely
	 * as a value of 3.
	 *
	 * This function uses a cryptographically secure PRNG.
	 */
	
	public static function randKeyWeighted ($array)
	{
		if (empty ($array))
		{
			return null;
		}
		
		$value = Rand::getInt(0, array_sum($array) - 1);
		
		foreach ($array as $key => $weight)
		{
			$value -= $weight;
			
			if ($value < 0)
			{
				break;
			}
		}
		
		return $key;
	}
	
	public static function implode ($glue, $array, $prefix=null, $suffix=null)
	{
		$glue = (string) $glue;
		$prefix = (string) $prefix;
		$suffix = (string) $suffix;
		
		if ($prefix == '' && $suffix == '')
		{
			return implode($glue, $array);
		}
		
		$out = '';
		$is_first = true;
		
		foreach ($array as $value)
		{
			if ($is_first)
			{
				$is_first = false;
			}
			else
			{
				$out .= $glue;
			}
			
			$out .= $prefix.$value.$suffix;
			
		}
		
		return $out;
	}
	
	public static function implodeCommaSeries ($array, $conjunction=null, $use_serial_comma=null, $delimiter=null, $alt_delimiter=null)
	{
		if ($conjunction === null)
		{
			$conjunction = 'and';
		}
		
		if (count($array) < 3)
		{
			return implode(" {$conjunction} ", $array);
		}
		
		if ($use_serial_comma === null)
		{
			$use_serial_comma = true;
		}
		
		if ($delimiter === null)
		{
			$delimiter = ',';
			
			foreach ($array as $value)
			{
				if (false !== strpos($value, $delimiter))
				{
					$delimiter = ($alt_delimiter === null ? ';' : $alt_delimiter);
					break;
				}
			}
		}
		
		$last_item = array_pop($array);
		return implode("{$delimiter} ", $array).($use_serial_comma ? $delimiter : '')." {$conjunction} {$last_item}";
	}
}
