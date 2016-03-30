<?php

namespace Clascade\Router;

class ErrorPage extends Page
{
	public function __construct ($route_path, $controller, $request_path=null)
	{
		$this->request_path = $request_path;
		parent::__construct($route_path, $controller);
	}
}
