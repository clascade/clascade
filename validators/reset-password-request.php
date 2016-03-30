<?php

namespace Clascade;

$this->label(
[
	'email' => 'Email',
]);

// Begin form validation.

$this->requireNonBlank(
[
	'email',
]);
