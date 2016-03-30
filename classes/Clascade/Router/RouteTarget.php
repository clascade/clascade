<?php

namespace Clascade\Router;
use Clascade\Exception;

class RouteTarget
{
	public $route_path;
	public $request_path;
	public $path_matches = [];
	
	public function __construct ($route_path)
	{
		$this->route_path = $route_path;
	}
	
	public function load ()
	{
		throw new Exception\UnexpectedValueException('Unexpected target type "'.get_class($this).'".');
	}
}
