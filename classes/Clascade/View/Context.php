<?php

namespace Clascade\View;
use Clascade\Core;
use Clascade\Lang;

class Context implements \ArrayAccess
{
	public $page;
	public $view;
	public $vars = [];
	public $handler;
	public $nonce;
	
	public function __construct ($page, $view, $vars)
	{
		$this->page = $page;
		$this->view = $view;
		$this->nonce = $page->view_nonce;
		
		if (is_array($view) || is_object($view))
		{
			$this->handler = $view;
		}
		else
		{
			$this->handler = path("/views/{$view}.php");
		}
		
		// Wrap the variables in ViewVar objects.
		
		foreach ((array) $vars as $key => $value)
		{
			$this->vars[$key] = ViewVar::wrap($value);
		}
	}
	
	public function renderSelf ()
	{
		return Core::load($this->handler, $this, 'render', $this->vars);
	}
	
	public function view ($view, $vars=null)
	{
		return $this->page->getRender($view, $vars);
	}
	
	public function headElements ($indent=null)
	{
		if ($indent === null)
		{
			$indent = "\t\t";
		}
		
		return implode("\n{$indent}", $this->page->head_elements)."\n";
	}
	
	public function langAttr ()
	{
		$lang_attr = Lang::toBCP47($this->page->lang);
		$lang_attr = ViewVar::wrap($lang_attr);
		return $lang_attr;
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
