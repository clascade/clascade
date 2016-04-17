<?php

namespace Clascade\Util\Providers;
use Clascade\Core;

class CoreCacheProvider
{
	public $cache;
	
	public function genCache ()
	{
		$this->cache =
		[
			'effective-paths' => [],
			'controller-wildcards' => [],
			'conf-files' => [],
			'lang-files' => [],
			'autoload' => [[]],
			'autoloader-path' => null,
		];
		
		$core = Core::provider();
		
		for ($i = count($core->layer_paths) - 1; $i >= 0; --$i)
		{
			$base = $core->layer_paths[$i];
			$this->crawl($base, '', $i, null);
			$this->collectAutoloaders($base);
		}
		
		$this->optimizeAutoloadMaps();
		
		return $this->cache;
	}
	
	public function crawl ($base, $rel_path, $layer_num, $section)
	{
		$dirs = [];
		$d = opendir($base.$rel_path);
		
		if ($d !== false)
		{
			while ($filename = readdir($d))
			{
				if (substr($filename, 0, 1) != '.')
				{
					if ($layer_num != 0 && !isset ($this->cache['effective-paths']["{$rel_path}/{$filename}"]))
					{
						$this->cache['effective-paths']["{$rel_path}/{$filename}"] = "{$base}{$rel_path}/{$filename}";
					}
					
					if (is_dir("{$base}{$rel_path}/{$filename}"))
					{
						// Keep track of the directories. We'll search them
						// after we close our existing directory pointer.
						
						$dirs[] = $filename;
						
						if (strlen($filename) >= 2 && substr($filename, 0, 1) == '_' && substr($filename, -1) == '_')
						{
							// This could be a wildcard directory
							// for controller routing.
							
							$this->cache['controller-wildcards'][$base.$rel_path]["{$base}{$rel_path}/{$filename}"] = 1;
						}
					}
					else
					{
						$this->collectFile($section, $base, $rel_path, $filename);
					}
				}
			}
			
			closedir($d);
			
			// Recurse into subdirectories.
			
			foreach ($dirs as $dir)
			{
				$this->crawl($base, "{$rel_path}/{$dir}", $layer_num, ($section === null ? $dir : $section));
			}
		}
	}
	
	public function collectFile ($section, $base, $rel_path, $filename)
	{
		switch ($section)
		{
		case 'classes':
			if (ends_with($filename, '.php'))
			{
				$class_name = "{$rel_path}/".substr($filename, 0, -4);
				
				// Remove first path component (/classes/).
				
				$class_name = substr($class_name, strpos($class_name, '/', 1) + 1);
				
				$class_name = str_replace('/', '\\', $class_name);
				$this->addAutoloadMap('classmap', [$class_name => "{$base}{$rel_path}/{$filename}"]);
			}
			
			break;
		
		case 'conf':
		case 'lang':
			$pos = strrpos($filename, '.');
			
			if ($pos !== false)
			{
				$extension = substr($filename, $pos + 1);
				
				if ($extension == 'json' || ($section == 'conf' && $extension == 'php'))
				{
					$conf_name = "{$rel_path}/".substr($filename, 0, $pos);
					
					// Remove first path component (/conf/ or /lang/).
					
					$conf_name = substr($conf_name, strpos($conf_name, '/', 1) + 1);
					
					if ($extension == 'json' || !file_exists("{$base}/{$section}/{$conf_name}.json"))
					{
						$entry = "{$base}{$rel_path}/{$filename}";
						
						if ($section == 'conf')
						{
							$entry = [$entry, $extension];
						}
						
						if (!isset ($this->cache["{$section}-files"][$conf_name]))
						{
							$this->cache["{$section}-files"][$conf_name] = [$entry];
						}
						else
						{
							array_unshift($this->cache["{$section}-files"][$conf_name], $entry);
						}
					}
				}
			}
			
			break;
		}
	}
	
	public function collectAutoloaders ($base)
	{
		if (!isset ($this->cache['autoloader-path']) && file_exists("{$base}/classes/Composer/Autoload/ClassLoader.php"))
		{
			$this->cache['autoloader-path'] = "{$base}/classes/Composer/Autoload/ClassLoader.php";
		}
		
		if (file_exists("{$base}/vendor/autoload.php"))
		{
			if (!isset ($this->cache['autoloader-path']))
			{
				$this->cache['autoloader-path'] = "{$base}/vendor/composer/ClassLoader.php";
			}
			
			$maps = ['classmap', 'psr4', 'namespaces', 'files'];
			
			foreach ($maps as $type)
			{
				$path = "{$base}/vendor/composer/autoload_{$type}.php";
				
				if (file_exists($path))
				{
					$this->addAutoloadMap($type, require ($path));
				}
			}
			
			$maps = $this->cache['autoload'][count($this->cache['autoload']) - 1];
			
			if (!empty ($maps) && (count($maps) > 1 || !isset ($maps['classmap'])))
			{
				$this->cache['autoload'][] = [];
			}
		}
	}
	
	public function addAutoloadMap ($type, $map)
	{
		if (!empty ($map))
		{
			$level = count($this->cache['autoload']) - 1;
			
			if (!isset ($this->cache['autoload'][$level][$type]))
			{
				$this->cache['autoload'][$level][$type] = $map;
			}
			else
			{
				$this->cache['autoload'][$level][$type] += $map;
			}
		}
	}
	
	public function optimizeAutoloadMaps ()
	{
		// If Clascade's autoloader checks all of the cached
		// autoload maps and still can't find the requested
		// class, it will try looking at the "effective path"
		// location. If a class can be found there and isn't
		// followed by other Composer autoload types, then we
		// can safely remove it from the autoload cache array
		// and still have cache-accelerated lookup performance.
		
		$level = array_last_key($this->cache['autoload']);
		
		if (count($this->cache['autoload'][$level]) == 1 && isset ($this->cache['autoload'][$level]['classmap']))
		{
			$map = [];
			
			foreach ($this->cache['autoload'][$level]['classmap'] as $class_name => $path)
			{
				if ($path != path('/classes/'.str_replace('\\', '/', $class_name).'.php'))
				{
					$map[] = $path;
				}
			}
			
			if (empty ($map))
			{
				array_pop($this->cache['autoload']);
			}
			else
			{
				$this->cache['autoload'][$level]['classmap'] = $map;
			}
		}
		elseif (empty ($this->cache['autoload'][$level]))
		{
			// Either there are no autoload rules defined or
			// the last rules were non-classmap rules.
			// Remove the empty autoload level at the end.
			
			array_pop($this->cache['autoload']);
		}
	}
}
