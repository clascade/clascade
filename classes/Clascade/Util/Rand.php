<?php

namespace Clascade\Util;
use Clascade\Util\Rand\Source;

class Rand
{
	/**
	 * Returns a string containing a specified number of random
	 * bytes from a cryptographically secure source.
	 */
	
	public static function getBytes ($length)
	{
		return Source::getBytes($length);
	}
	
	/**
	 * Returns a random integer within a given range.
	 *
	 * This function pulls from a cryptographically secure source,
	 * and it produces output with a uniform distribution.
	 */
	
	public static function getInt ($min, $max)
	{
		$min = (int) $min;
		$max = (int) $max;
		
		if ($max < $min)
		{
			throw new Exception\DomainException('$max must not be less than $min');
		}
		
		if ($min === $max)
		{
			return $min;
		}
		
		$num_acceptable_values = $max - $min + 1;
		
		// Count the number of bytes that will be needed.
		
		$num_bytes = 0;
		
		for ($b = $num_acceptable_values; $b > 1; $b >>= 8)
		{
			++$num_bytes;
		}
		
		// Calculate the uniformity ceiling (the smallest random
		// number that would have to be thrown away to prevent
		// bias).
		
		$num_possible_values = pow(256, $num_bytes);
		$uniformity_ceiling = $num_possible_values - ($num_possible_values % $num_acceptable_values);
		
		do
		{
			// Fetch the random bytes.
			
			$rand_bytes = Source::getBytes($num_bytes);
			
			// Convert the bytes into an integer.
			
			$rand_value = 0;
			
			for ($i = 0; $i < $num_bytes; ++$i)
			{
				$rand_value |= ord($rand_bytes[$i]) << ($i * 8);
			}
		}
		while ($rand_value >= $uniformity_ceiling);
		
		// We got a usable random number.
		
		return ($rand_value % $num_acceptable_values) + $min;
	}
}
