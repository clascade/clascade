<?php

namespace Clascade;

$this->label(
[
	'reset-key' => 'k parameter',
	'new-password' => 'New Password',
	'confirm' => 'Retype New Password',
]);

$this->redact(
[
	'new-password',
	'confirm',
]);

$this->preserve(
[
	'reset-key',
	'new-password',
	'confirm',
]);

// Begin form validation.

$this->requireNonBlank(
[
	'reset-key',
	'new-password',
	'confirm',
]);
$this->enforce();

// Find the user that matches the key.

$user = Auth::getStore()->getUserByResetKey($this['reset-key']);

if ($user === false)
{
	$this->error(null, 'This reset request is no longer valid. Please make sure you\'re using the most recent reset link.');
	$this->enforce();
}

// Check new password validity.

if (!Auth::isAcceptablePassword($this['new-password']))
{
	$this->error('new-password', 'The new password is too weak. Please try a stronger password.');
}

if ($this['confirm'] !== $this['new-password'])
{
	$this->error('confirm', 'The retyped password was different. Please try again.');
}

$this->enforce();

// Successfully validated.

// Hold a reference to the user, for the invoker to use.

$this['user'] = $user;
