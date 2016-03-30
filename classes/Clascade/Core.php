<?php

namespace Clascade;

/**
 * Clascade core.
 *
 * This is a special class that can't be overridden by other layers,
 * because it is loaded before the layers are initialized.
 *
 * The provider management duplicates the behavior of StaticProxy. But
 * for bootstrapping reasons, it doesn't actually use StaticProxy.
 */

class Core
{
	public static $provider;
	public static $replaced_providers = [];
	
	public static function provider ()
	{
		if (!isset (static::$provider))
		{
			static::$provider = static::createProvider();
		}
		
		return static::$provider;
	}
	
	public static function createProvider ($provider_class=null, $layer_paths=null, $cache=null)
	{
		if ($provider_class === null)
		{
			$provider_class = static::getProviderClass();
		}
		
		if ($layer_paths === null)
		{
			$layer_paths =
			[
				__DIR__.'/../..',
				__DIR__.'/../../../app',
				__DIR__.'/../../../local',
			];
		}
		
		if ($cache === null)
		{
			// Load cache from last (local) layer, if present.
			
			$path = $layer_paths[count($layer_paths) - 1].'/cache.json';
			
			if (file_exists($path))
			{
				$cache = json_decode(file_get_contents($path), true);
			}
		}
		
		if ($cache === false)
		{
			$cache = null;
		}
		
		$path = static::getPathByClassNameGeneric($provider_class);
		$path = static::getEffectivePathGeneric($path, $layer_paths, $cache);
		require ($path);
		return new $provider_class($layer_paths, $cache);
	}
	
	public static function replaceProvider ($new_provider)
	{
		static::$replaced_providers[] = static::$provider;
		static::$provider = $new_provider;
	}
	
	public static function restoreProvider ()
	{
		static::$provider = array_pop(static::$replaced_providers);
	}
	
	public static function getProviderClass ()
	{
		return 'Clascade\Providers\CoreProvider';
	}
	
	public static function getEffectivePathGeneric ($rel_path, $layer_paths, $cache=null)
	{
		if (isset ($cache['effective-paths']))
		{
			// The cache has been initialized. Use it to find
			// the effective path.
			
			if (isset ($cache['effective-paths'][$rel_path]))
			{
				return $cache['effective-paths'][$rel_path];
			}
		}
		else
		{
			for ($i = count($layer_paths) - 1; $i > 0; --$i)
			{
				$path = $layer_paths[$i].$rel_path;
				
				if (file_exists($path))
				{
					return $path;
				}
			}
		}
		
		return $layer_paths[0].$rel_path;
	}
	
	public static function getPathByClassNameGeneric ($class_name)
	{
		$class_name = ltrim($class_name, '\\');
		
		if ($pos = strrpos($class_name, '\\'))
		{
			$namespace = substr($class_name, 0, $pos);
			$class_name = substr($class_name, $pos + 1);
			$path  = str_replace('\\', '/', $namespace).'/';
		}
		else
		{
			$namespace = '';
			$path = '';
		}
		
		$path .= str_replace('_', '/', $class_name);
		return "/classes/{$path}.php";
	}
	
	//== Provider shortcuts ==//
	
	public static function load ($handler, $context=null, $object_method=null, $params=null)
	{
		return static::provider()->load($handler, $context, $object_method, $params);
	}
	
	public static function getCache ($key=null)
	{
		return static::provider()->getCache($key);
	}
	
	public static function getLayerPaths ()
	{
		return static::provider()->getLayerPaths();
	}
	
	public static function make ($class_name)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		return static::provider()->makeByArray($class_name, $args);
	}
	
	public static function makeByArray ($class_name, $args=null)
	{
		return static::provider()->makeByArray($class_name, $args);
	}
	
	public static function singleton ($class_name)
	{
		return static::provider()->singleton($class_name);
	}
	
	public static function bind ($class_name, $builder, $is_singleton=null)
	{
		return static::provider()->bind($class_name, $builder, $is_singleton);
	}
	
	public static function unbind ($class_name)
	{
		return static::provider()->unbind($class_name);
	}
	
	public static function unbindAll ($class_name=null)
	{
		return static::provider()->unbindAll($class_name);
	}
	
	public static function isBound ($class_name)
	{
		return static::provider()->isBound($class_name);
	}
	
	public static function getCallable ($ref, $default_method=null, $use_singleton=null)
	{
		return static::provider()->getCallable($ref, $default_method, $use_singleton);
	}
	
	public static function getEffectivePath ($rel_path)
	{
		return static::provider()->getEffectivePath($rel_path);
	}
	
	public static function getPathByClassName ($class_name)
	{
		return static::provider()->getPathByClassName($class_name);
	}
}
