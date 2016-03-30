<?php

namespace Clascade\Session\Manager;

class PHPSession extends \Clascade\Session\Manager
{
	public $handler;
	
	public function __construct ()
	{
		$handler = conf('common.session.manager.php-session.handler');
		$this->handler = make($handler);
	}
	
	public function open ()
	{
		session_set_save_handler($this->handler, false);
		session_start();
	}
	
	public function close ()
	{
		session_write_close();
	}
	
	public function read ()
	{
		if (!isset ($_SESSION['clascade-data']))
		{
			return false;
		}
		
		return $_SESSION['clascade-data'];
	}
	
	public function write ($data)
	{
		$_SESSION['clascade-data'] = $data;
	}
	
	public function id ()
	{
		return session_id();
	}
	
	public function clear ()
	{
		session_destroy();
		session_start();
		session_regenerate_id(true);
	}
	
	public function createNew ()
	{
		session_regenerate_id();
	}
}
