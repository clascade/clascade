<?php

namespace Clascade\Providers;
use Clascade\Core;
use Clascade\Exception;

class CoreProvider
{
	public $layer_paths = [];
	public $cache;
	
	public $bindings = [];
	public $instances = [];
	
	public $load_handler;
	public $load_params;
	public $dependency_checks_enabled = true;
	public $environment_checks_enabled = true;
	
	public function __construct ($layer_paths, $cache)
	{
		$this->cache = $cache;
		
		foreach ($layer_paths as $layer_path)
		{
			if (file_exists($layer_path))
			{
				$this->layer_paths[] = realpath($layer_path);
			}
		}
	}
	
	//== Initialization ==/
	
	public function init ()
	{
		$this->initFunctions();
		$this->initEnvironment();
		$this->initAutoload();
		$this->checkEnvironment();
	}
	
	public function initFunctions ()
	{
		for ($i = count($this->layer_paths) - 1; $i >= 0; --$i)
		{
			$path = "{$this->layer_paths[$i]}/functions.php";
			
			if (file_exists($path))
			{
				$this->load($path);
			}
		}
	}
	
	public function initEnvironment ()
	{
		ini_set('default_charset', 'UTF-8');
		
		if (extension_loaded('mbstring'))
		{
			mb_internal_encoding('UTF-8');
			mb_substitute_character(65533);
		}
	}
	
	public function initAutoload ()
	{
		spl_autoload_register([$this, 'autoload']);
	}
	
	public function checkEnvironment ($force=null)
	{
		if (!$this->environment_checks_enabled && !$force)
		{
			return;
		}
		
		if (ini_get('mbstring.func_overload') & 2 !== 0)
		{
			throw new Exception\ConfigurationException('PHP\'s mbstring.func_overload setting is enabled for string functions. This can lead to dangerous side effects.');
		}
	}
	
	/**
	 * PSR-0 class autoloader.
	 */
	
	public function autoload ($class_name)
	{
		$path = $this->getPathByClassName($class_name);
		$path = $this->getEffectivePath($path);
		
		if (file_exists($path))
		{
			$this->load($path);
		}
	}
	
	/**
	 * Load a script with a clean variable scope.
	 */
	
	public function load ($handler, $context=null, $object_method=null, $params=null)
	{
		if ($context === null)
		{
			$context = $this;
		}
		
		$params = (array) $params;
		
		if (is_array($handler) || $handler instanceof \Closure)
		{
			// The handler is a callable function.
			
			return call_user_func($handler, $context, $params);
		}
		elseif (is_object($handler))
		{
			// The handler is a regular object.
			
			if ($object_method === null)
			{
				$object_method = 'load';
			}
			
			return $handler->$object_method($context, $params);
		}
		else
		{
			// The handler is a PHP script.
			
			$this->load_handler = $handler;
			$this->load_params = [];
			
			// Convert keys into the format for variables.
			
			foreach ($params as $key => $value)
			{
				$this->load_params[str_replace('-', '_', $key)] = $value;
			}
			
			// Create an anonymous function to hold the variable scope.
			
			$closure = function ()
			{
				extract(\Clascade\Core::provider()->load_params);
				require (\Clascade\Core::provider()->load_handler);
			};
			
			$closure = $closure->bindTo($context);
			$result = $closure();
			
			// Clean up.
			
			$this->load_handler = null;
			$this->load_params = null;
			return $result;
		}
	}
	
	public function getEffectivePath ($rel_path)
	{
		return Core::getEffectivePathGeneric($rel_path, $this->layer_paths, $this->cache);
	}
	
	public function getPathByClassName ($class_name)
	{
		return Core::getPathByClassNameGeneric($class_name);
	}
	
	public function getCache ($key=null)
	{
		if ($key === null)
		{
			return $this->cache;
		}
		else
		{
			return $this->cache[$key];
		}
	}
	
	public function getLayerPaths ()
	{
		return $this->layer_paths;
	}
	
	//== Inversion of control ==//
	
	public function make ($class_name)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		return $this->makeByArray($class_name, $args);
	}
	
	public function makeByArray ($class_name, $args=null)
	{
		$args = (array) $args;
		
		if (!isset ($this->bindings[$class_name][0]))
		{
			return $this->build($class_name, $args);
		}
		
		$binding = $this->bindings[$class_name][0];
		$builder = $binding['builder'];
		$args += $binding['args'];
		
		if (is_array($builder) || $builder instanceof \Closure)
		{
			return call_user_func_array($builder, $args);
		}
		elseif (is_object($builder))
		{
			return $builder;
		}
		else
		{
			return $this->makeByArray((string) $builder, $args);
		}
	}
	
	public function build ($class_name, $args=null)
	{
		$args = (array) $args;
		$class = new \ReflectionClass($class_name);
		$constructor = $class->getConstructor();
		
		if ($constructor === null)
		{
			// There's no constructor.
			
			return new $class_name;
		}
		
		$params = $constructor->getParameters();
		$args = $this->getInvocationArgs($params, $args);
		return $class->newInstanceArgs($args);
	}
	
	public function getInvocationArgs ($params, $args)
	{
		$effective_args = [];
		
		foreach ($params as $pos => $param)
		{
			$param_name = $param->getName();
			
			if (array_key_exists($param_name, $args))
			{
				$value = $args[$param_name];
			}
			elseif (array_key_exists($pos, $args))
			{
				$value = $args[$pos];
			}
			elseif ($param->isOptional())
			{
				$value = $param->getDefaultValue();
			}
			else
			{
				$type = $param->getClass();
				
				if ($type === null)
				{
					// This argument is type-hinted with a primitive
					// type, but no value has been provided. This will
					// fail when we attempt to instantiated it.
					
					$value = null;
				}
				else
				{
					// This argument is type hinted with a class name.
					// Attempt to build a suitable object automatically.
					
					$value = $this->makeByArray($type->getName(), []);
				}
			}
			
			$effective_args[$pos] = $value;
		}
		
		return $effective_args;
	}
	
	public function singleton ($class_name)
	{
		if (!isset ($this->instances[$class_name][0]))
		{
			$this->instances[$class_name][0] = $this->makeByArray($class_name, []);
		}
		
		return $this->instances[$class_name][0];
	}
	
	public function bind ($class_name, $builder, $args=null)
	{
		$binding =
		[
			'builder' => $builder,
			'args' => (array) $args,
		];
		
		if (isset ($this->bindings[$class_name]))
		{
			array_unshift($this->bindings[$class_name], $binding);
		}
		else
		{
			$this->bindings[$class_name][] = $binding;
		}
		
		if (isset ($this->instances[$class_name]))
		{
			array_unshift($this->instances[$class_name], null);
		}
		else
		{
			$this->instances[$class_name][] = null;
		}
	}
	
	public function unbind ($class_name)
	{
		if (isset ($this->bindings[$class_name]))
		{
			if (count($this->bindings[$class_name]) > 1)
			{
				array_shift($this->bindings[$class_name]);
			}
			else
			{
				unset ($this->bindings[$class_name]);
			}
			
			array_shift($this->instances[$class_name]);
		}
	}
	
	public function unbindAll ($class_name=null)
	{
		if ($class_name === null)
		{
			$this->bindings = [];
		}
		else
		{
			unset ($this->bindings[$class_name]);
		}
	}
	
	public function isBound ($class_name)
	{
		return isset ($this->bindings[$class_name][0]);
	}
	
	public function getCallable ($ref, $default_method=null, $hook=null, $use_singleton=null)
	{
		if ($use_singleton === null)
		{
			$use_singleton = true;
		}
		
		if (is_object($ref) || is_array($ref))
		{
			return $ref;
		}
		
		$ref = (string) $ref;
		$pos = strpos($ref, '#');
		
		if ($pos !== false)
		{
			$scope = substr($ref, 0, $pos);
			$method = substr($ref, $pos + 1);
			$is_static = false;
		}
		else
		{
			$pos = strpos($ref, '::');
			
			if ($pos !== false)
			{
				$scope = substr($ref, 0, $pos);
				$method = substr($ref, $pos + 2);
				$is_static = true;
			}
			else
			{
				$scope = $ref;
				$method = ($default_method === null ? 'handle' : $default_method);
				$is_static = false;
			}
		}
		
		return function () use ($scope, $method, $is_static, $use_singleton, $hook)
		{
			$args = func_get_args();
			
			if (!$is_static)
			{
				if ($use_singleton)
				{
					$scope = Core::singleton($scope);
				}
				else
				{
					$scope = Core::make($scope);
				}
			}
			
			if ($hook !== null)
			{
				Hook::handle($hook);
			}
			
			return call_user_func_array([$scope, $method], $args);
		};
	}
}
