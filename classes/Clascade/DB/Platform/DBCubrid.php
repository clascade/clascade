<?php

namespace Clascade\DB\Platform;

class DBCubrid extends \Clascade\DB\Platform
{
	public function doLimit ($query, $limit, $offset)
	{
		if ($limit === null)
		{
			$limit = '9223372036854775807';
		}
		
		return parent::doLimit($query, $limit, $offset);
	}
}
