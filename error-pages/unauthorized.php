<?php

namespace Clascade;

header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");

$realm = Conf::get('common.auth.source.http-basic.realm');
$realm = strtr($realm, array ('\\' => '\\\\', '"', '\\"'));

header("WWW-Authenticate: Basic realm=\"{$realm}\"");
$this->render('pages/error/unauthorized');
