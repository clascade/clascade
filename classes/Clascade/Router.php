<?php

namespace Clascade;

class Router extends StaticProxy
{
	public static function getProviderClass ()
	{
		return 'Clascade\Router\RouterProvider';
	}
	
	public static function route ($request_uri, $http_method)
	{
		return static::provider()->route($request_uri, $http_method);
	}
	
	public static function get ($path, $controller, $middleware=null)
	{
		return static::provider()->get($path, $controller, $middleware);
	}
	
	public static function post ($path, $controller, $middleware=null)
	{
		return static::provider()->post($path, $controller, $middleware);
	}
	
	public static function put ($path, $controller, $middleware=null)
	{
		return static::provider()->put($path, $controller, $middleware);
	}
	
	public static function delete ($path, $controller, $middleware=null)
	{
		return static::provider()->delete($path, $controller, $middleware);
	}
	
	public static function middlewareByArray ($http_method, $path, $handlers)
	{
		return static::provider()->middlewareByArray($http_method, $path, $handlers);
	}
	
	public static function middleware ($http_method, $path)
	{
		$handlers = func_get_args();
		$handlers = array_slice($handlers, 2);
		return static::provider()->middlewareByArray($http_method, $path, $handlers);
	}
	
	public static function findTarget ($request_uri, $http_method)
	{
		return static::provider()->findTarget($request_uri, $http_method);
	}
	
	public static function findController ($base, $path)
	{
		return static::provider()->findController($base, $path);
	}
	
	public static function appBase ()
	{
		return static::provider()->appBase();
	}
	
	public static function decodeURLPath ($path)
	{
		return static::provider()->decodeURLPath($path);
	}
	
	public static function errorPage ($error_page, $request_path=null)
	{
		return static::provider()->errorPage($error_page, $request_path);
	}
	
	public static function findMethods ($path)
	{
		return static::provider()->findMethods($path);
	}
	
	public static function sessionError ()
	{
		return static::provider()->sessionError();
	}
}
