<?php

namespace Clascade\Util\Providers;
use Clascade\Core;

class CoreCacheProvider
{
	public function findCachablePaths ()
	{
		$cache =
		[
			'effective-paths' => [],
			'controller-wildcards' => [],
			'conf-files' => [],
			'lang-files' => [],
			'autoload' => [[]],
		];
		
		$core = Core::provider();
		
		for ($i = count($core->layer_paths) - 1; $i >= 0; --$i)
		{
			$this->collectCachablePaths($core->layer_paths[$i], '', $cache, $i);
		}
		
		return $cache;
	}
	
	public function collectCachablePaths ($base, $rel_path, &$cache, $layer_num=null, $section=null)
	{
		$dirs = [];
		$conf_files = [];
		$classes = [];
		$autoload_level = count($cache['autoload']) - 1;
		$d = opendir("{$base}{$rel_path}");
		
		if ($d !== false)
		{
			while ($filename = readdir($d))
			{
				if (substr($filename, 0, 1) != '.')
				{
					if ($layer_num != 0 && !isset ($cache['effective-paths']["{$rel_path}/{$filename}"]))
					{
						$cache['effective-paths']["{$rel_path}/{$filename}"] = "{$base}{$rel_path}/{$filename}";
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
							
							$cache['controller-wildcards']["{$base}{$rel_path}"]["{$base}{$rel_path}/{$filename}"] = 1;
						}
					}
					else
					{
						switch ($section)
						{
						case 'classes':
							if (ends_with($filename, '.php'))
							{
								$class_name = substr("{$rel_path}/{$filename}", 9, -4);
								$class_name = str_replace('/', '\\', $class_name);
								$classes[$class_name] = "{$base}{$rel_path}/{$filename}";
							}
							break;
						
						case 'conf':
						case 'lang':
							$pos = strrpos($filename, '.');
							
							if ($pos !== false)
							{
								$extension = substr($filename, $pos + 1);
								
								if ($extension == 'json' || $extension == 'php')
								{
									$conf_name = "{$rel_path}/".substr($filename, 0, $pos);
									
									// Remove first path component (/conf/ or /lang/).
									
									$conf_name = substr($conf_name, strpos($conf_name, '/', 1) + 1);
									
									if ($extension == 'json' || !isset ($conf_files[$conf_name]))
									{
										if ($section == 'conf')
										{
											$conf_files[$conf_name] = ["{$base}{$rel_path}/{$filename}", $extension];
										}
										else
										{
											$conf_files[$conf_name] = "{$base}{$rel_path}/{$filename}";
										}
									}
								}
							}
							
							break;
						}
					}
				}
			}
			
			closedir($d);
			
			switch ($section)
			{
			case 'classes':
				if (!empty ($classes))
				{
					if (!isset ($cache['autoload'][$autoload_level]['classmap']))
					{
						$cache['autoload'][$autoload_level]['classmap'] = $classes;
					}
					else
					{
						$cache['autoload'][$autoload_level]['classmap'] += $classes;
					}
				}
				break;
			
			case 'conf':
			case 'lang':
				foreach ($conf_files as $conf_name => $conf_file)
				{
					if (!isset ($cache["{$section}-files"][$conf_name]))
					{
						$cache["{$section}-files"][$conf_name] = [$conf_file];
					}
					else
					{
						array_unshift($cache["{$section}-files"][$conf_name], $conf_file);
					}
				}
				
				break;
			}
			
			// Recurse into subdirectories.
			
			foreach ($dirs as $dir)
			{
				$this->collectCachablePaths($base, "{$rel_path}/{$dir}", $cache, $layer_num, ($section === null ? $dir : $section));
			}
			
			if ($section === null)
			{
				// Base directory of the layer.
				
				if (!isset ($cache['autoloader-path']) && file_exists("{$base}/classes/Composer/Autoload/ClassLoader.php"))
				{
					$cache['autoloader-path'] = "{$base}/classes/Composer/Autoload/ClassLoader.php";
				}
				
				if (file_exists("{$base}/vendor/autoload.php"))
				{
					if (!isset ($cache['autoloader-path']))
					{
						$cache['autoloader-path'] = "{$base}/vendor/composer/ClassLoader.php";
					}
					
					$maps = ['classmap', 'psr4', 'namespaces', 'files'];
					
					foreach ($maps as $key)
					{
						$path = "{$base}/vendor/composer/autoload_{$key}.php";
						
						if (file_exists($path))
						{
							$map = require ($path);
							
							if (!empty ($map))
							{
								if (!isset ($cache['autoload'][$autoload_level][$key]))
								{
									$cache['autoload'][$autoload_level][$key] = $map;
								}
								else
								{
									$cache['autoload'][$autoload_level][$key] += $map;
								}
							}
						}
					}
					
					$maps = $cache['autoload'][$autoload_level];
					
					if (!empty ($maps) && (count($maps) > 1 || !isset ($maps['classmap'])))
					{
						$cache['autoload'][] = [];
					}
				}
			}
		}
	}
}
