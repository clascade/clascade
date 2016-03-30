<?php

namespace Clascade\Providers;
use Clascade\Core;
use Clascade\Exception;

class ConfProvider
{
	public $cache = [];
	
	public function exists ($path)
	{
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		$conf_file = $path[0];
		
		if (!isset ($this->cache[$conf_file]))
		{
			$this->cache[$conf_file] = $this->getFile($conf_file);
		}
		
		if ($this->cache[$conf_file] === false)
		{
			return false;
		}
		
		if (count($path) == 1)
		{
			return true;
		}
		
		array_shift($path);
		return array_exists($this->cache[$conf_file], $path);
	}
	
	public function get ($path, $default=null)
	{
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		$conf_file = $path[0];
		
		if (!isset ($this->cache[$conf_file]))
		{
			$this->cache[$conf_file] = $this->getFile($conf_file);
		}
		
		if ($this->cache[$conf_file] === false)
		{
			return $default;
		}
		
		if (count($path) == 1)
		{
			return $this->cache[$conf_file];
		}
		
		array_shift($path);
		return array_get($this->cache[$conf_file], $path, $default);
	}
	
	public function set ($path, $value)
	{
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		$conf_file = $path[0];
		
		if (count($path) == 1)
		{
			$this->cache[$conf_file] = $value;
			return;
		}
		
		if (!isset ($this->cache[$conf_file]))
		{
			$this->cache[$conf_file] = $this->getFile($conf_file);
		}
		
		if ($this->cache[$conf_file] === false)
		{
			$this->cache[$conf_file] = [];
		}
		
		array_shift($path);
		array_set($this->cache[$conf_file], $path, $value);
	}
	
	public function delete ($path)
	{
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		$conf_file = $path[0];
		
		if (count($path) == 1)
		{
			$this->cache[$conf_file] = false;
			return;
		}
		
		if (!isset ($this->cache[$conf_file]))
		{
			$this->cache[$conf_file] = $this->getFile($conf_file);
		}
		
		if ($this->cache[$conf_file] !== false)
		{
			array_shift($path);
			array_delete($this->cache[$conf_file], $path);
		}
	}
	
	public function reset ($path)
	{
		if (!is_array($path))
		{
			$path = explode('.', $path);
		}
		
		$conf_file = $path[0];
		
		if (count($path) == 1)
		{
			unset ($this->cache[$conf_file]);
			return null;
		}
		
		array_shift($path);
		
		if (!isset ($this->cache[$conf_file]))
		{
			$this->cache[$conf_file] = $this->getFile($conf_file);
			return array_get($this->cache[$conf_file], $path);
		}
		
		$original = $this->getFile($conf_file);
		
		if ($original === false)
		{
			array_delete($this->cache[$conf_file], $path);
			return null;
		}
		
		$value = array_get($original, $path);
		array_set($this->cache[$conf_file], $path, $value);
		return $value;
	}
	
	public function getFile ($conf_file)
	{
		$core = Core::provider();
		
		if (isset ($core->cache['conf-files']))
		{
			return $this->getFromCache($core->cache['conf-files'], $conf_file);
		}
		else
		{
			return $this->getFromLayers($core->layer_paths, $conf_file);
		}
	}
	
	public function getFromCache (&$cache, $rel_path)
	{
		$conf = false;
		
		if (isset ($cache[$rel_path]))
		{
			foreach ($cache[$rel_path] as $conf_file)
			{
				switch ($conf_file[1])
				{
				case 'json':
					$layer_conf = json_decode(file_get_contents($conf_file[0]), true);
					
					if (json_last_error() !== \JSON_ERROR_NONE)
					{
						throw new Exception\ConfigurationException("Error parsing {$conf_file[0]}: ".json_last_error_msg());
					}
					
					break;
				
				case 'php':
					$layer_conf = include ($conf_file[0]);
					break;
				
				default:
					$layer_conf = false;
					break;
				}
				
				if ($layer_conf !== false)
				{
					if ($conf === false)
					{
						$conf = [];
					}
					
					foreach ($layer_conf as $key => $value)
					{
						array_set($conf, $key, $value);
					}
				}
			}
		}
		
		return $conf;
	}
	
	public function getFromLayers ($layer_paths, $rel_path)
	{
		$conf = false;
		
		foreach ($layer_paths as $layer_path)
		{
			if (file_exists("{$layer_path}/conf/{$rel_path}.json"))
			{
				$layer_conf = json_decode(file_get_contents("{$layer_path}/conf/{$rel_path}.json"), true);
				
				if (json_last_error() !== \JSON_ERROR_NONE)
				{
					throw new Exception\ConfigurationException("Error parsing {$layer_path}/conf/{$rel_path}.json: ".json_last_error_msg());
				}
			}
			elseif (file_exists("{$layer_path}/conf/{$rel_path}.php"))
			{
				$layer_conf = include ("{$layer_path}/conf/{$rel_path}.php");
			}
			else
			{
				$layer_conf = false;
			}
			
			if ($layer_conf !== false)
			{
				if ($conf === false)
				{
					$conf = [];
				}
				
				foreach ($layer_conf as $key => $value)
				{
					array_set($conf, $key, $value);
				}
			}
		}
		
		return $conf;
	}
}
