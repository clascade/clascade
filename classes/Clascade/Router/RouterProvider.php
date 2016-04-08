<?php

namespace Clascade\Router;
use Clascade\Core;
use Clascade\Exception;
use Clascade\Hook;
use Clascade\Session;
use Clascade\Util\Escape;
use Clascade\Util\Str;

class RouterProvider
{
	public $routes = [];
	public $route_wildcards = [];
	public $target;
	
	public $method_dirs =
	[
		'delete' => 'delete-handlers',
		'get' => 'pages',
		'head' => 'pages',
		'post' => 'actions',
		'put' => 'put-handlers',
	];
	
	public function route ($request_uri, $http_method)
	{
		$this->target = $this->findTarget($request_uri, $http_method);
		$this->target->load();
	}
	
	//== Route management ==//
	
	public function get ($path, $controller, $middleware=null)
	{
		return $this->add('get', $path, $controller, $middleware);
	}
	
	public function post ($path, $controller, $middleware=null)
	{
		return $this->add('post', $path, $controller, $middleware);
	}
	
	public function put ($path, $controller, $middleware=null)
	{
		return $this->add('put', $path, $controller, $middleware);
	}
	
	public function delete ($path, $controller, $middleware=null)
	{
		return $this->add('delete', $path, $controller, $middleware);
	}
	
	public function add ($http_method, $path, $controller, $middleware=null)
	{
		$http_method = Str::lowerAscii($http_method);
		$path = str_begin($path, '/');
		$base = path('/'.$this->method_dirs[$http_method]);
		$this->routes[$base.$path] = Core::getCallable($controller, $http_method);
		Hook::addByArray("route:{$base}{$path}", $middleware);
		$pos = 0;
		
		while (preg_match('#(/_[^/]*_)(/|$)#', $path, $match, \PREG_OFFSET_CAPTURE, $pos))
		{
			$rel_path = substr($path, 0, $match[0][1]);
			$this->route_wildcards["{$base}{$rel_path}"]["{$base}{$rel_path}{$match[1][0]}"] = 1;
			$pos = $match[2][1];
		}
	}
	
	//== Middleware management ==//
	
	public function middlewareByArray ($http_method, $path, $handlers)
	{
		$http_method = Str::lowerAscii($http_method);
		$base = path('/'.$this->method_dirs[$http_method]);
		Hook::addByArray("route:{$base}{$path}", $handlers);
	}
	
	public function middleware ($http_method, $path)
	{
		$handlers = func_get_args();
		$handlers = array_slice($handlers, 2);
		return $this->middlewareByArray($http_method, $path, $handlers);
	}
	
	//== Route resolution ==//
	
	public function findTarget ($request_uri, $http_method)
	{
		$request_path = request_path($request_uri);
		
		// Map the method to a directory.
		
		$http_method = Str::lowerAscii($http_method);
		
		if (!isset ($this->method_dirs[$http_method]))
		{
			return $this->errorPage('method-not-allowed', $request_path);
		}
		
		$base = path("/{$this->method_dirs[$http_method]}");
		
		// Decode and validate URL path.
		
		$app_path = $this->decodeURLPath($request_path);
		
		if ($app_path === false)
		{
			return $this->errorPage('not-found', $request_path);
		}
		
		// Get the path relative to the app base.
		
		$app_base = $this->appBase();
		
		if ($app_base != '')
		{
			if (starts_with($app_path, $app_base))
			{
				$app_path = substr($app_path, strlen($app_base));
			}
			else
			{
				// This path is outside the app base.
				
				return $this->errorPage('not-found', $request_path);
			}
		}
		
		// Check if this is a path to the assets.
		
		$assets_base = conf('common.urls.assets-base');
		
		if ($assets_base !== null)
		{
			$assets_base = rtrim($assets_base, '/').'/';
			
			if (substr($app_path, 0, strlen($assets_base)) == $assets_base)
			{
				$path = substr_replace($app_path, '/assets/', 0, strlen($assets_base));
				$path = path($path);
				
				if (!file_exists($path))
				{
					return $this->errorPage('not-found', $request_path);
				}
				
				// Send the file and exit.
				
				return make(FileTarget::class, $path);
			}
		}
		
		// Search for a matching controller.
		
		$target = $this->findController($base, $app_path);
		
		if ($target === false)
		{
			// Note: These error pages will look for other methods
			// allowed on this path. If none are found, they will
			// produce a "not found" error page instead.
			
			$error_type = ($http_method == 'post' ? 'post-not-allowed' : 'method-not-allowed');
			return $this->errorPage($error_type, $request_path);
		}
		
		if ($target instanceof Redirect)
		{
			// Add trailing slash to directory requests.
			
			$target->request_path = "{$request_path}/";
			return $target;
		}
		
		// Return a Page controller.
		
		$target->request_path = $request_path;
		return $target;
	}
	
	public function findController ($base, $path)
	{
		if (preg_match('#/(?:_[^/]*_|\*\*)(/|$)#', $path, $match, \PREG_OFFSET_CAPTURE))
		{
			// This path has a component that might literally
			// match the name of a wildcard component. We
			// don't want wildcard components to be treated
			// as literal matches, so let's act as though all
			// components up to this one had been tested and
			// failed to find a match. We'll search from there.
			
			$slash_pos = $match[0][1];
			$param_path = substr($path, $match[1][1]);
			$path = substr($path, 0, $match[1][1]);
		}
		else
		{
			$target = $this->findDirectController("{$base}{$path}");
			
			if ($target !== false)
			{
				return $target;
			}
			
			$slash_pos = strrpos($path, '/');
			$param_path = '';
		}
		
		// No direct match. Find the nearest wildcard route.
		
		do
		{
			// Remove the last path component, which we'll
			// move into the $param_path.
			
			$component = substr($path, $slash_pos + 1);
			$path = substr($path, 0, $slash_pos);
			
			if ($component != '')
			{
				$target = $this->findWildcardController("{$base}{$path}", $component, $param_path);
				
				if ($target !== false)
				{
					return $target;
				}
			}
			
			$param_path = "/{$component}{$param_path}";
			$slash_pos = strrpos($path, '/');
		}
		while ($slash_pos !== false);
		
		return false;
	}
	
	public function findDirectController ($path)
	{
		$basename = substr($path, strrpos($path, '/') + 1);
		
		if ($basename != '**' && isset ($this->routes[$path]))
		{
			return make(Page::class, $path, $this->routes[$path]);
		}
		
		// If the path ends with "/default" or "/index",
		// don't treat it as a direct match to a script file.
		//
		// Note: This doesn't apply to dynamically-added routes,
		// because they don't use these names for special purposes.
		
		if ($basename != 'default' && $basename != 'index')
		{
			$effective_path = $path;
			
			if (ends_with($path, '/'))
			{
				$effective_path .= 'index';
			}
			
			if (file_exists("{$effective_path}.php"))
			{
				return make(Page::class, $path, "{$effective_path}.php");
			}
		}
		
		if (!ends_with($path, '/'))
		{
			// Check for a directory match.
			
			if (isset ($this->routes["{$path}/"]))
			{
				return make(Redirect::class, "{$path}/", $this->routes["{$path}/"]);
			}
			
			if (file_exists("{$path}/index.php"))
			{
				return make(Redirect::class, "{$path}/", "{$path}/index.php");
			}
		}
		
		return false;
	}
	
	public function findWildcardController ($path, $component, $param_path)
	{
		// Gather wildcard candidates for this path.
		
		$wildcards = [];
		$cache = Core::getCache();
		
		if (isset ($cache['controller-wildcards']))
		{
			if (isset ($cache['controller-wildcards'][$path]))
			{
				$wildcards = $cache['controller-wildcards'][$path];
			}
		}
		elseif (is_dir($path))
		{
			$pattern = Escape::glob($path);
			$glob = glob("{$pattern}/_*_", \GLOB_NOSORT | \GLOB_ONLYDIR);
			
			if (!empty ($glob))
			{
				$wildcards = array_flip($glob);
			}
		}
		
		if (isset ($this->wildcard_routes[$path]))
		{
			$wildcards += $this->wildcard_routes[$path];
		}
		
		if (!empty ($wildcards))
		{
			// Found one or more wildcard candidates.
			
			foreach (conf('common.router.wildcard-patterns') as list ($wildcard, $pattern))
			{
				if (isset ($wildcards["{$path}/_{$wildcard}_"]) && preg_match($pattern, $component))
				{
					// Found a matching wildcard directory.
					// See if we can resolve the path within it.
					
					$target = $this->findController("{$path}/_{$wildcard}_", $param_path);
					
					if ($target !== false)
					{
						array_unshift($target->path_matches, $component);
						return $target;
					}
				}
			}
		}
		
		// Check for an default handler.
		
		if (isset ($this->routes["{$path}/**"]))
		{
			return make(Page::class, "{$path}/**", $this->routes["{$path}/**"]);
		}
		
		if (file_exists("{$path}/default.php"))
		{
			// Found a default script to handle the path.
			
			return make(Page::class, "{$path}/**", "{$path}/default.php", "/{$component}{$param_path}");
		}
		
		return false;
	}
	
	//== Miscellaneous ==//
	
	public function appBase ()
	{
		return rtrim(conf('common.urls.app-base'), '/');
	}
	
	public function decodeURLPath ($path)
	{
		// Validate general path format.
		
		if (!preg_match('/^(?:\/|(?:\/[^\/]+)+\/?)$/', $path))
		{
			return false;
		}
		
		// Decode and validate path components.
		
		$path = explode('/', $path);
		
		foreach ($path as $index => $component)
		{
			$component = rawurldecode($component);
			
			// Ensure that the decoded path component doesn't begin
			// with a dot and doesn't contain null bytes or slashes.
			
			if (substr($component, 0, 1) == '.' || strcspn($component, "\x00/") != strlen($component))
			{
				return false;
			}
			
			$path[$index] = $component;
		}
		
		return implode('/', $path);
	}
	
	public function errorPage ($error_page, $request_path=null)
	{
		if ($request_path === null)
		{
			$request_path = request_path();
		}
		
		return make(ErrorPage::class, "/error-pages/{$error_page}", path("/error-pages/{$error_page}.php"), $request_path);
	}
	
	public function findMethods ($path)
	{
		$methods = [];
		
		if ($this->findController(path('/delete-handlers'), $path) !== false)
		{
			$methods[] = 'DELETE';
		}
		
		if ($this->findController(path('/pages'), $path) !== false)
		{
			$methods[] = 'GET';
			$methods[] = 'HEAD';
		}
		
		if ($this->findController(path('/actions'), $path) !== false)
		{
			$methods[] = 'POST';
		}
		
		if ($this->findController(path('/put-handlers'), $path) !== false)
		{
			$methods[] = 'PUT';
		}
		
		return $methods;
	}
	
	public function sessionError ()
	{
		$this->errorPage(Session::isLocked() ? 'locked' : 'forbidden')->load();
	}
}
