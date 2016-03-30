<?php

namespace Clascade\Session;
use Clascade\Util\Crypto;
use Clascade\Exception;

class SessionProvider
{
	public $manager;
	public $data;
	public $key;
	public $csrf_exempt = false;
	
	//== Session data ==//
	
	public function exists ($path=null)
	{
		return array_exists($this->data, $path);
	}
	
	public function get ($path=null, $default=null)
	{
		return array_get($this->data, $path, $default);
	}
	
	public function set ($path=null, $value=null)
	{
		// Make sure the value only contains scalar values and/or arrays.
		
		if (is_array($value))
		{
			array_walk_recursive($value, function (&$value)
			{
				if (!is_scalar($value))
				{
					$value = (string) $value;
				}
			});
		}
		elseif (!is_scalar($value))
		{
			$value = (string) $value;
		}
		
		return array_set($this->data, $path, $value);
	}
	
	public function delete ($path=null)
	{
		return array_delete($this->data, $path);
	}
	
	//== Encryption key ==//
	
	public function getKey ()
	{
		if (!isset ($this->key))
		{
			$this->initKey();
		}
		
		return $this->key;
	}
	
	public function initKey ()
	{
		if (isset ($_COOKIE['sesskey']))
		{
			$sesskey = nice64_decode($_COOKIE['sesskey']);
		}
		else
		{
			$sesskey = null;
		}
		
		$sid_hash = hash('sha512', $this->getManager()->id(), true);
		
		if ($sesskey !== null && strlen($sesskey) == 128 && substr($sesskey, 0, 64) == $sid_hash)
		{
			$this->key = substr($sesskey, 64);
		}
		else
		{
			$this->regenKey($sid_hash);
		}
	}
	
	public function regenKey ($sid_hash=null)
	{
		if ($sid_hash === null)
		{
			$sid_hash = hash('sha512', $this->getManager()->id(), true);
		}
		
		$this->key = rand_bytes(64);
		setcookie('sesskey', nice64_encode($sid_hash.$this->key), 0, '/', '', request_is_https());
	}
	
	//== Locking ==//
	
	public function lock ()
	{
		$this->set('clascade.session.locked', true);
	}
	
	public function isLocked ()
	{
		return (bool) $this->get('clascade.session.locked');
	}
	
	//== Manager ==//
	
	public function getManager ()
	{
		if ($this->manager === null)
		{
			$this->manager = make(conf('common.session.default-manager'));
		}
		
		return $this->manager;
	}
	
	public function clear ()
	{
		$this->getManager()->clear();
		$this->initData();
	}
	
	public function createNew ()
	{
		$this->getManager()->createNew();
		$this->initData();
	}
	
	public function read ()
	{
		$data = $this->getManager()->read();
		$data = Crypto::decrypt($this->getKey(), $data);
		
		if ($data !== false)
		{
			$data = unserialize($data);
		}
		
		return (is_array($data) ? $data : []);
	}
	
	public function write ($data)
	{
		$data = serialize($data);
		$data = Crypto::encrypt($this->getKey(), $data);
		$this->getManager()->write($data);
	}
	
	//== Lifecycle ==//
	
	public function init ()
	{
		$this->getManager()->open();
		$this->initData();
		
		if (!$this->initState())
		{
			return false;
		}
		
		register_shutdown_function([$this, 'close']);
		return true;
	}
	
	public function close ()
	{
		$this->write($this->data);
		$this->getManager()->close();
	}
	
	public function regen ()
	{
		$this->clear();
		$this->initState();
	}
	
	public function initData ()
	{
		$this->key = null;
		$this->data = $this->read();
	}
	
	public function initState ()
	{
		$time = time();
		
		if ($this->exists('clascade.session.host') && $this->get('clascade.session.host') !== $_SERVER['HTTP_HOST'])
		{
			// This session is for a different domain. Create a
			// new session, without deleting the existing one.
			
			$this->createNew();
			$this->delete('clascade.session.host');
		}
		
		if (!$this->exists('clascade.session.host'))
		{
			$this->delete('clascade.session.locked');
			$this->delete('clascade.session.last-time');
		}
		
		if ($this->isLocked())
		{
			// The session is locked. This can happen when a user
			// tries to log out of an authentication source that
			// can't reliably desist login credentials, such as
			// with some single sign-on solutions. In these cases,
			// a browser restart may be required.
			
			return false;
		}
		
		if (!$this->exists('clascade.session.last-time') || $time - $this->get('clascade.session.last-time') > conf('common.session.expire-time-seconds'))
		{
			// This is either a new uninitialized session, or it
			// has gone too long without activity. Destroy it and
			// create a new one.
			
			// We do this for new sessions in order to guarantee
			// that the session ID comes from us. This addresses
			// session fixation attacks.
			
			$this->clear();
		}
		
		$this->set('clascade.session.last-time', $time);
		
		if (!$this->exists('clascade.session.host'))
		{
			$this->set('clascade.session.host', $_SERVER['HTTP_HOST']);
		}
		
		$info_hash = hash('sha512', $this->getInfoHashData(), true);
		
		if (!$this->exists('clascade.session.info-hash'))
		{
			$this->set('clascade.session.info-hash', $info_hash);
		}
		elseif (!str_equals($this->get('clascade.session.info-hash'), $info_hash))
		{
			// The session has a different infohash, indicating
			// that the client seems to be different from when the
			// session was created. This may be a sign of a stolen
			// session. Don't use it.
			
			return false;
		}
		
		if (!$this->exists('clascade.session.csrf-token'))
		{
			$this->set('clascade.session.csrf-token', rand_bytes(32));
		}
		
		return true;
	}
	
	//== Miscellaneous ==//
	
	public function getInfoHashData ()
	{
		$data = [];
		
		if (isset ($_SERVER['HTTP_USER_AGENT']))
		{
			$data[] = base64_encode($_SERVER['HTTP_USER_AGENT']);
		}
		
		return implode(':', $data);
	}
	
	public function csrfToken ()
	{
		$csrf_token = $this->get('clascade.session.csrf-token');
		return nice64_encode(Crypto::mask($csrf_token));
	}
	
	public function validateCSRFToken ($request_method)
	{
		if ($this->csrf_exempt)
		{
			return true;
		}
		
		switch (strtoupper($request_method))
		{
		case 'GET':
		case 'HEAD':
			// Exempt from CSRF token validation.
			break;
		
		default:
			// Validate CSRF token.
			
			$token_name = conf('common.field-names.csrf-token');
			
			if (!isset ($_POST[$token_name]))
			{
				return false;
			}
			
			$submitted_token = Crypto::unmask(nice64_decode($_POST[$token_name]));
			
			if (!str_equals($this->get('clascade.session.csrf-token'), $submitted_token))
			{
				return false;
			}
		}
		
		return true;
	}
}
