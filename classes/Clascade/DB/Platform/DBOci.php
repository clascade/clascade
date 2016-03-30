<?php

namespace Clascade\DB\Platform;

class DBOci extends \Clascade\DB\Platform
{
	public function doLastInsertId ($table, $col)
	{
		$table = $this->normalize($table);
		return $this->pdo->lastInsertId("{$table}_{$col}_seq");
	}
}
