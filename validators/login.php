<?php

namespace Clascade;

$this->label(
[
	'email' => 'Email',
	'password' => 'Password',
]);

$this->redact(
[
	'password',
]);

$this->preserve(
[
	'password',
]);

// Begin form validation.

$this->requireNonBlank(
[
	'email',
	'password',
]);
$this->enforce();

// Attempt to authenticate with the email/password.

$auth_ident = 'email:'.strtolower($this['email']);

if (Auth::isThrottled($auth_ident))
{
	// Too many recent failed login attempts.
	
	$this->error(null, 'This account is temporarily locked due to multiple failed login attempts. Please try again later.');
}
else
{
	$user = Auth::attemptPassword($auth_ident, $this['password']);
	
	if ($user === false)
	{
		// Authentication error.
		
		$this->error(null, 'Incorrect email or password. Please try again.');
	}
}

$this->enforce();

// Successfully validated/authenticated.

// Hold a reference to the user, for the invoker to use.

$this['user'] = $user;
