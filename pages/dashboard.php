<?php

namespace Clascade;

$this->render('pages/dashboard',
[
	'display-name' => user()->get('display-name'),
]);
