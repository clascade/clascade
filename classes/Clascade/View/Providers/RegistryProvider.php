<?php

namespace Clascade\View\Providers;

class RegistryProvider
{
	public $handlers = [];
	public $last_nonce = -1;
	
	public function get ($view)
	{
		return (isset ($this->handlers[$view]) ? $this->handlers[$view] : null);
	}
	
	public function set ($view, $handler)
	{
		$handlers[$view] = $handler;
	}
	
	public function nonce ()
	{
		return ++$this->last_nonce;
	}
}
