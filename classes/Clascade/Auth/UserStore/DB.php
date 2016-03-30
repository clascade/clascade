<?php

namespace Clascade\Auth\UserStore;
use Clascade\Auth\User;

class DB extends \Clascade\Auth\UserStore
{
	public $connection_name;
	
	public function __construct ()
	{
		$this->connection_name = conf('common.auth.store.db.connection-name');
	}
	
	public function getUserByAuthIdent ($auth_ident)
	{
		$auth_ident = User::normalizeAuthIdent($auth_ident);
		
		if (u_strlen($auth_ident) > 255)
		{
			return false;
		}
		
		$user = $this->getUserWhere(['auth_ident' => $auth_ident]);
		
		if ($user === false || $user->auth_ident !== $auth_ident)
		{
			return false;
		}
		
		return $user;
	}
	
	public function getUserByID ($user_id)
	{
		return $this->getUserWhere(['id' => $user_id]);
	}
	
	public function getID ($auth_ident)
	{
		$auth_ident = User::normalizeAuthIdent($auth_ident);
		
		if (u_strlen($auth_ident) > 255)
		{
			return false;
		}
		
		$db = db($this->connection_name);
		$row = $db->row('SELECT id, auth_ident FROM :#users WHERE auth_ident=?', $auth_ident);
		
		if ($row === false || $row->auth_ident !== $auth_ident)
		{
			return false;
		}
		
		return $row->id;
	}
	
	public function createUser ($auth_ident, $meta)
	{
		$auth_ident = User::normalizeAuthIdent($auth_ident);
		
		if (u_strlen($auth_ident) > 255)
		{
			return false;
		}
		
		// Set defaults for missing fields.
		
		$meta +=
		[
			'email' => null,
			'display-name' => (isset ($meta['email']) ? $meta['email'] : null),
			'is-admin' => false,
		];
		
		$auth_type = substr($auth_ident, 0, strpos($auth_ident, ':'));
		$meta['secret'] = nice64_encode(rand_bytes(64));
		
		// Confirm that the ident isn't already in use.
		
		$db = db($this->connection_name);
		$db->begin();
		$result = !$db->rowExistsIn(':#users WHERE auth_ident=?', $auth_ident);
		
		if ($result)
		{
			// Prepare the column data.
			
			$meta = $this->getNormalizedMeta($meta);
			
			$values = array_merge($meta['cols'],
			[
				'auth_ident' => $auth_ident,
				'auth_type' => $auth_type,
				'added' => $db->now(),
				'updated' => $db->now(),
			]);
			
			// Insert the new user row.
			
			$result = $db->insertInto(':#users')->set($values)->query();
			
			if ($result)
			{
				// Get the automatically-generated ID of the new user.
				
				$user_id = $db->lastInsertId(':#users', 'id');
				
				if ($user_id === false)
				{
					// Database engine doesn't support lastInsertId.
					// Try to look up the new user by ident instead.
					
					$user_id = $this->getID($auth_ident);
					
					if ($user_id === false)
					{
						$result = false;
					}
				}
			}
		}
		
		if (!$result)
		{
			// Failed to insert new user.
			
			$db->rollback();
			return false;
		}
		
		// Add the nonstandard meta fields.
		
		$values = [];
		
		foreach ($meta['custom'] as $field => $value)
		{
			if ($value !== null)
			{
				$values[] =
				[
					$user_id,
					$field,
					$value,
					$db->now(),
				];
			}
		}
		
		if (!empty ($values))
		{
			$db->insertInto(':#users_meta')->cols('user_id, meta_key, meta_value, updated')->values($values)->query();
		}
		
		$db->commit();
		return true;
	}
	
	public function writeMeta ($user_id, $meta)
	{
		if (!empty ($meta))
		{
			$db = db($this->connection_name);
			$db->begin();
			$meta = $this->getNormalizedMeta($meta);
			
			if (!empty ($meta['cols']))
			{
				$meta['cols']['updated'] = $db->now();
				$db->update(':#users')->set($meta['cols'])->where('id=?', $user_id)->query();
			}
			
			if (!empty ($meta['custom']))
			{
				$values = [];
				
				foreach ($meta['custom'] as $meta_key => $meta_value)
				{
					if ($meta_value !== null)
					{
						$values[] =
						[
							$user_id,
							$meta_key,
							$meta_value,
							$db->now(),
						];
					}
				}
				
				$db->query('DELETE FROM :#users_meta WHERE user_id=? AND meta_key IN (?)', $user_id, array_keys($meta['custom']));
				
				if (!empty ($values))
				{
					$db->insertInto(':#users_meta')->cols('user_id, meta_key, meta_value, updated')->values($values)->query();
				}
			}
			
			$db->commit();
		}
	}
	
	//== Transactions ==//
	
	public function begin ()
	{
		return db($this->connection_name)->begin();
	}
	
	public function commit ()
	{
		return db($this->connection_name)->commit();
	}
	
	public function rollback ()
	{
		return db($this->connection_name)->rollback();
	}
	
	public function inTransaction ()
	{
		return db($this->connection_name)->inTransaction();
	}
	
	//== Reset keys ==//
	
	public function getUserByResetKey ($k)
	{
		$reset_key = hash('sha512', $k, true);
		$reset_key = nice64_encode($reset_key);
		
		$db = db($this->connection_name);
		$user_info = $db->row('SELECT id, reset_time, ? server_time FROM :#users WHERE reset_key=?', $db->now(), $reset_key);
		
		if ($user_info === false)
		{
			return false;
		}
		
		// Check whether the reset has expired.
		
		$server_time = strtotime($user_info->server_time);
		$reset_time = strtotime($user_info->reset_time);
		
		$reset_lifetime = conf('common.auth.reset-lifetime-hours') * 60 * 60;
		
		if ($server_time - $reset_time >= $reset_lifetime)
		{
			// The reset has expired.
			
			return false;
		}
		
		return $this->getUserByID($user_info->id);
	}
	
	public function createResetKey ()
	{
		$db = db($this->connection_name);
		
		do
		{
			$k = nice64_encode(rand_bytes(16));
			$reset_key = hash('sha512', $k, true);
			$reset_key = nice64_encode($reset_key);
		}
		while ($db->rowExistsIn(':#users WHERE reset_key=?', $reset_key));
		return [$k, $reset_key];
	}
	
	//== Auth tokens ==//
	
	public function getUserByAuthToken ($token)
	{
		$token = nice64_decode($token);
		
		if (strlen($token) != 128)
		{
			return false;
		}
		
		$series_id_raw = substr($token, 0, 64);
		$series_id = base64_encode($series_id_raw);
		
		$token_hash = hash('sha512', substr($token, 64), true);
		$token_hash = base64_encode($token_hash);
		
		$db = db($this->connection_name);
		$db->begin();
		$token_info = $db->row('SELECT token_hash, user_id FROM :#users_tokens WHERE series_id=? AND expires>?', $series_id, $db->now());
		$failed = false;
		
		if ($token_info === false)
		{
			// No valid token was found.
			
			$failed = true;
		}
		elseif ($token_info->token_hash !== $token_hash)
		{
			// This isn't the current token in the series. The
			// series may have been compromised.
			
			$failed = true;
		}
		else
		{
			$user = static::getUserByID($token_info->id);
			
			if ($user === false)
			{
				// The user no longer exists.
				
				$failed = true;
			}
		}
		
		if ($failed)
		{
			// The token series is invalid or we suspect it
			// may be compromised. Delete the series.
			
			$db->query('DELETE FROM :#users_tokens WHERE series_id=?', $series_id);
			$db->commit();
			return false;
		}
		
		// The token is accepted. Generate the next one in the series.
		
		$new_token = rand_bytes(64);
		$new_token_hash = hash('sha512', $new_token, true);
		$new_token_hash = base64_encode($new_token_hash);
		
		$db->query('UPDATE :#users_tokens SET token_hash=? WHERE series_id=?', $new_token_hash, $series_id);
		$db->commit();
		
		$token = nice64_encode($series_id_raw.$new_token);
		return [$user, $token];
	}
	
	public function createAuthToken ($user_id, $expires=null)
	{
		if ($expires === null)
		{
			$expires = time() + conf('common.auth.remember-me-time');
		}
		
		$db = db($this->connection_name);
		$db->begin();
		
		do
		{
			$series_id_raw = rand_bytes(64);
			$series_id = base64_encode($series_id_raw);
		}
		while ($db->rowExistsIn(':#users_tokens WHERE series_id=?', $series_id));
		
		$token = rand_bytes(64);
		$token_hash = hash('sha512', $token, true);
		$token_hash = base64_encode($token_hash);
		
		$db->query('INSERT INTO :#users_tokens (series_id, token_hash, user_id, expires) VALUES (?, ?, ?, ?)', $series_id, $token_hash, $user_id, date('Y-m-d H:i:s', $expires));
		$db->commit();
		
		return nice64_encode($series_id_raw.$token);
	}
	
	public function clearAuthTokens ($user_id)
	{
		$db = db($this->connection_name);
		$db->query('DELETE FROM :#users_tokens WHERE user_id=?', $user_id);
	}
	
	public function getFailStatus ($auth_ident)
	{
		$auth_ident = User::normalizeAuthIdent($auth_ident);
		
		if (u_strlen($auth_ident) > 255)
		{
			return false;
		}
		
		$db = db($this->connection_name);
		$row = $db->row('SELECT last_fail, fail_timeout, ? server_time FROM :#failed_logins WHERE auth_ident=?', $db->now(), $auth_ident);
		
		if ($row === false)
		{
			return false;
		}
		
		return
		[
			'last-fail' => strtotime($row->last_fail),
			'fail-timeout' => $row->fail_timeout,
			'server-time' => strtotime($row->server_time),
		];
	}
	
	public function updateFailStatus ($auth_ident, $time_remaining)
	{
		$auth_ident = User::normalizeAuthIdent($auth_ident);
		
		if (u_strlen($auth_ident) > 255)
		{
			return;
		}
		
		$db = db($this->connection_name);
		$db->begin();
		
		// Upsert.
		
		$result = $db->query('UPDATE :#failed_logins SET last_fail=?, fail_timeout=? WHERE auth_ident=?', $db->now(), $time_remaining, $auth_ident);
		
		if ($result->rowCount() == 0)
		{
			// No row was updated. Insert instead.
			
			$db->query('INSERT INTO :#failed_logins (auth_ident, last_fail, fail_timeout) VALUES (?, ?, ?)', $auth_ident, $db->now(), $time_remaining);
		}
		
		$db->commit();
	}
	
	public function updateUserInfo ($user_id, $values)
	{
		$db = db($this->connection_name);
		$db->update(':#users')->set($values)->where('id=?', $user_id)->query();
	}
	
	//== Internal use functions ==//
	
	public function getUserWhere ($where)
	{
		$db = db($this->connection_name);
		
		// Get standard user information.
		
		$row = $db->select('id, auth_ident, email, display_name, pass_hash, secret, reset_key, reset_time, is_admin')->from(':#users')->where($where)->row();
		
		if ($row === false)
		{
			return false;
		}
		
		if ($row->reset_time !== null)
		{
			$reset_time = strtotime($row->reset_time);
			$row->reset_time = ($reset_time === false ? null : (string) $reset_time);
		}
		
		$row = (array) $row;
		$meta = [];
		
		foreach ($row as $key => $value)
		{
			if ($key != 'auth_ident' && $key != 'id')
			{
				$meta[str_replace('_', '-', $key)] = $value;
			}
		}
		
		// Get custom meta information.
		
		$meta_rows = $db->query('SELECT meta_key, meta_value FROM :#users_meta WHERE user_id=?', $row['id']);
		
		foreach ($meta_rows as $row)
		{
			$meta[$row->meta_key] = $row->meta_value;
		}
		
		return make('Clascade\Auth\User', $row['auth_ident'], $meta, $row['id']);
	}
	
	public function getNormalizedMeta ($meta)
	{
		$normalized =
		[
			'cols' => [],
			'custom' => [],
		];
		
		foreach ($meta as $field => $value)
		{
			switch ($field)
			{
			case 'email':
			case 'display-name':
			case 'pass-hash':
			case 'secret':
			case 'reset-key':
			case 'is-admin':
				$field = str_replace('-', '_', $field);
				$normalized['cols'][$field] = $value;
				break;
				
			case 'reset-time':
				if ($value !== null)
				{
					$value = date('Y-m-d H:i:s', $value);
				}
				
				$normalized['cols']['reset_time'] = $value;
				break;
			
			default:
				$normalized['custom'][$field] = $value;
				break;
			}
		}
		
		return $normalized;
	}
}
