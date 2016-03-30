<?php

namespace Clascade\Providers;
use Clascade\Exception;
use Clascade\Util\Filesystem;
use Clascade\Session;

class ResponseProvider
{
	public function redirect ($location=null, $status=null)
	{
		if ($location === null)
		{
			$location = request_redirect_to($_SERVER['REQUEST_URI']);
		}
		
		$userinfo = null;
		
		if (is_array($status))
		{
			if (isset ($status['userinfo']))
			{
				$userinfo = $status['userinfo'];
			}
			
			$status = isset ($status['status']) ? $status['status'] : null;
		}
		
		$phrase = null;
		
		if ($status === null)
		{
			$status = ($_SERVER['REQUEST_METHOD'] === 'POST' ? 303 : 302);
		}
		elseif (is_string($status))
		{
			$status = explode(' ', $status, 2);
			
			if (count($status) == 2 && $status[1] != '')
			{
				$phrase = $status[1];
			}
			
			$status = $status[0];
		}
		
		$status = (int) $status;
		
		if (substr($location, 0, 1) == '/')
		{
			$location = url_base($userinfo).$location;
		}
		
		throw new Exception\RedirectionException("Redirecting to \"{$location}\".", $status, null, $location, $phrase);
	}
	
	public function createRedirectTo ($url=null)
	{
		if ($url === null)
		{
			$url = request_path().request_query();
		}
		
		$hmac = hash_hmac('sha512', $url, session_get('clascade.session.csrf-token'), true);
		$hmac = Base64::encodeNice($hmac);
		return "{$hmac}.{$url}";
	}
	
	/**
	 * Send a static file to the client, in a cacheable manner.
	 *
	 * You should only use this function if requests to the same URL
	 * should always lead to the same static file path.
	 */
	
	public function sendFile ($path)
	{
		if (isset ($_SERVER['IF-MODIFIED-SINCE']))
		{
			if (strtotime($_SERVER['IF-MODIFIED-SINCE']) > filemtime($path))
			{
				header("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
				return true;
			}
		}
		
		header('Content-type: '.Filesystem::contentType($path));
		header('Cache-Control: private');
		
		if (conf('common.server.xsendfile-enabled'))
		{
			// X-Sendfile is enabled. Instead of sending the file
			// ourselves, we're going to tell the webserver to do
			// it. This can help improve performance.
			
			header("X-Sendfile: {$path}");
		}
		else
		{
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($path)).' GMT');
			header('Content-Length: '.filesize($path));
			
			readfile($path);
		}
	}
}
