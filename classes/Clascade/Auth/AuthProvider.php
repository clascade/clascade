<?php

namespace Clascade\Auth;
use Clascade\Exception;
use Clascade\Session;
use Clascade\Util\PassHash;

class AuthProvider
{
	public $user;
	public $store;
	public $credential_sources = [];
	
	public function init ()
	{
		// Initialize all configured credential sources.
		
		$this->credential_sources = [];
		
		foreach ((array) conf('common.auth.sources-enabled') as $class_name)
		{
			$source = make($class_name);
			$this->credential_sources[get_class($source)] = $source;
		}
		
		// Check whether an authenticated session already exists.
		
		if (session_exists('clascade.auth.user'))
		{
			$user = session_get('clascade.auth.user');
			$this->user = make('Clascade\Auth\User', $user['ident'], $user['meta'], $user['user-id']);
		}
		else
		{
			// Try authenticating from a credential source.
			
			foreach ($this->credential_sources as $class_name => $auth_source)
			{
				if ($auth_source->authenticate())
				{
					// The user has been authenticated.
					
					session_set('clascade.auth.sources.', $class_name);
					break;
				}
			}
			
			if ($this->user === null)
			{
				// The user is unauthenticated.
				
				$this->user = make('Clascade\Auth\User', 'unauthenticated:guest',
				[
					'display-name' => 'Guest',
				]);
			}
		}
	}
	
	public function getStore ()
	{
		if ($this->store === null)
		{
			$this->store = make(conf('common.auth.default-store'));
		}
		
		return $this->store;
	}
	
	public function getUser ($auth_ident=null)
	{
		if ($auth_ident === null)
		{
			return $this->user;
		}
		else
		{
			return $this->getStore()->getUserByAuthIdent($auth_ident);
		}
	}
	
	//== Passwords ==//
	
	/**
	 * Perform a password trial.
	 *
	 * This will keep track of failed login attempts and will
	 * enforce throttling. It also performs automatic password
	 * rehashing, if needed.
	 *
	 * If a login attempt fails, it will create a deliberate delay
	 * in the response time. This guards against timing attacks.
	 */
	
	public function attemptPassword ($auth_ident, $password)
	{
		$start_time = microtime(true);
		$user_store = $this->getStore();
		$user = $user_store->getUserByAuthIdent($auth_ident);
		
		if ($user === false)
		{
			// Unrecognized user.
		}
		elseif (!PassHash::verify($password, $user['pass-hash']))
		{
			// Incorrect password.
			
			$user = false;
		}
		
		if ($user === false)
		{
			// Authentication failed.
			
			$time_remaining = $this->getThrottleTimeRemaining($auth_ident) + conf('common.auth.login-attempt-period');
			$user_store->updateFailStatus($auth_ident, $time_remaining);
			
			// Sleep to guard against timing attacks.
			
			$this->passwordSleep($start_time);
			return false;
		}
		
		// The user is successfully authenticated.
		
		// Now that we temporarily know the user's password, we have
		// an opportunity to regenerate the password hash if needed.
		
		if (PassHash::needsRehash($user['pass-hash']))
		{
			// This hash is due for regeneration.
			
			$pass_hash = PassHash::hash($password);
			$user['pass-hash'] = PassHash::hash($password);
			$user->save('pass-hash');
		}
		
		return $user;
	}
	
	/**
	 * Sleep to help guard against timing attacks.
	 *
	 * This will sleep a configured number of seconds from
	 * $start_time, with microsecond precision. $start_time should
	 * be the result of microtime(true) from before the
	 * authentication logic began.
	 */
	
	public function passwordSleep ($start_time)
	{
		$sleep_time = conf('common.auth.password-sleep-time');
		$sleep_time -= round((microtime(true) - $start_time) * 1000000);
		usleep($sleep_time);
	}
	
	public function isAcceptablePassword ($password)
	{
		return (PassHash::getMaxEntropy($password) >= conf('common.auth.password-min-score'));
	}
	
	//== Password trial throttling ==//
	
	public function isThrottled ($auth_ident)
	{
		return ($this->getThrottleTimeRemaining($auth_ident) > conf('common.auth.max-login-attempts-per-period') * conf('common.auth.login-attempt-period'));
	}
	
	public function getThrottleTimeRemaining ($auth_ident)
	{
		$throttle = $this->getStore()->getFailStatus($auth_ident);
		
		if ($throttle === false)
		{
			return 0;
		}
		else
		{
			// Logins on this auth ident were throttled. Calculate
			// how much time remains, if any.
			
			$time_remaining = $throttle['fail-timeout'] + $throttle['last-fail'] - $throttle['server-time'];
			return max($time_remaining, 0);
		}
	}
	
	//== Login session management ==//
	
	/**
	 * Attempt to direct the client to provide authentication
	 * information.
	 *
	 * This will typically be achieved using a complete HTTP
	 * response, such as a redirect to a login page. If a solution
	 * is found, this function will throw either a
	 * RedirectionException or a PageLoadedException.
	 */
	
	public function prompt ()
	{
		foreach ($this->credential_sources as $source)
		{
			$source->prompt($this);
		}
	}
	
	/**
	 * Set the session's authentication status to the given user,
	 * effectively completing a login.
	 *
	 * If applicable, $credential_source should either be the
	 * responsible credential source object, or a string containing
	 * its fully-qualified class (the same value returned by
	 * get_class($this) from within the credential source object).
	 * This will be used during the logout process to desist the
	 * credentials.
	 */
	
	public function setUser ($user, $credential_source=null)
	{
		if (is_string($user))
		{
			$user = $this->getStore()->getUserByAuthIdent($user);
		}
		
		if (!($user instanceof User))
		{
			throw new Exception\InvalidArgumentException('Invalid $user argument.');
		}
		
		Session::regen();
		$this->user = $user;
		session_set('clascade.auth.user',
		[
			'ident' => $user->auth_ident,
			'meta' => $user->meta,
			'user-id' => $user->id,
		]);
		
		if ($credential_source !== null)
		{
			if (is_object($credential_source))
			{
				$credential_source = get_class($credential_source);
			}
			
			session_set('clascade.auth.sources.', $credential_source);
		}
	}
	
	/**
	 * Log out the user.
	 *
	 * This function will return null or a string, depending on
	 * which credential sources were used. If it returns a string,
	 * this will be a URL that the user must visit in order to
	 * complete the logout. The URL can be fed to redirect().
	 */
	
	public function endSession ()
	{
		// Before we destroy the session, we need to keep a list
		// of the credential sources that were used to
		// authenticate the user.
		
		$session_sources = (array) session_get('clascade.auth.sources');
		$session_sources = array_flip($session_sources);
		
		// Destroy the session and create a new one.
		
		Session::regen();
		
		// Attempt to desist credentials from all credential sources.
		
		$needs_lock = false;
		$redirect_url = null;
		
		foreach ($this->credential_sources as $class_name => $source)
		{
			$result = $source->desist(isset ($session_sources[$class_name]));
			
			if ($result !== true)
			{
				if ($result === false || $redirect !== null)
				{
					// Either the desist failed, or multiple credential
					// sources require a redirect to complete the desist.
					// We have to lock the session.
					
					$needs_lock = true;
				}
				else
				{
					// Desisting this credential source requires a redirect.
					
					$redirect_url = $result;
				}
			}
		}
		
		if ($needs_lock)
		{
			Session::lock();
		}
		
		$this->getStore()->clearAuthTokens($this->user->id);
		return $redirect_url;
	}
}
