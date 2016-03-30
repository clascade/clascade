<?php

namespace Clascade\Providers;
use Clascade\Exception;
use Clascade\Hook;
use Clascade\Router;

class RequestProvider
{
	public function handle ()
	{
		try
		{
			if (!Hook::handle('request'))
			{
				return;
			}
			
			Router::route($this->url(), $this->method());
		}
		catch (Exception\RedirectionException $e)
		{
			$code = $e->getCode();
			$status_phrase = $e->getStatusPhrase();
			header("{$_SERVER['SERVER_PROTOCOL']} {$code} {$status_phrase}");
			header("Location: {$e->location}");
		}
	}
	
	public function isHTTPS ()
	{
		return (!empty ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
	}
	
	public function method ()
	{
		return $_SERVER['REQUEST_METHOD'];
	}
	
	public function urlBase ($userinfo=null)
	{
		$scheme = $this->isHTTPS() ? 'https' : 'http';
		
		$userinfo = ($userinfo === null ? '' : $userinfo.'@');
		
		return "{$scheme}://{$userinfo}{$_SERVER['HTTP_HOST']}";
	}
	
	public function url ()
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	public function path ($request_uri=null)
	{
		if ($request_uri === null)
		{
			$request_uri = $this->url();
		}
		
		$pos = strpos($request_uri, '?');
		
		if ($pos === false)
		{
			return $request_uri;
		}
		
		return substr($request_uri, 0, $pos);
	}
	
	public function query ()
	{
		return (empty ($_SERVER['QUERY_STRING']) ? '' : "?{$_SERVER['QUERY_STRING']}");
	}
	
	public function getRedirectTo ($default=null, $params=null)
	{
		if ($default === null)
		{
			$default = '/';
		}
		
		$field_name = conf('common.field-names.redirect-to');
		
		if ($params === null)
		{
			$params = (isset ($_POST[$field_name]) ? $_POST : $_GET);
		}
		
		if (isset ($params[$field_name]))
		{
			$value = explode('.', $params[$field_name], 2);
			
			if (count($value) == 2)
			{
				// Authenticate the URL with the user session's CSRF token.
				
				$hmac = hash_hmac('sha512', $value[1], session_get('clascade.session.csrf-token'), true);
				
				if (str_equals($hmac, nice64_decode($value[0])))
				{
					return $params[$field_name];
				}
			}
		}
		
		return $default;
	}
}
