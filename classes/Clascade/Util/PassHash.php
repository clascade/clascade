<?php

namespace Clascade\Util;
use Clascade\Exception;

class PassHash
{
	public static function hash ($password, $options=null)
	{
		// Prepare hash parameters. The defaults are recommended
		// for optimal security.
		
		// Prehashing is meant for cases where existing password
		// hashes are being migrated from another system. It
		// probably isn't a good idea to use it otherwise.
		
		$pre_algo   = (string) (isset ($options['pre-algo'])   ? $options['pre-algo']   : ''); // Prehashing algorithm.
		$pre_params = (string) (isset ($options['pre-params']) ? $options['pre-params'] : ''); // Prehashing parameters.
		$algo       = (string) (isset ($options['algo'])       ? $options['algo']       : conf('common.util.pass-hash.default.algo'));
		$rounds     = (int)    (isset ($options['rounds'])     ? $options['rounds']     : static::getOptimalRounds());
		$salt_size  = (int)    (isset ($options['salt-size'])  ? $options['salt-size']  : conf('common.util.pass-hash.default.salt-size'));
		$salt       = (string) (isset ($options['salt'])       ? $options['salt']       : Rand::getBytes($salt_size));
		$encrypt    =          (isset ($options['encrypt'])    ? $options['encrypt']    : conf('common.util.pass-hash.default.encrypt'));
		$keys       =          (isset ($options['keys'])       ? $options['keys']       : null);
		$key_id     =          (isset ($options['key-id'])     ? $options['key-id']     : null);
		
		// Verify that we support this hashing algorithm family.
		// We currently only support generating PBKDF2 hashes.
		
		$algo_parts = explode('-', $algo, 2);
		
		if ($algo_parts[0] != 'pbkdf2' || count($algo_parts) < 2)
		{
			throw new Exception\InvalidArgumentException("Unsupported password hashing algorithm \"{$algo}\".");
		}
		
		// Hash the password using PBKDF2.
		
		$hash = hash_pbkdf2($algo_parts[1], $password, $salt, $rounds, 0, true);
		
		// Encode the hash components using a variant of base64.
		
		$salt_encoded = Base64::encode($salt, false, './');
		$hash_encoded = Base64::encode($hash, false, './');
		
		// Serialize the hash components. This uses a variant of
		// the modular crypt format that is compatible with
		// Python's passlib when prehashing isn't used.
		
		if ($pre_algo != '')
		{
			$pre_algo = "{$pre_algo}-";
		}
		
		if ($pre_params != '')
		{
			$pre_params = "\${$pre_params}";
		}
		
		$hash = "\${$pre_algo}{$algo}{$pre_params}\${$rounds}\${$salt_encoded}\${$hash_encoded}";
		
		if (($encrypt || $encrypt === null) && $keys === null)
		{
			// Get the encryption keys from the configuration.
			
			$keys = conf('keys.pass-hash');
			
			if ($keys == '' || (is_array($keys) && empty ($keys)))
			{
				// The keys aren't configured.
				
				if ($encrypt === null)
				{
					// Encryption wasn't explicitly requested.
					// We'll just use the non-encrypted hash.
					
					$encrypt = false;
				}
				else
				{
					// Encryption was explicitly requested,
					// either in the function call or in the
					// configuration. We can't fulfill the
					// request.
					
					throw new Exception\ConfigurationException("The \"keys.pass-hash\" configuration setting hasn't been configured. Please set this to a randomly-generated 512-bit value encoded in base64, or an array of such values.");
				}
			}
		}
		
		if ($encrypt)
		{
			// We're going to encrypt the hash.
			
			// Select an encryption key.
			
			$keys = (array) $keys;
			
			if ($key_id === null)
			{
				$key_id = Arr::randKey($keys);
			}
			elseif (!array_key_exists($key_id, $keys))
			{
				$source = isset ($options['keys']) ? 'provided keys' : '"keys.pass-hash" configuration';
				throw new Exception\InvalidArgumentException("Cannot find key ID \"{$key_id}\" in the {$source}.");
			}
			
			// Found a key. Check if it's valid.
			
			$key = base64_decode($keys[$key_id], true);
			
			if ($key === false || strlen($key) != 64)
			{
				$source = isset ($options['keys']) ? 'provided keys' : '"keys.pass-hash" configuration';
				throw new Exception\ConfigurationException("Key ID \"{$key_id}\" in the {$source} has an invalid value. Password keys should be randomly-generated 512-bit values encoded in base64.");
			}
			
			// Encrypt the hash using this key.
			
			$hash = Crypto::encrypt([$key_id => $key], $hash);
			
			// Base64-encode the hash for safe storage.
			
			$hash = base64_encode($hash);
		}
		
		return $hash;
	}
	
	public static function verify ($password, $pass_hash, $options=null)
	{
		$comps = static::getHashComponents($pass_hash, $options);
		
		if ($comps === false)
		{
			// Unrecognized hash format.
			
			return false;
		}
		
		if ($comps[1] == 'transitional')
		{
			return make('Clascade\Util\PassHash\TransitionalVerifier')->verifyPassword($password, $comps, $options);
		}
		
		// Parse the algorithm identifier.
		
		if (!preg_match('/^(?:([a-z0-9,-]*[a-z0-9])-)?(pbkdf2-[a-z0-9,-]*[a-z0-9])$/', $comps[1], $algo))
		{
			// This isn't a normal Clascade hashing algorithm.
			// Treat it as a basic crypt hash.
			
			$hash = crypt($password, $pass_hash);
			return Str::equals($pass_hash, $hash);
		}
		
		// Handle prehashing algorithms.
		
		if ($algo[1] != '')
		{
			switch ($algo[1])
			{
			case 'crypt':
				// Standard crypt hash.
				
				$crypt_salt = '';
				
				for ($i = 2; $i < count($comps) && $comps[$i] != ''; ++$i)
				{
					$crypt_salt .= "\${$comps[$i]}";
				}
				
				$password = crypt($password, $crypt_salt);
				array_splice($comps, 2, $i - 1);
				break;
			
			case 'phpassp':
				// PHPass "portable" hash.
				
				if (count($comps) < 7)
				{
					return false;
				}
				
				$pre_rounds = (int) $comps[2];
				$pre_salt = $comps[3];
				
				// Perform rounds of MD5 prehashing.
				
				$prehash = md5($pre_salt.$password, true);
				
				do {
					$prehash = md5($prehash.$password, true);
				} while (--$pre_rounds);
				
				// This MD5 hash is now our input for normal hashing.
				
				$password = $prehash;
				array_splice($comps, 2, 2);
				break;
			
			default:
				// Treat this as a primitive hash.
				
				$password = hash($algo[1], $password, true);
				
				if ($password === false)
				{
					return false;
				}
				break;
			}
		}
		
		$algo = explode('-', $algo[2], 2);
		
		switch ($algo[0])
		{
		case 'pbkdf2':
			// Salted hash with variable rounds via PBKDF2.
			
			if (count($algo) != 2 || count($comps) < 5)
			{
				return false;
			}
			
			$rounds = $comps[2];
			$salt = Base64::decode($comps[3], false, './');
			$expected_hash = Base64::decode($comps[4], false, './');
			$hash = hash_pbkdf2($algo[1], $password, $salt, $rounds, 0, true);
			return Str::equals($expected_hash, $hash);
			break;
		}
		
		// Unhandled algorithm. This should be unreachable code,
		// because the supported main algorithms should be
		// enumerated in the parsing regexp.
		
		return false;
	}
	
	//== Quality control ==//
	
	/**
	 * Returns an estimate of the maximum bits of entropy the given
	 * password may contain.
	 *
	 * This function is meant to quickly identify obviously weak
	 * weak passwords. However, even if this function produces a
	 * high score, the password might still be weak in practice.
	 *
	 * Note: This function may underestimate the entropy contributed
	 * by symbols. All non-alphanumeric characters are treated as
	 * symbols, although they are scored as if a symbol has only 32
	 * possibilities. This number is based on the number of typable
	 * non-whitespace symbols on a standard US keyboard.
	 *
	 * For the purposes of this function, a multi-byte UTF-8
	 * character is scored as a symbol and contributes one character
	 * to the password length.
	 */
	
	public static function getMaxEntropy ($password)
	{
		$max_entropy = 0;
		
		// Collect number of possible values per character,
		// based on which character classes are present in the
		// password.
		
		if (preg_match('/[a-z]/', $password))
		{
			$max_entropy += 26; // Lowercase letter.
		}
		
		if (preg_match('/[A-Z]/', $password))
		{
			
			$max_entropy += 26; // Uppercase letter.
		}
		
		if (preg_match('/[0-9]/', $password))
		{
			$max_entropy += 10; // Number.
		}
		
		if (preg_match('/[^a-zA-Z0-9]/', $password))
		{
			$max_entropy += 32; // Symbol.
		}
		
		// Calculate total number of possible passwords with
		// this length and these character classes.
		
		$max_entropy = pow($max_entropy, Str::length($password));
		
		// Convert to bits of entropy.
		
		$max_entropy = log($max_entropy, 2);
		return $max_entropy;
	}
	
	public static function needsRehash ($pass_hash, $options=null)
	{
		$comps = static::getHashComponents($pass_hash, $options);
		
		if ($comps === false)
		{
			// Unrecognized hash format.
			
			return true;
		}
		
		switch ($comps[1])
		{
		case 'pbkdf2-sha256':
		case 'pbkdf2-sha384':
		case 'pbkdf2-sha512':
			if (count($comps) < 5)
			{
				// Incorrect number of components.
				
				return true;
			}
			
			if ($comps[2] * 2 < static::getOptimalRounds())
			{
				// Not enough rounds.
				
				return true;
			}
			
			if (strlen(Base64::decode($comps[3], false, './')) < conf('common.util.pass-hash.default.salt-size'))
			{
				// Salt is too short.
				
				return true;
			}
			break;
		
		default:
			// Not a recommended algorithm.
			
			return true;
		}
		
		// This is a modern hash.
		
		return false;
	}
	
	//== Helpers ==//
	
	public static function getHashComponents ($pass_hash, $options=null)
	{
		$pass_hash = (string) $pass_hash;
		
		if ($pass_hash == '')
		{
			return false;
		}
		
		if ($pass_hash[0] != '$')
		{
			// The string doesn't begin with a "$". This means the hash
			// is either encrypted or invalid. Attempt to decrypt it.
			//
			// Note: encrypted hashes are base64-encoded, so they'll
			// never begin with a "$".
			
			$pass_hash = base64_decode($pass_hash);
			$key_id = Crypto::getKeyID($pass_hash);
			
			if ($key_id === false)
			{
				// Unrecognized encryption scheme or format.
				
				return false;
			}
			
			$keys = isset ($options['keys']) ? $options['keys'] : null;
			
			if ($keys === null)
			{
				$keys = conf('keys.pass-hash');
				
				if ($keys == '' || (is_array($keys) && empty ($keys)))
				{
					throw new Exception\ConfigurationException("The \"keys.pass-hash\" configuration setting hasn't been configured. Please set this to a randomly-generated 512-bit value encoded in base64, or an array of such values.");
				}
			}
			
			$keys = (array) $keys;
			
			if (!isset ($keys[$key_id]))
			{
				return false;
			}
			
			$key = base64_decode($keys[$key_id], true);
			
			if ($key === false || strlen($key) != 64)
			{
				$source = isset ($options['keys']) ? 'provided keys' : '"keys.pass-hash" configuration';
				throw new Exception\ConfigurationException("Key ID \"{$key_id}\" in the {$source} has an invalid value. Password keys should be randomly-generated 512-bit values encoded in base64.");
			}
			
			$pass_hash = Crypto::decrypt($key, $pass_hash);
			
			if ($pass_hash === false)
			{
				// Failed to decrypt.
				
				return false;
			}
		}
		
		$comps = explode('$', $pass_hash);
		
		if (count($comps) < 2 || $comps[0] !== '')
		{
			// Unrecognized hash format.
			
			return false;
		}
		
		return $comps;
	}
	
	public static function getOptimalRounds ($hash_strength=null)
	{
		if ($hash_strength === null)
		{
			$hash_strength = conf('common.util.pass-hash.hash-strength');
		}
		
		return (int) round($hash_strength * pow(2, (time() - 978336000) / 63113904)); // Doubles every two years.
	}
}
