#!/usr/bin/env php
<?php

/**
 * This script creates a new admin user account. To use this, you must
 * specify the new user's email address and full name, like so:
 *
 * sudo -u www-data ./create-superuser.sh "foo@example.com" "John Doe"
 *
 * When it's run, it will output a relative URL similar to this:
 *
 * /reset?k=Q7_T5tdTFIiGpjoDFexZ4w
 *
 * If you visit this URL (relative to your domain) in a browser, the
 * site should prompt you to set a password on the new account. This
 * requires Clascade\Middleware\SimpleAuth in the common.hooks.request
 * configuration, which should be present by default.
 */

namespace Clascade;

require (__DIR__.'/../global.php');

$form =
[
	'is-admin' => '1',
	'email' => (isset ($argv[1]) ? $argv[1] : ''),
	'display-name' => (isset ($argv[2]) ? $argv[2] : ''),
];

$store = Auth::getStore();
$store->begin();

try {
	$v = make('Clascade\Validator', path('/validators/add-user.php'));
	$v->validate($form);
}
catch (Exception\ValidationException $e)
{
	$store->rollback();
	
	foreach ($e->validator->errors as $field_name => $errors)
	{
		foreach ($errors as $message)
		{
			echo "Error in \"{$field_name}\": {$message}\n";
		}
	}
	
	echo "Usage: {$argv[0]} <email-address> <display-name>\n";
	exit (1);
}

$meta =
[
	'email' => $v['email'],
	'display-name' => $v['display-name'],
	'is-admin' => (bool) $v['is-admin'],
];

$k = $store->createUserForKey($v['auth-ident'], $meta);

if ($k === false)
{
	$store->rollback();
	echo "An unexpected error occurred. The user couldn't be added.\n";
	exit (1);
}

$store->commit();

echo conf('common.urls.reset-password')."?k={$k}\n";
