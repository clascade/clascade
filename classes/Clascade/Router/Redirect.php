<?php

namespace Clascade\Router;

class Redirect extends Page
{
	public function load ()
	{
		redirect($this->request_path.request_query());
	}
}
