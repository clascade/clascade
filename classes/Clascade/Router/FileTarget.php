<?php

namespace Clascade\Router;

class FileTarget extends RouteTarget
{
	public function load ()
	{
		send_file($this->route_path);
	}
}
