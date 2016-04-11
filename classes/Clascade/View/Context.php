<?php

namespace Clascade\View;
use Clascade\Core;
use Clascade\Lang;

class Context implements \ArrayAccess
{
	public $view;
	public $vars = [];
	public $handler;
	public $nonce;
	
	public function __construct ($view, $vars)
	{
		$this->view = $view;
		$this->nonce = Registry::nonce();
		
		if (is_array($view) || is_object($view))
		{
			$this->handler = $view;
		}
		else
		{
			$view = (string) $view;
			$this->handler = Registry::get($view);
			
			if ($this->handler === null)
			{
				$this->handler = path("/views/{$view}.php");
			}
		}
		
		// Wrap the variables in ViewVar objects.
		
		foreach ((array) $vars as $key => $value)
		{
			$this->vars[$key] = ViewVar::wrap($value);
		}
	}
	
	public function __toString ()
	{
		return $this->getRender();
	}
	
	public function getRender ()
	{
		ob_start();
		$this->render();
		return ob_get_clean();
	}
	
	public function render ()
	{
		return Core::load($this->handler, $this, 'render', $this->vars);
	}
	
	//== ArrayAccess ==//
	
	public function offsetExists ($offset)
	{
		return isset ($this->vars[$offset]);
	}
	
	public function offsetGet ($offset)
	{
		return $this->vars[$offset];
	}
	
	public function offsetSet ($offset, $value)
	{
		$this->vars[$offset] = ViewVar::wrap($value);
	}
	
	public function offsetUnset ($offset)
	{
		unset ($this->vars[$offset]);
	}
}
