<?php

namespace Clascade\Mail;
use Clascade\Util\Filesystem;
use Clascade\Util\Str;

class Message implements \ArrayAccess
{
	public $parts;
	
	public function __construct ()
	{
		$this->reset();
	}
	
	public function reset ()
	{
		$this->parts =
		[
			'to' => [],
			'cc' => [],
			'bcc' => [],
			'from' => null,
			'reply-to' => null,
			'subject' => null,
			'html' => null,
			'text' => null,
			'attachments' => [],
			'embeds' => [],
			'extra-headers' => [],
		];
		return $this;
	}
	
	public function send ($mailer=null)
	{
		if ($mailer === null)
		{
			$mailer = Mailer::provider();
		}
		
		return $mailer->send($this);
	}
	
	//== Addresses ==//
	
	public function to ()
	{
		$args = func_get_args();
		$this->parts['to'] = static::normalizeAddressArgList($args);
		return $this;
	}
	
	public function addTo ()
	{
		$args = func_get_args();
		$addresses = static::normalizeAddressArgList($args);
		$this->parts['to'] = array_merge($this->parts['to'], $addresses);
		return $this;
	}
	
	public function cc ()
	{
		$args = func_get_args();
		$this->parts['cc'] = static::normalizeAddressArgList($args);
		return $this;
	}
	
	public function addCc ()
	{
		$args = func_get_args();
		$addresses = static::normalizeAddressArgList($args);
		$this->parts['cc'] = array_merge($this->parts['cc'], $addresses);
		return $this;
	}
	
	public function bcc ()
	{
		$args = func_get_args();
		$this->parts['bcc'] = static::normalizeAddressArgList($args);
		return $this;
	}
	
	public function addBcc ()
	{
		$args = func_get_args();
		$addresses = static::normalizeAddressArgList($args);
		$this->parts['bcc'] = array_merge($this->parts['bcc'], $addresses);
		return $this;
	}
	
	public function from ($address=null)
	{
		$this->parts['from'] = static::normalizeAddressArg($address);
		return $this;
	}
	
	public function replyTo ($address=null)
	{
		$this->parts['reply-to'] = static::normalizeAddressArg($address);
		return $this;
	}
	
	//== Other parts ==//
	
	public function subject ($subject=null)
	{
		$this->parts['subject'] = $subject;
		return $this;
	}
	
	public function html ($html=null)
	{
		$this->parts['html'] = $html;
		return $this;
	}
	
	public function text ($text=null)
	{
		$this->parts['text'] = $text;
		return $this;
	}
	
	public function embed (&$reference, $data, $filename=null, $content_type=null)
	{
		do
		{
			$content_id = nice64_encode(rand_bytes(32));
			
		} // If this actually loops, a miracle has happened or rand_bytes() is broken.
		while (isset ($this->parts['embeds'][$content_id]));
		
		$this->parts['embeds'][$content_id] = static::makeFileEntry($data, $filename, $content_type);
		$reference = "cid:{$content_id}";
		return $this;
	}
	
	public function embedFile (&$reference, $path, $content_type=null)
	{
		return $this->embed($reference, new File($path, $content_type));
	}
	
	public function attach ($data, $filename=null, $content_type=null)
	{
		$this->parts['attachments'][] = static::makeFileEntry($data, $filename, $content_type);
		return $this;
	}
	
	public function attachFile ($path, $content_type=null)
	{
		return $this->attach(new File($path, $content_type));
	}
	
	public function header ($header, $value=null)
	{
		$header = static::normalizeHeader($header);
		
		if ($value === null)
		{
			unset ($this->parts['extra-headers'][$header]);
		}
		else
		{
			$value = strtr($value, ["\r" => '', "\n" => '']);
			$this->parts['extra-headers'][$header] = $value;
		}
		
		return $this;
	}
	
	public function extraHeaders ($header_list)
	{
		if (is_string($header_list))
		{
			$arg = preg_split('/\r\n(?![ \t])/', $header_list);
			$header_list = [];
			
			foreach ($arg as $value)
			{
				$value = explode(':', $value, 2);
				$header_list[trim($value[0], " \t")] = trim($value[1], " \t");
			}
		}
		
		foreach ((array) $header_list as $header => $value)
		{
			$this->header($header, $value);
		}
		
		return $this;
	}
	
	public function set ($fields)
	{
		foreach ((array) $fields as $key => $value)
		{
			$this->offsetSet($key, $value);
		}
		
		return $this;
	}
	
	//== Formatting ==//
	
	public function format ($header)
	{
		$header = Str::lowerAscii($header);
		
		if (array_key_exists($header, $this->parts))
		{
			switch ($header)
			{
			case 'from':
			case 'reply-to':
				$value = $this->parts[$header];
				
				if ($value === null)
				{
					return null;
				}
				
				return static::formatAddress(array_first_key($value), array_first($value));
			}
			
			if (is_array($this->parts[$header]))
			{
				return static::formatAddressList($this->parts[$header]);
			}
			
			return $this->parts[$header];
		}
		else
		{
			$header = static::normalizeHeader($header);
			
			if (isset ($this->parts['extra-headers'][$header]))
			{
				return $this->parts['extra-headers'][$header];
			}
		}
		
		return null;
	}
	
	public function formatMIME (&$headers=null, $ignore_headers=null)
	{
		if ($ignore_headers === null)
		{
			$ignore_lookup = [];
		}
		else
		{
			if (!is_array($ignore_headers))
			{
				$ignore_headers = preg_split('/\s*,\s*/', $ignore_headers);
			}
			
			$ignore_lookup = [];
			
			foreach ($ignore_headers as $header)
			{
				$ignore_lookup[static::normalizeHeader($header)] = true;
			}
		}
		
		// Gather standard headers.
		
		$headers =
		[
			'MIME-Version' => '1.0',
		];
		
		foreach (['subject', 'from', 'reply-to', 'to', 'cc', 'bcc'] as $header)
		{
			if (isset ($this->parts[$header]) && (!is_array($this->parts[$header]) || !empty ($this->parts[$header])))
			{
				$headers[static::normalizeHeader($header)] = $this->format($header);
			}
		}
		
		// Generate message body.
		
		$body = $this->formatMIMEBody($part_headers);
		$body = $this->formatMIMEEmbeds($body, $part_headers);
		$body = $this->formatMIMEAttachments($body, $part_headers);
		$headers += $part_headers;
		
		// Add extra headers.
		
		$headers += $this->parts['extra-headers'];
		
		// Remove ignored headers.
		
		$headers = array_diff_key($headers, $ignore_lookup);
		$headers = $this->joinHeaders($headers);
		return $body;
	}
	
	public function formatMIMEBody (&$headers=null)
	{
		if (isset ($this->parts['html']))
		{
			if (isset ($this->parts['text']))
			{
				$boundary = MIME::createBoundary();
				$text = $this->formatMIMEText($text_headers);
				$html = $this->formatMIMEHTML($html_headers);
				$body =
					"--{$boundary}\r\n".
					$this->joinHeaders($text_headers)."\r\n".
					"\r\n".
					"{$text}\r\n".
					"--{$boundary}\r\n".
					$this->joinHeaders($html_headers)."\r\n".
					"\r\n".
					"{$html}\r\n".
					"--{$boundary}--";
				
				$headers =
				[
					'Content-Type' => "multipart/alternative; boundary={$boundary}",
				];
				
				return $body;
			}
			else
			{
				return $this->formatMIMEHTML($headers);
			}
		}
		else
		{
			return $this->formatMIMEText($headers);
		}
	}
	
	public function formatMIMEHTML (&$headers=null)
	{
		$headers =
		[
			'Content-Type' => 'text/html; charset=UTF-8',
			'Content-Transfer-Encoding' => 'quoted-printable',
		];
		
		return MIME::quotePrintable((string) $this->parts['html']);
	}
	
	public function formatMIMEText (&$headers=null)
	{
		$headers =
		[
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => 'quoted-printable',
		];
		
		return MIME::quotePrintable((string) $this->parts['text']);
	}
	
	public function formatMIMEEmbeds ($body, &$headers=null)
	{
		if (!empty ($this->parts['embeds']))
		{
			$boundary = MIME::createBoundary();
			$body =
				"--{$boundary}\r\n".
				$this->joinHeaders($headers)."\r\n".
				"\r\n".
				"{$body}\r\n";
			
			foreach ($this->parts['embeds'] as $key => $embed)
			{
				if (isset ($embed['filename']))
				{
					$filename = addslashes($embed['filename']);
					$name_param = "; name=\"{$filename}\"";
					$filename_param = "; filename=\"{$filename}\"";
				}
				else
				{
					$name_param = '';
					$filename_param = '';
				}
				
				$content_id = rawurlencode($key);
				
				$body .=
					"--{$boundary}\r\n".
					"Content-Type: {$embed['content-type']}{$name_param}\r\n".
					"Content-Disposition: inline{$filename_param}\r\n".
					"Content-Transfer-Encoding: base64\r\n".
					"Content-ID: <{$content_id}>\r\n".
					"\r\n".
					chunk_split(base64_encode((string) $embed['data']));
			}
			
			$body .= "--{$boundary}--";
			
			$headers =
			[
				'Content-Type' => "multipart/related; boundary={$boundary}",
			];
		}
		
		return $body;
	}
	
	public function formatMIMEAttachments ($body, &$headers=null)
	{
		if (!empty ($this->parts['attachments']))
		{
			$boundary = MIME::createBoundary();
			$body =
				"--{$boundary}\r\n".
				$this->joinHeaders($headers)."\r\n".
				"{$body}\r\n";
			
			foreach ($this->parts['attachments'] as $attachment)
			{
				if (isset ($attachment['filename']))
				{
					$filename = addslashes($attachment['filename']);
					$name_param = "; name=\"{$filename}\"";
					$filename_param = "; filename=\"{$filename}\"";
				}
				else
				{
					$name_param = '';
					$filename_param = '';
				}
				
				$body .=
					"--{$boundary}\r\n".
					"Content-Type: {$attachment['content-type']}{$name_param}\r\n".
					"Content-Disposition: attachment{$filename_param}\r\n".
					"Content-Transfer-Encoding: base64\r\n".
					"\r\n".
					chunk_split(base64_encode((string) $attachment['data']));
			}
			
			$body .= "--{$boundary}--";
			
			$headers =
			[
				'Content-Type' => "multipart/mixed; boundary={$boundary}",
			];
		}
		
		return $body;
	}
	
	public function joinHeaders ($headers)
	{
		foreach ($headers as $header => $value)
		{
			$value = trim($value, " \t");
			$value = strtr($value,
			[
				"\r\n " => "\r\n ",
				"\r\n\t" => "\r\n\t",
				"\r\n" => "\r\n ",
				"\r" => '',
				"\n" => '',
			]);
			$headers[$header] = "{$header}: {$value}";
		}
		
		return implode("\r\n", $headers);
	}
	
	//== ArrayAccess ==//
	
	public function offsetExists ($offset)
	{
		$offset = Str::lowerAscii($offset);
		return isset ($this->parts[$offset]);
	}
	
	public function offsetGet ($offset)
	{
		$offset = Str::lowerAscii($offset);
		return $this->parts[$offset];
	}
	
	public function offsetSet ($offset, $value)
	{
		$offset = Str::lowerAscii($offset);
		
		if (array_key_exists($offset, $this->parts))
		{
			$method = Str::camel($offset);
			
			if (method_exists($this, $method))
			{
				call_user_func([$this, $method], $value);
			}
			else
			{
				$this->parts[$offset] = (array) $value;
			}
		}
		else
		{
			$this->header($offset, $value);
		}
	}
	
	public function offsetUnset ($offset)
	{
		$this->offsetSet($offset, null);
	}
	
	//== Helpers ==//
	
	public static function makeFileEntry ($data, $filename=null, $content_type=null)
	{
		if ($data instanceof File)
		{
			if ($filename === null)
			{
				$filename = basename($data->path());
			}
			
			if ($content_type === null)
			{
				$content_type = $data->contentType();
			}
		}
		else
		{
			if ($content_type === null)
			{
				$content_type = Filesystem::sniffType($data);
			}
		}
		
		return
		[
			'content-type' => $content_type,
			'filename' => $filename,
			'data' => $data,
		];
	}
	
	public static function normalizeHeader ($header)
	{
		$header = Str::lowerAscii($header);
		$header = trim($header, " \t");
		$irregular_normalizations = conf('iana/message-headers/irregular-normalizations');
		
		if (isset ($irregular_normalizations[$header]))
		{
			return $irregular_normalizations[$header];
		}
		
		return preg_replace_callback('/(?:^|-)[a-z]/', function ($match)
		{
			return Str::upperAscii($match[0]);
		}, $header);
	}
	
	public static function normalizeAddressArgList ($args)
	{
		$normalized = [];
		
		foreach ($args as $address_list)
		{
			foreach ((array) $address_list as $key => $value)
			{
				if (is_int($key))
				{
					$normalized[$value] = null;
				}
				else
				{
					$normalized[$key] = $value;
				}
			}
		}
		
		return $normalized;
	}
	
	public static function normalizeAddressArg ($address)
	{
		foreach ((array) $address as $key => $value)
		{
			if (is_int($key))
			{
				return [$value => null];
			}
			else
			{
				return [$key => $value];
			}
		}
		
		return null;
	}
	
	public static function formatAddressList ($address_list)
	{
		foreach ($address_list as $address => $display_name)
		{
			$address_list[$address] = static::formatAddress($address, $display_name);
		}
		
		return implode(",\r\n ", $address_list);
	}
	
	public static function formatAddress ($address, $display_name)
	{
		if ($display_name !== null)
		{
			if (strlen($display_name) <= 256 && Str::isPrint($display_name))
			{
				$display_name = preg_replace('/[^A-Za-z0-9!#$%&\'*+\-\/=?^_`{|}~ \t]/', '\\\\$0', $display_name);
				$display_name = "\"{$display_name}\"";
			}
			else
			{
				// 45 = floor((75 [max width] - 12 [serialization overhead]) / 4 [base64 consequent]) * 3 [base64 antecedent]
				$name_parts = str_split($display_name, 45);
				
				foreach ($name_parts as $key => $name_part)
				{
					$name_parts[$key] = '=?UTF-8?B?'.base64_encode($name).'?=';
				}
				
				$display_name = implode("\r\n ", $name_parts);
			}
			
			$address = "{$display_name} <{$address}>";
		}
		
		return $address;
	}
}
