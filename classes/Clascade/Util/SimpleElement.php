<?php

namespace Clascade\Util;

class SimpleElement
{
	const CONTENT_NORMAL = 0;
	const CONTENT_VOID = 1;
	const CONTENT_RAW = 2;
	const CONTENT_ESCAPABLE_RAW = 3;
	
	public $tag_name;
	public $attribs;
	public $content;
	public $content_format;
	
	public function __construct ($tag_name, $attribs=null, $content=null, $content_format=null)
	{
		$this->tag_name = (string) $tag_name;
		$this->attribs = (array) $attribs;
		$this->content = (is_array($content) ? $content : (string) $content);
		
		// Set the content model.
		
		if ($content_format === null)
		{
			$tag_name_lower = Str::lowerAscii($this->tag_name);
			
			if (isset (static::$void_elements_lookup[$tag_name_lower]))
			{
				$this->content_format = static::CONTENT_VOID;
			}
			elseif (isset (static::$raw_elements_lookup[$tag_name_lower]))
			{
				$this->content_format = static::CONTENT_RAW;
			}
			elseif (isset (static::$escapable_raw_elements_lookup[$tag_name_lower]))
			{
				$this->content_format = static::CONTENT_ESCAPABLE_RAW;
			}
			else
			{
				$this->content_format = static::CONTENT_NORMAL;
			}
		}
		else
		{
			$this->content_format = $content_format;
		}
	}
	
	public function __toString ()
	{
		return $this->getHTML();
	}
	
	public function getAttributeHTML ()
	{
		$attribs = '';
		
		foreach ($this->attribs as $attrib => $value)
		{
			$attrib = Escape::sanitizeAttributeName($attrib);
			
			if ($attrib != '')
			{
				$value = Escape::html($value);
				$attribs .= " {$attrib}=\"{$value}\"";
			}
		}
		
		return $attribs;
	}
	
	public function getContentHTML ()
	{
		switch ($this->content_format)
		{
		case static::CONTENT_VOID:
			return '';
		
		case static::CONTENT_RAW:
			$content = (is_array($this->content) ? implode('', $this->content) : $this->content);
			return Escape::scriptBody($content);
		
		case static::CONTENT_ESCAPABLE_RAW:
			$content = (is_array($this->content) ? implode('', $this->content) : $this->content);
			return Escape::html($content);
		}
		
		if (is_array($this->content))
		{
			$content = '';
			
			foreach ($this->content as $child)
			{
				if ($child instanceof SimpleElement)
				{
					$content .= (string) $child;
				}
				else
				{
					$content .= Escape::html($child);
				}
			}
			
			return $content;
		}
		
		return Escape::html($this->content);
	}
	
	public function getHTML ()
	{
		$tag_name = preg_replace('/[^a-zA-Z0-9]/', '', $this->tag_name);
		
		if ($tag_name == '')
		{
			return '';
		}
		
		$content = $this->getContentHTML();
		$attribs = $this->getAttributeHTML();
		$closing_tag = ($this->content_format == static::CONTENT_VOID ? '' : "</{$tag_name}>");
		
		return "<{$tag_name}{$attribs}>{$content}{$closing_tag}";
	}
	
	// Ref: http://www.w3.org/TR/html5/syntax.html#elements-0
	
	public static $void_elements_lookup =
	[
		'area' => true,
		'base' => true,
		'br' => true,
		'col' => true,
		'embed' => true,
		'hr' => true,
		'img' => true,
		'input' => true,
		'keygen' => true,
		'link' => true,
		'meta' => true,
		'param' => true,
		'source' => true,
		'track' => true,
		'wbr' => true,
	];
	
	public static $raw_elements_lookup =
	[
		'script' => true,
		'style' => true,
	];
	
	public static $escapable_raw_elements_lookup =
	[
		'textarea' => true,
		'title' => true,
	];
}
