<?php

namespace Clascade\Providers;
use Clascade\Core;

class HookProvider
{
	public $handlers = [];
	
	public function __construct ()
	{
		foreach ((array) conf('common.hooks') as $hook => $handlers)
		{
			foreach ($handlers as $handler)
			{
				$this->handlers[$hook][] = Core::getCallable($handler);
			}
		}
	}
	
	public function addByArray ($hook, $handlers=null)
	{
		foreach ((array) $handlers as $handler)
		{
			$this->handlers[$hook][] = Core::getCallable($handler);
		}
	}
	
	public function add ($hook, $handler)
	{
		$handlers = func_get_args();
		$handlers = array_slice($handlers, 1);
		return $this->addByArray($hook, $handlers);
	}
	
	public function addBefore ($hook, $handler, $index=null)
	{
		if (isset ($this->handlers[$hook]))
		{
			array_splice($this->handlers[$hook], (int) $index, 0, [Core::getCallable($handler)]);
		}
		else
		{
			$this->add($hook, $handler);
		}
	}
	
	public function handleByArray ($hook, $args=null)
	{
		$args = (array) $args;
		
		if (isset ($this->handlers[$hook]))
		{
			foreach ($this->handlers[$hook] as $handler)
			{
				if (call_user_func_array($handler, $args) === false)
				{
					return false;
				}
			}
		}
		
		return true;
	}
	
	public function handle ($hook)
	{
		$args = func_get_args();
		$args = array_slice($params, 1);
		return $this->handleByArray($hook, $args);
	}
}
