<?php

namespace Clascade;

abstract class StaticProxy
{
	public static function provider ()
	{
		$class_name = get_called_class();
		
		if (!Core::isBound($class_name))
		{
			Core::bind($class_name, static::getProviderClass());
		}
		
		return Core::singleton($class_name);
	}
	
	public static function getProviderClass ()
	{
		$class_name = get_called_class();
		$pos = strrpos($class_name, '\\');
		
		if ($pos === false)
		{
			$provider_class = $class_name;
		}
		else
		{
			$provider_class = substr($class_name, 0, $pos).'\Providers'.substr($class_name, $pos);
		}
		
		return "{$provider_class}Provider";
	}
}
