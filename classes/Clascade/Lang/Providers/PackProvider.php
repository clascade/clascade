<?php

namespace Clascade\Lang\Providers;
use Clascade\Core;

class PackProvider
{
	public $cache = [];
	
	public function get ($path, $default=null)
	{
		$path = explode('.', $path, 2);
		$lang_file = $path[0];
		
		if (!isset ($this->cache[$lang_file]))
		{
			// Load each lang file in order, replacing duplicate keys.
			
			$core = Core::provider();
			
			if (isset ($core->cache['lang-files']))
			{
				$this->cache[$lang_file] = $this->getFromCache($core->cache['lang-files'], $lang_file);
			}
			else
			{
				$this->cache[$lang_file] = $this->getFromLayers($core->layer_paths, $lang_file);
			}
		}
		
		if ($this->cache[$lang_file] === false)
		{
			return $default;
		}
		
		if (count($path) == 1)
		{
			return $this->cache[$lang_file];
		}
		
		if (array_key_exists($path[1], $this->cache[$lang_file]))
		{
			return $this->cache[$lang_file][$path[1]];
		}
		
		return $default;
	}
	
	public function getFromCache (&$cache, $rel_path)
	{
		if (isset ($cache[$rel_path]))
		{
			$lang_files = $cache[$rel_path];
		}
		else
		{
			$path = path("/lang/{$rel_path}.json");
			
			if (file_exists($path))
			{
				$lang_files = [$path];
			}
			else
			{
				return false;
			}
		}
		
		$lang = false;
		
		foreach ($lang_files as $lang_file)
		{
			$layer_lang = json_decode(file_get_contents($lang_file), true);
			
			if ($lang === false)
			{
				$lang = $layer_lang;
			}
			elseif ($layer_lang !== false)
			{
				$lang = array_merge($lang, $layer_lang);
			}
		}
		
		return $lang;
	}
	
	public function getFromLayers ($layer_paths, $rel_path)
	{
		$lang = false;
		
		foreach ($layer_paths as $layer_path)
		{
			if (file_exists("{$layer_path}/lang/{$rel_path}.json"))
			{
				$layer_lang = json_decode(file_get_contents("{$layer_path}/lang/{$rel_path}.json"), true);
				
				if ($lang === false)
				{
					$lang = $layer_lang;
				}
				elseif ($layer_lang !== false)
				{
					$lang = array_merge($lang, $layer_lang);
				}
			}
		}
		
		return $lang;
	}
}
