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
		];
		
		$core = Core::provider();
		
		for ($i = count($core->layers) - 1; $i >= 0; --$i)
		{
			$this->collectCachablePaths($core->layers[$i], '', $cache, $i);
		}
		
		return $cache;
	}
	
	public function collectCachablePaths ($base, $rel_path, &$cache, $layer_num=null, $section=null)
	{
		$dirs = [];
		$conf_files = [];
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
		}
	}
}
