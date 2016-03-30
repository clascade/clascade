<?php

namespace Clascade\Exception;
use Clascade\Util\HTTP;

class RedirectionException extends ControlFlowException
{
	public $location;
	public $status_phrase;
	
	// $code should be the HTTP status code number.
	
	public function __construct ($message='', $code=0, \Exception $previous=null, $location=null, $status_phrase=null)
	{
		$this->location = $location;
		$this->status_phrase = $status_phrase;
		parent::__construct($message, $code, $previous);
	}
	
	public function getStatusPhrase ()
	{
		if ($this->status_phrase === null)
		{
			return HTTP::getStatusPhrase($this->getCode());
		}
		
		return $this->status_phrase;
	}
}
