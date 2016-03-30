<?php

namespace Clascade;

header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
$this->render('pages/error/forbidden');
