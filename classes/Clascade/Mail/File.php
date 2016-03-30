<?php

namespace Clascade\Mail;
use Clascade\Util\Filesystem;

class File
{
	public $path;
	public $content_type;
	
	public function __construct ($path, $content_type=null)
	{
		$this->path = $path;
		$this->content_type = $content_type;
	}
	
	public function __toString ()
	{
		return $this->contents();
	}
	
	public function path ()
	{
		return $this->path;
	}
	
	public function contents ()
	{
		return file_get_contents($this->path);
	}
	
	public function contentType ()
	{
		if ($this->content_type !== null)
		{
			return $this->content_type;
		}
		
		return Filesystem::contentType($this->path);
	}
}
