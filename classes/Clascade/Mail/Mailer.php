<?php

namespace Clascade\Mail;

class Mailer extends \Clascade\StaticProxy
{
	public static function getProviderClass ()
	{
		return conf('common.mail.default-mailer');
	}
	
	public static function send ($message)
	{
		return static::provider()->send($message);
	}
}
