<?php

namespace Clascade;

class Hook extends StaticProxy
{
	public static function addByArray ($hook, $handlers)
	{
		return static::provider()->addByArray($hook, $handlers);
	}
	
	public static function add ($hook, $handler)
	{
		$handlers = func_get_args();
		$handlers = array_slice($handlers, 1);
		return $this->addByArray($hook, $handlers);
	}
	
	public static function addBefore ($hook, $handler, $index=null)
	{
		return static::provider()->addBefore($hook, $handler, $index);
	}
	
	public static function handleByArray ($hook, $args=null)
	{
		return static::provider()->handleByArray($hook, $args);
	}
	
	public static function handle ($hook)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		return static::provider()->handleByArray($hook, $args);
	}
}
