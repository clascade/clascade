<?php

namespace Clascade;

$realm = Conf::get('common.auth.source.http-basic.realm');
$realm = strtr($realm, array ('\\' => '\\\\', '"', '\\"'));

$this->render('pages/error/unauthorized',
[
	'realm' => $realm,
]);
