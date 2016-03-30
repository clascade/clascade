<?php

namespace Clascade\View;

class FieldVar extends ViewVar
{
	public $field_name;
	public $report;
	
	public function __construct ($raw, $report, $field_name)
	{
		$this->report = $report;
		$this->field_name = $field_name;
		
		parent::__construct($raw);
	}
	
	public function hasError ()
	{
		return (isset ($this->report->errors[$this->field_name]));
	}
	
	public function fieldClass ()
	{
		$class = 'validated-field';
		
		if ($this->hasError())
		{
			$class = "{$class} error";
		}
		
		return static::wrap($class);
	}
	
	public function error ()
	{
		if (isset ($this->report->errors[$this->field_name][0]))
		{
			$error = $this->report->errors[$this->field_name][0];
		}
		else
		{
			$error = '';
		}
		
		return static::wrap($error);
	}
}
