<?php

namespace Clascade;

$this->label(
[
	'email' => 'Email',
	'display-name' => 'Name',
]);

$this->requireNonBlank(
[
	'display-name',
]);

$this->requireEmail('email');
$this->requireMaxLength('email', 249); // 255 - Str::length('email:')

$this->enforce();

$auth_ident = 'email:'.strtolower($this['email']);

// Check for duplicate email.

if (Auth::getStore()->getUserByAuthIdent($auth_ident) !== false)
{
	$this->error('email', 'There is already a user with that email address.');
}

// Keep a copy of the ident.

$this['auth-ident'] = $auth_ident;
