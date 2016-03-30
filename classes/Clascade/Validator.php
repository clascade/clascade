<?php

namespace Clascade;
use Clascade\Util\Str;

class Validator implements \ArrayAccess
{
	public $init_values;
	public $values = [];
	public $labels = [];
	public $redactions = [];
	
	public $handler;
	public $params;
	
	public $errors = [];
	public $messages = [];
	
	public function __construct ($handler, $params=null)
	{
		$this->handler = $handler;
		$this->params = $params;
	}
	
	public function __call ($method, $args)
	{
		if (method_exists('Clascade\Util\Str', $method))
		{
			if (isset ($args[0]))
			{
				$args[0] = $this[$args[0]];
			}
			
			return call_user_func_array(['Clascade\Util\Str', $method], $args);
		}
		elseif (starts_with($method, 'require'))
		{
			$check_method = 'is'.substr($method, 7);
			$context = null;
			
			if (method_exists($this, $check_method))
			{
				$context = $this;
			}
			elseif (method_exists('Clascade\Util\Str', $check_method))
			{
				$context = 'Clascade\Util\Str';
			}
			
			if ($context !== null)
			{
				$error_message_key = substr($check_method, 2);
				$error_message_key = Str::snakeFromStudly($error_message_key, '-');
				$error_message_key = "validator.require.{$error_message_key}";
				
				return $this->doGenericRequire([$context, $check_method], $args, $error_message_key);
			}
		}
	}
	
	public function validate ($init_values=null)
	{
		$this->init_values = (array) $init_values;
		return Core::load($this->handler, $this, 'validate', $this->params);
		$this->enforce();
	}
	
	public function value ($field_name)
	{
		if (!array_key_exists($field_name, $this->values))
		{
			if (isset ($this->init_values[$field_name]) && !is_array($this->init_values[$field_name]))
			{
				$this->values[$field_name] = trim($this->init_values[$field_name], "\t ");
			}
			else
			{
				$this->values[$field_name] = null;
			}
		}
		
		return $this->values[$field_name];
	}
	
	public function error ($field_name, $message)
	{
		$this->errors[$field_name][] = $message;
	}
	
	public function message ($message)
	{
		$this->messages[] = $message;
	}
	
	public function enforce ()
	{
		if (!empty ($this->errors))
		{
			throw new Exception\ValidationException('Errors were found during validation.', 0, null, $this);
		}
	}
	
	public function hasError ($field_name=null)
	{
		if ($field_name === null)
		{
			return !empty ($this->errors);
		}
		
		return isset ($this->errors[$field_name]);
	}
	
	public function getLabel ($field_name)
	{
		if (isset ($this->labels[$field_name]))
		{
			return $this->labels[$field_name];
		}
		
		return $field_name;
	}
	
	public function label ($labels)
	{
		$this->labels = array_merge($this->labels, $labels);
	}
	
	public function preserve ($field_names=null)
	{
		if ($field_names === null)
		{
			// Preserve all fields.
			
			$this->values = $this->init_values;
		}
		else
		{
			foreach (func_get_args() as $field_names)
			{
				foreach ((array) $field_names as $field_name)
				{
					if (isset ($this->init_values[$field_name]))
					{
						$this->values[$field_name] = $this->init_values[$field_name];
					}
				}
			}
		}
	}
	
	public function redact ($field_names)
	{
		foreach (func_get_args() as $field_names)
		{
			$this->redactions = array_merge($this->redactions, (array) $field_names);
		}
	}
	
	//== Checks ==//
	
	public function hasLength ($field_name, $length)
	{
		return (Str::length($this[$field_name]) == $length);
	}
	
	public function hasMaxLength ($field_name, $length)
	{
		return (Str::length($this[$field_name]) <= $length);
	}
	
	public function hasMaxByteLength ($field_name, $length)
	{
		return (strlen($this[$field_name]) <= $length);
	}
	
	public function isOneOf ($field_name, $values)
	{
		return in_array($this[$field_name], $values);
	}
	
	//== Requirements ==//
	
	// These functions can be called with either a single string
	// field name or an array of field names.
	
	public function requireLength ($field_names, $length)
	{
		$status = true;
		
		foreach ((array) $field_names as $field_name)
		{
			if (!$this->hasLength($field_name, $length))
			{
				$this->error($field_name, trans('validator.require.length',
				[
					'field' => $this->getLabel($field_name),
					'num' => $length,
				]));
				$status = false;
			}
		}
		
		return $status;
	}
	
	public function requireMaxLength ($field_names, $length)
	{
		$status = true;
		
		foreach ((array) $field_names as $field_name)
		{
			if (!$this->hasMaxLength($field_name, $length))
			{
				$this->error($field_name, trans('validator.require.max-length',
				[
					'field' => $this->getLabel($field_name),
					'num' => $length,
				]));
				$status = false;
			}
		}
		
		return $status;
	}
	
	public function requireMaxByteLength ($field_names, $length)
	{
		$status = true;
		
		foreach ((array) $field_names as $field_name)
		{
			if (!$this->hasMaxByteLength($field_name, $length))
			{
				$this->error($field_name, trans('validator.require.max-byte-length',
				[
					'field' => $this->getLabel($field_name),
					'num' => $length,
				]));
				$status = false;
			}
		}
		
		return $status;
	}
	
	public function requireNonBlank ($field_names)
	{
		$status = true;
		
		foreach ((array) $field_names as $field_name)
		{
			if ($this->isBlank($field_name))
			{
				$this->error($field_name, trans('validator.require.non-blank',
				[
					'field' => $this->getLabel($field_name),
				]));
				$status = false;
			}
		}
		
		return $status;
	}
	
	public function requireOneOf ($field_names, $values)
	{
		$status = true;
		
		foreach ((array) $field_names as $field_name)
		{
			if (!$this->isOneOf($field_name, $values))
			{
				$this->error($field_name, trans('validator.require.one-of',
				[
					'field' => $this->getLabel($field_name),
				]));
				$status = false;
			}
		}
		
		return $status;
	}
	
	//== Helpers ==//
	
	public function doGenericRequire ($callback, $args, $error_message_key)
	{
		$status = true;
		
		foreach ((array) $args[0] as $field_name)
		{
			$args[0] = $this[$field_name];
			
			if (!call_user_func_array($callback, $args))
			{
				$this->error($field_name, trans($error_message_key,
				[
					'field' => $this->getLabel($field_name),
				]));
				$status = false;
			}
		}
		
		return $status;
	}
	
	//== ArrayAccess ==//
	
	public function offsetExists ($offset)
	{
		return ($this->value($offset) !== null);
	}
	
	public function offsetGet ($offset)
	{
		return $this->value($offset);
	}
	
	public function offsetSet ($offset, $value)
	{
		$this->values[$offset] = $value;
	}
	
	public function offsetUnset ($offset)
	{
		unset ($this->values[$offset]);
	}
}
