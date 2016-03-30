<?php

namespace Clascade;
use Clascade\Mail\Message;

class Mail
{
	public static function create ($to=null, $subject=null, $message=null, $additional_headers=null)
	{
		return (new Message())->to($to)->subject($subject)->text($message)->extraHeaders($additional_headers);
	}
	
	public static function send ($to=null, $subject=null, $message=null, $additional_headers=null)
	{
		if ($to instanceof Message)
		{
			return $to->send();
		}
		
		return static::create($to, $subject, $message, $additional_headers)->send();
	}
	
	public static function to ()
	{
		$args = func_get_args();
		return call_user_func_array([new Message(), 'to'], $args);
	}
	
	public function cc ()
	{
		$args = func_get_args();
		return call_user_func_array([new Message(), 'cc'], $args);
	}
	
	public function bcc ()
	{
		$args = func_get_args();
		return call_user_func_array([new Message(), 'bcc'], $args);
	}
	
	public function from ($address=null)
	{
		$message = new Message();
		return $message->from($address);
	}
	
	public function replyTo ($address=null)
	{
		$message = new Message();
		return $message->replyTo($address);
	}
	
	public function subject ($subject=null)
	{
		$message = new Message();
		return $message->subject($subject);
	}
	
	public function html ($html=null)
	{
		$message = new Message();
		return $message->html($html);
	}
	
	public function text ($text=null)
	{
		$message = new Message();
		return $message->text($text);
	}
	
	public function embed (&$content_id, $data, $filename=null, $content_type=null)
	{
		$message = new Message();
		return $message->embed($content_id, $date, $filename, $content_type);
	}
	
	public function embedFile (&$content_id, $path, $content_type=null)
	{
		$message = new Message();
		return $message->embedFile($content_id, $path, $content_type);
	}
	
	public function attach ($data, $filename=null, $content_type=null)
	{
		$message = new Message();
		return $message->attach($date, $filename, $content_type);
	}
	
	public function attachFile ($path, $content_type=null)
	{
		$message = new Message();
		return $message->attachFile($path, $content_type);
	}
	
	public function header ($header, $value=null)
	{
		$message = new Message();
		return $message->header($header, $value);
	}
	
	public function extraHeaders ($header_list)
	{
		$message = new Message();
		return $message->extraHeaders($header_list);
	}
	
	public function set ($fields)
	{
		$message = new Message();
		return $message->set($fields);
	}
}
