<?php

namespace Clascade;

class Auth extends StaticProxy
{
	public static function getProviderClass ()
	{
		return 'Clascade\Auth\AuthProvider';
	}
	
	public static function init ()
	{
		return static::provider()->init();
	}
	
	public static function getStore ()
	{
		return static::provider()->getStore();
	}
	
	public static function getUser ()
	{
		return static::provider()->getUser();
	}
	
	public static function attemptPassword ($auth_ident, $password)
	{
		return static::provider()->attemptPassword($auth_ident, $password);
	}
	
	public static function passwordSleep ($start_time)
	{
		return static::provider()->passwordSleep($start_time);
	}
	
	public static function isAcceptablePassword ($password)
	{
		return static::provider()->isAcceptablePassword($password);
	}
	
	public static function isThrottled ($auth_ident)
	{
		return static::provider()->isThrottled($auth_ident);
	}
	
	public static function getThrottleTimeRemaining ($auth_ident)
	{
		return static::provider()->getThrottleTimeRemaining($auth_ident);
	}
	
	public static function prompt ()
	{
		return static::provider()->prompt();
	}
	
	public static function setUser ($user, $auth_source=null)
	{
		return static::provider()->setUser($user, $auth_source);
	}
	
	public static function endSession ()
	{
		return static::provider()->endSession();
	}
}
