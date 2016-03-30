<?php

namespace Clascade\View;
use Clascade\Util\Escape;

class ViewVar implements \ArrayAccess, \Countable, \Iterator
{
	public $raw;
	
	public function __construct ($raw)
	{
		if ($raw instanceof ViewVar)
		{
			$this->raw = $raw->raw;
		}
		else
		{
			$this->raw = $raw;
		}
	}
	
	public function __invoke ()
	{
		$args = func_get_args();
		$result = call_user_func_array($this->raw, $args);
		return static::wrap($result);
	}
	
	public function __call ($method, $args)
	{
		if (method_exists('Clascade\Util\Escape', $method))
		{
			$class = 'Clascade\Util\Escape';
			$wrap_output = false;
		}
		elseif (method_exists('Clascade\Util\Str', $method))
		{
			$class = 'Clascade\Util\Str';
			$wrap_output = true;
		}
		else
		{
			throw new Exception\BadMethodCallException("Method {$method} does not exist.");
		}
		
		array_unshift($args, $this->raw);
		$result = call_user_func_array([$class, $method], $args);
		
		if ($wrap_output && is_string($result))
		{
			$result = static::wrap($result);
		}
		
		return $result;
	}
	
	/**
	 * Convert the value to an HTML-escaped string by default.
	 */
	
	public function __toString ()
	{
		return $this->html();
	}
	
	public function __get ($key)
	{
		return $this->$key();
	}
	
	//== Additional string functions ==//
	
	public function lTrim ($character_mask=null)
	{
		if ($character_mask === null)
		{
			$character_mask = " \t\n\r\0\x0B";
		}
		
		return static::wrap(ltrim($this->raw, $character_mask));
	}
	
	public function pregReplace ($pattern, $replacement, $limit=null)
	{
		if ($limit === null)
		{
			$limit = -1;
		}
		
		return static::wrap(preg_replace($pattern, $replacement, $this->raw, $limit));
	}
	
	public function replace ($search, $replace)
	{
		return static::wrap(str_replace($search, $replace, $this->raw));
	}
	
	public function rTrim ($character_mask=null)
	{
		if ($character_mask === null)
		{
			$character_mask = " \t\n\r\0\x0B";
		}
		
		return static::wrap(rtrim($this->raw, $character_mask));
	}
	
	public function stripTags ($allowable_tags=null)
	{
		return static::wrap(strip_tags($this->raw, $allowable_tags));
	}
	
	public function trim ($character_mask=null)
	{
		if ($character_mask === null)
		{
			$character_mask = " \t\n\r\0\x0B";
		}
		
		return static::wrap(trim($this->raw, $character_mask));
	}
	
	//== HTML flag attributes ==//
	
	/**
	 * Output " checked" if this value is equal to $value.
	 */
	
	public function checked ($value)
	{
		return $this->htmlFlag('checked', $value);
	}
	
	/**
	 * Output " disabled" if this value is equal to $value.
	 */
	
	public function disabled ($value)
	{
		return $this->htmlFlag('disabled', $value);
	}
	
	/**
	 * Output " selected" if this value is equal to $value.
	 */
	
	public function selected ($value)
	{
		return $this->htmlFlag('selected', $value);
	}
	
	public function htmlFlag ($attribute, $value)
	{
		if ($value instanceof ViewVar)
		{
			$value = $value->raw;
		}
		
		if ($value == $this->raw)
		{
			return " {$attribute}";
		}
		
		return '';
	}
	
	//== ArrayAccess ==//
	
	public function offsetExists ($offset)
	{
		return isset ($this->raw[$offset]) && $this->raw[$offset] !== null;
	}
	
	public function offsetGet ($offset)
	{
		return static::wrap($this->raw[$offset]);
	}
	
	public function offsetSet ($offset, $value)
	{
		$this->raw[$offset] = $value;
	}
	
	public function offsetUnset ($offset)
	{
		unset ($this->raw[$offset]);
	}
	
	//== Countable ==//
	
	public function count ()
	{
		return count($this->raw);
	}
	
	//== Iterator ==//
	
	public function current ()
	{
		return static::wrap(current($this->raw));
	}
	
	public function key ()
	{
		return static::wrap(key($this->raw));
	}
	
	public function next ()
	{
		next($this->raw);
	}
	
	public function rewind ()
	{
		reset($this->raw);
	}
	
	public function valid ()
	{
		return key($this->raw) !== null;
	}
	
	/**
	 * Wrap the given value in a ViewVar if it isn't one already.
	 */
	
	public static function wrap ($value)
	{
		if ($value instanceof ViewVar)
		{
			return $value;
		}
		else
		{
			return new ViewVar($value);
		}
	}
}
