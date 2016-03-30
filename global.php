<?php

namespace Clascade;

if (!class_exists('Clascade\Core', false))
{
	require (__DIR__.'/classes/Clascade/Core.php');
	Core::provider()->init();
}
