<?php

namespace Clascade\Util\Providers;

class FilesystemProvider
{
	public function extension ($path)
	{
		return pathinfo($path, \PATHINFO_EXTENSION);
	}
	
	public function contentType ($path, $default=null)
	{
		// Try to find a content type by file extension.
		
		$extension = $this->extension($path);
		
		if ($extension != '')
		{
			$type = conf("content-types.{$extension}");
			
			if ($type !== null)
			{
				return $type;
			}
		}
		
		// No extension match was found.
		// Try to find a content type from magic.
		
		$finfo = finfo_open(\FILEINFO_MIME_TYPE);
		$type = finfo_file($finfo, $path);
		finfo_close($finfo);
		
		if ($type !== false)
		{
			return $type;
		}
		
		// Fall back to a default content type.
		
		if ($default !== null)
		{
			return $default;
		}
		
		return 'application/octet-stream';
	}
	
	public function sniffType ($data, $default=null)
	{
		$finfo = finfo_open(\FILEINFO_MIME_TYPE);
		$type = finfo_buffer($finfo, $data);
		finfo_close($finfo);
		
		if ($type !== false)
		{
			return $type;
		}
		
		// Fall back to a default content type.
		
		if ($default !== null)
		{
			return $default;
		}
		
		return 'application/octet-stream';
	}
}
