<?php

namespace Clascade\DB\Platform;

class DB4D extends \Clascade\DB\Platform
{
	public function doRollbackTo ($savepoint)
	{
		return false;
	}
	
	public function doSavepoint ($savepoint)
	{
		return false;
	}
}
