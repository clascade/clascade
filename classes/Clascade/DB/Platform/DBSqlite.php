<?php

namespace Clascade\DB\Platform;

class DBSqlite extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		if ($limit === null)
		{
			$limit = -1;
		}
		
		return parent::doLimit($query, $limit, $offset);
	}
}
