#!/usr/bin/env php
<?php

namespace Clascade;

// This script will generate content you can use for a conf/keys.json file.

require (__DIR__.'/../global.php');

$conf = [];

for ($i = 0; $i < 8; ++$i)
{
	$conf['pass-hash'][] = base64_encode(rand_bytes(64));
}

echo json_encode($conf, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
