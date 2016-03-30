<?php

namespace Clascade\View;

class HTMLVar extends ViewVar
{
	public function __toString ()
	{
		return $this->raw;
	}
}
