<?php

namespace Clascade\Exception;

class ValidationException extends \RuntimeException implements ExceptionInterface
{
	public $validator;
	
	public function __construct ($message='', $code=0, \Exception $previous=null, $validator=null)
	{
		$this->validator = $validator;
		parent::__construct($message, $code, $previous);
	}
}
