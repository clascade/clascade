<?php

namespace Clascade;

header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
$this->render('pages/error/not-found');
