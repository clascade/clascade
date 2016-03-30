<?php

namespace Clascade\Util\PassHash;
use Clascade\Util\PassHash;

/**
 * This class provides tools to migrate a password hash from another
 * system into a more secure transitional hash that benefits from our
 * standard multi-round hashing and encryption model.
 *
 * For example, if we are transitioning an existing collection of user
 * accounts from another system into a Clascade site, and the old system
 * only used unsalted md5 hashes for user passwords, we can strengthen
 * those hashes by taking the md5s as the inputs and hashing them as we
 * would normally hash a password. The user would be able to log in with
 * their existing password, at which point our system could replace the
 * stored hash with a fresh one generated the normal way.
 *
 * The algorithm indicator in the migrated hash will include
 * "prehashing" information showing that it's a pass-hash of an md5 (in
 * this example) of the password rather than a pass-hash of the password
 * itself. When we go to verify a user-provided password against this
 * hash, we will first prehash the password using the indicated
 * algorithm (md5 in this example), and then pass-hash the result as
 * normal. The end result will be the stored hash if the input was the
 * correct password.
 *
 * Individual apps can define their own prehashing algorithms by
 * replacing the TransitionalVerifier class, by creating a
 * classes/Clascade/Util/PassHash/TransitionalVerifier.php file in a
 * higher layer). Such hashes will need to begin with "$transitional$",
 * but the app's code can decide how to handle the rest of the hash
 * components. This can be useful when migrating users from another
 * account system that used its own custom hashing method.
 */

class ForeignHashUtils
{
	const PHPASSP_CHARS = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	
	public static $primitive_lengths =
	[
		128 => 'md5',
		160 => 'sha1',
		224 => 'sha224',
		256 => 'sha256',
		384 => 'sha384',
		512 => 'sha512',
	];
	
	public static function migrate ($hash)
	{
		if (substr($hash, 0, 1) == '$')
		{
			$ident_end = strpos($hash, '$', 1);
			
			if ($ident_end === false)
			{
				return false;
			}
			
			switch (substr($hash, 1, $ident_end - 1))
			{
			case '2a':
			case '2x':
			case '2y':
				return static::migrateBlowfishHash($hash);
				break;
			
			case 'H':
			case 'P':
				return static::migratePHPassPortableHash($hash);
				break;
			
			default:
				// Assume a standard crypt hash.
				
				return PassHash::hash($hash,
				[
					'pre-algo' => 'crypt',
					'pre-params' => substr($hash, 0, strrpos($hash, '$') + 1),
				]);
				break;
			}
		}
		elseif (preg_match('/^(?:[0-9a-f]{2})+$/', $hash))
		{
			// This is a hex string. Assume a common hashing
			// primitive based on the hash's bit length.
			
			$bit_length = strlen($hash) * 4;
			
			if (isset (static::primitive_lengths[$bit_length]))
			{
				$hash = hex2bin($hash);
				return PassHash::hash($hash, ['pre-algo' => static::primitive_lengths[$bit_length]]);
			}
		}
		
		// Unrecognized hash type.
		
		return false;
	}
	
	public static function migrateBlowfishHash ($hash)
	{
		if (!preg_match('/^\$2[axy]\$\d\d\$[0-9a-zA-Z.\/]{22,}$/', $hash))
		{
			return false;
		}
		
		// Although crypt's Blowfish hashes have no separator
		// between the salt and the hash part, the salt is
		// always a 128-bit value encoded in base64 separately
		// from the hash part. The final byte of the salt part
		// only encodes the final two bits of the salt, and it
		// doesn't contain any of the hash itself. This means
		// the two parts should always be cleanly separable on
		// a byte boundary at a fixed offset.
		
		return PassHash::hash($hash,
		[
			'pre-algo' => 'crypt',
			'pre-params' => substr($hash, 0, 29).'$',
		]);
	}
	
	public static function migratePHPassPortableHash ($hash)
	{
		$prefix = substr($hash, 0, 3);
		
		if (($prefix != '$P$' && $prefix != '$H$') || strlen($hash) < 12)
		{
			return false;
		}
		
		$pre_params = substr($hash, 3, 9);
		
		if (preg_match('/[^.\/0-9A-Za-z]/', $pre_params))
		{
			return false;
		}
		
		// Extract number of rounds.
		
		$rounds = strpos(static::PHPASSP_CHARS, $pre_params[0]);
		$rounds = 1 << $rounds;
		
		$pre_params = "{$rounds}$".substr($pre_params, 1);
		
		// Decode from PHPass' custom base64.
		
		$hash_decoded = '';
		
		for ($i = 12; $i < 34; $i += 4)
		{
			$value = 0;
			
			for ($j = 3; $j >= 0; --$j)
			{
				if ($i + $j < 34)
				{
					$value |= strpos(static::PHPASSP_CHARS, $hash[$i + $j]);
				}
				
				if ($j != 0)
				{
					$value <<= 6;
				}
			}
			
			$hash_decoded .= chr($value & 255);
			$hash_decoded .= chr(($value >> 8) & 255);
			$hash_decoded .= chr(($value >> 16) & 255);
		}
		
		// Rehash the result.
		
		return PassHash::hash($hash_decoded,
		[
			'pre-algo' => 'phpassp',
			'pre-params' => $pre_params,
		]);
	}
}
